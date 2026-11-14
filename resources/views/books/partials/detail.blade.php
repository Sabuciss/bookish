{{--
    Kopīgs grāmatas skats (Google Books un BookTok).
    $detail = [
        backUrl, backLabel, eyebrow, title, author, cover, categories (teksts), year, pages,
        publisher, language, rating, ratingsCount, description, infoLink,
        volumeId, status,
    ]
    $recommendations = BookRecommendationService::recommend() rezultāts
--}}
@php
    // "Fiction / Fantasy / Epic, Romance" → ["Fiction", "Fantasy", "Epic"] (maks. 3 birkas)
    $categories = collect(preg_split('/[,\/]/u', (string) ($detail['categories'] ?? '')))
        ->map(fn ($category) => trim($category))
        ->filter(fn ($category) => $category !== '' && !str_starts_with($category, 'Nav norādīt') && mb_strtolower($category) !== 'general')
        ->unique(fn ($category) => mb_strtolower($category))
        ->take(3);
    $description = trim((string) ($detail['description'] ?? ''));
    $isLongDescription = mb_strlen($description) > 320;
    $statusLabels = [
        'read' => ['✓ Izlasīta', 'read'],
        'in_progress' => ['📖 Lasu', 'progress'],
        'want_to_read' => ['🔖 Gribu izlasīt', 'want'],
    ];
    $facts = array_filter([
        'Autors' => $detail['author'] ?? null,
        'Izdota' => $detail['year'] ?? null,
        'Izdevējs' => $detail['publisher'] ?? null,
        'Lappuses' => $detail['pages'] ?? null,
        'Valoda' => $detail['language'] ?? null,
    ], fn ($value) => filled($value) && !str_starts_with((string) $value, 'Nav norādīt'));
@endphp

<div class="bd-page">
    @if (session('status'))
        <div class="reading-progress-alert reading-progress-alert-success">{{ session('status') }}</div>
    @endif

    <a href="{{ $detail['backUrl'] }}" class="bd-back">← {{ $detail['backLabel'] }}</a>

    {{-- Hero --}}
    <section class="bd-hero">
        <div class="bd-hero-text">
            @if (!empty($detail['eyebrow']))
                <p class="bd-eyebrow">{{ $detail['eyebrow'] }}</p>
            @endif

            <h1 class="bd-title">{{ $detail['title'] }}</h1>

            @if (!empty($detail['author']))
                <p class="bd-author">{{ $detail['author'] }}</p>
            @endif

            @if ($categories->isNotEmpty() || !empty($detail['year']))
                <ul class="bd-chips" aria-label="Žanri">
                    @foreach ($categories as $category)
                        <li>{{ $category }}</li>
                    @endforeach
                    @if (!empty($detail['year']))
                        <li>{{ $detail['year'] }}</li>
                    @endif
                </ul>
            @endif

            @if ($description !== '')
                <p class="bd-teaser">{{ $description }}</p>
                @if ($isLongDescription)
                    <a href="#apraksts" class="bd-more">Lasīt visu aprakstu ↓</a>
                @endif
            @endif

            <div class="bd-stats">
                <div class="bd-stat">
                    <small>Vērtējums</small>
                    @if (!empty($detail['rating']))
                        <strong>★ {{ $detail['rating'] }}<span>/5</span></strong>
                        @if (!empty($detail['ratingsCount']))
                            <em>{{ $detail['ratingsCount'] }} vērtējumi</em>
                        @endif
                    @else
                        <strong>—</strong>
                        <em>nav pieejams</em>
                    @endif
                </div>
                <div class="bd-stat">
                    <small>Garums</small>
                    <strong>{{ $detail['pages'] ?: '—' }}<span> lpp.</span></strong>
                    @if (!empty($detail['pages']))
                        <em>~{{ max(1, (int) round($detail['pages'] / 40)) }} h lasīšanas</em>
                    @endif
                </div>
            </div>

            <div class="bd-actions">
                @auth
                    @if (isset($statusLabels[$detail['status'] ?? '']))
                        @php [$label, $modifier] = $statusLabels[$detail['status']]; @endphp
                        <span class="bd-status bd-status--{{ $modifier }}">{{ $label }}</span>
                    @else
                        <form action="{{ route('reading-progress.want-to-read.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="book_title" value="{{ $detail['title'] }}">
                            <input type="hidden" name="google_volume_id" value="{{ $detail['volumeId'] ?? '' }}">
                            <input type="hidden" name="book_cover_url" value="{{ $detail['cover'] ?? '' }}">
                            <input type="hidden" name="total_pages" value="{{ $detail['pages'] ?? '' }}">
                            <button type="submit" class="bd-btn bd-btn--primary">🔖 Gribu izlasīt</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="bd-btn bd-btn--primary">Ielogoties, lai pievienotu</a>
                @endauth
            </div>
        </div>

        <div class="bd-hero-cover">
            <span class="bd-blob" aria-hidden="true"></span>
            @if (!empty($detail['cover']))
                <img src="{{ $detail['cover'] }}" alt="{{ $detail['title'] }} vāks" class="bd-cover">
            @else
                <div class="bd-cover bd-cover--empty">
                    <span>📖</span>
                    <strong>{{ $detail['title'] }}</strong>
                </div>
            @endif
        </div>
    </section>

    {{-- Par grāmatu --}}
    <section class="bd-about" id="apraksts">
        <div class="bd-card bd-description">
            <p class="bd-eyebrow">Par grāmatu</p>
            <h2 class="bd-section-title">Apraksts</h2>
            <p class="bd-description-text">{{ $description !== '' ? $description : 'Šai grāmatai apraksts nav pieejams.' }}</p>

            @if (!empty($detail['infoLink']))
                <a class="bd-link" href="{{ $detail['infoLink'] }}" target="_blank" rel="noopener noreferrer">Uzzināt vairāk Google Books ↗</a>
            @endif
        </div>

        @if ($facts)
            <aside class="bd-card bd-facts">
                <p class="bd-eyebrow">Informācija</p>
                <dl>
                    @foreach ($facts as $label => $value)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </aside>
        @endif
    </section>

    {{-- Ieteikumi --}}
    @if (!empty($recommendations))
        <section class="bd-card bd-recs">
            <div class="bd-recs-head">
                <div>
                    <p class="bd-eyebrow">Ieteikumi</p>
                    <h2 class="bd-section-title">Tev varētu patikt</h2>
                </div>
                <a href="{{ route('booktok.index') }}" class="bd-link">BookTok tops →</a>
            </div>

            <ul class="bd-recs-grid">
                @foreach ($recommendations as $rec)
                    <li>
                        <a href="{{ $rec['url'] }}" class="bd-rec" rel="nofollow">
                            <span class="bd-rec-cover">
                                @if (!empty($rec['cover']))
                                    <img src="{{ $rec['cover'] }}" alt="" loading="lazy">
                                @else
                                    <span class="bd-rec-cover-empty">{{ $rec['title'] }}</span>
                                @endif
                                @if ($rec['booktok'])
                                    <span class="bd-rec-badge">BookTok</span>
                                @endif
                            </span>
                            <strong class="bd-rec-title">{{ $rec['title'] }}</strong>
                            @if (!empty($rec['author']))
                                <span class="bd-rec-author">{{ $rec['author'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
