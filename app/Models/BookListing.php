<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_title',
        'author',
        'condition',
        'language',
        'price',
        'description',
        'contact_email',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
