<?php

namespace App\Http\Controllers;

use App\Models\ReadingChallenge;
use App\Models\ReadingHighlight;
use App\Models\ReadingProgress;
use App\Models\BookListing;
use App\Models\User;
use App\Notifications\BookListingApplicationStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $activeFilter = $request->query('filter');
        $activeFilter = in_array($activeFilter, ['users', 'highlights', 'book-listings'], true)
            ? $activeFilter
            : null;

        return view('admin.dashboard', [
            'activeFilter' => $activeFilter,
            'stats' => [
                'users' => User::query()->count(),
                'highlights' => ReadingHighlight::query()->count(),
                'progressEntries' => ReadingProgress::query()->count(),
                'challenges' => ReadingChallenge::query()->count(),
                'bookListings' => BookListing::query()->count(),
            ],
            'users' => User::query()->latest()->get(),
            'highlights' => ReadingHighlight::query()
                ->with('user:id,name,email')
                ->latest()
                ->limit(20)
                ->get(),
            'saleListings' => BookListing::query()
                ->where('listing_type', 'sale')
                ->with('user:id,name,email')
                ->latest()
                ->limit(30)
                ->get(),
            'exchangeListings' => BookListing::query()
                ->where('listing_type', 'exchange')
                ->with('user:id,name,email')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function destroyHighlight(ReadingHighlight $highlight): RedirectResponse
    {
        $highlight->delete();

        return to_route('admin.dashboard')->with('status', 'Izcēlums dzēsts.');
    }

    public function destroyBookListing(Request $request, BookListing $bookListing): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $bookListing): string {
            $lockedListing = BookListing::query()
                ->lockForUpdate()
                ->findOrFail($bookListing->id);

            if ($lockedListing->applications()->whereIn('status', ['accepted', 'completed'])->exists()) {
                return 'blocked';
            }

            if ($lockedListing->applications()->exists()) {
                $pendingApplications = $lockedListing->applications()
                    ->where('status', 'pending')
                    ->with('user')
                    ->get();

                foreach ($pendingApplications as $application) {
                    $application->transitionTo('cancelled', (int) $request->user()->id, 'admin_withdrawn');
                    $application->user?->notify(new BookListingApplicationStatusChanged($application, 'cancelled', 'admin_withdrawn'));
                }

                $lockedListing->update(['availability' => 'unavailable']);

                return 'withdrawn';
            }

            DB::table('notifications')->where('data->listing_id', $lockedListing->id)->delete();
            $lockedListing->delete();

            return 'deleted';
        });

        if ($result === 'blocked') {
            return back()->with('status', 'Sludinājumu ar pieņemtu darījumu nevar dzēst.');
        }

        return to_route('admin.dashboard')->with('status', $result === 'withdrawn'
            ? 'Sludinājums atsaukts, pieteikumu vēsture saglabāta.'
            : 'Grāmatas sludinājums dzēsts.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return to_route('admin.dashboard')
                ->with('status', 'Admins nevar izdzēst pats savu kontu.');
        }

        $user->delete();

        return to_route('admin.dashboard')->with('status', 'Lietotājs dzēsts.');
    }
}
