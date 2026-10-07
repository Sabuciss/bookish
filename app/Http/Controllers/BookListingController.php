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
                ->when($request->filled('listing_id'), fn ($query) => $query->whereKey($request->integer('listing_id')))
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
            'listingLocked' => false,
        ]);
    }

    public function edit(Request $request, BookListing $bookListing): View
    {
        abort_unless($bookListing->user_id === $request->user()->id, 403);

        return view('book-listings.create', [
            'listingType' => $bookListing->listing_type,
            'listing' => $bookListing,
            'listingLocked' => $bookListing->applications()
                ->whereIn('status', ['accepted', 'completed'])
                ->exists(),
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

    public function update(StoreBookListingRequest $request, BookListing $bookListing): RedirectResponse
    {
        abort_unless($bookListing->user_id === $request->user()->id, 403);

        $listingType = DB::transaction(function () use ($request, $bookListing): ?string {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);

            if ($lockedListing->applications()->whereIn('status', ['accepted', 'completed'])->exists()) {
                return null;
            }

            if ($request->validated('listing_type') !== $lockedListing->listing_type
                && $lockedListing->applications()->whereIn('status', ['pending', 'accepted', 'completed'])->exists()) {
                return null;
            }

            $lockedListing->update([
                ...$request->validated(),
                'availability' => 'available',
            ]);

            return $lockedListing->listing_type;
        });

        if ($listingType === null) {
            return back()->with('status', 'Aktīva pieteikuma laikā sludinājuma tipu nevar mainīt; pēc darījuma pieņemšanas datus nevar labot.');
        }

        return to_route($listingType === 'exchange'
            ? 'book-exchange.index'
            : 'book-listings.index')
            ->with('status', 'Sludinājums veiksmīgi atjaunināts.');
    }

    public function destroy(Request $request, BookListing $bookListing): RedirectResponse
    {
        abort_unless($bookListing->user_id === $request->user()->id, 404);

        $result = DB::transaction(function () use ($bookListing, $request): array {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);

            if ($lockedListing->applications()->whereIn('status', ['accepted', 'completed'])->exists()) {
                return ['state' => 'blocked', 'type' => $lockedListing->listing_type];
            }

            $type = $lockedListing->listing_type;
            $applications = $lockedListing->applications()
                ->where('status', 'pending')
                ->with('user')
                ->get();

            if ($lockedListing->applications()->exists()) {
                foreach ($applications as $application) {
                    $application->transitionTo('cancelled', (int) $request->user()->id, 'listing_withdrawn');
                    $application->user?->notify(new BookListingApplicationStatusChanged($application, 'cancelled', 'listing_withdrawn'));
                }

                $lockedListing->update(['availability' => 'unavailable']);

                return ['state' => 'withdrawn', 'type' => $type];
            }

            $lockedListing->delete();

            return ['state' => 'deleted', 'type' => $type];
        });

        if ($result['state'] === 'blocked') {
            return back()->with('status', 'Sludinājumu ar pieņemtu pieteikumu vairs nevar dzēst.');
        }

        return to_route($result['type'] === 'exchange'
            ? 'book-exchange.index'
            : 'book-listings.index')->with('status', $result['state'] === 'withdrawn'
                ? 'Sludinājums atsaukts. Pieteikumu vēsture ir saglabāta.'
                : 'Sludinājums dzēsts.');
    }

    public function withdrawApplication(Request $request, BookListing $bookListing, BookListingApplication $application): RedirectResponse
    {
        abort_unless($application->book_listing_id === $bookListing->id, 404);
        abort_unless(
            $application->user_id === $request->user()->id || $bookListing->user_id === $request->user()->id,
            404,
        );

        $withdrawn = DB::transaction(function () use ($request, $bookListing, $application): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);
            $lockedApplication = BookListingApplication::query()
                ->where('book_listing_id', $lockedListing->id)
                ->lockForUpdate()
                ->findOrFail($application->id);

            $actorId = (int) $request->user()->id;
            $isApplicant = $lockedApplication->user_id === $actorId;
            $isOwner = $lockedListing->user_id === $actorId;
            $wasAccepted = $lockedApplication->status === 'accepted';

            if (($lockedApplication->status !== 'pending' || ! $isApplicant) && ! ($wasAccepted && ($isApplicant || $isOwner))) {
                return false;
            }

            $reason = $isApplicant ? 'applicant_cancelled' : 'owner_cancelled';
            if (! $lockedApplication->transitionTo('cancelled', $actorId, $reason)) {
                return false;
            }

            if ($wasAccepted) {
                $lockedListing->update(['availability' => 'available']);
            }

            $recipient = $isApplicant ? $lockedListing->user : $lockedApplication->user;
            $recipient?->notify(new BookListingApplicationStatusChanged($lockedApplication, 'cancelled', $reason));

            return true;
        });

        return back()->with('status', $withdrawn
            ? 'Pieteikums atcelts. Darījuma vēsture ir saglabāta.'
            : 'Šo pieteikumu šobrīd vairs nevar atcelt.');
    }

    public function updateApplicantApplication(StoreBookListingApplicationRequest $request, BookListing $bookListing, BookListingApplication $application): RedirectResponse
    {
        abort_unless($application->book_listing_id === $bookListing->id, 404);
        abort_unless($application->user_id === $request->user()->id, 404);

        $data = $request->validated();
        $updated = DB::transaction(function () use ($request, $bookListing, $application, $data): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);
            $lockedApplication = BookListingApplication::query()
                ->where('book_listing_id', $lockedListing->id)
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($lockedApplication->user_id !== $request->user()->id
                || $lockedApplication->status !== 'pending'
                || ! $lockedListing->isAvailable()) {
                return false;
            }

            $fields = ['offered_book_title', 'message'];
            $before = array_intersect_key($lockedApplication->only($fields), $data);
            $lockedApplication->update($data);
            $lockedApplication->recordEdit((int) $request->user()->id, $before, $data);

            return true;
        });

        return back()->with('status', $updated
            ? 'Pieteikums atjaunināts.'
            : 'Tikai gaidošu pieteikumu var rediģēt.');
    }

    public function completeApplication(Request $request, BookListing $bookListing, BookListingApplication $application): RedirectResponse
    {
        abort_unless($application->book_listing_id === $bookListing->id, 404);
        abort_unless($bookListing->user_id === $request->user()->id || $application->user_id === $request->user()->id, 404);

        $completed = DB::transaction(function () use ($request, $bookListing, $application): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);
            $lockedApplication = BookListingApplication::query()
                ->where('book_listing_id', $lockedListing->id)
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($lockedApplication->status !== 'accepted') {
                return false;
            }

            if (! $lockedApplication->transitionTo('completed', (int) $request->user()->id)) {
                return false;
            }

            $lockedListing->update(['availability' => 'unavailable']);

            $recipient = $lockedApplication->user_id === $request->user()->id
                ? $lockedListing->user
                : $lockedApplication->user;
            $recipient?->notify(new BookListingApplicationStatusChanged($lockedApplication, 'completed'));

            return true;
        });

        return back()->with('status', $completed
            ? 'Darījums atzīmēts kā pabeigts.'
            : 'Pabeigt var tikai pieņemtu darījumu.');
    }

    public function apply(StoreBookListingApplicationRequest $request, BookListing $bookListing): RedirectResponse
    {
        if (! $bookListing->isAvailable()) {
            return back()->with('status', 'Šis sludinājums vairs nav pieejams.');
        }

        if ($bookListing->user_id === $request->user()->id) {
            return back()->with('status', 'Uz savu sludinājumu pieteikties nevar.');
        }

        $created = DB::transaction(function () use ($bookListing, $request): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);

            if (! $lockedListing->isAvailable()) {
                return false;
            }

            $data = $request->validated();
            if ($lockedListing->isExchange() && blank($data['offered_book_title'] ?? null)) {
                return false;
            }
            if (! $lockedListing->isExchange()) {
                unset($data['offered_book_title']);
            }

            $application = $lockedListing->applications()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($application) {
                if (! in_array($application->status, ['rejected', 'cancelled'], true)) {
                    return false;
                }

                $before = array_intersect_key($application->only(['offered_book_title', 'message']), $data);
                $application->update($data);
                $application->recordEdit((int) $request->user()->id, $before, $data);
                $application->recordListingSnapshot('reapplied', $lockedListing->transactionSnapshot());

                if (! $application->transitionTo('pending', (int) $request->user()->id, 'reapplied')) {
                    return false;
                }
            } else {
                $application = $lockedListing->applications()->create([
                    ...$data,
                    'user_id' => $request->user()->id,
                    'status' => 'pending',
                    'listing_snapshot' => [[
                        'stage' => 'submitted',
                        'snapshot' => $lockedListing->transactionSnapshot(),
                    ]],
                    'status_history' => [[
                        'event' => 'submitted',
                        'from' => null,
                        'to' => 'pending',
                        'actor_id' => (int) $request->user()->id,
                        'at' => now()->toIso8601String(),
                    ]],
                ]);
            }

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

        $updated = DB::transaction(function () use ($request, $application, $bookListing, $status): bool {
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

            $snapshot = $status === 'accepted' ? $lockedListing->transactionSnapshot() : null;
            if (! $lockedApplication->transitionTo($status, (int) $request->user()->id, null, $snapshot)) {
                return false;
            }
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
                    $rejectedApplication->transitionTo('rejected', (int) $request->user()->id, 'listing_unavailable');
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

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $sent = DB::transaction(function () use ($request, $bookListing, $application, $validated): bool {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);
            $lockedApplication = BookListingApplication::query()
                ->where('book_listing_id', $lockedListing->id)
                ->lockForUpdate()
                ->findOrFail($application->id);

            if (! in_array($lockedApplication->status, ['pending', 'accepted'], true)) {
                return false;
            }

            $message = BookListingMessage::create([
                'book_listing_application_id' => $lockedApplication->id,
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            $recipient = $lockedListing->user_id === $request->user()->id
                ? $lockedApplication->user
                : $lockedListing->user;
            $recipient?->notify(new BookListingMessageReceived($message));

            return true;
        });

        if (! $sent) {
            return back()->with('status', 'Ziņu vairs nevar nosūtīt šim pieteikumam.');
        }

        return back()->with('status', 'Ziņa nosūtīta.');
    }
}
