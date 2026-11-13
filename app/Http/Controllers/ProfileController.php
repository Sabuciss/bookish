<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ReadingChallenge;
use App\Models\ReadingChallengeSession;
use App\Models\ReadingHighlight;
use App\Models\ReadingProgress;
use App\Models\BooktokFavoriteAuthor;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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
        ] + $this->buildDashboardStats($userId);

        return view('profile.edit', [
            'user' => $request->user(),
            'profileStats' => $profileStats,
        ]);
    }

    /**
     * Papildu dati profila statistikas panelim (laiks, nedēļas aktivitāte, sērija, emocijas).
     */
    private function buildDashboardStats(int $userId): array
    {
        $entries = ReadingProgress::latestSnapshotEntriesForUser($userId);

        $timerSessions = ReadingChallengeSession::query()
            ->where('user_id', $userId)
            ->where('timer_status', 'completed')
            ->whereNotNull('ended_at')
            ->get(['elapsed_seconds', 'planned_minutes', 'ended_at']);

        $timerMinutes = fn (ReadingChallengeSession $session): int => $session->elapsed_seconds !== null
            ? intdiv((int) $session->elapsed_seconds, 60)
            : (int) $session->planned_minutes;

        // Lasīšanas minūtes pa dienām (progresa pēdējās sesijas + taimera sesijas).
        $minutesByDay = [];
        foreach ($entries as $entry) {
            $minutes = $entry->lastSessionMinutes();
            if ($minutes > 0 && $entry->reading_date) {
                $day = $entry->reading_date->toDateString();
                $minutesByDay[$day] = ($minutesByDay[$day] ?? 0) + $minutes;
            }
        }
        foreach ($timerSessions as $session) {
            $day = Carbon::parse($session->ended_at)->toDateString();
            $minutesByDay[$day] = ($minutesByDay[$day] ?? 0) + $timerMinutes($session);
        }

        // Pēdējās 7 dienas joslu diagrammai.
        $weekdayNames = ['P', 'O', 'T', 'C', 'Pk', 'S', 'Sv'];
        $week = collect(range(6, 0))->map(function (int $daysAgo) use ($minutesByDay, $weekdayNames): array {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $weekdayNames[$date->dayOfWeekIso - 1],
                'minutes' => (int) ($minutesByDay[$date->toDateString()] ?? 0),
                'isToday' => $daysAgo === 0,
            ];
        });
        $weekMax = max(1, (int) $week->max('minutes'));

        // Sērija: secīgas dienas ar lasīšanu, skaitot no šodienas (vai vakardienas).
        $streak = 0;
        $cursor = now()->startOfDay();
        if (empty($minutesByDay[$cursor->toDateString()])) {
            $cursor->subDay();
        }
        while (!empty($minutesByDay[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        // Populārākās emocijas.
        $topEmotions = $entries
            ->flatMap(fn (ReadingProgress $entry) => array_map('trim', explode(',', (string) $entry->emotion)))
            ->filter(fn (string $emotion) => $emotion !== '' && $emotion !== 'Want to Read')
            ->countBy()
            ->sortDesc()
            ->take(4);

        // Pašlaik lasāmās grāmatas un vidējais progress.
        $inProgress = $entries
            ->filter(fn (ReadingProgress $entry) => $entry->pages_read > 0
                && $entry->total_pages
                && $entry->pages_read < $entry->total_pages)
            ->sortByDesc('reading_date')
            ->values();

        $averageProgress = $inProgress->isEmpty()
            ? 0
            : (int) round($inProgress->avg(fn (ReadingProgress $entry) => $entry->pages_read / $entry->total_pages * 100));

        $challenges = ReadingChallenge::query()->where('user_id', $userId);

        return [
            'totalMinutes' => (int) $entries->sum('duration_minutes') + (int) $timerSessions->sum($timerMinutes),
            'week' => $week->all(),
            'weekMax' => $weekMax,
            'weekMinutes' => (int) $week->sum('minutes'),
            'streak' => $streak,
            'topEmotions' => $topEmotions->all(),
            'currentlyReading' => $inProgress->take(3)->all(),
            'averageProgress' => $averageProgress,
            'highlights' => ReadingHighlight::query()->where('user_id', $userId)->count(),
            'challengesTotal' => (clone $challenges)->count(),
            'challengesCompleted' => (clone $challenges)->where('is_completed', true)->count(),
        ];
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
            try {
                $user->sendEmailVerificationNotification();
            } catch (TransportExceptionInterface $exception) {
                report($exception);

                return redirect()->route('verification.notice')
                    ->with('status', 'verification-email-failed');
            }

            return redirect()->route('verification.notice')
                ->with('status', 'verification-link-sent');
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
