<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Open Library (bez API atslēgas un kvotas) kā rezerve, ja Google Books
 * nedod vērtējumu vai aprakstu (piem., kad Google kvota ir beigusies).
 */
class OpenLibraryService
{
    private const BASE_URL = 'https://openlibrary.org';

    /**
     * @return array{average: float, count: int}|null
     */
    public function rating(string $title, ?string $author): ?array
    {
        return $this->remember('rating', $title, $author, 7, function (string $title, string $author): ?array {
            $docs = $this->search($title, $author, 'title,ratings_average,ratings_count');

            if ($docs === null) {
                return null;
            }

            $rated = collect($docs)
                ->filter(fn (array $doc): bool => !empty($doc['ratings_average']) && !empty($doc['ratings_count']));

            // Priekšroka precīzam nosaukumam, citādi izdevums ar visvairāk vērtējumiem
            $doc = $rated->first(fn (array $doc): bool => $this->sameTitle($doc['title'] ?? '', $title))
                ?? $rated->sortByDesc('ratings_count')->first();

            return $doc
                ? ['average' => round((float) $doc['ratings_average'], 1), 'count' => (int) $doc['ratings_count']]
                : [];
        });
    }

    public function description(string $title, ?string $author): ?string
    {
        return $this->remember('description', $title, $author, 30, function (string $title, string $author): ?string {
            $docs = $this->search($title, $author, 'key,title');

            if ($docs === null) {
                return null;
            }

            $doc = collect($docs)->first(fn (array $doc): bool => $this->sameTitle($doc['title'] ?? '', $title))
                ?? ($docs[0] ?? null);
            $key = (string) ($doc['key'] ?? '');

            if (!preg_match('#^/works/OL\d+W$#', $key)) {
                return '';
            }

            try {
                $response = Http::connectTimeout(3)->timeout(6)->get(self::BASE_URL . $key . '.json');
            } catch (\Throwable $e) {
                Log::warning('OpenLibrary work request failed', ['message' => $e->getMessage()]);

                return null;
            }

            if (!$response->ok()) {
                return null;
            }

            $description = $response->json('description');
            $description = is_array($description) ? ($description['value'] ?? '') : (string) $description;

            return $this->cleanDescription($description);
        });
    }

    /**
     * Kešo rezultātu. Callback atgriež null pie API kļūdas (nekešo),
     * tukšu vērtību, ja datu nav (kešo 1 dienu), vai datus (kešo $days dienas).
     */
    private function remember(string $type, string $title, ?string $author, int $days, callable $resolve): mixed
    {
        $title = trim($title);
        $author = filled($author) && $author !== 'Autors nav norādīts' ? trim(explode(',', $author)[0]) : '';

        if ($title === '') {
            return null;
        }

        $cacheKey = 'openlibrary_' . $type . '_v1_' . md5(mb_strtolower($title . '|' . $author));
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $value = $resolve($title, $author);

        if ($value === null) {
            return null;
        }

        $isEmpty = $value === [] || $value === '';
        Cache::put($cacheKey, $isEmpty ? false : $value, $isEmpty ? now()->addDay() : now()->addDays($days));

        return $isEmpty ? null : $value;
    }

    /** @return array<int, array<string, mixed>>|null */
    private function search(string $title, string $author, string $fields): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(6)->get(self::BASE_URL . '/search.json', array_filter([
                'title' => $title,
                'author' => $author ?: null,
                'fields' => $fields,
                'limit' => 5,
            ]));
        } catch (\Throwable $e) {
            Log::warning('OpenLibrary search failed', ['message' => $e->getMessage()]);

            return null;
        }

        return $response->ok() ? $response->json('docs', []) : null;
    }

    private function sameTitle(string $a, string $b): bool
    {
        return mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    private function cleanDescription(string $text): string
    {
        // Open Library aprakstos mēdz būt "----------" + avotu saraksts un Markdown saites
        $text = preg_split('/\R-{3,}/u', $text)[0] ?? $text;
        $text = preg_replace('/\(\[[^\]]*\]\[\d+\]\)|\[([^\]]+)\]\[\d+\]|\[([^\]]+)\]\([^)]+\)/u', '$1$2', $text);
        $text = preg_replace("/\r\n?/", "\n", (string) $text);
        $text = preg_replace("/\n{3,}/", "\n\n", (string) $text);

        return trim(strip_tags((string) $text));
    }
}
