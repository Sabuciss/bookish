<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function booktokTopBooks(): HasMany
    {
        return $this->hasMany(BooktokTopBook::class, 'author_id');
    }

    public function booktokFavoriteAuthors(): HasMany
    {
        return $this->hasMany(BooktokFavoriteAuthor::class, 'author_id');
    }
}
