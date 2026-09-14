<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookListingRequest;
use App\Http\Requests\StoreBookListingApplicationRequest;
use App\Models\BookListing;
use App\Models\BookListingApplication;
use App\Models\BookListingMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ->with(['applications.user:id,name,email', 'applications.messages.user:id,name'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        return view('book-listings.create', [
            'listingType' => $request->routeIs('book-exchange.*') ? 'exchange' : 'sale',
        ]);
    }

    public function store(StoreBookListingRequest $request): RedirectResponse
    {
        BookListing::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return to_route('book-listings.index')
            ->with('status', 'Sludinājums veiksmīgi publicēts.');
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

        $bookListing->applications()->create([
            'user_id' => $request->user()->id,
            'offered_book_title' => $request->validated('offered_book_title'),
            'message' => $request->validated('message'),
            'status' => 'pending',
        ]);

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

        $application->update(['status' => $status]);

        if ($status === 'accepted') {
            $bookListing->update(['availability' => 'unavailable']);
            $bookListing->applications()
                ->where('id', '!=', $application->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);
        }

        return back()->with('status', $status === 'accepted'
            ? 'Apmaiņas piedāvājums pieņemts.'
            : 'Apmaiņas piedāvājums noraidīts.');
    }

    public function sendMessage(Request $request, BookListing $bookListing, BookListingApplication $application): RedirectResponse
    {
        abort_unless($application->book_listing_id === $bookListing->id, 404);
        abort_unless($bookListing->user_id === $request->user()->id || $application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        BookListingMessage::create([
            'book_listing_application_id' => $application->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return back()->with('status', 'Ziņa nosūtīta.');
    }
}
