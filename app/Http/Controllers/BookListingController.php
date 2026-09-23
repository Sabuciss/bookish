<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookListingRequest;
use App\Http\Requests\StoreBookListingApplicationRequest;
use App\Models\BookListing;
use App\Models\BookListingApplication;
use App\Models\BookListingMessage;
use App\Notifications\BookListingApplicationReceived;
use App\Notifications\BookListingApplicationStatusChanged;
use App\Notifications\BookListingMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookListingController extends Controller
{
    public function index(Request $request): View
    {
        $listingType = $request->routeIs('book-exchange.*') ? 'exchange' : 'sale';

        return view('book-listings.index', [
            'listingType' => $listingType,
            'listings' => BookListing::query()
                ->where('listing_type', $listingType)
                ->with('user:id,name')
                ->with(['applications.user:id,name', 'applications.messages.user:id,name'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        return view('book-listings.create', [
            'listingType' => $request->routeIs('book-exchange.*') ? 'exchange' : 'sale',
            'listing' => null,
        ]);
    }

    public function edit(Request $request, BookListing $bookListing): View
    {
        abort_unless($bookListing->user_id === $request->user()->id, 403);

        return view('book-listings.create', [
            'listingType' => $bookListing->listing_type,
            'listing' => $bookListing,
        ]);
    }

    public function store(StoreBookListingRequest $request): RedirectResponse
    {
        BookListing::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'contact_email' => $request->user()->email,
        ]);

        return to_route('book-listings.index')
            ->with('status', 'Sludinājums veiksmīgi publicēts.');
    }

    public function update(StoreBookListingRequest $request, BookListing $bookListing): RedirectResponse
    {
        abort_unless($bookListing->user_id === $request->user()->id, 403);

        $bookListing->update($request->validated());

        return to_route($bookListing->listing_type === 'exchange'
            ? 'book-exchange.index'
            : 'book-listings.index')
            ->with('status', 'Sludinājums veiksmīgi atjaunināts.');
    }

    public function apply(StoreBookListingApplicationRequest $request, BookListing $bookListing): RedirectResponse
    {
        if (! $bookListing->isAvailable()) {
            return back()->with('status', 'Šis sludinājums vairs nav pieejams.');
        }

        if ($bookListing->user_id === $request->user()->id) {
            return back()->with('status', 'Uz savu sludinājumu pieteikties nevar.');
        }

        if ($bookListing->applications()->where('user_id', $request->user()->id)->exists()) {
            return back()->with('status', 'Tu jau esi pieteicies uz šo grāmatu.');
        }

        $created = DB::transaction(function () use ($bookListing, $request): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);

            if (! $lockedListing->isAvailable()) {
                return false;
            }

            if ($lockedListing->applications()->where('user_id', $request->user()->id)->exists()) {
                return false;
            }

            $application = $lockedListing->applications()->create([
                'user_id' => $request->user()->id,
                'offered_book_title' => $request->validated('offered_book_title'),
                'message' => $request->validated('message'),
                'status' => 'pending',
            ]);

            $lockedListing->user->notify(new BookListingApplicationReceived($application));

            return true;
        });

        if (! $created) {
            return back()->with('status', 'Pieteikumu vairs nevar iesniegt šim sludinājumam.');
        }

        return back()->with('status', $bookListing->isExchange()
            ? 'Apmaiņas piedāvājums nosūtīts sludinājuma autoram.'
            : 'Pieteikums nosūtīts grāmatas pārdevējam.');
    }

    public function updateApplication(Request $request, BookListing $bookListing, BookListingApplication $application): RedirectResponse
    {
        abort_unless($bookListing->user_id === $request->user()->id, 403);
        abort_unless($application->book_listing_id === $bookListing->id, 404);

        $status = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
        ])['status'];

        $updated = DB::transaction(function () use ($application, $bookListing, $status): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);
            $lockedApplication = BookListingApplication::query()
                ->where('book_listing_id', $lockedListing->id)
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($lockedApplication->status !== 'pending') {
                return false;
            }

            if ($status === 'accepted' && ! $lockedListing->isAvailable()) {
                return false;
            }

            $lockedApplication->update(['status' => $status]);
            $lockedApplication->load('user');
            $lockedApplication->user->notify(new BookListingApplicationStatusChanged($lockedApplication, $status));

            if ($status === 'accepted') {
                $lockedListing->update(['availability' => 'unavailable']);
                $rejectedApplications = $lockedListing->applications()
                    ->where('id', '!=', $lockedApplication->id)
                    ->where('status', 'pending')
                    ->with('user')
                    ->get();

                foreach ($rejectedApplications as $rejectedApplication) {
                    $rejectedApplication->update(['status' => 'rejected']);
                    $rejectedApplication->user->notify(new BookListingApplicationStatusChanged($rejectedApplication, 'rejected', 'unavailable'));
                }
            }

            return true;
        });

        if (! $updated) {
            return back()->with('status', 'Šo pieteikumu vairs nevar mainīt.');
        }

        return back()->with('status', $status === 'accepted'
            ? 'Apmaiņas piedāvājums pieņemts.'
            : 'Apmaiņas piedāvājums noraidīts.');
    }

    public function sendMessage(Request $request, BookListing $bookListing, BookListingApplication $application): RedirectResponse
    {
        abort_unless($application->book_listing_id === $bookListing->id, 404);
        abort_unless($bookListing->user_id === $request->user()->id || $application->user_id === $request->user()->id, 403);
        abort_if($application->status === 'rejected', 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = BookListingMessage::create([
            'book_listing_application_id' => $application->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        $recipient = $bookListing->user_id === $request->user()->id
            ? $application->user
            : $bookListing->user;
        $recipient->notify(new BookListingMessageReceived($message));

        return back()->with('status', 'Ziņa nosūtīta.');
    }
}
