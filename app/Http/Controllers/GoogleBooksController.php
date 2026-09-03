<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
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
        ]);

        $query = $validated['q'];
        $maxResults = (int) ($validated['maxResults'] ?? 3);
        $startIndex = (int) ($validated['startIndex'] ?? 0);
        $orderBy = $validated['orderBy'] ?? 'relevance';
        $cacheKey = 'google_books_top_' . md5($query . '_' . $maxResults . '_' . $startIndex . '_' . $orderBy);

        $items = Cache::remember($cacheKey, now()->addHours(6), function () use ($query, $maxResults, $startIndex, $orderBy) {
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

            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes', $params);

            if (!$response->ok()) {
                return [];
            }

            return $response->json('items', []);
        });

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(Request $request, string $volumeId): View
    {
        $cacheKey = 'google_books_volume_' . md5($volumeId);

        $book = Cache::remember($cacheKey, now()->addHours(6), function () use ($volumeId) {
            $apiKey = config('services.google_books.api_key');

            $params = [];
            if (!empty($apiKey)) {
                $params['key'] = $apiKey;
            }

            $response = Http::timeout(10)->get('https://www.googleapis.com/books/v1/volumes/' . rawurlencode($volumeId), $params);

            if (!$response->ok()) {
                return null;
            }

            return $response->json();
        });

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
