<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
use App\Models\BooktokTopBook;
use App\Services\BookMetadataService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class GoogleBooksController extends Controller
{
    public function top(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'maxResults' => ['nullable', 'integer', 'min:1', 'max:40'],
            'startIndex' => ['nullable', 'integer', 'min:0', 'max:960'],
            'orderBy' => ['nullable', 'in:relevance,newest'],
            'remote' => ['nullable', 'boolean'],
            'firstPublishedFrom' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'firstPublishedTo' => ['nullable', 'integer', 'min:1000', 'max:2100'],
        ]);

        $query = $validated['q'];
        $maxResults = (int) ($validated['maxResults'] ?? 3);
        $startIndex = (int) ($validated['startIndex'] ?? 0);
        $orderBy = $validated['orderBy'] ?? 'relevance';
        $remoteOnly = (bool) ($validated['remote'] ?? false);
        $firstPublishedFrom = isset($validated['firstPublishedFrom']) ? (int) $validated['firstPublishedFrom'] : null;
        $firstPublishedTo = isset($validated['firstPublishedTo']) ? (int) $validated['firstPublishedTo'] : null;
        $cacheKey = 'google_books_top_v6_' . md5($query . '_' . $maxResults . '_' . $startIndex . '_' . $orderBy . '_' . (int) $remoteOnly . '_' . $firstPublishedFrom . '_' . $firstPublishedTo);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json(['items' => $cached]);
        }

        try {
            $params = [
                'q' => $query,
                'orderBy' => $orderBy,
                'maxResults' => $maxResults,
                'startIndex' => $startIndex,
                'printType' => 'books',
            ];

            $apiKey = config('services.google_books.api_key');
            if (!empty($apiKey)) {
                $params['key'] = $apiKey;
            }

            $response = Http::connectTimeout(3)->timeout(10)->get('https://www.googleapis.com/books/v1/volumes', $params);

            if (!$response->ok()) {
                // Don't cache transient failures (e.g. Google 503s) so the next request retries instead of staying empty for hours.
                Log::warning('Google Books top request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($remoteOnly) {
                    return response()->json([
                        'items' => [],
                        'error' => $response->status() === 429 ? 'rate_limited' : 'provider_unavailable',
                    ], $response->status() === 429 ? 429 : 503);
                }

                $items = $remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex);
            } else {
                $items = $response->json('items', []);

                if (!$items) {
                    $items = $remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex);
                } else {
                    Cache::put($cacheKey, $items, now()->addHours(6));
                }
            }
        } catch (ConnectionException $e) {
            Log::warning('Google Books top request threw a connection exception', ['message' => $e->getMessage()]);

            if ($remoteOnly) {
                return response()->json([
                    'items' => [],
                    'error' => 'provider_unavailable',
                ], 503);
            }

            $items = $remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex);
        }

        if ($remoteOnly && str_starts_with(mb_strtolower(trim($query)), 'subject:')) {
            $openLibraryItems = $this->openLibraryGenreItems(
                $query,
                $maxResults,
                $startIndex,
                $firstPublishedFrom,
                $firstPublishedTo
            );
            $googleItems = collect($items);
            if ($firstPublishedFrom !== null && $firstPublishedTo !== null) {
                $googleItems = $googleItems->filter(function (array $item) use ($firstPublishedFrom, $firstPublishedTo): bool {
                    $publishedYear = substr((string) ($item['volumeInfo']['publishedDate'] ?? ''), 0, 4);

                    return ctype_digit($publishedYear)
                        && (int) $publishedYear >= $firstPublishedFrom
                        && (int) $publishedYear <= $firstPublishedTo;
                });
            }

            $items = $googleItems
                ->concat($openLibraryItems)
                ->filter(fn (array $item): bool => filled($item['volumeInfo']['title'] ?? null))
                ->unique(fn (array $item): string => mb_strtolower(trim($item['volumeInfo']['title'])))
                ->take($maxResults)
                ->values()
                ->all();

            if ($items !== []) {
                Cache::put($cacheKey, $items, now()->addHours(6));
            }
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    private function openLibraryGenreItems(
        string $query,
        int $maxResults,
        int $startIndex,
        ?int $firstPublishedFrom,
        ?int $firstPublishedTo
    ): array {
        $openLibraryQuery = $query;
        if ($firstPublishedFrom !== null && $firstPublishedTo !== null) {
            $openLibraryQuery .= ' AND first_publish_year:[' . $firstPublishedFrom . ' TO ' . $firstPublishedTo . ']';
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)->get(
                'https://openlibrary.org/search.json',
                [
                    'q' => $openLibraryQuery,
                    'fields' => 'key,title,author_name,first_publish_year,cover_i',
                    'limit' => $maxResults,
                    'page' => intdiv($startIndex, max(1, $maxResults)) + 1,
                ]
            );

            if (!$response->ok()) {
                Log::warning('OpenLibrary genre search failed', ['status' => $response->status()]);

                return [];
            }

            return collect($response->json('docs', []))
                ->map(function (array $book): array {
                    $coverId = $book['cover_i'] ?? null;
                    $key = $book['key'] ?? null;
                    $volumeInfo = array_filter([
                        'title' => $book['title'] ?? null,
                        'authors' => $book['author_name'] ?? null,
                        'publishedDate' => $book['first_publish_year'] ?? null,
                        'imageLinks' => $coverId
                            ? ['thumbnail' => 'https://covers.openlibrary.org/b/id/' . $coverId . '-M.jpg']
                            : null,
                        'infoLink' => $key ? 'https://openlibrary.org' . $key : null,
                    ], fn ($value) => $value !== null && $value !== []);

                    return ['volumeInfo' => $volumeInfo];
                })
                ->filter(fn (array $book): bool => filled($book['volumeInfo']['title'] ?? null))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('OpenLibrary genre search threw an exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    private function localBooktokItems(int $maxResults, int $startIndex): array
    {
        return BooktokTopBook::query()
            ->with('author:id,name')
            ->orderBy('rank_position')
            ->skip($startIndex)
            ->take($maxResults)
            ->get()
            ->map(fn (BooktokTopBook $book) => [
                'id' => $book->google_volume_id,
                'volumeInfo' => array_filter([
                    'title' => $book->title,
                    'authors' => [$book->authorName()],
                    'publishedDate' => $book->published_year,
                    'categories' => $book->google_categories
                        ? array_map('trim', explode(',', $book->google_categories))
                        : [],
                    'imageLinks' => $book->google_thumbnail
                        ? ['thumbnail' => $book->google_thumbnail]
                        : null,
                    'infoLink' => route('booktok.show', $book),
                ], fn ($value) => $value !== null),
            ])
            ->values()
            ->all();
    }

    public function show(Request $request, string $volumeId): View
    {
        $cacheKey = 'google_books_volume_' . md5($volumeId);

        try {
            $book = Cache::remember($cacheKey, now()->addHours(6), function () use ($volumeId) {
                $apiKey = config('services.google_books.api_key');

                $params = [];
                if (!empty($apiKey)) {
                    $params['key'] = $apiKey;
                }

                $response = Http::connectTimeout(3)->timeout(10)->get('https://www.googleapis.com/books/v1/volumes/' . rawurlencode($volumeId), $params);

                if (!$response->ok()) {
                    return null;
                }

                return $response->json();
            });
        } catch (ConnectionException) {
            abort(503, 'Google Books dati pašlaik nav pieejami. Mēģini vēlreiz pēc brīža.');
        }

        if (!$book) {
            abort(404, 'Grāmata nav atrasta.');
        }

        $info = $book['volumeInfo'] ?? [];

        $bookData = [
            'id' => $book['id'] ?? $volumeId,
            'title' => $info['title'] ?? 'Bez nosaukuma',
            'authors' => isset($info['authors']) && is_array($info['authors']) ? implode(', ', $info['authors']) : 'Autors nav norādīts',
            'description' => isset($info['description']) ? strip_tags($info['description']) : '',
            'publishedDate' => $info['publishedDate'] ?? 'Nav norādīts',
            'publisher' => $info['publisher'] ?? 'Nav norādīts',
            'pageCount' => $info['pageCount'] ?? null,
            'language' => isset($info['language']) ? strtoupper((string) $info['language']) : 'Nav norādīta',
            'categories' => isset($info['categories']) && is_array($info['categories']) ? implode(', ', $info['categories']) : 'Nav norādītas',
            'averageRating' => $info['averageRating'] ?? null,
            'ratingsCount' => $info['ratingsCount'] ?? null,
            'thumbnail' => BookMetadataService::normalizeThumbnailUrl(
                $info['imageLinks']['thumbnail'] ?? ($info['imageLinks']['smallThumbnail'] ?? null)
            ),
            'previewLink' => $info['previewLink'] ?? null,
            'infoLink' => $info['infoLink'] ?? null,
        ];

        $currentBookStatus = null;
        if ($request->user()) {
            $entry = ReadingProgress::query()
                ->where('user_id', (int) $request->user()->id)
                ->where(function ($query) use ($bookData) {
                    $query->where('google_volume_id', $bookData['id'])
                        ->orWhere('book_title', $bookData['title']);
                })
                ->latest('id')
                ->first();

            $currentBookStatus = $entry?->reading_status;
        }

        return view('books.show', [
            'book' => $bookData,
            'currentBookStatus' => $currentBookStatus,
            'backUrl' => $request->query('back') === 'booktok'
                ? route('booktok.index', array_filter([
                    'view' => $request->query('view', 'books'),
                    'author' => $request->query('author'),
                ]))
                : url()->previous(),
        ]);
    }

}
