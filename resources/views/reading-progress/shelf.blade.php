<x-layout>
    @php
        $s = $shelfStats;
        $fmt = fn (int $n) => number_format($n, 0, ',', ' ');
        $hours = intdiv($s['totalMinutes'], 60);
        $mins = $s['totalMinutes'] % 60;
        $shelfTotal = max(1, $s['wantToRead'] + $s['inProgress'] + $s['read']);
        $readDeg = round($s['read'] / $shelfTotal * 360, 1);
        $progressDeg = round(($s['read'] + $s['inProgress']) / $shelfTotal * 360, 1);
        $bookWord = fn (int $n) => $n === 1 ? 'grāmata' : 'grāmatas';

        $emotionToEmoji = [
            'Priecīgs' => '😊', 'Mierīgs' => '😌', 'Iedvesmots' => '🤩', 'Skumīgs' => '😢',
            'Satraukts' => '😱', 'Noguris' => '😴', 'Pārsteigts' => '😮', 'Sarūgtināts' => '😠',
            'Domājošs' => '🤔', 'Aizkustināts' => '🥺', 'Saspringts' => '😤', 'Smieklīgs' => '😂',
            'Apjucis' => '🤯', 'Skeptisks' => '🙄', 'Motivēts' => '💪', 'Mīlošs' => '🥰',
        ];
        $statusColumns = [
            'want_to_read' => ['label' => 'Gribu izlasīt', 'icon' => '★', 'empty' => 'Pievieno grāmatas no BookTok vai Jaunumiem.'],
            'in_progress' => ['label' => 'Lasu', 'icon' => '📖', 'empty' => 'Sāc lasīt kādu grāmatu no saraksta.'],
            'read' => ['label' => 'Izlasītas', 'icon' => '✓', 'empty' => 'Šeit parādīsies izlasītās grāmatas.'],
        ];
    @endphp

    <div class="sh-page">
        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">{{ session('status') }}</div>
        @endif

        <section class="pd" aria-label="Plaukta statistika">
            {{-- Hero --}}
            <article class="pd-card pd-hero">
                <div class="pd-hero-avatar" aria-hidden="true">📚</div>
                <div class="pd-hero-body">
                    <p class="pd-eyebrow">Tavs grāmatu plaukts</p>
                    <h1 class="pd-hero-name">Grāmatu plaukts</h1>
                    <div class="pd-hero-tags">
                        <span class="pd-pill pd-pill--accent">📚 {{ $fmt($s['onShelf']) }} {{ $bookWord($s['onShelf']) }} plauktā</span>
                        <span class="pd-pill">🏆 {{ $s['readThisYear'] }} izlasītas {{ now()->year }}. gadā</span>
                    </div>
                </div>
                <a href="{{ route('reading-progress.index') }}" class="pd-hero-link">+ Pievienot progresu</a>
            </article>

            {{-- KPI --}}
            <div class="pd-kpis">
                <article class="pd-card pd-kpi">
                    <span class="pd-kpi-icon">✅</span>
                    <strong>{{ $fmt($s['read']) }}</strong>
                    <span>Izlasītas grāmatas</span>
                </article>
                <article class="pd-card pd-kpi">
                    <span class="pd-kpi-icon">📖</span>
                    <strong>{{ $fmt($s['inProgress']) }}</strong>
                    <span>Pašlaik lasu</span>
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
            </div>

            {{-- Sadalījums --}}
            <article class="pd-card pd-shelf sh-span-4">
                <header class="pd-card-head">
                    <div>
                        <p class="pd-eyebrow">Sadalījums</p>
                        <h3>Plaukta statusi</h3>
                    </div>
                </header>
                <div class="pd-donut" style="--read: {{ $readDeg }}deg; --progress: {{ $progressDeg }}deg;" role="img"
                     aria-label="Izlasītas {{ $s['read'] }}, lasu {{ $s['inProgress'] }}, gribu izlasīt {{ $s['wantToRead'] }}">
                    <div class="pd-donut-center">
                        <strong>{{ $fmt($s['onShelf']) }}</strong>
                        <span>{{ $bookWord($s['onShelf']) }}</span>
                    </div>
                </div>
                <ul class="pd-legend">
                    <li><i class="pd-dot pd-dot--read"></i>Izlasītas <b>{{ $s['read'] }}</b></li>
                    <li><i class="pd-dot pd-dot--progress"></i>Lasu <b>{{ $s['inProgress'] }}</b></li>
                    <li><i class="pd-dot pd-dot--want"></i>Gribu izlasīt <b>{{ $s['wantToRead'] }}</b></li>
                </ul>
            </article>

            {{-- Progress --}}
            <article class="pd-card pd-ring-card sh-span-4">
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
                    <div><strong>{{ $fmt($s['pagesLeft']) }}</strong><span>lpp atlikušas</span></div>
                    <div><strong>{{ $s['pagesPerHour'] ?? '—' }}</strong><span>lpp stundā</span></div>
                </div>
            </article>

            {{-- Rekordi --}}
            <article class="pd-card sh-records sh-span-4">
                <header class="pd-card-head">
                    <div>
                        <p class="pd-eyebrow">Sasniegumi</p>
                        <h3>Tavi rekordi</h3>
                    </div>
                </header>
                <ul class="sh-record-list">
                    <li>
                        <span class="sh-record-icon">🏆</span>
                        <span class="sh-record-body"><small>Izlasītas šogad</small><strong>{{ $s['readThisYear'] }} {{ $bookWord($s['readThisYear']) }}</strong></span>
                    </li>
                    <li>
                        <span class="sh-record-icon">📏</span>
                        <span class="sh-record-body">
                            <small>Garākā izlasītā</small>
                            @if ($s['longestRead'])
                                <strong title="{{ $s['longestRead']->book_title }}">{{ $s['longestRead']->book_title }}</strong>
                                <em>{{ $fmt((int) $s['longestRead']->total_pages) }} lpp</em>
                            @else
                                <strong>—</strong>
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="sh-record-icon">📊</span>
                        <span class="sh-record-body"><small>Vidējais grāmatas garums</small><strong>{{ $s['averageBookLength'] ? $fmt($s['averageBookLength']) . ' lpp' : '—' }}</strong></span>
                    </li>
                </ul>
            </article>
        </section>

        {{-- ================= PLAUKTI (kanban) ================= --}}
        <section class="sh-board" aria-label="Grāmatas pa statusiem">
            @foreach ($statusColumns as $statusKey => $column)
                @php $columnBooks = $bookSnapshotsByStatus[$statusKey] ?? []; @endphp
                <div class="sh-column sh-column--{{ $statusKey }}">
                    <header class="sh-column-head">
                        <span class="sh-column-icon" aria-hidden="true">{{ $column['icon'] }}</span>
                        <h2>{{ $column['label'] }}</h2>
                        <span class="sh-count">{{ count($columnBooks) }}</span>
                    </header>

                    @forelse ($columnBooks as $snapshot)
                        @php
                            $totalPages = $snapshot['total_pages'];
                            $pagesRead = $snapshot['pages_read'];
                            $percent = $totalPages ? min(100, (int) round(($pagesRead / $totalPages) * 100)) : null;
                            $emotionNames = collect(explode(',', (string) ($snapshot['emotion'] ?? '')))
                                ->map(fn (string $emotion) => trim($emotion))
                                ->filter(fn (string $emotion) => isset($emotionToEmoji[$emotion]))
                                ->unique()
                                ->values();
                        @endphp

                        <article class="sh-book">
                            <div class="sh-book-main">
                                <span class="sh-book-cover">
                                    @if (!empty($snapshot['book_cover_url']))
                                        <img src="{{ $snapshot['book_cover_url'] }}" alt="{{ $snapshot['book_title'] }} vāks" loading="lazy">
                                    @else
                                        📖
                                    @endif
                                </span>
                                <div class="sh-book-body">
                                    <h3 title="{{ $snapshot['book_title'] }}">{{ $snapshot['book_title'] }}</h3>
                                    <p>{{ $pagesRead }}{{ $totalPages ? ' / ' . $totalPages : '' }} lpp · {{ $snapshot['reading_date']->format('d.m.Y') }}</p>
                                    @if ($emotionNames->isNotEmpty())
                                        <div class="sh-emotions" aria-label="Grāmatas emocijas">
                                            @foreach ($emotionNames as $emotionName)
                                                <span title="{{ $emotionName }}">{{ $emotionToEmoji[$emotionName] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @if ($statusKey === 'read')
                                    <span class="sh-done" title="Izlasīta">✓</span>
                                @endif
                            </div>

                            @if ($percent !== null && $statusKey !== 'want_to_read')
                                <div class="sh-progress">
                                    <span class="sh-progress-track"><span style="width: {{ $percent }}%"></span></span>
                                    <b>{{ $percent }}%</b>
                                </div>
                            @endif

                            <div class="sh-actions">
                                @if ($statusKey !== 'read')
                                    <a href="{{ route('reading-progress.index', ['entry_id' => $snapshot['entry_id'], 'session' => 'new']) }}" class="sh-action sh-action--primary">
                                        {{ $statusKey === 'want_to_read' ? '📖 Sākt lasīt' : '🔄 Atjaunot' }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('reading-progress.destroy', $snapshot['entry_id']) }}" data-confirm="Noņemt „{{ $snapshot['book_title'] }}” no plaukta? Šo nevar atsaukt.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sh-action sh-action--ghost" aria-label="Noņemt {{ $snapshot['book_title'] }} no plaukta" title="Noņemt no plaukta">✕</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="sh-empty">{{ $column['empty'] }}</p>
                    @endforelse
                </div>
            @endforeach
        </section>

        <div class="reading-progress-pagination">{{ $bookSnapshotsPaginator->links() }}</div>
    </div>
</x-layout>
