<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * Latest snapshot per distinct book (by google_volume_id, falling back to book_title) for a user,
     * keyed by that same identifier. Resolved at the database level to avoid loading the full history.
     *
     * @return \Illuminate\Support\Collection<string, array>
     */
    public static function latestSnapshotsForUser(int $userId): \Illuminate\Support\Collection
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
            })
            ->get()
            ->keyBy(fn (self $entry): string => $entry->google_volume_id ?: mb_strtolower(trim((string) $entry->book_title)))
            ->map(function (self $entry): array {
                $pagesRead = (int) $entry->pages_read;
                $totalPages = $entry->total_pages ? (int) $entry->total_pages : null;

                return [
                    'entry_id' => $entry->id,
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
            });
    }
}
