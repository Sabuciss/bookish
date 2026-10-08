<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ReadingChallengeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_id',
        'user_id',
        'planned_minutes',
        'elapsed_seconds',
        'pages_read',
        'notes',
        'is_public',
        'started_at',
        'ended_at',
        'active_started_at',
        'timer_status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'active_started_at' => 'datetime',
        'elapsed_seconds' => 'integer',
        'pages_read' => 'integer',
        'is_public' => 'boolean',
    ];

    public static function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d h %02d min %02d s', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%d min %02d s', $minutes, $remainingSeconds);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(ReadingChallenge::class, 'challenge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
