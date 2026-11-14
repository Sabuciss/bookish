<x-layout>
    @include('books.partials.detail', [
        'detail' => [
            'backUrl' => route('booktok.index'),
            'backLabel' => 'Atpakaļ uz BookTok topu',
            'eyebrow' => 'BookTok tops #' . $book->rank_position,
            'title' => $book->title,
            'author' => $book->authorName(),
            'cover' => $book->google_thumbnail,
            'categories' => $book->google_categories,
            'year' => $book->published_year ?: (preg_match('/^\d{4}/', (string) $book->google_published_date, $year) ? $year[0] : null),
            'pages' => $book->google_page_count,
            'publisher' => $book->google_publisher,
            'language' => null,
            'rating' => $book->google_average_rating,
            'ratingsCount' => $book->google_ratings_count,
            'description' => $book->google_description,
            'infoLink' => $book->google_info_link,
            'volumeId' => $book->google_volume_id,
            'status' => $currentBookStatus,
        ],
        'recommendations' => $recommendations,
    ])
</x-layout>
