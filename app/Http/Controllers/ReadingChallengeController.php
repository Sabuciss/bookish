<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingChallengeRequest;
use App\Http\Requests\StartReadingTimerSessionRequest;
use App\Http\Requests\CompleteReadingTimerSessionRequest;
use App\Models\ReadingChallenge;
use App\Models\ReadingChallengeSession;
use App\Notifications\ReadingTimerSessionSaved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReadingChallengeController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $challenges = ReadingChallenge::query()
            ->with('user:id,name')
            ->where('user_id', $userId)
            ->latest('start_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('reading-challenges.index', [
            'challenges' => $challenges,
            'editingChallenge' => null,
        ]);
    }

    public function edit(int $challengeId): View
    {
        $userId = (int) auth()->id();

        $editingChallenge = ReadingChallenge::query()
            ->where('user_id', $userId)
            ->findOrFail($challengeId);

        return view('reading-challenges.edit', [
            'challenge' => $editingChallenge,
        ]);
    }

    public function timer(): View
    {
        $userId = auth()->id();
        $sessions = ReadingChallengeSession::query()
            ->where('user_id', $userId)
            ->where('timer_status', 'completed')
            ->with('challenge:id,title')
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();
        $activeSession = ReadingChallengeSession::query()
            ->where('user_id', $userId)
            ->whereIn('timer_status', ['running', 'paused'])
            ->latest('id')
            ->first();

        return view('reading-timer.index', [
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'activeElapsedSeconds' => $activeSession ? $this->elapsedSeconds($activeSession) : 0,
            'challenges' => ReadingChallenge::query()
                ->where('user_id', $userId)
                ->where('challenge_type', 'time')
                ->whereDate('start_date', '<=', now()->toDateString())
                ->whereDate('end_date', '>=', now()->toDateString())
                ->where('is_completed', false)
                ->orderBy('end_date')
                ->limit(50)
                ->get(['id', 'title', 'target_value']),
        ]);
    }

    public function results(Request $request): View
    {
        $userId = auth()->id();

        $scope = $request->query('scope', 'mine');
        if (! in_array($scope, ['mine', 'all'], true)) {
            $scope = 'mine';
        }

        $period = $request->query('period', '30d');
        if (! in_array($period, ['7d', '30d', '90d', 'all'], true)) {
            $period = '30d';
        }

        $minMinutes = min(525600, max(0, (int) $request->query('min_minutes', 0)));

        $query = ReadingChallengeSession::query()
            ->where('timer_status', 'completed')
            ->with(['user:id,name', 'challenge:id,title'])
            ->latest('created_at')
            ->latest('id');

        if ($scope === 'mine') {
            $query->where('user_id', $userId);
        } else {
            $query->where('is_public', true);
        }

        if ($request->filled('session_id')) {
            $query->whereKey($request->integer('session_id'));
        }

        if ($period !== 'all') {
            $days = (int) str_replace('d', '', $period);
            $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }

        if ($minMinutes > 0) {
            $query->where('elapsed_seconds', '>=', $minMinutes * 60);
        }

        $sessions = (clone $query)
            ->paginate(20)
            ->withQueryString();

        $stats = (clone $query)
            ->reorder()
            ->selectRaw('COUNT(*) as total_sessions, COALESCE(SUM(pages_read), 0) as total_pages, COALESCE(SUM(elapsed_seconds), 0) as total_elapsed_seconds')
            ->first();
        $totalElapsedSeconds = (int) $stats->total_elapsed_seconds;

        return view('reading-challenges.results', [
            'sessions' => $sessions,
            'scope' => $scope,
            'period' => $period,
            'minMinutes' => $minMinutes,
            'totalSessions' => (int) $stats->total_sessions,
            'totalPages' => (int) $stats->total_pages,
            'totalElapsedSeconds' => $totalElapsedSeconds,
        ]);
    }

    public function store(StoreReadingChallengeRequest $request): RedirectResponse
    {
        $challenge = ReadingChallenge::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);
        $challenge->refreshCompletionFromProgress();

        return redirect()->route('reading-challenges.index')
            ->with('status', 'Izaicinājums veiksmīgi izveidots.');
    }

    public function update(StoreReadingChallengeRequest $request, int $challengeId): RedirectResponse
    {
        $challenge = ReadingChallenge::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($challengeId);

        if ($request->validated('challenge_type') !== $challenge->challenge_type
            && ($challenge->sessions()->exists() || $challenge->progressEntries()->exists())) {
            throw ValidationException::withMessages([
                'challenge_type' => 'Izaicinājuma tipu nevar mainīt pēc progresa piesaistes.',
            ]);
        }

        $challenge->update($request->validated());
        $challenge->refreshCompletionFromProgress();

        return redirect()->route('reading-challenges.index')
            ->with('status', 'Izaicinājums veiksmīgi atjaunots.');
    }

    public function destroy(Request $request, int $challengeId): RedirectResponse
    {
        $challenge = ReadingChallenge::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($challengeId);
        $challenge->delete();

        return to_route('reading-challenges.index')->with('status', 'Izaicinājums dzēsts.');
    }

    public function startTimer(StartReadingTimerSessionRequest $request): JsonResponse
    {
        $session = DB::transaction(function () use ($request): ReadingChallengeSession {
            $user = $request->user();
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();

            if (ReadingChallengeSession::query()
                ->where('user_id', $user->id)
                ->whereIn('timer_status', ['running', 'paused'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'timer' => 'Vispirms pabeidz vai turpini iesākto taimera sesiju.',
                ]);
            }

            return ReadingChallengeSession::query()->create([
                ...$request->validated(),
                'user_id' => $user->id,
                'elapsed_seconds' => 0,
                'pages_read' => 0,
                'is_public' => false,
                'started_at' => now(),
                'active_started_at' => now(),
                'timer_status' => 'running',
            ]);
        });

        return response()->json([
            'id' => $session->id,
            'status' => $session->timer_status,
            'elapsedSeconds' => 0,
            'plannedMinutes' => $session->planned_minutes,
        ]);
    }

    public function pauseTimer(Request $request, int $sessionId): JsonResponse
    {
        $session = DB::transaction(function () use ($request, $sessionId): ReadingChallengeSession {
            $session = $this->ownedTimerSession($request, $sessionId);
            abort_unless($session->timer_status === 'running', 409);

            $session->elapsed_seconds = $this->elapsedSeconds($session);
            $session->active_started_at = null;
            $session->timer_status = 'paused';
            $session->save();

            return $session;
        });

        return response()->json(['status' => 'paused', 'elapsedSeconds' => $session->elapsed_seconds]);
    }

    public function resumeTimer(Request $request, int $sessionId): JsonResponse
    {
        $session = DB::transaction(function () use ($request, $sessionId): ReadingChallengeSession {
            $session = $this->ownedTimerSession($request, $sessionId);
            abort_unless($session->timer_status === 'paused', 409);

            $session->active_started_at = now();
            $session->timer_status = 'running';
            $session->save();

            return $session;
        });

        return response()->json(['status' => 'running', 'elapsedSeconds' => $session->elapsed_seconds]);
    }

    public function completeTimer(CompleteReadingTimerSessionRequest $request, int $sessionId): JsonResponse
    {
        $session = DB::transaction(function () use ($request, $sessionId): ReadingChallengeSession {
            $session = $this->ownedTimerSession($request, $sessionId);
            abort_unless(in_array($session->timer_status, ['running', 'paused'], true), 409);

            $elapsedSeconds = $session->timer_status === 'running'
                ? $this->elapsedSeconds($session)
                : (int) $session->elapsed_seconds;

            $session->fill([
                ...$request->validated(),
                'elapsed_seconds' => min($elapsedSeconds, 86400),
                'active_started_at' => null,
                'ended_at' => now(),
                'timer_status' => 'completed',
            ])->save();

            if ($session->challenge) {
                $session->challenge->refreshCompletionFromProgress();
            }

            return $session;
        });

        try {
            $request->user()->notify(new ReadingTimerSessionSaved($session));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'status' => 'completed',
            'elapsedSeconds' => $session->elapsed_seconds,
            'redirect' => route('reading-timer.index'),
        ]);
    }

    public function destroyTimer(Request $request, int $sessionId): RedirectResponse
    {
        DB::transaction(function () use ($request, $sessionId): void {
            $session = $this->ownedTimerSession($request, $sessionId);
            $challenge = $session->challenge;

            $request->user()->notifications()
                ->where('type', ReadingTimerSessionSaved::class)
                ->where('data->session_id', $session->id)
                ->delete();

            $session->delete();
            $challenge?->refreshCompletionFromProgress();
        });

        return to_route('reading-timer.index')->with('status', 'Taimera sesija dzēsta.');
    }

    private function ownedTimerSession(Request $request, int $sessionId): ReadingChallengeSession
    {
        return ReadingChallengeSession::query()
            ->where('user_id', $request->user()->id)
            ->lockForUpdate()
            ->findOrFail($sessionId);
    }

    private function elapsedSeconds(ReadingChallengeSession $session): int
    {
        $activeSeconds = $session->active_started_at
            ? max(0, (int) $session->active_started_at->diffInSeconds(now()))
            : 0;

        return min((int) $session->elapsed_seconds + $activeSeconds, 86400);
    }

}
