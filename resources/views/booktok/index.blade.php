<x-layout>
    <div class="reading-progress-page booktok-page">
        <h1>BookTok tops </h1>
        <p>Atsevišķa sadaļa ar BookTok top grāmatām no sagatavotā seedera.</p>

        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <nav class="booktok-view-switcher" aria-label="BookTok skata izvēle">
            <a
                href="{{ route('booktok.index', ['view' => 'authors']) }}"
                class="booktok-view-button {{ $selectedView === 'authors' ? 'is-active' : '' }}"
                @if ($selectedView === 'authors') aria-current="page" @endif
            >
                Autori
            </a>
            <a
                href="{{ route('booktok.index', ['view' => 'books']) }}"
                class="booktok-view-button {{ $selectedView === 'books' && !$selectedFavoriteAuthors ? 'is-active' : '' }}"
                @if ($selectedView === 'books' && !$selectedFavoriteAuthors) aria-current="page" @endif
            >
                Grāmatas
            </a>
            @auth
                <a
                    href="{{ route('booktok.index', ['view' => 'books', 'favorite_authors' => 1]) }}"
                    class="booktok-view-button booktok-favorites-button {{ $selectedFavoriteAuthors ? 'is-active' : '' }}"
                    @if ($selectedFavoriteAuthors) aria-current="page" @endif
                >
                    Favorīti
                </a>
            @else
                <a href="{{ route('login') }}" class="booktok-view-button booktok-favorites-button">Favorīti</a>
            @endauth
        </nav>

        <section class="uiverse-container" style="margin-bottom: 16px;">
            <form method="GET" action="{{ route('booktok.index') }}">
                <input type="hidden" name="view" value="{{ $selectedView }}">
                <p class="welcome-card-text">Filtrē BookTok topu pēc gada, žanra, autora vai grāmatas nosaukuma.</p>
                <div class="weekly-top-controls">
                    <div class="bookish-field-group">
                        <label class="bookish-field-label" for="booktok-author">Autors</label>
                        <input
                            id="booktok-author"
                            type="search"
                            name="author"
                            value="{{ $selectedAuthor }}"
                            class="weekly-genre-select"
                            placeholder="Meklēt autoru"
                        >
                    </div>
                    <div class="bookish-field-group">
                        <label class="bookish-field-label" for="booktok-title">Grāmatas nosaukums</label>
                        <input
                            id="booktok-title"
                            type="search"
                            name="title"
                            value="{{ $selectedTitle }}"
                            class="weekly-genre-select"
                            placeholder="Meklēt grāmatu"
                        >
                    </div>
                    <div class="bookish-field-group">
                        <label class="bookish-field-label" for="published_year">Publicēšanas gads</label>
                        <select id="published_year" name="published_year" class="weekly-genre-select weekly-year-input">
                            <option value="">Visi gadi</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bookish-field-group">
                        <label class="bookish-field-label" for="genre">Žanrs</label>
                        <select id="genre" name="genre" class="weekly-genre-select">
                            <option value="">Visi žanri</option>
                            @foreach ($availableGenres as $genre)
                                <option value="{{ $genre }}" @selected($selectedGenre === $genre)>{{ $genre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="reading-progress-submit">Pielietot filtru</button>

                    @if ($selectedYear || $selectedGenre || $selectedAuthor || $selectedTitle || $selectedFavoriteAuthors)
                        <a href="{{ route('booktok.index') }}" class="reading-progress-submit">Notīrīt filtru</a>
                    @endif
                </div>
            </form>
        </section>

        @if ($selectedView === 'authors')
        <section class="booktok-authors uiverse-container">
            <div class="booktok-section-heading">
                <div>
                    <p class="book-detail-eyebrow">BookTok kopiena</p>
                    <h2 class="booktok-section-title">Top autori</h2>
                </div>
                <span class="booktok-section-count">
                    {{ $booktokAuthors->count() }} autori
                    @auth
                        · {{ count($favoriteAuthors) }} favorīti
                    @endauth
                </span>
            </div>

            <div class="booktok-author-grid">
                @foreach ($booktokAuthors as $author)
                    <div class="booktok-author-card">
                        <a
                            href="{{ request()->fullUrlWithQuery(['view' => 'authors', 'author' => $author['name']]) }}"
                            class="booktok-author-link"
                        >
                            <span class="booktok-author-avatar">{{ mb_strtoupper(mb_substr($author['name'], 0, 1)) }}</span>
                            <span class="booktok-author-info">
                                <strong>{{ $author['name'] }}</strong>
                                <span>{{ $author['book_count'] }} {{ $author['book_count'] === 1 ? 'grāmata' : 'grāmatas' }}</span>
                            </span>
                        </a>
                        @auth
                            @if (in_array($author['name'], $favoriteAuthors, true))
                                <form action="{{ route('booktok.favorite-authors.destroy', ['author' => $author['name']]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="booktok-favorite-button is-favorite" aria-label="Noņemt {{ $author['name'] }} no favorītiem" title="Noņemt no favorītiem">♥</button>
                                </form>
                            @else
                                <form action="{{ route('booktok.favorite-authors.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="author" value="{{ $author['name'] }}">
                                    <button type="submit" class="booktok-favorite-button" aria-label="Pievienot {{ $author['name'] }} favorītiem" title="Pievienot favorītiem">♡</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="booktok-favorite-button" aria-label="Ielogojies, lai pievienotu favorītus" title="Ielogojies, lai pievienotu favorītus">♡</a>
                        @endauth
                    </div>
                @endforeach
            </div>

            @if ($selectedAuthor && count($authorBooks['books'] ?? []))
                <div class="booktok-author-books-section">
                    <div class="booktok-section-heading">
                        <div>
                            <p class="book-detail-eyebrow">Autora bibliogrāfija</p>
                            <h2 class="booktok-section-title">{{ $selectedAuthor }} grāmatas</h2>
                        </div>
                        <span class="booktok-section-count">Kopā atrastas {{ $authorBooks['total'] ?? count($authorBooks['books'] ?? []) }} grāmatas</span>
                    </div>

                    <div class="booktok-author-books-grid">
                        @foreach (($authorBooks['books'] ?? []) as $authorBook)
                            <a
                                class="booktok-author-book-card"
                                href="{{ $authorBook['info_link'] ?: 'https://books.google.com/books?id=' . urlencode($authorBook['id'] ?? '') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                @if ($authorBook['thumbnail'])
                                    <img src="{{ $authorBook['thumbnail'] }}" alt="{{ $authorBook['title'] }} vāks">
                                @endif
                                <span>
                                    <strong>{{ $authorBook['title'] }}</strong>
                                    @if ($authorBook['published_date'])
                                        <small>{{ $authorBook['published_date'] }}</small>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
        @endif

        @if ($selectedView === 'books')
        @if ($selectedAuthor && count($authorBooks['books'] ?? []))
        <section class="booktok-author-books-section uiverse-container">
            <div class="booktok-section-heading">
                <div>
                    <p class="book-detail-eyebrow">Autora bibliogrāfija</p>
                    <h2 class="booktok-section-title">{{ $selectedAuthor }} grāmatas</h2>
                </div>
                <span class="booktok-section-count">Kopā atrastas {{ $authorBooks['total'] ?? count($authorBooks['books'] ?? []) }} grāmatas</span>
            </div>

            <div class="booktok-author-books-grid">
                @foreach (($authorBooks['books'] ?? []) as $authorBook)
                    <a
                        class="booktok-author-book-card"
                        href="{{ $authorBook['info_link'] ?: 'https://books.google.com/books?id=' . urlencode($authorBook['id'] ?? '') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        @if ($authorBook['thumbnail'])
                            <img src="{{ $authorBook['thumbnail'] }}" alt="{{ $authorBook['title'] }} vāks">
                        @endif
                        <span>
                            <strong>{{ $authorBook['title'] }}</strong>
                            @if ($authorBook['published_date'])
                                <small>{{ $authorBook['published_date'] }}</small>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        <section class="reading-progress-history uiverse-container">
            @if ($books->isEmpty())
                @if ($selectedYear || $selectedGenre || $selectedAuthor || $selectedTitle || $selectedFavoriteAuthors)
                    <p>Izvēlētajiem filtriem nav atrastas top grāmatas.</p>
                @else
                    <p>BookTok top dati vēl nav pieejami. Palaiž `php artisan migrate --seed`.</p>
                @endif
            @else
                <table class="reading-progress-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vāks</th>
                            <th>Nosaukums</th>
                            <th>Autors</th>
                            <th>Publicēta</th>
                            <th>Want to Read</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($books as $book)
                            @php
                                $bookStatusKey = $book->google_volume_id ?: mb_strtolower(trim($book->title));
                                $bookStatus = $userBookStatuses[$bookStatusKey] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $book->rank_position }}</td>
                                <td>
                                    @if (!empty($book->google_thumbnail))
                                        <a href="{{ route('booktok.show', $book) }}">
                                            <img src="{{ $book->google_thumbnail }}" alt="{{ $book->title }} vāks" style="width: 44px; height: 66px; object-fit: cover; border-radius: 6px;">
                                        </a>
                                    @else
                                        <span>Nav vāka</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('booktok.show', $book) }}">{{ $book->title }}</a>
                                </td>
                                <td>{{ $book->author }}</td>
                                <td>{{ $book->published_year ?? '—' }}</td>
                                <td>
                                    @auth
                                        @if ($bookStatus === 'read')
                                            <span class="reading-progress-status-badge">READ</span>
                                        @elseif ($bookStatus === 'in_progress')
                                            <span class="reading-progress-status-badge">In Progress</span>
                                        @elseif ($bookStatus === 'want_to_read')
                                            <span class="reading-progress-status-badge">Added to Want to Read</span>
                                        @else
                                            <form action="{{ route('reading-progress.want-to-read.store') }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                <input type="hidden" name="book_title" value="{{ $book->title }}">
                                                <input type="hidden" name="google_volume_id" value="{{ $book->google_volume_id }}">
                                                <input type="hidden" name="book_cover_url" value="{{ $book->google_thumbnail }}">
                                                <input type="hidden" name="total_pages" value="{{ $book->google_page_count }}">
                                                <button type="submit" class="reading-progress-submit">Want to Read</button>
                                            </form>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="reading-progress-submit" style="text-decoration: none; display: inline-block;">Ielogojies</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
        @endif
    </div>
</x-layout>
