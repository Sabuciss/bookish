<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Zvaigžņu vērtējums no Open Library (bez kvotas) — izmanto, ja Google Books vērtējuma nav.
 */
class BookRatingService
{
    /**
     * @return array{average: float, count: int}|null
     */
    public function openLibraryRating(string $title, ?string $author): ?array
    {
        $title = trim($title);
        $author = filled($author) && $author !== 'Autors nav norādīts' ? trim(explode(',', $author)[0]) : '';

        if ($title === '') {
            return null;
        }

        $cacheKey = 'book_rating_ol_v1_' . md5(mb_strtolower($title . '|' . $author));
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $rating = $this->fetch($title, $author);

        if ($rating === null) {
            return null; // API kļūda — nekešojam
        }

        // false = vērtējuma nav; to kešojam īsāk, jo tas var parādīties vēlāk
        Cache::put($cacheKey, $rating ?: false, $rating ? now()->addDays(7) : now()->addDay());

        return $rating ?: null;
    }

    /**
     * @return array{average: float, count: int}|array{}|null  null = kļūda, [] = nav vērtējuma
     */
    private function fetch(string $title, string $author): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(6)->get('https://openlibrary.org/search.json', array_filter([
                'title' => $title,
                'author' => $author ?: null,
                'fields' => 'title,ratings_average,ratings_count',
                'limit' => 5,
            ]));
        } catch (\Throwable $e) {
            Log::warning('OpenLibrary rating request failed', ['message' => $e->getMessage()]);

            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $rated = collect($response->json('docs', []))
            ->filter(fn (array $doc): bool => !empty($doc['ratings_average']) && !empty($doc['ratings_count']));

        // Priekšroka precīzam nosaukumam, citādi izdevums ar visvairāk vērtējumiem
        $doc = $rated->first(fn (array $doc): bool => mb_strtolower(trim((string) ($doc['title'] ?? ''))) === mb_strtolower($title))
            ?? $rated->sortByDesc('ratings_count')->first();

        if (!$doc) {
            return [];
        }

        return [
            'average' => round((float) $doc['ratings_average'], 1),
            'count' => (int) $doc['ratings_count'],
        ];
    }
}
