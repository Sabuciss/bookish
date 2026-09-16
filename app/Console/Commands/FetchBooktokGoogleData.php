<?php

namespace App\Console\Commands;

use App\Models\BooktokTopBook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FetchBooktokGoogleData extends Command
{
    protected $signature = 'booktok:fetch-google-data';
    protected $description = 'Fetch and cache Google Books data for all BookTok top books';

    public function handle(): int
    {
        $books = BooktokTopBook::all();
        $count = 0;

        $this->info('Fetching Google Books data for ' . $books->count() . ' books...');
        $this->withProgressBar($books, function (BooktokTopBook $book) use (&$count) {
            $googleData = $this->fetchGoogleBookData($book->title, $book->author);

            if (!empty($googleData)) {
                $book->update([
                    'google_thumbnail' => $googleData['thumbnail'] ?? null,
                    'google_volume_id' => $googleData['volume_id'] ?? null,
                    'google_page_count' => $googleData['page_count'] ?? null,
                    'google_published_date' => $googleData['published_date'] ?? null,
                    'google_publisher' => $googleData['publisher'] ?? null,
                    'google_categories' => $googleData['categories'] ?? null,
                    'google_average_rating' => $googleData['average_rating'] ?? null,
                    'google_ratings_count' => $googleData['ratings_count'] ?? null,
                    'google_description' => $googleData['description'] ?? null,
                    'google_preview_link' => $googleData['preview_link'] ?? null,
                    'google_info_link' => $googleData['info_link'] ?? null,
                    'google_data_fetched_at' => now(),
                ]);
                $count++;
            }
        });

        $this->newLine();
        $this->info('Successfully fetched data for ' . $count . ' books!');

        return Command::SUCCESS;
    }

    private function fetchGoogleBookData(string $title, string $author): ?array
    {
        $cacheKey = 'booktok_google_book_data_v3_' . md5($title . '|' . $author);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($title, $author) {
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
                $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', $params);

                if (!$response->ok()) {
                    return $this->fallbackBookData($title, $author);
                }

                $item = $response->json('items.0');

                if (!is_array($item)) {
                    $fallbackResponse = Http::connectTimeout(3)->timeout(10)->get(
                        'https://www.googleapis.com/books/v1/volumes',
                        [...$params, 'q' => $title . ' ' . $author]
                    );

                    if ($fallbackResponse->ok()) {
                        $item = $fallbackResponse->json('items.0');
                    }
                }

                if (!is_array($item)) {
                    return $this->fallbackBookData($title, $author);
                }

                $volumeInfo = $item['volumeInfo'] ?? [];
                $saleInfo = $item['saleInfo'] ?? [];

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
            } catch (\Exception $e) {
                return null;
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
        } catch (\Exception $e) {
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
