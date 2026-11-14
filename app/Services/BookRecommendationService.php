<?php

namespace App\Services;

use App\Models\BooktokTopBook;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Tev varētu patikt" ieteikumi grāmatas skatam.
 *
 * Google Books kvotu netērē: ieteikumi nāk no BookTok topa (datubāze)
 * un Open Library (bez kvotas), Open Library atbildes kešo uz 7 dienām.
 */
class BookRecommendationService
{
    /** Google kategoriju atslēgvārdi → Open Library tēma. Secība = prioritāte (specifiskākie pirmie). */
    private const SUBJECTS = [
        'romantasy' => 'romantasy',
        'dystopian' => 'dystopias',
        'science fiction' => 'science_fiction',
        'fantasy' => 'fantasy',
        'paranormal' => 'paranormal_fiction',
        'thriller' => 'thrillers',
        'suspense' => 'suspense',
        'mystery' => 'mystery',
        'crime' => 'crime',
        'horror' => 'horror',
        'historical' => 'historical_fiction',
        'romance' => 'romance',
        'love' => 'love',
        'young adult' => 'young_adult_fiction',
        'juvenile' => 'juvenile_fiction',
        'biography' => 'biography',
        'self-help' => 'self-help',
        'poetry' => 'poetry',
        'classics' => 'classic_literature',
    ];

    public function __construct(private readonly int $cacheDays = 7)
    {
    }

    /**
     * @return array<int, array{title: string, author: ?string, cover: ?string, year: ?string, url: string, booktok: bool}>
     */
    public function recommend(string $title, ?string $author, ?string $categories, int $limit = 6): array
    {
        $seen = [$this->normalizeTitle($title) => true];
        $items = collect();

        $push = function (Collection $candidates, int $max) use (&$items, &$seen): void {
            $added = 0;

            foreach ($candidates as $candidate) {
                if ($items->count() >= $max || $added >= $max) {
                    return;
                }

                $key = $this->normalizeTitle($candidate['title']);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $items->push($candidate);
                $added++;
            }
        };

        $author = filled($author) && $author !== 'Autors nav norādīts' ? trim(explode(',', $author)[0]) : null;

        // 1) Vairāk no tā paša autora (maks. 2, lai saraksts nav vienveidīgs)
        if ($author) {
            $byAuthor = $this->booktokBooks(fn ($query) => $query->whereHas(
                'author',
                fn ($authorQuery) => $authorQuery->where('name', $author)
            ))->concat($this->openLibrary('author', $author));

            $push($byAuthor, 2);
        }

        // 2) Līdzīgs žanrs
        $subject = $this->subjectFromCategories($categories);
        if ($subject) {
            $push($this->openLibrary('subject', $subject), $limit);
        }

        // 3) Rezerve: populārākās BookTok grāmatas
        $push($this->booktokBooks(fn ($query) => $query->orderBy('rank_position')->limit(20)), $limit);

        return $items->values()->all();
    }

    public function subjectFromCategories(?string $categories): ?string
    {
        $haystack = mb_strtolower((string) $categories);

        if ($haystack === '' || str_contains($haystack, 'nav norād')) {
            return null;
        }

        foreach (self::SUBJECTS as $keyword => $subject) {
            if (str_contains($haystack, $keyword)) {
                return $subject;
            }
        }

        return str_contains($haystack, 'fiction') ? 'fiction' : null;
    }

    private function booktokBooks(callable $scope): Collection
    {
        $query = BooktokTopBook::query()->with('author:id,name');
        $scope($query);

        return $query->get()->map(fn (BooktokTopBook $book): array => [
            'title' => $book->title,
            'author' => $book->authorName(),
            'cover' => BookMetadataService::normalizeThumbnailUrl($book->google_thumbnail),
            'year' => $book->published_year ? (string) $book->published_year : null,
            'url' => route('booktok.show', $book),
            'booktok' => true,
        ]);
    }

    /**
     * @param 'author'|'subject' $field
     */
    private function openLibrary(string $field, string $value): Collection
    {
        $cacheKey = 'book_recs_ol_v1_' . $field . '_' . md5(mb_strtolower($value));

        $docs = Cache::get($cacheKey);

        if (!is_array($docs)) {
            $docs = $this->fetchOpenLibrary($field, $value);

            if ($docs === null) {
                return collect(); // kļūdu nekešojam — nākamreiz mēģinās vēlreiz
            }

            Cache::put($cacheKey, $docs, now()->addDays($this->cacheDays));
        }

        return collect($docs)->map(fn (array $doc): array => [
            'title' => $doc['title'],
            'author' => $doc['author'],
            'cover' => $doc['cover'],
            'year' => $doc['year'],
            'url' => route('books.find', array_filter(['title' => $doc['title'], 'author' => $doc['author']])),
            'booktok' => false,
        ]);
    }

    private function fetchOpenLibrary(string $field, string $value): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(8)->get('https://openlibrary.org/search.json', [
                'q' => $field === 'author'
                    ? 'author:"' . str_replace('"', '', $value) . '"'
                    : 'subject_key:' . $value . ' AND first_publish_year:[2000 TO ' . now()->year . ']',
                'fields' => 'title,author_name,first_publish_year,cover_i,language',
                'sort' => $field === 'author' ? 'rating' : 'readinglog',
                'limit' => 24,
            ]);

            if (!$response->ok()) {
                Log::warning('OpenLibrary recommendations failed', ['status' => $response->status()]);

                return null;
            }

            return collect($response->json('docs', []))
                ->filter(fn (array $doc): bool => filled($doc['title'] ?? null) && !empty($doc['cover_i']))
                ->filter(fn (array $doc): bool => empty($doc['language']) || in_array('eng', $doc['language'], true))
                ->map(fn (array $doc): array => [
                    'title' => (string) $doc['title'],
                    'author' => $doc['author_name'][0] ?? null,
                    'cover' => 'https://covers.openlibrary.org/b/id/' . (int) $doc['cover_i'] . '-M.jpg',
                    'year' => isset($doc['first_publish_year']) ? (string) $doc['first_publish_year'] : null,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('OpenLibrary recommendations threw an exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function normalizeTitle(string $title): string
    {
        // "Fourth Wing: Deluxe edition" un "Fourth Wing" uzskatām par vienu grāmatu
        $base = preg_split('/[:(\[]/u', $title)[0] ?? $title;

        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($base)));
    }
}
