<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookListingApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_listing_id',
        'user_id',
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
}
