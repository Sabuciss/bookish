<x-layout>
    <div class="reading-progress-page">
        <h1>BookTok tops (Goodreads)</h1>
        <p>Atsevišķa sadaļa ar BookTok top grāmatām no sagatavotā seedera.</p>

        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <section class="uiverse-container" style="margin-bottom: 16px;">
            <form method="GET" action="{{ route('booktok.index') }}">
                <p class="welcome-card-text">Filtrē BookTok topu pēc gada, žanra, autora vai grāmatas nosaukuma.</p>
                <div class="weekly-top-controls">
                    <input
                        type="search"
                        name="author"
                        value="{{ $selectedAuthor }}"
                        class="weekly-genre-select"
                        placeholder="Autors"
                    >
                    <input
                        type="search"
                        name="title"
                        value="{{ $selectedTitle }}"
                        class="weekly-genre-select"
                        placeholder="Grāmatas nosaukums"
                    >
                    <select id="published_year" name="published_year" class="weekly-genre-select weekly-year-input">
                        <option value="">Visi gadi</option>
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>

                    <select id="genre" name="genre" class="weekly-genre-select">
                        <option value="">Visi žanri</option>
                        @foreach ($availableGenres as $genre)
                            <option value="{{ $genre }}" @selected($selectedGenre === $genre)>{{ $genre }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="reading-progress-submit">Pielietot filtru</button>

                    @if ($selectedYear || $selectedGenre || $selectedAuthor || $selectedTitle)
                        <a href="{{ route('booktok.index') }}" class="reading-progress-submit">Notīrīt filtru</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="booktok-authors uiverse-container">
            <div class="booktok-section-heading">
                <div>
                    <p class="book-detail-eyebrow">BookTok kopiena</p>
                    <h2 class="booktok-section-title">Top autori</h2>
                </div>
                <span class="booktok-section-count">{{ $booktokAuthors->count() }} autori</span>
            </div>

            <div class="booktok-author-grid">
                @foreach ($booktokAuthors as $author)
                    <div class="booktok-author-card">
                        <span class="booktok-author-avatar">{{ mb_strtoupper(mb_substr($author['name'], 0, 1)) }}</span>
                        <div>
                            <strong>{{ $author['name'] }}</strong>
                            <span>{{ $author['book_count'] }} {{ $author['book_count'] === 1 ? 'grāmata' : 'grāmatas' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="reading-progress-history uiverse-container">
            @if ($books->isEmpty())
                @if ($selectedYear || $selectedGenre || $selectedAuthor || $selectedTitle)
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
                                            <span style="background: #dcfce7; color: #166534; border-radius: 999px; padding: 4px 10px; font-size: 0.8rem; font-weight: 700;">READ</span>
                                        @elseif ($bookStatus === 'in_progress')
                                            <span style="background: #dbeafe; color: #1e3a8a; border-radius: 999px; padding: 4px 10px; font-size: 0.8rem; font-weight: 700;">In Progress</span>
                                        @elseif ($bookStatus === 'want_to_read')
                                            <span style="background: #fef3c7; color: #92400e; border-radius: 999px; padding: 4px 10px; font-size: 0.8rem; font-weight: 700;">Added to Want to Read</span>
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
    </div>
</x-layout>
