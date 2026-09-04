<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReleaseReminder extends Model
{
    protected $fillable = [
        'user_id',
        'google_volume_id',
        'title',
        'author',
        'release_date',
        'info_link',
        'cover_url',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
