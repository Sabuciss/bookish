<x-layout>
    <div class="reading-progress-page">
        <h1>Grāmatu plaukts</h1>
        <p>Atsevišķs skats grāmatām pa statusiem. In Progress grāmatām vari spiest Atjaunot un nonākt rediģēšanas formā.</p>

        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <div class="rp-actions-row">
            <a href="{{ route('reading-progress.index') }}" class="reading-progress-submit" style="text-decoration: none; display: inline-block;">Pievienot reading progress</a>
        </div>

        <section class="reading-progress-overview uiverse-container" style="margin-bottom: 1rem;">
            <p class="welcome-eyebrow">Tavs lasīšanas kopsavilkums</p>
            <h2 class="uiverse-heading">Kopā izlasīts: {{ number_format($totalPagesRead, 0, ',', ' ') }} lappuses</h2>
            <h2 class="uiverse-heading">Kopā izlasītas: {{ $totalBooksRead }} {{ $totalBooksRead === 1 ? 'grāmata' : 'grāmatas' }}</h2>
        </section>

        <section class="reading-progress-overview uiverse-container" style="margin-bottom: 1rem;">
            <h2 class="uiverse-heading">Plaukti: Want to Read / In Progress / Read</h2>
            <div style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); align-items: start;">
                @php
                    $statusColumns = [
                        'want_to_read' => 'Want to Read',
                        'in_progress' => 'In Progress',
                        'read' => 'Read',
                    ];
                @endphp

                @foreach ($statusColumns as $statusKey => $statusLabel)
                    <div class="rp-snapshot-col">
                        <h3 class="rp-shelf-column-title">{{ $statusLabel }}</h3>

                        @forelse (($bookSnapshotsByStatus[$statusKey] ?? []) as $snapshot)
                            @php
                                $totalPages = $snapshot['total_pages'];
                                $pagesRead = $snapshot['pages_read'];
                                $percent = $totalPages ? min(100, (int) round(($pagesRead / $totalPages) * 100)) : null;
                                $emotionToEmoji = [
                                    'Priecīgs' => '😊',
                                    'Mierīgs' => '😌',
                                    'Iedvesmots' => '🤩',
                                    'Skumīgs' => '😢',
                                    'Satraukts' => '😱',
                                    'Noguris' => '😴',
                                    'Pārsteigts' => '😮',
                                    'Sarūgtināts' => '😠',
                                    'Domājošs' => '🤔',
                                    'Aizkustināts' => '🥺',
                                    'Saspringts' => '😤',
                                    'Smieklīgs' => '😂',
                                    'Apjucis' => '🤯',
                                    'Skeptisks' => '🙄',
                                    'Motivēts' => '💪',
                                    'Mīlošs' => '🥰',
                                ];
                                $emotionNames = collect(explode(',', (string) ($snapshot['emotion'] ?? '')))
                                    ->map(fn (string $emotion) => trim($emotion))
                                    ->filter()
                                    ->unique()
                                    ->values();
                            @endphp

                            <article class="rp-snapshot-article">
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                                    @if (!empty($snapshot['book_cover_url']))
                                        <img src="{{ $snapshot['book_cover_url'] }}" alt="{{ $snapshot['book_title'] }} vāks" style="width: 46px; height: 68px; object-fit: cover; border-radius: 8px;">
                                    @endif
                                    <div>
                                        <strong>{{ $snapshot['book_title'] }}</strong>
                                        @if ($statusKey === 'read')
                                            <div class="rp-read-badge">READ</div>
                                        @endif
                                        <div class="rp-snapshot-meta">Pēdējais ieraksts: {{ $snapshot['reading_date']->format('Y-m-d') }}</div>
                                        @if ($emotionNames->isNotEmpty())
                                            <div class="rp-emotion-icons" aria-label="Grāmatas emocijas">
                                                @foreach ($emotionNames as $emotionName)
                                                    @if (isset($emotionToEmoji[$emotionName]))
                                                        <span class="rp-emotion-icon" title="{{ $emotionName }}">{{ $emotionToEmoji[$emotionName] }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="rp-snapshot-pages">
                                    {{ $pagesRead }} lpp
                                    @if ($totalPages)
                                        / {{ $totalPages }}
                                    @endif
                                </div>

                                @if ($percent !== null)
                                    <div style="margin-bottom: 8px;">
                                        <div style="height: 8px; border-radius: 999px; background: #e5e7eb; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $percent }}%; background: linear-gradient(90deg, #0f766e, #10b981);"></div>
                                        </div>
                                        <div class="rp-snapshot-meta" style="margin-top: 4px;">{{ $percent }}%</div>
                                    </div>
                                @endif

                                @if ($statusKey !== 'read')
                                    @php
                                        $btnLabel = $statusKey === 'want_to_read' ? '📖 Sākt lasīt' : '🔄 Atjaunot';
                                        $prefillStatus = $statusKey === 'want_to_read' ? 'in_progress' : 'in_progress';
                                    @endphp
                                    <a
                                        href="{{ route('reading-progress.index', [
                                            'entry_id' => $snapshot['entry_id'] ?? '',
                                            'book_title' => $snapshot['book_title'],
                                            'google_volume_id' => $snapshot['google_volume_id'] ?? '',
                                            'book_cover_url' => $snapshot['book_cover_url'] ?? '',
                                            'pages_read' => $snapshot['pages_read'],
                                            'total_pages' => $snapshot['total_pages'] ?? '',
                                            'reading_status' => $prefillStatus,
                                            'emotion' => $snapshot['emotion'] ?? '',
                                            'reading_date' => now()->format('Y-m-d'),
                                            'start_time' => '00:00',
                                            'end_time' => '00:01',
                                        ]) }}"
                                        class="login-button"
                                        style="display: inline-block; text-decoration: none;"
                                    >{{ $btnLabel }}</a>
                                @endif
                            </article>
                        @empty
                            <p class="rp-snapshot-meta">Šeit vēl nav grāmatu.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layout>
