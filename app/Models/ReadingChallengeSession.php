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
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(ReadingChallenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
