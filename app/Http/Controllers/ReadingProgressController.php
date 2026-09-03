<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingProgressRequest;
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
        $data = $this->buildProgressData($userId);

        return view('reading-progress.shelf', [
            'bookSnapshots' => $data['bookSnapshots'],
            'bookSnapshotsByStatus' => $data['bookSnapshotsByStatus'],
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
        ];

        $prefill['is_edit_mode'] = !empty($prefill['entry_id']) || !empty($prefill['book_title']);

        return view('reading-progress.index', [
            'progressEntries' => $data['progressEntries'],
            'latestPagesByBook' => $data['latestPagesByBook'],
            'prefill' => $prefill,
        ]);
    }

    private function buildProgressData(int $userId): array
    {

        $allEntries = ReadingProgress::query()
            ->where('user_id', $userId)
            ->latest('reading_date')
            ->latest('id')
            ->get();

        $progressEntries = ReadingProgress::query()
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->where('reading_status', '!=', 'want_to_read')
                    ->orWhere('pages_read', '>', 0);
            })
            ->latest('reading_date')
            ->latest('id')
            ->get();

        $bookSnapshots = [];
        foreach ($allEntries as $entry) {
            $key = $entry->google_volume_id ?: mb_strtolower(trim((string) $entry->book_title));

            if (!isset($bookSnapshots[$key])) {
                $pagesRead = (int) $entry->pages_read;
                $totalPages = $entry->total_pages ? (int) $entry->total_pages : null;

                if ($totalPages !== null && $pagesRead >= $totalPages) {
                    $effectiveStatus = 'read';
                } elseif ($pagesRead <= 0) {
                    $effectiveStatus = 'want_to_read';
                } else {
                    $effectiveStatus = 'in_progress';
                }

                $bookSnapshots[$key] = [
                    'entry_id' => $entry->id,
                    'book_title' => $entry->book_title,
                    'google_volume_id' => $entry->google_volume_id,
                    'book_cover_url' => $entry->book_cover_url,
                    'pages_read' => $pagesRead,
                    'total_pages' => $totalPages,
                    'reading_status' => $effectiveStatus,
                    'emotion' => $entry->emotion,
                    'reading_date' => $entry->reading_date,
                    'start_time' => $entry->start_time,
                    'end_time' => $entry->end_time,
                ];
            }
        }

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
            'bookSnapshots' => array_values($bookSnapshots),
            'bookSnapshotsByStatus' => $bookSnapshotsByStatus,
            'latestPagesByBook' => collect($bookSnapshots)
                ->mapWithKeys(fn (array $snapshot, string $key) => [$key => (int) $snapshot['pages_read']])
                ->all(),
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
            $data['reading_status'] = 'read';
        } elseif ((int) $data['pages_read'] <= 0) {
            $data['reading_status'] = 'want_to_read';
        } else {
            $data['reading_status'] = 'in_progress';
        }

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

        if ($existingEntry) {
            $existingEntry->update($data);

            return redirect()->route('reading-shelf.show')
                ->with('status', 'Lasīšanas progress tika atjaunināts.');
        }

        ReadingProgress::create($data);

        return redirect()->route('reading-progress.index')
            ->with('status', 'Lasīšanas progress veiksmīgi saglabāts.');
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
