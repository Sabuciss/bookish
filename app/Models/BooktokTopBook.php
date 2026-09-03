<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BooktokTopBook extends Model
{
    use HasFactory;

    protected $table = 'booktok_top_books';

    protected $fillable = [
        'rank_position',
        'title',
        'author',
        'published_year',
    ];
}
