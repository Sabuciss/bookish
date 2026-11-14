<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
use App\Models\BooktokTopBook;
use App\Services\BookMetadataService;
use App\Services\BookRecommendationService;
use App\Services\OpenLibraryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $isGenreQuery = $remoteOnly && str_starts_with(mb_strtolower(trim($query)), 'subject:');
        $firstPublishedFrom = isset($validated['firstPublishedFrom']) ? (int) $validated['firstPublishedFrom'] : null;
        $firstPublishedTo = isset($validated['firstPublishedTo']) ? (int) $validated['firstPublishedTo'] : null;
        $cacheKey = 'google_books_top_v6_' . md5($query . '_' . $maxResults . '_' . $startIndex . '_' . $orderBy . '_' . (int) $remoteOnly . '_' . $firstPublishedFrom . '_' . $firstPublishedTo);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json(['items' => $cached]);
        }

        $providerError = null;

        // Žanri ar gadu filtru: Google "relevance" atgriež gandrīz tikai vecas grāmatas,
        // kuras tāpat tiek izmestas, tāpēc Google nevaicājam (ietaupa API kvotu) un
        // grāmatas ņemam no Open Library (bez Google kvotas).
        $skipGoogle = $isGenreQuery && $firstPublishedFrom !== null && $firstPublishedTo !== null;
        $items = [];

        if (! $skipGoogle) {
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

                    $providerError = $response->status() === 429 ? 'rate_limited' : 'provider_unavailable';

                    if ($remoteOnly && ! $isGenreQuery) {
                        return response()->json([
                            'items' => [],
                            'error' => $providerError,
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

                $providerError = 'provider_unavailable';

                if ($remoteOnly && ! $isGenreQuery) {
                    return response()->json([
                        'items' => [],
                        'error' => $providerError,
                    ], 503);
                }

                $items = $remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex);
            }
        }

        if ($isGenreQuery) {
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
                Cache::put($cacheKey, $items, now()->addDay()); // žanri mainās lēni → 24 h kešs
            }

            if ($providerError && $items === []) {
                return response()->json([
                    'items' => [],
                    'error' => $providerError,
                ], $providerError === 'rate_limited' ? 429 : 503);
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

    /**
     * Ieteikumu saite (grāmata no Open Library, kurai nav Google ID).
     * Google meklējam tikai tad, kad lietotājs tiešām uzklikšķina, un rezultātu kešojam.
     */
    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:120'],
        ]);

        $title = trim($validated['title']);
        $author = trim((string) ($validated['author'] ?? ''));

        $booktokBook = BooktokTopBook::query()->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])->first();
        if ($booktokBook) {
            return redirect()->route('booktok.show', $booktokBook);
        }

        $cacheKey = 'google_books_find_v1_' . md5(mb_strtolower($title . '|' . $author));
        $volumeId = Cache::get($cacheKey);

        if ($volumeId === null) {
            $volumeId = $this->findGoogleVolumeId($title, $author);

            if ($volumeId !== null) {
                // '' = nav atrasta; to arī kešojam, lai atkārtoti netērētu kvotu
                Cache::put($cacheKey, $volumeId, $volumeId === '' ? now()->addDay() : now()->addDays(30));
            }
        }

        if (!$volumeId) {
            return redirect()->back()->with('status', 'Šīs grāmatas informācija pašlaik nav pieejama.');
        }

        return redirect()->route('books.show', $volumeId);
    }

    /** null = API kļūda (nekešot), '' = grāmata nav atrasta. */
    private function findGoogleVolumeId(string $title, string $author): ?string
    {
        $query = 'intitle:"' . str_replace('"', '', $title) . '"';
        if ($author !== '') {
            $query .= ' inauthor:"' . str_replace('"', '', $author) . '"';
        }

        $params = ['q' => $query, 'maxResults' => 5, 'printType' => 'books'];
        $apiKey = config('services.google_books.api_key');
        if (!empty($apiKey)) {
            $params['key'] = $apiKey;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)->get('https://www.googleapis.com/books/v1/volumes', $params);
        } catch (ConnectionException) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $items = collect($response->json('items', []));

        // Priekšroka precīzam nosaukumam (nevis, piem., "The Little Prince (ESL Version)")
        $exact = $items->first(
            fn (array $item): bool => mb_strtolower(trim((string) ($item['volumeInfo']['title'] ?? ''))) === mb_strtolower($title)
        );

        return (string) (($exact ?? $items->first())['id'] ?? '');
    }

    public function show(
        Request $request,
        string $volumeId,
        BookRecommendationService $recommendations,
        OpenLibraryService $openLibrary
    ): View
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
            'infoLink' => $info['infoLink'] ?? null,
        ];

        // Ja Google nedod vērtējumu vai aprakstu, ņemam no Open Library
        if (empty($bookData['averageRating'])) {
            $rating = $openLibrary->rating($bookData['title'], $bookData['authors']);
            $bookData['averageRating'] = $rating['average'] ?? null;
            $bookData['ratingsCount'] = $rating['count'] ?? null;
        }

        if (trim($bookData['description']) === '') {
            $bookData['description'] = (string) $openLibrary->description($bookData['title'], $bookData['authors']);
        }

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
            'recommendations' => $recommendations->recommend($bookData['title'], $bookData['authors'], $bookData['categories']),
            'backUrl' => $request->query('back') === 'booktok'
                ? route('booktok.index', array_filter([
                    'view' => $request->query('view', 'books'),
                    'author' => $request->query('author'),
                ]))
                : url()->previous(),
        ]);
    }

}
