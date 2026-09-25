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
}
