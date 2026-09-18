<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ReadingProgress;
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
        $bookSnapshots = [];

        ReadingProgress::query()
            ->where('user_id', $request->user()->id)
            ->latest('reading_date')
            ->latest('id')
            ->get()
            ->each(function (ReadingProgress $entry) use (&$bookSnapshots): void {
                $key = $entry->google_volume_id ?: mb_strtolower(trim($entry->book_title));

                if (isset($bookSnapshots[$key])) {
                    return;
                }

                $pagesRead = (int) $entry->pages_read;
                $totalPages = $entry->total_pages ? (int) $entry->total_pages : null;
                $status = $totalPages !== null && $pagesRead >= $totalPages
                    ? 'read'
                    : ($pagesRead <= 0 ? 'want_to_read' : 'in_progress');

                $bookSnapshots[$key] = [
                    'pages_read' => $pagesRead,
                    'reading_status' => $status,
                ];
            });

        $profileStats = [
            'booksRead' => collect($bookSnapshots)->where('reading_status', 'read')->count(),
            'pagesRead' => collect($bookSnapshots)->sum('pages_read'),
            'booksOnShelf' => count($bookSnapshots),
            'booksInProgress' => collect($bookSnapshots)->where('reading_status', 'in_progress')->count(),
            'booksWantToRead' => collect($bookSnapshots)->where('reading_status', 'want_to_read')->count(),
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
