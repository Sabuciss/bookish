<x-layout>
    @php
        $genres = [
            ['key' => 'fantasy', 'query' => 'subject:fantasy', 'lv' => 'Fantāzija', 'en' => 'Fantasy', 'icon' => '🐉',
                'text' => 'Stāsti ar maģiju, mītiskām būtnēm un pārdabiskām pasaulēm.', 'loading' => 'Ielādējam fantāzijas grāmatas...'],
            ['key' => 'science-fiction', 'query' => 'subject:science fiction', 'lv' => 'Zinātniskā fantastika', 'en' => 'Science Fiction', 'icon' => '🚀',
                'text' => 'Stāsti par nākotnes zinātni, kosmosa ceļojumiem un attīstītām tehnoloģijām.', 'loading' => 'Ielādējam zinātniskās fantastikas grāmatas...'],
            ['key' => 'mystery-thriller', 'query' => 'subject:mystery', 'lv' => 'Detektīvi un trilleri', 'en' => 'Mystery & Thriller', 'icon' => '🔍',
                'text' => 'Saspringti sižeti par detektīviem, noziegumiem un augstām likmēm.', 'loading' => 'Ielādējam detektīvus un trillerus...'],
            ['key' => 'romance', 'query' => 'subject:romance', 'lv' => 'Romantika', 'en' => 'Romance', 'icon' => '💌',
                'text' => 'Stāsti, kuros galvenā loma ir mīlestības attiecībām.', 'loading' => 'Ielādējam romantikas grāmatas...'],
            ['key' => 'historical-fiction', 'query' => 'subject:historical fiction', 'lv' => 'Vēsturiskā proza', 'en' => 'Historical Fiction', 'icon' => '🏛️',
                'text' => 'Izdomāti stāsti, kas norisinās īstos vēstures periodos.', 'loading' => 'Ielādējam vēsturiskās prozas grāmatas...'],
            ['key' => 'horror', 'query' => 'subject:horror', 'lv' => 'Šausmu literatūra', 'en' => 'Horror', 'icon' => '🕯️',
                'text' => 'Tumši stāsti, kas radīti, lai lasītāju nobiedētu vai satricinātu.', 'loading' => 'Ielādējam šausmu literatūras grāmatas...'],
        ];
        $continueReading = $continueReading ?? collect();
        $mainBook = $continueReading->first();
    @endphp

    <div id="bookish-home" class="welcome-container">
        <meta name="bookish-authenticated" content="{{ auth()->check() ? '1' : '0' }}">
        <meta name="book-release-reminder-url" content="{{ route('book-release-reminders.store') }}">
        <meta name="book-login-url" content="{{ route('login') }}">

        {{-- ================= HERO ================= --}}
        <section class="home-hero">
            <div class="home-hero-copy">
                <p class="home-eyebrow"><span class="home-eyebrow-dot"></span> Bookish jaunumi</p>
                <h1 class="home-hero-title">Mazliet vairāk <em>brīnuma</em> katrā lappusē.</h1>
                <p class="home-hero-text">Atklāj gaidāmās grāmatas, pārlūko žanrus un seko savam lasīšanas ceļam vienuviet.</p>

                <div class="home-search" role="search">
                    <span class="home-search-icon" aria-hidden="true">⌕</span>
                    <input
                        id="upcoming-book-search-input"
                        type="text"
                        class="home-search-input"
                        placeholder="Meklē autoru vai grāmatu…"
                        aria-label="Autors vai grāmatas nosaukums"
                    >
                    <button id="upcoming-book-search" type="button" class="home-search-button">Meklēt</button>
                </div>

                <div class="home-hero-chips" aria-label="Ātrie meklējumi">
                    @foreach (['Rebecca Yarros', 'Sarah J. Maas', 'Stephen King', 'Colleen Hoover'] as $suggestion)
                        <button type="button" class="home-chip" data-search-suggestion="{{ $suggestion }}">{{ $suggestion }}</button>
                    @endforeach
                </div>
            </div>

            <div class="home-hero-visual">
                @if ($mainBook)
                    @php $mainPct = (int) min(100, round($mainBook->pages_read / $mainBook->total_pages * 100)); @endphp
                    <article class="home-continue">
                        <p class="home-continue-label">Turpini lasīt</p>
                        <div class="home-continue-cover">
                            @if ($mainBook->book_cover_url)
                                <img src="{{ $mainBook->book_cover_url }}" alt="{{ $mainBook->book_title }} vāks">
                            @else
                                <span>📖</span>
                            @endif
                        </div>
                        <h2 class="home-continue-title">{{ $mainBook->book_title }}</h2>
                        <div class="home-continue-progress">
                            <span class="home-continue-track"><span style="width: {{ $mainPct }}%"></span></span>
                            <b>{{ $mainPct }}%</b>
                        </div>
                        <p class="home-continue-meta">{{ $mainBook->pages_read }} / {{ $mainBook->total_pages }} lpp · atlikušas {{ $mainBook->total_pages - $mainBook->pages_read }}</p>
                        <a href="{{ route('reading-progress.index', ['entry_id' => $mainBook->id, 'session' => 'new']) }}" class="home-continue-button">Turpināt lasīt →</a>

                        @if ($continueReading->count() > 1)
                            <div class="home-continue-more">
                                @foreach ($continueReading->slice(1) as $book)
                                    <a href="{{ route('reading-progress.index', ['entry_id' => $book->id, 'session' => 'new']) }}" title="{{ $book->book_title }}">
                                        @if ($book->book_cover_url)
                                            <img src="{{ $book->book_cover_url }}" alt="{{ $book->book_title }} vāks">
                                        @else
                                            <span>📖</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @else
                    <div class="home-stack" aria-hidden="true">
                        <div class="home-stack-sun"></div>
                        <div class="home-stack-book home-stack-book--1"><span>Chapter<br>One</span></div>
                        <div class="home-stack-book home-stack-book--2"><span>The Quiet<br>Sea</span></div>
                        <div class="home-stack-book home-stack-book--3"><span>Bookish</span></div>
                    </div>
                    @guest
                        <a href="{{ route('register') }}" class="home-hero-cta">Sākt savu plauktu →</a>
                    @else
                        <a href="{{ route('reading-shelf.show') }}" class="home-hero-cta">Atvērt plauktu →</a>
                    @endguest
                @endif
            </div>
        </section>

        {{-- ================= POPULĀRI ŠONEDĒĻ (NYT) ================= --}}
        @php $bestsellers = $bestsellers ?? ['published' => null, 'lists' => []]; @endphp
        @if (!empty($bestsellers['lists']))
            <section class="home-panel home-trending" data-trending>
                <header class="home-section-head">
                    <div>
                        <p class="home-eyebrow"><span class="home-eyebrow-dot"></span> NYT bestselleri</p>
                        <h2 class="home-section-title">Populāri šonedēļ</h2>
                    </div>
                    <div class="home-trending-tabs" role="tablist" aria-label="Bestselleru saraksti">
                        @foreach ($bestsellers['lists'] as $index => $list)
                            <button type="button" role="tab" class="home-trending-tab {{ $index === 0 ? 'is-active' : '' }}"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                    data-trending-tab="{{ $list['key'] }}">{{ $list['name'] }}</button>
                        @endforeach
                    </div>
                </header>

                @foreach ($bestsellers['lists'] as $index => $list)
                    <ol class="home-trending-list" role="tabpanel" data-trending-panel="{{ $list['key'] }}" @if ($index !== 0) hidden @endif>
                        @foreach ($list['books'] as $book)
                            <li class="home-trending-item {{ $book['rank'] <= 3 ? 'is-top' : '' }}">
                                <span class="home-trending-rank">{{ $book['rank'] }}</span>
                                <a class="home-trending-cover" @if ($book['link']) href="{{ $book['link'] }}" target="_blank" rel="noopener noreferrer" @endif>
                                    @if ($book['image'])
                                        <img src="{{ $book['image'] }}" alt="{{ $book['title'] }} vāks" loading="lazy">
                                    @else
                                        <span>📖</span>
                                    @endif
                                </a>
                                <div class="home-trending-body">
                                    <h3>{{ $book['title'] }}</h3>
                                    <p class="home-trending-author">{{ $book['author'] }}</p>
                                    <div class="home-trending-meta">
                                        @switch($book['trend'])
                                            @case('new') <span class="home-trend home-trend--new">Jauns</span> @break
                                            @case('up') <span class="home-trend home-trend--up" title="Pakāpās">▲</span> @break
                                            @case('down') <span class="home-trend home-trend--down" title="Noslīdēja">▼</span> @break
                                            @default <span class="home-trend" title="Bez izmaiņām">●</span>
                                        @endswitch
                                        @if ($book['weeks'] > 1)
                                            <span>{{ $book['weeks'] }} ned. sarakstā</span>
                                        @endif
                                    </div>
                                    @auth
                                        <form method="POST" action="{{ route('reading-progress.want-to-read.store') }}" class="home-trending-form">
                                            @csrf
                                            <input type="hidden" name="book_title" value="{{ $book['title'] }}">
                                            @if ($book['image'])
                                                <input type="hidden" name="book_cover_url" value="{{ $book['image'] }}">
                                            @endif
                                            <button type="submit" class="home-trending-add">+ Gribu izlasīt</button>
                                        </form>
                                    @endauth
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endforeach

                @if ($bestsellers['published'])
                    <p class="home-trending-source">Avots: The New York Times Best Sellers · {{ \Illuminate\Support\Carbon::parse($bestsellers['published'])->format('d.m.Y') }}</p>
                @endif
            </section>
        @endif

        {{-- ================= GAIDĀMIE IZDEVUMI ================= --}}
        <section class="home-panel">
            <header class="home-section-head">
                <div>
                    <p class="home-eyebrow">Kas jauns / kas plānojas</p>
                    <h2 class="home-section-title">Gaidāmie izdevumi</h2>
                </div>
                <span class="home-note">Google Books dati</span>
            </header>
            <div id="upcoming-book-search-results" class="weekly-top-books home-upcoming">
                <div class="home-empty">
                    <span aria-hidden="true">🔎</span>
                    <p class="welcome-card-text">Ievadi autora vārdu vai grāmatas nosaukumu augšā, un te parādīsies gaidāmās grāmatas.</p>
                </div>
            </div>
        </section>

        {{-- ================= ŽANRI ================= --}}
        <section class="home-panel welcome-genres-section">
            <header class="home-section-head">
                <div>
                    <p class="home-eyebrow">Žanru grāmatu filtrs</p>
                    <h2 class="home-section-title">Pārlūko pēc žanra</h2>
                </div>
                <div class="home-year-filter">
                    <label for="recommendation-year-from">No</label>
                    <select id="recommendation-year-from" class="home-select"></select>
                    <label for="recommendation-year-to">līdz</label>
                    <select id="recommendation-year-to" class="home-select"></select>
                </div>
            </header>

            <div class="home-genre-tabs book-genre-filter" role="group" aria-label="Izvēlēties grāmatu žanru">
                <button type="button" class="book-genre-filter-button is-active" data-genre-filter="all">Visi žanri</button>
                @foreach ($genres as $genre)
                    <button type="button" class="book-genre-filter-button" data-genre-filter="{{ $genre['key'] }}">{{ $genre['lv'] }}</button>
                @endforeach
            </div>

            <div class="book-genre-sections">
                @foreach ($genres as $genre)
                    <section class="book-genre-section home-genre" data-genre="{{ $genre['key'] }}" data-query="{{ $genre['query'] }}">
                        <div class="book-genre-heading home-genre-head">
                            <div>
                                <h3>{{ $genre['lv'] }} <span>({{ $genre['en'] }})</span></h3>
                                <p>{{ $genre['text'] }}</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books home-books">
                            <p class="welcome-card-text">{{ $genre['loading'] }}</p>
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    </div>
</x-layout>
