<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ReadingChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'challenge_type',
        'target_value',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_completed' => 'boolean',
        'completion_date' => 'date',
        'completion_value' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ReadingChallengeSession::class, 'challenge_id');
    }

    public function progressEntries(): HasMany
    {
        return $this->hasMany(ReadingProgress::class, 'challenge_id');
    }

    public function refreshCompletionFromProgress(): void
    {
        if ($this->challenge_type === 'pages') {
            $progress = $this->progressEntries()->where('user_id', $this->user_id);
            $completionValue = (int) (clone $progress)->sum('pages_read');
            $completionDate = (clone $progress)->max('reading_date');
        } else {
            $sessions = $this->sessions()
                ->where('user_id', $this->user_id)
                ->where('timer_status', 'completed')
                ->whereNotNull('elapsed_seconds');
            $completionValue = intdiv((int) (clone $sessions)->sum('elapsed_seconds'), 60);
            $completionDate = (clone $sessions)->latest('ended_at')->value('ended_at');
        }

        $isCompleted = $completionValue >= $this->target_value;

        $this->forceFill([
            'completion_value' => $completionValue,
            'is_completed' => $isCompleted,
            'completion_date' => $isCompleted ? ($completionDate ?? now()->toDateString()) : null,
        ])->save();
    }

}
