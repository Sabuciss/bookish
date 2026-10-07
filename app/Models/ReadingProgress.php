<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $table = 'reading_progresses';

    protected $fillable = [
        'user_id',
        'challenge_id',
        'book_title',
        'google_volume_id',
        'book_cover_url',
        'pages_read',
        'total_pages',
        'reading_status',
        'emotion',
        'reading_date',
        'start_time',
        'duration_minutes',
        'end_time',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'total_pages' => 'integer',
        'challenge_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(ReadingChallenge::class, 'challenge_id');
    }

    public static function deriveReadingStatus(int $pagesRead, ?int $totalPages): string
    {
        if ($totalPages !== null && $pagesRead >= $totalPages) {
            return 'read';
        }

        if ($pagesRead <= 0) {
            return 'want_to_read';
        }

        return 'in_progress';
    }

    public static function latestSnapshotsPageForUser(int $userId, int $perPage = 50): LengthAwarePaginator
    {
        return static::latestSnapshotQueryForUser($userId)
            ->latest('reading_date')
            ->latest('id')
            ->paginate($perPage)
            ->through(fn (self $entry): array => self::snapshotArray($entry));
    }

    public static function latestSnapshotStatsForUser(int $userId): object
    {
        return static::latestSnapshotQueryForUser($userId)
            ->selectRaw('COUNT(*) as books_on_shelf, COALESCE(SUM(pages_read), 0) as pages_read')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_pages IS NOT NULL AND pages_read >= total_pages THEN 1 ELSE 0 END), 0) as books_read')
            ->selectRaw('COALESCE(SUM(CASE WHEN pages_read > 0 AND (total_pages IS NULL OR pages_read < total_pages) THEN 1 ELSE 0 END), 0) as books_in_progress')
            ->selectRaw('COALESCE(SUM(CASE WHEN pages_read <= 0 THEN 1 ELSE 0 END), 0) as books_want_to_read')
            ->first();
    }

    /** @return array<string, int> */
    public static function latestPageCountsForUser(int $userId): array
    {
        $query = static::latestSnapshotQueryForUser($userId);

        $pageCounts = (clone $query)
            ->whereNotNull('google_volume_id')
            ->get(['google_volume_id', 'pages_read'])
            ->mapWithKeys(fn (self $entry): array => [$entry->google_volume_id => (int) $entry->pages_read]);

        $titlePageCounts = (clone $query)
            ->whereNull('google_volume_id')
            ->get(['book_title', 'pages_read'])
            ->mapWithKeys(fn (self $entry): array => [mb_strtolower(trim($entry->book_title)) => (int) $entry->pages_read]);

        return $pageCounts->union($titlePageCounts)->all();
    }

    private static function latestSnapshotQueryForUser(int $userId): Builder
    {
        $latestGoogleEntryIds = static::query()
            ->selectRaw('MAX(id)')
            ->where('user_id', $userId)
            ->whereNotNull('google_volume_id')
            ->groupBy('google_volume_id');

        $latestTitleEntryIds = static::query()
            ->selectRaw('MAX(id)')
            ->where('user_id', $userId)
            ->whereNull('google_volume_id')
            ->groupByRaw('LOWER(book_title)');

        return static::query()
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
            });
    }

    private static function snapshotArray(self $entry): array
    {
        $pagesRead = (int) $entry->pages_read;
        $totalPages = is_null($entry->total_pages) ? null : (int) $entry->total_pages;

        return [
            'entry_id' => $entry->id,
            'challenge_id' => $entry->challenge_id,
            'book_title' => $entry->book_title,
            'google_volume_id' => $entry->google_volume_id,
            'book_cover_url' => $entry->book_cover_url,
            'pages_read' => $pagesRead,
            'total_pages' => $totalPages,
            'reading_status' => self::deriveReadingStatus($pagesRead, $totalPages),
            'emotion' => $entry->emotion,
            'reading_date' => $entry->reading_date,
            'start_time' => $entry->start_time,
            'end_time' => $entry->end_time,
        ];
    }
}
