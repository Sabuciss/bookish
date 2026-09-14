<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listing_type',
        'availability',
        'book_title',
        'exchange_book_title',
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

    public function isExchange(): bool
    {
        return $this->listing_type === 'exchange';
    }

    public function isAvailable(): bool
    {
        return $this->availability === 'available';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(BookListingApplication::class);
    }
}
