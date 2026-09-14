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
}
