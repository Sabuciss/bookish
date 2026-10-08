<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BooktokTopBook extends Model
{
    use HasFactory;

    protected $table = 'booktok_top_books';

    protected $fillable = [
        'rank_position',
        'title',
        'author',
        'author_id',
        'published_year',
        'google_thumbnail',
        'google_volume_id',
        'google_page_count',
        'google_published_date',
        'google_publisher',
        'google_categories',
        'google_average_rating',
        'google_ratings_count',
        'google_description',
        'google_preview_link',
        'google_info_link',
        'google_data_fetched_at',
    ];

    protected function googleThumbnail(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value && str_starts_with($value, 'http://')
                ? 'https://' . substr($value, 7)
                : $value,
        );
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

        return $this->author()->value('name');
    }
}
