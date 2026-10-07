<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookListingApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_listing_id',
        'user_id',
        'offered_book_title',
        'message',
        'status',
        'listing_snapshot',
        'status_history',
    ];

    protected $casts = [
        'listing_snapshot' => 'array',
        'status_history' => 'array',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(BookListing::class, 'book_listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BookListingMessage::class, 'book_listing_application_id');
    }

    public function transitionTo(string $status, int $actorId, ?string $reason = null, ?array $listingSnapshot = null): bool
    {
        $allowedTransitions = [
            'pending' => ['accepted', 'rejected', 'cancelled'],
            'accepted' => ['cancelled', 'completed'],
            'rejected' => ['pending'],
            'cancelled' => ['pending'],
        ];

        if (! in_array($status, $allowedTransitions[$this->status] ?? [], true)) {
            return false;
        }

        $history = $this->status_history ?? [];
        $history[] = [
            'event' => 'status_changed',
            'from' => $this->status,
            'to' => $status,
            'actor_id' => $actorId,
            'reason' => $reason,
            'at' => now()->toIso8601String(),
        ];

        $changes = [
            'status' => $status,
            'status_history' => $history,
        ];
        if ($listingSnapshot !== null) {
            $changes['listing_snapshot'] = $this->appendListingSnapshot($status, $listingSnapshot);
        }

        $this->forceFill($changes)->save();

        return true;
    }

    public function recordEdit(int $actorId, array $before, array $after): void
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'event' => 'application_edited',
            'status' => $this->status,
            'actor_id' => $actorId,
            'before' => $before,
            'after' => $after,
            'at' => now()->toIso8601String(),
        ];

        $this->forceFill(['status_history' => $history])->save();
    }

    public function recordListingSnapshot(string $stage, array $snapshot): void
    {
        $this->forceFill([
            'listing_snapshot' => $this->appendListingSnapshot($stage, $snapshot),
        ])->save();
    }

    private function appendListingSnapshot(string $stage, array $snapshot): array
    {
        $snapshots = $this->listing_snapshot ?? [];
        $snapshots[] = [
            'stage' => $stage,
            'snapshot' => $snapshot,
        ];

        return $snapshots;
    }
}
