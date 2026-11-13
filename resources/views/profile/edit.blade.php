<x-layout>
    <div class="profile-page">
        <header class="profile-page-heading">
            <p class="welcome-eyebrow">Bookish lasītāja profils</p>
            <h1>Tavs profils</h1>
            <p>Pārvaldi kontu un apskati savu lasīšanas ceļu vienuviet.</p>
        </header>

            @php
                $s = $profileStats;
                $fmt = fn (int $n) => number_format($n, 0, ',', ' ');
                $hours = intdiv($s['totalMinutes'], 60);
                $mins = $s['totalMinutes'] % 60;
                $shelfTotal = max(1, $s['booksWantToRead'] + $s['booksInProgress'] + $s['booksRead']);
                $readDeg = round($s['booksRead'] / $shelfTotal * 360, 1);
                $progressDeg = round(($s['booksRead'] + $s['booksInProgress']) / $shelfTotal * 360, 1);
                $emotionEmoji = [
                    'Priecīgs' => '😊', 'Mierīgs' => '😌', 'Iedvesmots' => '🤩', 'Skumīgs' => '😢',
                    'Satraukts' => '😱', 'Noguris' => '😴', 'Pārsteigts' => '😮', 'Sarūgtināts' => '😠',
                    'Domājošs' => '🤔', 'Aizkustināts' => '🥺', 'Saspringts' => '😤', 'Smieklīgs' => '😂',
                    'Apjucis' => '🤯', 'Skeptisks' => '🙄', 'Motivēts' => '💪', 'Mīlošs' => '🥰',
                ];
                $emotionMax = max(1, (int) (collect($s['topEmotions'])->max() ?? 1));
                $initial = mb_strtoupper(mb_substr($user->name, 0, 1));
                $lvMonths = ['janvāra', 'februāra', 'marta', 'aprīļa', 'maija', 'jūnija', 'jūlija', 'augusta', 'septembra', 'oktobra', 'novembra', 'decembra'];
                $memberSince = $user->created_at ? $lvMonths[$user->created_at->month - 1] . ' ' . $user->created_at->year : '—';
            @endphp

            <section class="pd" aria-label="Lasīšanas statistika">
                {{-- Hero --}}
                <article class="pd-card pd-hero">
                    <div class="pd-hero-avatar" aria-hidden="true">{{ $initial }}</div>
                    <div class="pd-hero-body">
                        <p class="pd-eyebrow">Bookish lasītājs kopš {{ $memberSince }}</p>
                        <h2 class="pd-hero-name">{{ $user->name }}</h2>
                        <div class="pd-hero-tags">
                            <span class="pd-pill pd-pill--accent">📚 {{ $fmt($s['booksOnShelf']) }} grāmatas plauktā</span>
                            @if ($s['streak'] > 0)
                                <span class="pd-pill">🔥 {{ $s['streak'] }} {{ $s['streak'] === 1 ? 'diena' : 'dienas' }} pēc kārtas</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('reading-shelf.show') }}" class="pd-hero-link">Atvērt plauktu →</a>
                </article>

                {{-- KPI --}}
                <div class="pd-kpis">
                    <article class="pd-card pd-kpi">
                        <span class="pd-kpi-icon">📖</span>
                        <strong>{{ $fmt($s['booksRead']) }}</strong>
                        <span>Izlasītas grāmatas</span>
                    </article>
                    <article class="pd-card pd-kpi">
                        <span class="pd-kpi-icon">📄</span>
                        <strong>{{ $fmt($s['pagesRead']) }}</strong>
                        <span>Izlasītas lappuses</span>
                    </article>
                    <article class="pd-card pd-kpi">
                        <span class="pd-kpi-icon">⏱️</span>
                        <strong>{{ $hours }}<small>h</small> {{ $mins }}<small>min</small></strong>
                        <span>Kopā lasīts</span>
                    </article>
                    <article class="pd-card pd-kpi">
                        <span class="pd-kpi-icon">💬</span>
                        <strong>{{ $fmt($s['highlights']) }}</strong>
                        <span>Saglabāti citāti</span>
                    </article>
                </div>

                {{-- Nedēļa --}}
                <article class="pd-card pd-week">
                    <header class="pd-card-head">
                        <div>
                            <p class="pd-eyebrow">Pēdējās 7 dienas</p>
                            <h3>{{ $s['weekMinutes'] }} min lasīšanas</h3>
                        </div>
                        <span class="pd-pill">🔥 {{ $s['streak'] }}</span>
                    </header>
                    <div class="pd-bars" role="img" aria-label="Lasīšanas minūtes pa dienām">
                        @foreach ($s['week'] as $day)
                            <div class="pd-bar {{ $day['isToday'] ? 'is-today' : '' }}" title="{{ $day['minutes'] }} min">
                                <span class="pd-bar-value">{{ $day['minutes'] ?: '' }}</span>
                                <span class="pd-bar-track">
                                    <span class="pd-bar-fill" style="--h: {{ $day['minutes'] > 0 ? max(8, round($day['minutes'] / $s['weekMax'] * 100)) : 0 }}%"></span>
                                </span>
                                <span class="pd-bar-label">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="pd-streak" aria-label="Lasīšanas dienas">
                        @foreach ($s['week'] as $day)
                            <span class="pd-streak-dot {{ $day['minutes'] > 0 ? 'is-on' : '' }}" title="{{ $day['label'] }}">{{ $day['minutes'] > 0 ? '✓' : '' }}</span>
                        @endforeach
                    </div>
                </article>

                {{-- Plaukts (donut) --}}
                <article class="pd-card pd-shelf">
                    <header class="pd-card-head">
                        <div>
                            <p class="pd-eyebrow">Plaukts</p>
                            <h3>Grāmatu sadalījums</h3>
                        </div>
                    </header>
                    <div class="pd-donut" style="--read: {{ $readDeg }}deg; --progress: {{ $progressDeg }}deg;" role="img"
                         aria-label="Izlasītas {{ $s['booksRead'] }}, lasu {{ $s['booksInProgress'] }}, gribu izlasīt {{ $s['booksWantToRead'] }}">
                        <div class="pd-donut-center">
                            <strong>{{ $fmt($s['booksOnShelf']) }}</strong>
                            <span>grāmatas</span>
                        </div>
                    </div>
                    <ul class="pd-legend">
                        <li><i class="pd-dot pd-dot--read"></i>Izlasītas <b>{{ $s['booksRead'] }}</b></li>
                        <li><i class="pd-dot pd-dot--progress"></i>Lasu <b>{{ $s['booksInProgress'] }}</b></li>
                        <li><i class="pd-dot pd-dot--want"></i>Gribu izlasīt <b>{{ $s['booksWantToRead'] }}</b></li>
                    </ul>
                </article>

                {{-- Progresa gredzens + izaicinājumi --}}
                <article class="pd-card pd-ring-card">
                    <header class="pd-card-head">
                        <div>
                            <p class="pd-eyebrow">Vidējais progress</p>
                            <h3>Pašlaik lasāmās</h3>
                        </div>
                    </header>
                    <div class="pd-ring" style="--p: {{ $s['averageProgress'] }};" role="img" aria-label="Vidējais progress {{ $s['averageProgress'] }}%">
                        <strong>{{ $s['averageProgress'] }}<small>%</small></strong>
                    </div>
                    <div class="pd-mini-stats">
                        <div><strong>{{ $s['challengesCompleted'] }}/{{ $s['challengesTotal'] }}</strong><span>Izaicinājumi</span></div>
                        <div><strong>{{ $s['favoriteAuthors'] }}</strong><span>Autori</span></div>
                    </div>
                </article>

                {{-- Emocijas --}}
                <article class="pd-card pd-emotions">
                    <header class="pd-card-head">
                        <div>
                            <p class="pd-eyebrow">Noskaņojums</p>
                            <h3>Biežākās emocijas</h3>
                        </div>
                    </header>
                    @forelse ($s['topEmotions'] as $emotion => $count)
                        <div class="pd-emotion-row">
                            <span class="pd-emotion-emoji">{{ $emotionEmoji[$emotion] ?? '📘' }}</span>
                            <span class="pd-emotion-name">{{ $emotion }}</span>
                            <span class="pd-emotion-track"><span style="width: {{ round($count / $emotionMax * 100) }}%"></span></span>
                            <b>{{ $count }}</b>
                        </div>
                    @empty
                        <p class="pd-empty">Pievieno emocijas progresa ierakstiem, un tās parādīsies šeit.</p>
                    @endforelse
                </article>

                {{-- Pašlaik lasu --}}
                <article class="pd-card pd-reading">
                    <header class="pd-card-head">
                        <div>
                            <p class="pd-eyebrow">Turpini lasīt</p>
                            <h3>Pašlaik lasu</h3>
                        </div>
                        <a href="{{ route('reading-progress.index') }}" class="pd-head-link">+ Progress</a>
                    </header>
                    @forelse ($s['currentlyReading'] as $book)
                        @php $pct = (int) min(100, round($book->pages_read / $book->total_pages * 100)); @endphp
                        <a href="{{ route('reading-progress.index', ['entry_id' => $book->id, 'session' => 'new']) }}" class="pd-book">
                            @if ($book->book_cover_url)
                                <img src="{{ $book->book_cover_url }}" alt="" class="pd-book-cover" loading="lazy">
                            @else
                                <span class="pd-book-cover pd-book-cover--empty">📖</span>
                            @endif
                            <span class="pd-book-body">
                                <strong>{{ $book->book_title }}</strong>
                                <small>{{ $book->pages_read }} / {{ $book->total_pages }} lpp</small>
                                <span class="pd-book-track"><span style="width: {{ $pct }}%"></span></span>
                            </span>
                            <span class="pd-book-pct">{{ $pct }}%</span>
                        </a>
                    @empty
                        <p class="pd-empty">Šobrīd nelasi nevienu grāmatu. <a href="{{ route('reading-shelf.show') }}">Izvēlies no plaukta →</a></p>
                    @endforelse
                </article>
            </section>

            <div class="profile-settings-grid">
                <section class="profile-card uiverse-container">
                    @include('profile.partials.update-profile-information-form')
                </section>

                <section class="profile-card uiverse-container">
                    @include('profile.partials.update-password-form')
                </section>

                <section class="profile-card profile-card-danger uiverse-container">
                    @include('profile.partials.delete-user-form')
                </section>
            </div>
        </div>
</x-layout>
