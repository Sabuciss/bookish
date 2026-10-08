<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\BooktokTopBook;
use App\Models\BooktokFavoriteAuthor;
use App\Models\ReadingProgress;
use App\Services\BookMetadataService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BooktokTopController extends Controller
{
    public function __construct(private readonly BookMetadataService $bookMetadata)
    {
    }

    public function index(Request $request): View
    {
        $selectedYear = $request->filled('published_year')
            ? $request->integer('published_year')
            : null;
        $selectedGenre = $request->filled('genre')
            ? trim($request->string('genre')->toString())
            : null;
        $selectedAuthor = $request->filled('author')
            ? trim($request->string('author')->toString())
            : null;
        $selectedTitle = $request->filled('title')
            ? trim($request->string('title')->toString())
            : null;
        $selectedView = $request->query('view', 'books') === 'authors' ? 'authors' : 'books';
        $selectedFavoriteAuthors = $request->boolean('favorite_authors');
        $favoriteAuthors = $request->user()
            ? $request->user()->booktokFavoriteAuthors()
                ->with('author:id,name')
                ->get()
                ->map(fn (BooktokFavoriteAuthor $favorite) => $favorite->authorName())
                ->filter()
                ->values()
                ->all()
            : [];

        $booksQuery = BooktokTopBook::query()
            ->with('author:id,name')
            ->orderBy('rank_position')
            ;

        if ($selectedYear !== null) {
            $booksQuery->where('published_year', $selectedYear);
        }

        if ($selectedAuthor !== null) {
            $booksQuery->whereHas('author', fn ($query) => $query->where('name', 'like', '%' . $selectedAuthor . '%'));
        }

        if ($selectedTitle !== null) {
            $booksQuery->where('title', 'like', '%' . $selectedTitle . '%');
        }

        if ($selectedFavoriteAuthors && $request->user()) {
            $favoriteAuthorIds = $request->user()->booktokFavoriteAuthors()->pluck('author_id')->all();
            $booksQuery->whereIn('author_id', $favoriteAuthorIds);
        }

        $books = $booksQuery->get();

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

        $authorBooks = $selectedAuthor !== null
            ? $this->authorBibliography($selectedAuthor)
            : ['books' => [], 'total' => 0];

        $booktokAuthors = $books
            ->groupBy(fn (BooktokTopBook $book) => $book->authorName() ?? '')
            ->map(function ($authorBooks, $author) {
                $firstBook = $authorBooks->first();

                return [
                    'id' => $firstBook->author_id
                        ?? Author::query()->where('name', $author)->value('id'),
                    'name' => $author,
                    'book_count' => $authorBooks->count(),
                    'books' => $authorBooks->map(fn (BooktokTopBook $book) => [
                        'title' => $book->title,
                        'id' => $book->getKey(),
                    ])->values()->all(),
                ];
            })
            ->sortBy(fn (array $author) => [
                -$author['book_count'],
                mb_strtolower($author['name']),
            ])
            ->values();

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
            'selectedAuthor' => $selectedAuthor,
            'selectedTitle' => $selectedTitle,
            'selectedView' => $selectedView,
            'selectedFavoriteAuthors' => $selectedFavoriteAuthors,
            'availableGenres' => $availableGenres,
            'selectedGenre' => $selectedGenre,
            'booktokAuthors' => $booktokAuthors,
            'favoriteAuthors' => $favoriteAuthors,
            'userBookStatuses' => $userBookStatuses,
            'authorBooks' => $authorBooks,
        ]);
    }

    public function addFavoriteAuthor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'author_id' => ['required', 'integer', 'exists:authors,id'],
        ]);

        $author = Author::query()->findOrFail($validated['author_id']);
        $favoriteQuery = $request->user()->booktokFavoriteAuthors()
            ->where(function ($query) use ($author): void {
                $query->where('author_id', $author->id);

                if (Schema::hasColumn('booktok_favorite_authors', 'author')) {
                    $query->orWhere('author', $author->name);
                }
            });
        $favorite = $favoriteQuery->first();

        if ($favorite) {
            if (! $favorite->author_id) {
                $favorite->update(['author_id' => $author->id]);
            }
        } else {
            $attributes = [
                'user_id' => (int) $request->user()->id,
                'author_id' => $author->id,
            ];

            if (Schema::hasColumn('booktok_favorite_authors', 'author')) {
                $attributes['author'] = $author->name;
            }

            BooktokFavoriteAuthor::query()->create($attributes);
        }

        return back()->with('status', 'Autors pievienots favorītiem.');
    }

    public function removeFavoriteAuthor(Request $request, Author $author): RedirectResponse
    {
        $favorites = $request->user()->booktokFavoriteAuthors()
            ->where(function ($query) use ($author): void {
                $query->where('author_id', $author->id);

                if (Schema::hasColumn('booktok_favorite_authors', 'author')) {
                    $query->orWhere('author', $author->name);
                }
            });

        $favorites->delete();

        return back()->with('status', 'Autors noņemts no favorītiem.');
    }

    public function show(Request $request, BooktokTopBook $book): View
    {
        $googleBook = $book->google_data_fetched_at
            ? $this->bookMetadata->storedBookData($book)
            : $this->bookMetadata->fetchGoogleBookData($book->title, $book->authorName() ?? '');
        $book->google_thumbnail = $googleBook['thumbnail'] ?? null;
        $book->google_volume_id = $googleBook['volume_id'] ?? null;
        $book->google_page_count = $googleBook['page_count'] ?? null;
        $book->google_categories = $googleBook['categories'] ?? null;
        $book->google_published_date = $googleBook['published_date'] ?? null;
        $book->google_publisher = $googleBook['publisher'] ?? null;
        $book->google_average_rating = $googleBook['average_rating'] ?? null;
        $book->google_ratings_count = $googleBook['ratings_count'] ?? null;
        $book->google_description = $googleBook['description'] ?? null;
        $book->google_preview_link = $googleBook['preview_link'] ?? null;
        $book->google_info_link = $googleBook['info_link'] ?? null;
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

    private function fetchGoogleBooksByAuthor(string $author): array
    {
        return $this->bookMetadata->fetchBooksByAuthor($author);
    }

    /**
     * Author bibliography, falling back to our own curated books when the remote
     * Google Books lookup fails or returns nothing (e.g. on hosts without reliable
     * outbound access to the Google Books API).
     */
    private function authorBibliography(string $author): array
    {
        $remoteBooks = collect($this->fetchGoogleBooksByAuthor($author)['books'] ?? [])
            ->map(fn (array $book): array => [
                ...$book,
                'url' => !empty($book['id'])
                    ? route('books.show', ['volumeId' => $book['id']])
                    : ($book['info_link'] ?: null),
            ]);

        $localBooks = BooktokTopBook::query()
            ->whereHas('author', fn ($query) => $query->where('name', 'like', '%' . $author . '%'))
            ->orderBy('rank_position')
            ->get()
            ->map(fn (BooktokTopBook $book): array => [
                'id' => $book->google_volume_id,
                'title' => $book->title,
                'published_date' => $book->published_year,
                'thumbnail' => $book->google_thumbnail,
                'info_link' => null,
                'url' => route('booktok.show', $book),
            ]);

        // Local (BookTok top) entries take precedence so the link stays on our own book page, not Google's.
        $books = $localBooks
            ->concat($remoteBooks)
            ->unique(fn (array $book): string => mb_strtolower(trim((string) $book['title'])))
            ->values()
            ->all();

        return [
            'books' => $books,
            'total' => count($books),
        ];
    }
}
