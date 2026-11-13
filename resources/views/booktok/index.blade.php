<x-layout>
    @php
        $hasFilters = $selectedYear || $selectedGenre || $selectedAuthor || $selectedTitle || $selectedFavoriteAuthors;
        $heroCovers = $books->filter(fn ($book) => !empty($book->google_thumbnail))->take(3)->values();
        $podium = $books->take(3);
        $rest = $books->slice(3);
    @endphp

    <div id="bookish-booktok">
        {{-- ================= HERO ================= --}}
        <section class="bt-hero">
            <div class="bt-hero-copy">
                <p class="bt-eyebrow"><span class="bt-eyebrow-dot"></span> BookTok tops</p>
                <h1 class="bt-hero-title">Grāmatas, par kurām <em>runā</em> visi.</h1>
                <p class="bt-hero-text">Populārākās BookTok grāmatas un autori vienuviet. Atrodi nākamo grāmatu, kuru visi iesaka.</p>

                <nav class="bt-tabs" aria-label="BookTok skata izvēle">
                    <a href="{{ route('booktok.index', ['view' => 'books']) }}"
                       class="bt-tab {{ $selectedView === 'books' && !$selectedFavoriteAuthors ? 'is-active' : '' }}"
                       @if ($selectedView === 'books' && !$selectedFavoriteAuthors) aria-current="page" @endif>📚 Grāmatas</a>
                    <a href="{{ route('booktok.index', ['view' => 'authors']) }}"
                       class="bt-tab {{ $selectedView === 'authors' && !$selectedFavoriteAuthors ? 'is-active' : '' }}"
                       @if ($selectedView === 'authors' && !$selectedFavoriteAuthors) aria-current="page" @endif>✍️ Autori</a>
                    @auth
                        <a href="{{ route('booktok.index', ['view' => 'authors', 'favorite_authors' => 1]) }}"
                           class="bt-tab {{ $selectedFavoriteAuthors ? 'is-active' : '' }}"
                           @if ($selectedFavoriteAuthors) aria-current="page" @endif>♥ Favorīti</a>
                    @else
                        <a href="{{ route('login') }}" class="bt-tab">♥ Favorīti</a>
                    @endauth
                </nav>
            </div>

            <div class="bt-hero-visual" aria-hidden="true">
                @if ($heroCovers->count() >= 3)
                    <div class="bt-fan">
                        @foreach ($heroCovers as $index => $cover)
                            <img src="{{ $cover->google_thumbnail }}" alt="" class="bt-fan-cover bt-fan-cover--{{ $index + 1 }}">
                        @endforeach
                        <span class="bt-fan-badge">#BookTok</span>
                    </div>
                @else
                    <div class="bt-fan bt-fan--empty">
                        <span class="bt-fan-cover bt-fan-cover--1"></span>
                        <span class="bt-fan-cover bt-fan-cover--2"></span>
                        <span class="bt-fan-cover bt-fan-cover--3"></span>
                        <span class="bt-fan-badge">#BookTok</span>
                    </div>
                @endif
            </div>
        </section>

        @if (session('status'))
            <div class="bt-alert">{{ session('status') }}</div>
        @endif

        {{-- ================= FILTRS ================= --}}
        <section class="bt-panel bt-filter-panel">
            <form method="GET" action="{{ route('booktok.index') }}" class="bt-filter">
                <input type="hidden" name="view" value="{{ $selectedView }}">

                <label class="bt-field bt-field--search">
                    <span>Autors</span>
                    <input id="booktok-author" type="search" name="author" value="{{ $selectedAuthor }}" placeholder="Meklēt autoru">
                </label>
                <label class="bt-field bt-field--search">
                    <span>Grāmata</span>
                    <input id="booktok-title" type="search" name="title" value="{{ $selectedTitle }}" placeholder="Meklēt grāmatu">
                </label>
                <label class="bt-field">
                    <span>Gads</span>
                    <select id="published_year" name="published_year">
                        <option value="">Visi gadi</option>
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="bt-field">
                    <span>Žanrs</span>
                    <select id="genre" name="genre">
                        <option value="">Visi žanri</option>
                        @foreach ($availableGenres as $genre)
                            <option value="{{ $genre }}" @selected($selectedGenre === $genre)>{{ $genre }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="bt-filter-actions">
                    <button type="submit" class="bt-button">Filtrēt</button>
                    @if ($hasFilters)
                        <a href="{{ route('booktok.index') }}" class="bt-button bt-button--ghost">Notīrīt</a>
                    @endif
                </div>
            </form>
        </section>

        {{-- Autora bibliogrāfija (abos skatos) --}}
        @if ($selectedAuthor && count($authorBooks['books'] ?? []))
            <section class="bt-panel">
                <header class="bt-section-head">
                    <div>
                        <p class="bt-eyebrow">Autora bibliogrāfija</p>
                        <h2 class="bt-section-title">{{ $selectedAuthor }} grāmatas</h2>
                    </div>
                    <span class="bt-count">{{ count($authorBooks['books'] ?? []) }} grāmatas</span>
                </header>

                <div class="bt-shelf">
                    @foreach (($authorBooks['books'] ?? []) as $authorBook)
                        <a class="bt-shelf-book"
                           href="{{ !empty($authorBook['id']) ? route('books.show', ['volumeId' => $authorBook['id'], 'back' => 'booktok', 'view' => $selectedView, 'author' => $selectedAuthor]) : ($authorBook['info_link'] ?: '#') }}"
                           @if (empty($authorBook['id'])) target="_blank" rel="noopener noreferrer" @endif>
                            <span class="bt-shelf-cover">
                                @if ($authorBook['thumbnail'])
                                    <img src="{{ $authorBook['thumbnail'] }}" alt="{{ $authorBook['title'] }} vāks" loading="lazy">
                                @else
                                    <span>📖</span>
                                @endif
                            </span>
                            <strong title="{{ $authorBook['title'] }}">{{ $authorBook['title'] }}</strong>
                            @if ($authorBook['published_date'])
                                <small>{{ $authorBook['published_date'] }}</small>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= AUTORI ================= --}}
        @if ($selectedView === 'authors')
            <section class="bt-panel">
                <header class="bt-section-head">
                    <div>
                        <p class="bt-eyebrow">BookTok kopiena</p>
                        <h2 class="bt-section-title">{{ $selectedFavoriteAuthors ? 'Mani favorītu autori' : 'Top autori' }}</h2>
                    </div>
                    @if ($selectedFavoriteAuthors)
                        <span class="bt-count">{{ count($favoriteAuthors) }} favorīti</span>
                    @endif
                </header>

                <div class="bt-authors">
                    @forelse ($booktokAuthors as $author)
                        <div class="bt-author">
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'authors', 'author' => $author['name']]) }}" class="bt-author-link">
                                <span class="bt-author-avatar">{{ mb_strtoupper(mb_substr($author['name'], 0, 1)) }}</span>
                                <span class="bt-author-info">
                                    <strong>{{ $author['name'] }}</strong>
                                    <span>{{ $author['book_count'] }} {{ $author['book_count'] === 1 ? 'grāmata' : 'grāmatas' }}</span>
                                </span>
                            </a>
                            @auth
                                @if (in_array($author['name'], $favoriteAuthors, true))
                                    <form action="{{ route('booktok.favorite-authors.destroy', ['author' => $author['id']]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bt-heart is-favorite" aria-label="Noņemt {{ $author['name'] }} no favorītiem" title="Noņemt no favorītiem">♥</button>
                                    </form>
                                @else
                                    <form action="{{ route('booktok.favorite-authors.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="author_id" value="{{ $author['id'] }}">
                                        <button type="submit" class="bt-heart" aria-label="Pievienot {{ $author['name'] }} favorītiem" title="Pievienot favorītiem">♡</button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="bt-heart" aria-label="Ielogojies, lai pievienotu favorītus" title="Ielogojies, lai pievienotu favorītus">♡</a>
                            @endauth
                        </div>
                    @empty
                        <p class="bt-empty">{{ $selectedFavoriteAuthors ? 'Tev vēl nav favorītu autoru. Spied ♡ pie autora, lai pievienotu.' : 'Autori netika atrasti.' }}</p>
                    @endforelse
                </div>
            </section>
        @endif

        {{-- ================= GRĀMATAS ================= --}}
        @if ($selectedView === 'books')
            <section class="bt-panel">
                <header class="bt-section-head">
                    <div>
                        <p class="bt-eyebrow">{{ $selectedFavoriteAuthors ? 'Tava izlase' : 'Topā šobrīd' }}</p>
                        <h2 class="bt-section-title">{{ $selectedFavoriteAuthors ? 'Favorītu autoru grāmatas' : 'BookTok top grāmatas' }}</h2>
                    </div>
                    <span class="bt-count">{{ $books->count() }} grāmatas</span>
                </header>

                @if ($books->isEmpty())
                    <div class="bt-empty">
                        <span aria-hidden="true">🔎</span>
                        @if ($hasFilters)
                            <p>Izvēlētajiem filtriem nav atrastas top grāmatas.</p>
                        @else
                            <p>BookTok top dati vēl nav pieejami. Palaid <code>php artisan migrate --seed</code>.</p>
                        @endif
                    </div>
                @else
                    {{-- Top 3 pjedestāls --}}
                    <div class="bt-podium">
                        @foreach ($podium as $book)
                            <article class="bt-podium-card" @if (!empty($book->google_thumbnail)) style="--cover: url('{{ $book->google_thumbnail }}')" @endif>
                                <span class="bt-podium-rank">#{{ $book->rank_position }}</span>
                                @if (!empty($book->google_thumbnail))
                                    <a href="{{ route('booktok.show', $book) }}" class="bt-podium-cover">
                                        <img src="{{ $book->google_thumbnail }}" alt="{{ $book->title }} vāks">
                                    </a>
                                @else
                                    <div class="bt-podium-cover">
                                        <span data-book-cover-fallback data-book-title="{{ $book->title }}" data-book-author="{{ $book->authorName() }}" data-book-url="{{ route('booktok.show', $book) }}">📖</span>
                                    </div>
                                @endif
                                <div class="bt-podium-body">
                                    <h3><a href="{{ route('booktok.show', $book) }}">{{ $book->title }}</a></h3>
                                    <a class="bt-podium-author" href="{{ request()->fullUrlWithQuery(['view' => 'authors', 'author' => $book->authorName()]) }}">{{ $book->authorName() }}</a>
                                    @if ($book->published_year)
                                        <span class="bt-podium-year">{{ $book->published_year }}</span>
                                    @endif
                                    <div class="bt-podium-action">
                                        @include('booktok.partials.want-button', ['book' => $book])
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Pārējās: numurēts saraksts --}}
                    @if ($rest->isNotEmpty())
                        <ol class="bt-list">
                            @foreach ($rest as $book)
                                <li class="bt-row">
                                    <span class="bt-row-rank">{{ $book->rank_position }}</span>
                                    @if (!empty($book->google_thumbnail))
                                        <a href="{{ route('booktok.show', $book) }}" class="bt-row-cover">
                                            <img src="{{ $book->google_thumbnail }}" alt="{{ $book->title }} vāks" loading="lazy">
                                        </a>
                                    @else
                                        <div class="bt-row-cover">
                                            <span data-book-cover-fallback data-book-title="{{ $book->title }}" data-book-author="{{ $book->authorName() }}" data-book-url="{{ route('booktok.show', $book) }}">📖</span>
                                        </div>
                                    @endif
                                    <div class="bt-row-body">
                                        <h3><a href="{{ route('booktok.show', $book) }}">{{ $book->title }}</a></h3>
                                        <p>
                                            <a href="{{ request()->fullUrlWithQuery(['view' => 'authors', 'author' => $book->authorName()]) }}">{{ $book->authorName() }}</a>
                                            @if ($book->published_year) · {{ $book->published_year }} @endif
                                        </p>
                                    </div>
                                    <div class="bt-row-action">
                                        @include('booktok.partials.want-button', ['book' => $book])
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                @endif
            </section>
        @endif
    </div>
</x-layout>
