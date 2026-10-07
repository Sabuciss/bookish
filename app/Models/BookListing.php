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
        'google_volume_id',
        'book_cover_url',
        'exchange_book_title',
        'exchange_google_volume_id',
        'exchange_book_cover_url',
        'exchange_book_author',
        'author',
        'condition',
        'language',
        'price',
        'description',
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

    public function notificationUrl(?string $anchor = null): string
    {
        $route = $this->isExchange() ? 'book-exchange.index' : 'book-listings.index';
        $anchor ??= 'book-listing-' . $this->id;

        return route($route, ['listing_id' => $this->id]) . '#' . $anchor;
    }

    public function transactionSnapshot(): array
    {
        return [
            'captured_at' => now()->toIso8601String(),
            'listing' => $this->only([
                'id',
                'listing_type',
                'availability',
                'book_title',
                'google_volume_id',
                'book_cover_url',
                'author',
                'condition',
                'language',
                'price',
                'exchange_book_title',
                'exchange_google_volume_id',
                'exchange_book_cover_url',
                'exchange_book_author',
                'description',
            ]),
        ];
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
