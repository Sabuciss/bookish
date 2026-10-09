<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ReadingProgress;
use App\Models\BooktokFavoriteAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $snapshotStats = ReadingProgress::latestSnapshotStatsForUser($userId);

        $profileStats = [
            'booksRead' => (int) $snapshotStats->books_read,
            'pagesRead' => (int) $snapshotStats->pages_read,
            'booksOnShelf' => (int) $snapshotStats->books_on_shelf,
            'booksInProgress' => (int) $snapshotStats->books_in_progress,
            'booksWantToRead' => (int) $snapshotStats->books_want_to_read,
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
        $user = $request->user();
        $user->fill($request->validated());
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

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

        DB::transaction(fn () => $user->delete());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
