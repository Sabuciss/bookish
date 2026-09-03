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
        'is_completed',
        'completion_comment',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ReadingChallengeSession::class);
    }
}
