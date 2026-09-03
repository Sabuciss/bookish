<?php

namespace App\Http\Controllers;

use App\Models\BooktokTopBook;
use App\Models\ReadingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BooktokTopController extends Controller
{
    public function index(Request $request): View
    {
        $selectedYear = $request->filled('published_year')
            ? $request->integer('published_year')
            : null;
        $selectedGenre = $request->filled('genre')
            ? trim($request->string('genre')->toString())
            : null;

        $booksQuery = BooktokTopBook::query()
            ->orderBy('rank_position')
            ;

        if ($selectedYear !== null) {
            $booksQuery->where('published_year', $selectedYear);
        }

        $books = $booksQuery->get();

        $books->transform(function (BooktokTopBook $book) {
            $googleBook = $this->fetchGoogleBookData($book->title, $book->author);
            $book->google_thumbnail = $googleBook['thumbnail'] ?? null;
            $book->google_volume_id = $googleBook['volume_id'] ?? null;
            $book->google_page_count = $googleBook['page_count'] ?? null;
            $book->google_categories = $googleBook['categories'] ?? null;

            return $book;
        });

        $availableGenres = $books
            ->flatMap(function (BooktokTopBook $book) {
                return preg_split('/\s*,\s*/', (string) $book->google_categories, -1, PREG_SPLIT_NO_EMPTY);
            })
            ->map(fn (string $genre) => trim($genre))
            ->filter()
            ->unique(fn (string $genre) => mb_strtolower($genre))
            ->sort(fn (string $first, string $second) => strcasecmp($first, $second))
            ->values();

        if ($selectedGenre !== null) {
            $books = $books->filter(function (BooktokTopBook $book) use ($selectedGenre) {
                $genres = preg_split('/\s*,\s*/', (string) $book->google_categories, -1, PREG_SPLIT_NO_EMPTY);

                return collect($genres)->contains(
                    fn (string $genre) => mb_strtolower(trim($genre)) === mb_strtolower($selectedGenre)
                );
            })->values();
        }

        $userBookStatuses = [];
        if ($request->user()) {
            $userBookStatuses = $this->buildUserBookStatuses(
                (int) $request->user()->id,
                $books->map(function (BooktokTopBook $book) {
                    return [
                        'volume_id' => $book->google_volume_id,
                        'title' => $book->title,
                    ];
                })->all()
            );
        }

        $availableYears = BooktokTopBook::query()
            ->whereNotNull('published_year')
            ->distinct()
            ->orderByDesc('published_year')
            ->pluck('published_year');

        return view('booktok.index', [
            'books' => $books,
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'availableGenres' => $availableGenres,
            'selectedGenre' => $selectedGenre,
            'userBookStatuses' => $userBookStatuses,
        ]);
    }

    public function show(Request $request, BooktokTopBook $book): View
    {
        $googleBook = $this->fetchGoogleBookData($book->title, $book->author);
        $currentBookStatus = null;

        if ($request->user()) {
            $statuses = $this->buildUserBookStatuses(
                (int) $request->user()->id,
                [[
                    'volume_id' => $googleBook['volume_id'] ?? null,
                    'title' => $book->title,
                ]]
            );

            $key = !empty($googleBook['volume_id'])
                ? (string) $googleBook['volume_id']
                : mb_strtolower(trim($book->title));

            $currentBookStatus = $statuses[$key] ?? null;
        }

        return view('booktok.show', [
            'book' => $book,
            'googleBook' => $googleBook,
            'currentBookStatus' => $currentBookStatus,
        ]);
    }

    private function buildUserBookStatuses(int $userId, array $books): array
    {
        $requiredKeys = [];

        foreach ($books as $book) {
            $volumeId = $book['volume_id'] ?? null;
            $title = mb_strtolower(trim((string) ($book['title'] ?? '')));

            if (!empty($volumeId)) {
                $requiredKeys[(string) $volumeId] = true;
            }

            if ($title !== '') {
                $requiredKeys[$title] = true;
            }
        }

        if (empty($requiredKeys)) {
            return [];
        }

        $entries = ReadingProgress::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();

        $statuses = [];
        foreach ($entries as $entry) {
            $key = $entry->google_volume_id ?: mb_strtolower(trim((string) $entry->book_title));

            if ($key === '' || isset($statuses[$key]) || !isset($requiredKeys[$key])) {
                continue;
            }

            $statuses[$key] = $entry->reading_status;
        }

        return $statuses;
    }

    private function fetchGoogleBookData(string $title, string $author): ?array
    {
        $cacheKey = 'booktok_google_book_data_' . md5($title . '|' . $author);

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
                    return null;
                }

                $item = $response->json('items.0');

                if (!is_array($item)) {
                    return null;
                }

                $volumeInfo = $item['volumeInfo'] ?? [];
                $saleInfo = $item['saleInfo'] ?? [];

                return [
                    'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
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
}
