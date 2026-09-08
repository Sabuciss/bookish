<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BooktokFavoriteAuthor extends Model
{
    protected $fillable = [
        'user_id',
        'author',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
