<x-layout>
    @include('books.partials.detail', [
        'detail' => [
            'backUrl' => $backUrl,
            'backLabel' => 'Atpakaļ',
            'eyebrow' => 'Grāmata',
            'title' => $book['title'],
            'author' => $book['authors'],
            'cover' => $book['thumbnail'],
            'categories' => $book['categories'],
            'year' => preg_match('/^\d{4}/', (string) $book['publishedDate'], $year) ? $year[0] : null,
            'pages' => $book['pageCount'],
            'publisher' => $book['publisher'],
            'language' => $book['language'],
            'rating' => $book['averageRating'],
            'ratingsCount' => $book['ratingsCount'],
            'description' => $book['description'],
            'infoLink' => $book['infoLink'],
            'volumeId' => $book['id'],
            'status' => $currentBookStatus,
        ],
        'recommendations' => $recommendations,
    ])
</x-layout>
