<?php

namespace App\Http\Controllers;

use App\Models\ReadingChallenge;
use App\Models\ReadingHighlight;
use App\Models\ReadingProgress;
use App\Models\BookListing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'bookListings' => BookListing::query()
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

    public function destroyBookListing(BookListing $bookListing): RedirectResponse
    {
        $bookListing->delete();

        return to_route('admin.dashboard')->with('status', 'Grāmatas sludinājums dzēsts.');
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
