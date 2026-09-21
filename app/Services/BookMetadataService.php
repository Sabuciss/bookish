<?php

namespace App\Services;

use App\Models\BooktokTopBook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BookMetadataService
{
    public function fetchGoogleBookData(string $title, string $author): ?array
    {
        $cacheKey = 'booktok_google_book_data_v3_' . md5($title . '|' . $author);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($title, $author): ?array {
            $params = [
                'q' => 'intitle:"' . $title . '" inauthor:"' . $author . '"',
                'maxResults' => 1,
                'printType' => 'books',
            ];

            $apiKey = config('services.google_books.api_key');
            if (!empty($apiKey)) {
                $params['key'] = $apiKey;
            }

            try {
                $response = Http::connectTimeout(3)->timeout(10)
                    ->get('https://www.googleapis.com/books/v1/volumes', $params);

                if (!$response->ok()) {
                    return $this->fallbackBookData($title, $author);
                }

                $item = $response->json('items.0');
                if (!is_array($item)) {
                    $fallbackResponse = Http::connectTimeout(3)->timeout(10)->get(
                        'https://www.googleapis.com/books/v1/volumes',
                        [...$params, 'q' => $title . ' ' . $author]
                    );

                    $item = $fallbackResponse->ok() ? $fallbackResponse->json('items.0') : null;
                }

                if (!is_array($item)) {
                    return $this->fallbackBookData($title, $author);
                }

                $volumeInfo = $item['volumeInfo'] ?? [];
                $thumbnail = $this->normalizeThumbnailUrl(
                    $volumeInfo['imageLinks']['thumbnail']
                        ?? $volumeInfo['imageLinks']['smallThumbnail']
                        ?? null
                ) ?: $this->fetchOpenLibraryCover($title, $author);

                return [
                    'thumbnail' => $thumbnail,
                    'volume_id' => $item['id'] ?? null,
                    'page_count' => $volumeInfo['pageCount'] ?? null,
                    'published_date' => $volumeInfo['publishedDate'] ?? null,
                    'publisher' => $volumeInfo['publisher'] ?? null,
                    'categories' => implode(', ', $volumeInfo['categories'] ?? []),
                    'average_rating' => $volumeInfo['averageRating'] ?? null,
                    'ratings_count' => $volumeInfo['ratingsCount'] ?? null,
                    'description' => $volumeInfo['description'] ?? null,
                    'preview_link' => $volumeInfo['previewLink'] ?? null,
                    'info_link' => $volumeInfo['infoLink'] ?? null,
                ];
            } catch (\Throwable) {
                return $this->fallbackBookData($title, $author);
            }
        });
    }

    public function storedBookData(BooktokTopBook $book): array
    {
        return [
            'thumbnail' => $book->google_thumbnail,
            'volume_id' => $book->google_volume_id,
            'page_count' => $book->google_page_count,
            'published_date' => $book->google_published_date,
            'publisher' => $book->google_publisher,
            'categories' => $book->google_categories,
            'average_rating' => $book->google_average_rating,
            'ratings_count' => $book->google_ratings_count,
            'description' => $book->google_description,
            'preview_link' => $book->google_preview_link,
            'info_link' => $book->google_info_link,
        ];
    }

    public function fetchBooksByAuthor(string $author): array
    {
        $cacheKey = 'booktok_google_author_books_v2_' . md5(mb_strtolower($author));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($author): array {
            $params = [
                'q' => 'inauthor:"' . $author . '"',
                'maxResults' => 40,
                'orderBy' => 'relevance',
                'printType' => 'books',
            ];
            $apiKey = config('services.google_books.api_key');
            if (!empty($apiKey)) {
                $params['key'] = $apiKey;
            }

            try {
                $response = Http::connectTimeout(3)->timeout(10)
                    ->get('https://www.googleapis.com/books/v1/volumes', $params);
                if (!$response->ok()) {
                    return ['books' => [], 'total' => 0];
                }

                $books = collect($response->json('items', []))
                    ->map(function (array $item): array {
                        $volumeInfo = $item['volumeInfo'] ?? [];

                        return [
                            'id' => $item['id'] ?? null,
                            'title' => $volumeInfo['title'] ?? null,
                            'published_date' => $volumeInfo['publishedDate'] ?? null,
                            'thumbnail' => $this->normalizeThumbnailUrl(
                                $volumeInfo['imageLinks']['thumbnail']
                                    ?? $volumeInfo['imageLinks']['smallThumbnail']
                                    ?? null
                            ),
                            'info_link' => $volumeInfo['infoLink'] ?? null,
                        ];
                    })
                    ->filter(fn (array $book): bool => filled($book['title']))
                    ->unique(fn (array $book): string => mb_strtolower($book['title']))
                    ->values()
                    ->all();

                return [
                    'books' => $books,
                    'total' => max(count($books), (int) $response->json('totalItems', count($books))),
                ];
            } catch (\Throwable) {
                return ['books' => [], 'total' => 0];
            }
        });
    }

    private function fallbackBookData(string $title, string $author): ?array
    {
        $thumbnail = $this->fetchOpenLibraryCover($title, $author);

        return $thumbnail ? ['thumbnail' => $thumbnail] : null;
    }

    private function fetchOpenLibraryCover(string $title, string $author): ?string
    {
        try {
            $response = Http::connectTimeout(3)->timeout(8)->get(
                'https://openlibrary.org/search.json',
                ['title' => $title, 'author' => $author, 'limit' => 1, 'fields' => 'cover_i']
            );
            $coverId = $response->json('docs.0.cover_i');

            return $response->ok() && $coverId
                ? 'https://covers.openlibrary.org/b/id/' . $coverId . '-M.jpg'
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeThumbnailUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        return str_starts_with($url, 'http://')
            ? 'https://' . substr($url, 7)
            : $url;
    }
}
