<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ReadingProgress;
use App\Models\BooktokFavoriteAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $userId = (int) $request->user()->id;
        $latestGoogleEntryIds = ReadingProgress::query()
            ->selectRaw('MAX(id)')
            ->where('user_id', $userId)
            ->whereNotNull('google_volume_id')
            ->groupBy('google_volume_id');
        $latestTitleEntryIds = ReadingProgress::query()
            ->selectRaw('MAX(id)')
            ->where('user_id', $userId)
            ->whereNull('google_volume_id')
            ->groupByRaw('LOWER(book_title)');

        $latestEntries = ReadingProgress::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($latestGoogleEntryIds, $latestTitleEntryIds): void {
                $query
                    ->where(function ($query) use ($latestGoogleEntryIds): void {
                        $query->whereNotNull('google_volume_id')
                            ->whereIn('id', $latestGoogleEntryIds);
                    })
                    ->orWhere(function ($query) use ($latestTitleEntryIds): void {
                        $query->whereNull('google_volume_id')
                            ->whereIn('id', $latestTitleEntryIds);
                    });
            })
            ->get();

        $bookSnapshots = $latestEntries->map(function (ReadingProgress $entry): array {
            $pagesRead = (int) $entry->pages_read;
            $totalPages = $entry->total_pages ? (int) $entry->total_pages : null;

            return [
                'pages_read' => $pagesRead,
                'reading_status' => $totalPages !== null && $pagesRead >= $totalPages
                    ? 'read'
                    : ($pagesRead <= 0 ? 'want_to_read' : 'in_progress'),
            ];
        });

        $profileStats = [
            'booksRead' => collect($bookSnapshots)->where('reading_status', 'read')->count(),
            'pagesRead' => collect($bookSnapshots)->sum('pages_read'),
            'booksOnShelf' => count($bookSnapshots),
            'booksInProgress' => collect($bookSnapshots)->where('reading_status', 'in_progress')->count(),
            'booksWantToRead' => collect($bookSnapshots)->where('reading_status', 'want_to_read')->count(),
            'favoriteAuthors' => BooktokFavoriteAuthor::query()
                ->where('user_id', $userId)
                ->count(),
        ];

        return view('profile.edit', [
            'user' => $request->user(),
            'profileStats' => $profileStats,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
