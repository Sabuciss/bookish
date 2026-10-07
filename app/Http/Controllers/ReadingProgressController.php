<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingProgressRequest;
use App\Models\ReadingChallenge;
use App\Models\ReadingProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingProgressController extends Controller
{
    public function showBookshelf(): View
    {
        $userId = (int) auth()->id();
        $data = $this->buildProgressData($userId, true);

        return view('reading-progress.shelf', [
            'bookSnapshots' => $data['bookSnapshots'],
            'bookSnapshotsPaginator' => $data['bookSnapshotsPaginator'],
            'bookSnapshotsByStatus' => $data['bookSnapshotsByStatus'],
            'totalPagesRead' => $data['totalPagesRead'],
            'totalBooksRead' => $data['totalBooksRead'],
        ]);
    }

    public function showProgressTracker(Request $request): View
    {
        $userId = (int) auth()->id();
        $data = $this->buildProgressData($userId);

        $prefill = [
            'entry_id' => (string) $request->query('entry_id', ''),
            'book_title' => (string) $request->query('book_title', ''),
            'google_volume_id' => (string) $request->query('google_volume_id', ''),
            'book_cover_url' => (string) $request->query('book_cover_url', ''),
            'pages_read' => (string) $request->query('pages_read', ''),
            'total_pages' => (string) $request->query('total_pages', ''),
            'reading_status' => (string) $request->query('reading_status', 'in_progress'),
            'emotion' => (string) $request->query('emotion', ''),
            'reading_date' => (string) $request->query('reading_date', now()->toDateString()),
            'start_time' => (string) $request->query('start_time', '00:00'),
            'end_time' => (string) $request->query('end_time', '00:01'),
            'challenge_id' => (string) $request->query('challenge_id', ''),
        ];

        $prefill['is_edit_mode'] = !empty($prefill['entry_id']) || !empty($prefill['book_title']);

        return view('reading-progress.index', [
            'progressEntries' => $data['progressEntries'],
            'latestPagesByBook' => $data['latestPagesByBook'],
            'challenges' => ReadingChallenge::query()
                ->where('user_id', $userId)
                ->where('challenge_type', 'pages')
                ->where(function ($query) use ($userId): void {
                    $query->where(function ($query): void {
                        $query->whereDate('start_date', '<=', now()->toDateString())
                            ->whereDate('end_date', '>=', now()->toDateString())
                            ->where('is_completed', false);
                    })->orWhereIn('id', ReadingProgress::query()
                        ->select('challenge_id')
                        ->where('user_id', $userId)
                        ->whereNotNull('challenge_id'));
                })
                ->orderBy('end_date')
                ->limit(50)
                ->get(['id', 'title', 'target_value']),
            'prefill' => $prefill,
        ]);
    }

    private function buildProgressData(int $userId, bool $includeBookSnapshots = false): array
    {
        $progressEntries = ReadingProgress::query()
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->where('reading_status', '!=', 'want_to_read')
                    ->orWhere('pages_read', '>', 0);
            })
            ->latest('reading_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $bookSnapshotsPaginator = $includeBookSnapshots
            ? ReadingProgress::latestSnapshotsPageForUser($userId)
            : null;
        $bookSnapshots = $bookSnapshotsPaginator?->getCollection() ?? collect();
        $snapshotStats = $includeBookSnapshots
            ? ReadingProgress::latestSnapshotStatsForUser($userId)
            : null;

        $bookSnapshotsByStatus = [
            'want_to_read' => [],
            'in_progress' => [],
            'read' => [],
        ];

        foreach ($bookSnapshots as $snapshot) {
            $status = $snapshot['reading_status'] ?: 'in_progress';

            if (!array_key_exists($status, $bookSnapshotsByStatus)) {
                $status = 'in_progress';
            }

            $bookSnapshotsByStatus[$status][] = $snapshot;
        }

        return [
            'progressEntries' => $progressEntries,
            'bookSnapshots' => $bookSnapshots->values()->all(),
            'bookSnapshotsPaginator' => $bookSnapshotsPaginator,
            'bookSnapshotsByStatus' => $bookSnapshotsByStatus,
            'totalPagesRead' => (int) ($snapshotStats->pages_read ?? 0),
            'totalBooksRead' => (int) ($snapshotStats->books_read ?? 0),
            'latestPagesByBook' => ReadingProgress::latestPageCountsForUser($userId),
        ];
    }

    public function storeProgressEntry(StoreReadingProgressRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $entryId = isset($data['entry_id']) ? (int) $data['entry_id'] : 0;
        unset($data['entry_id']);

        $startTime = Carbon::createFromFormat('H:i', $data['start_time']);
        $endTime = Carbon::createFromFormat('H:i', $data['end_time']);

        $data['duration_minutes'] = $startTime->diffInMinutes($endTime);
        $data['user_id'] = $request->user()->id;

        if (!empty($data['total_pages']) && (int) $data['pages_read'] >= (int) $data['total_pages']) {
            $data['pages_read'] = (int) $data['total_pages'];
        }

        $data['reading_status'] = ReadingProgress::deriveReadingStatus(
            (int) $data['pages_read'],
            !empty($data['total_pages']) ? (int) $data['total_pages'] : null,
        );

        $existingEntry = null;

        if ($entryId > 0) {
            $existingEntry = ReadingProgress::query()
                ->where('user_id', $data['user_id'])
                ->where('id', $entryId)
                ->first();
        }

        if (!$existingEntry) {
            $existingEntry = $this->findExistingEntry(
            $data['user_id'],
            $data['book_title'],
            $data['google_volume_id'] ?? null,
            );
        }

        $wasUpdate = $existingEntry !== null;
        $previousChallengeId = $existingEntry?->challenge_id;

        if ($existingEntry) {
            $existingEntry->update($data);
        } else {
            $existingEntry = ReadingProgress::create($data);
        }

        foreach (array_unique(array_filter([$previousChallengeId, $existingEntry->challenge_id])) as $challengeId) {
            $challenge = ReadingChallenge::query()
                ->where('user_id', $request->user()->id)
                ->find($challengeId);

            $challenge?->refreshCompletionFromProgress();
        }

        if ($wasUpdate) {
            return redirect()->route('reading-shelf.show')
                ->with('status', 'Lasīšanas progress tika atjaunināts.');
        }

        return redirect()->route('reading-progress.index')
            ->with('status', 'Lasīšanas progress veiksmīgi saglabāts.');
    }

    public function destroy(Request $request, int $entryId): RedirectResponse
    {
        $entry = ReadingProgress::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($entryId);
        $challenge = $entry->challenge;
        $entry->delete();
        $challenge?->refreshCompletionFromProgress();

        return to_route('reading-shelf.show')->with('status', 'Lasīšanas ieraksts dzēsts.');
    }

    public function storeWantToRead(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'book_title' => ['required', 'string', 'max:255'],
            'google_volume_id' => ['nullable', 'string', 'max:120'],
            'book_cover_url' => ['nullable', 'url', 'max:2048'],
            'total_pages' => ['nullable', 'integer', 'min:1'],
        ]);

        $userId = (int) $request->user()->id;
        $existingEntry = $this->findExistingEntry(
            $userId,
            $data['book_title'],
            $data['google_volume_id'] ?? null,
        );

        if ($existingEntry) {
            $existingEntry->fill([
                'book_title' => $data['book_title'],
                'google_volume_id' => $data['google_volume_id'] ?? $existingEntry->google_volume_id,
                'book_cover_url' => $data['book_cover_url'] ?? $existingEntry->book_cover_url,
                'total_pages' => $data['total_pages'] ?? $existingEntry->total_pages,
            ]);

            if ((int) $existingEntry->pages_read === 0 && $existingEntry->reading_status !== 'read') {
                $existingEntry->reading_status = 'want_to_read';
            }

            $existingEntry->save();

            return redirect()->back()->with('status', 'Grāmata jau bija tavā sarakstā, dati atjaunināti.');
        }

        ReadingProgress::create([
            'user_id' => $userId,
            'book_title' => $data['book_title'],
            'google_volume_id' => $data['google_volume_id'] ?? null,
            'book_cover_url' => $data['book_cover_url'] ?? null,
            'pages_read' => 0,
            'total_pages' => $data['total_pages'] ?? null,
            'reading_status' => 'want_to_read',
            'emotion' => 'Want to Read',
            'reading_date' => now()->toDateString(),
            'start_time' => '00:00:00',
            'duration_minutes' => 0,
            'end_time' => '00:00:00',
        ]);

        return redirect()->back()->with('status', 'Grāmata pievienota Want to Read sarakstam.');
    }

    private function findExistingEntry(int $userId, string $bookTitle, ?string $googleVolumeId): ?ReadingProgress
    {
        $normalizedBookTitle = mb_strtolower(trim($bookTitle));

        return ReadingProgress::query()
            ->where('user_id', $userId)
            ->when(
                !empty($googleVolumeId),
                fn ($query) => $query->where('google_volume_id', $googleVolumeId),
                fn ($query) => $query
                    ->whereNull('google_volume_id')
                    ->whereRaw('LOWER(book_title) = ?', [$normalizedBookTitle])
            )
            ->first();
    }
}
