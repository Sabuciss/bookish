<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingChallengeRequest;
use App\Http\Requests\StoreReadingChallengeSessionRequest;
use App\Models\ReadingChallenge;
use App\Models\ReadingChallengeSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            ->get();

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
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('reading-timer.index', [
            'sessions' => $sessions,
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

        $minMinutes = max(0, (int) $request->query('min_minutes', 0));

        $query = ReadingChallengeSession::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id');

        if ($scope === 'mine') {
            $query->where('user_id', $userId);
        }

        if ($period !== 'all') {
            $days = (int) str_replace('d', '', $period);
            $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }

        if ($minMinutes > 0) {
            $query->where(function ($subQuery) use ($minMinutes) {
                $subQuery
                    ->where(function ($q) use ($minMinutes) {
                        $q->whereNotNull('elapsed_seconds')
                            ->where('elapsed_seconds', '>=', $minMinutes * 60);
                    })
                    ->orWhere(function ($q) use ($minMinutes) {
                        $q->whereNull('elapsed_seconds')
                            ->where('planned_minutes', '>=', $minMinutes);
                    });
            });
        }

        $sessions = (clone $query)
            ->paginate(20)
            ->withQueryString();

        $statsSource = (clone $query)->get();

        $totalSessions = $statsSource->count();
        $totalPages = $statsSource->sum('pages_read');
        $totalMinutes = $statsSource->sum(function (ReadingChallengeSession $session): int {
            if (! is_null($session->elapsed_seconds)) {
                return (int) floor($session->elapsed_seconds / 60);
            }

            return (int) $session->planned_minutes;
        });

        return view('reading-challenges.results', [
            'sessions' => $sessions,
            'scope' => $scope,
            'period' => $period,
            'minMinutes' => $minMinutes,
            'totalSessions' => $totalSessions,
            'totalPages' => $totalPages,
            'totalMinutes' => $totalMinutes,
        ]);
    }

    public function store(StoreReadingChallengeRequest $request): RedirectResponse
    {
        ReadingChallenge::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('reading-challenges.index')
            ->with('status', 'Izaicinājums veiksmīgi izveidots.');
    }

    public function update(StoreReadingChallengeRequest $request, int $challengeId): RedirectResponse
    {
        $challenge = ReadingChallenge::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($challengeId);

        $challenge->update($request->validated());

        return redirect()->route('reading-challenges.index')
            ->with('status', 'Izaicinājums veiksmīgi atjaunots.');
    }

    public function storeSession(StoreReadingChallengeSessionRequest $request): RedirectResponse
    {
        ReadingChallengeSession::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('reading-timer.index')
            ->with('status', 'Taimera sesija veiksmīgi saglabāta.');
    }
}
