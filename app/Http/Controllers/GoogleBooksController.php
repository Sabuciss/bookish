<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
use App\Models\BooktokTopBook;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        ]);

        $query = $validated['q'];
        $maxResults = (int) ($validated['maxResults'] ?? 3);
        $startIndex = (int) ($validated['startIndex'] ?? 0);
        $orderBy = $validated['orderBy'] ?? 'relevance';
        $remoteOnly = (bool) ($validated['remote'] ?? false);
        $cacheKey = 'google_books_top_v2_' . md5($query . '_' . $maxResults . '_' . $startIndex . '_' . $orderBy . '_' . (int) $remoteOnly);

        try {
            $items = Cache::remember($cacheKey, now()->addHours(6), function () use ($query, $maxResults, $startIndex, $orderBy, $remoteOnly) {
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
                    return $remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex);
                }

                $items = $response->json('items', []);

                return $items ?: ($remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex));
            });
        } catch (ConnectionException) {
            $items = $remoteOnly ? [] : $this->localBooktokItems($maxResults, $startIndex);
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    private function localBooktokItems(int $maxResults, int $startIndex): array
    {
        return BooktokTopBook::query()
            ->orderBy('rank_position')
            ->skip($startIndex)
            ->take($maxResults)
            ->get()
            ->map(fn (BooktokTopBook $book) => [
                'id' => $book->google_volume_id,
                'volumeInfo' => array_filter([
                    'title' => $book->title,
                    'authors' => [$book->author],
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
            'thumbnail' => $info['imageLinks']['thumbnail'] ?? ($info['imageLinks']['smallThumbnail'] ?? null),
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
        ]);
    }
}
