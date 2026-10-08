<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BooktokFavoriteAuthor extends Model
{
    protected $fillable = [
        'user_id',
        'author',
        'author_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function authorName(): ?string
    {
        $relatedAuthor = $this->getRelation('author');
        if ($relatedAuthor instanceof Author) {
            return $relatedAuthor->name;
        }

        $legacyName = $this->getRawOriginal('author');
        if (is_string($legacyName) && trim($legacyName) !== '') {
            return trim($legacyName);
        }

        return null;
    }
}
