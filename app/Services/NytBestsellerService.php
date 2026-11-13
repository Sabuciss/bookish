<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NYT Books API bestselleru saraksti sadaļai "Populāri šonedēļ".
 *
 * - Viens pieprasījums (lists/overview) atgriež visus sarakstus uzreiz.
 * - Rezultāts tiek kešots 24 h (NYT saraksti mainās reizi nedēļā).
 * - Pēdējais veiksmīgais rezultāts tiek glabāts kā rezerve, ja API nav pieejams.
 */
class NytBestsellerService
{
    private const CACHE_KEY = 'nyt_bestsellers_overview_v1';
    private const FALLBACK_KEY = 'nyt_bestsellers_overview_last_good_v1';
    private const ENDPOINT = 'https://api.nytimes.com/svc/books/v3/lists/overview.json';

    /** Kurus NYT sarakstus rādīt un ar kādu latvisku nosaukumu. */
    public const LISTS = [
        'combined-print-and-e-book-fiction' => 'Daiļliteratūra',
        'young-adult-hardcover' => 'Jauniešiem',
        'combined-print-and-e-book-nonfiction' => 'Populārzinātne',
    ];

    private const BOOKS_PER_LIST = 10;

    public function isConfigured(): bool
    {
        return filled(config('services.nyt_books.api_key'));
    }

    /**
     * @return array{published: ?string, lists: array<int, array{key: string, name: string, books: array<int, array<string, mixed>>}>}
     */
    public function weeklyLists(): array
    {
        $empty = ['published' => null, 'lists' => []];

        if (! $this->isConfigured()) {
            return $empty;
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $fresh = $this->fetch();

        if ($fresh !== null && $fresh['lists'] !== []) {
            Cache::put(self::CACHE_KEY, $fresh, now()->addDay());
            Cache::forever(self::FALLBACK_KEY, $fresh);

            return $fresh;
        }

        // API kļūda: rādām pēdējo veiksmīgo sarakstu un mēģinām vēlreiz pēc stundas,
        // nevis katrā lapas ielādē (lai neiztērētu NYT limitu).
        $fallback = Cache::get(self::FALLBACK_KEY);
        $result = is_array($fallback) ? $fallback : $empty;
        Cache::put(self::CACHE_KEY, $result, now()->addHour());

        return $result;
    }

    private function fetch(): ?array
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->get(self::ENDPOINT, ['api-key' => config('services.nyt_books.api_key')]);
        } catch (ConnectionException $exception) {
            Log::warning('NYT Books API connection failed', ['message' => $exception->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            Log::warning('NYT Books API request failed', ['status' => $response->status()]);

            return null;
        }

        $listsByKey = collect($response->json('results.lists', []))
            ->keyBy(fn (array $list): string => (string) ($list['list_name_encoded'] ?? ''));

        $lists = [];
        foreach (self::LISTS as $key => $name) {
            $books = collect($listsByKey->get($key)['books'] ?? [])
                ->take(self::BOOKS_PER_LIST)
                ->map(fn (array $book): array => $this->normalizeBook($book))
                ->filter(fn (array $book): bool => $book['title'] !== '')
                ->values()
                ->all();

            if ($books !== []) {
                $lists[] = ['key' => $key, 'name' => $name, 'books' => $books];
            }
        }

        return [
            'published' => $response->json('results.published_date'),
            'lists' => $lists,
        ];
    }

    private function normalizeBook(array $book): array
    {
        $rank = (int) ($book['rank'] ?? 0);
        $lastWeek = (int) ($book['rank_last_week'] ?? 0);
        $isbn = (string) ($book['primary_isbn13'] ?? '');
        $image = (string) ($book['book_image'] ?? '');

        return [
            'rank' => $rank,
            // NYT nosaukumi ir LIELIEM BURTIEM → "Iron Flame"
            'title' => mb_convert_case(trim((string) ($book['title'] ?? '')), MB_CASE_TITLE, 'UTF-8'),
            'author' => trim((string) ($book['author'] ?? '')),
            'description' => trim((string) ($book['description'] ?? '')),
            'image' => str_starts_with($image, 'https://') ? $image : '',
            'weeks' => (int) ($book['weeks_on_list'] ?? 0),
            'trend' => match (true) {
                $lastWeek === 0 => 'new',
                $rank < $lastWeek => 'up',
                $rank > $lastWeek => 'down',
                default => 'same',
            },
            'link' => $isbn !== '' ? 'https://openlibrary.org/isbn/' . rawurlencode($isbn) : null,
        ];
    }
}
