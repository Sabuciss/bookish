<x-layout>
    @php
        $prefill = $prefill ?? [];
        $isEditMode = (bool) ($prefill['is_edit_mode'] ?? false);
        $prefillStatus = $prefill['reading_status'] ?? 'in_progress';
        $prefillEmotion = $prefill['emotion'] ?? '';
        $selectedEmotions = array_filter(array_map('trim', explode(',', old('emotion', $prefillEmotion))));
    @endphp

    <div
        class="reading-progress-page"
        id="reading-progress-page"
        data-latest-pages='@json($latestPagesByBook ?? [])'
        data-is-edit-mode="{{ $isEditMode ? '1' : '0' }}"
    >
        <h1>{{ $isEditMode ? 'Atjaunot lasīšanas progresu' : 'Pievienot lasīšanas progresu' }}</h1>
        <p>Šeit vari pievienot un atjaunot lasīšanas progresu.</p>

        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="reading-progress-alert reading-progress-alert-error">
                <ul class="reading-progress-errors-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rp-actions-row">
            <a href="{{ route('reading-shelf.show') }}" class="reading-progress-submit" style="text-decoration: none;">Atvērt grāmatu plauktu</a>
        </div>

        <div class="reading-progress-layout">
            <section class="reading-progress-history uiverse-container">
                <h2 class="uiverse-heading">Iepriekšējie ieraksti</h2>

                @if ($progressEntries->isEmpty())
                    <p>Vēl nav neviena progresa ieraksta.</p>
                @else
                    <div class="rp-entries-grid">
                        @foreach ($progressEntries as $entry)
                            @php
                                $progressPercent = $entry->total_pages ? min(100, (int) round(($entry->pages_read / $entry->total_pages) * 100)) : null;
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
                                $emotionNames = collect(explode(',', (string) $entry->emotion))
                                    ->map(fn (string $emotion) => trim($emotion))
                                    ->filter()
                                    ->unique()
                                    ->values();
                            @endphp
                            <article class="rp-entry-card">
                                <div class="rp-entry-preview">
                                    @if ($entry->book_cover_url)
                                        <img src="{{ $entry->book_cover_url }}" alt="{{ $entry->book_title }} vāks" class="rp-entry-cover">
                                    @else
                                        <div class="rp-entry-cover-placeholder">📖</div>
                                    @endif
                                    <div class="rp-entry-brief">
                                        <h3 class="rp-entry-title">{{ $entry->book_title }}</h3>
                                        <div class="rp-entry-progress">
                                            @if ($progressPercent !== null)
                                                <div class="rp-progress-bar">
                                                    <div class="rp-progress-fill" style="width: {{ $progressPercent }}%"></div>
                                                </div>
                                                <span class="rp-progress-text">{{ $progressPercent }}%</span>
                                            @else
                                                <span class="rp-progress-text">-</span>
                                            @endif
                                        </div>
                                        @if ($emotionNames->isNotEmpty())
                                            <div class="rp-entry-emotions" aria-label="Ieraksta emocijas">
                                                @foreach ($emotionNames as $emotionName)
                                                    @if (isset($emotionToEmoji[$emotionName]))
                                                        <span class="rp-emotion-badge" title="{{ $emotionName }}">{{ $emotionToEmoji[$emotionName] }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="rp-entry-details">
                                    @php
                                        $formatMinutes = function (int $minutes): string {
                                            $hours = intdiv($minutes, 60);
                                            $rest = $minutes % 60;
                                            return $hours > 0 ? $hours . ' h ' . $rest . ' min' : $rest . ' min';
                                        };
                                        $sessionMinutes = $entry->lastSessionMinutes();
                                        $totalMinutes = max((int) $entry->duration_minutes, $sessionMinutes);
                                    @endphp
                                    <div class="rp-detail-row">
                                        <strong>Pēdējoreiz lasīts:</strong>
                                        <span>{{ $entry->reading_date->format('d.m.Y') }}</span>
                                    </div>
                                    <div class="rp-detail-row">
                                        <strong>Izlasīts:</strong>
                                        <span>{{ $entry->pages_read }} / {{ $entry->total_pages ?? '?' }} lpp</span>
                                    </div>
                                    <div class="rp-detail-row">
                                        <strong>Statuss:</strong>
                                        <span>
                                            @if ($entry->reading_status === 'read')
                                                <span class="reading-progress-status-badge">IZLASĪTA</span>
                                            @elseif ($entry->reading_status === 'want_to_read')
                                                Gribu izlasīt
                                            @else
                                                Lasu
                                            @endif
                                        </span>
                                    </div>
                                    <div class="rp-detail-row">
                                        <strong>Pēdējā sesija:</strong>
                                        <span>{{ $entry->start_time ? \Illuminate\Support\Carbon::parse($entry->start_time)->format('H:i') : '-' }} - {{ $entry->end_time ? \Illuminate\Support\Carbon::parse($entry->end_time)->format('H:i') : '-' }} ({{ $formatMinutes($sessionMinutes) }})</span>
                                    </div>
                                    <div class="rp-detail-row rp-detail-row--total">
                                        <strong>Kopā lasīts:</strong>
                                        <span>{{ $formatMinutes($totalMinutes) }}</span>
                                    </div>
                                    <div class="rp-entry-actions">
                                        <a href="{{ route('reading-progress.index', ['entry_id' => $entry->id]) }}" class="rp-action-btn rp-action-edit">✎ Rediģēt</a>
                                        <form method="POST" action="{{ route('reading-progress.destroy', $entry->id) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rp-action-btn rp-action-delete">✕ Dzēst</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="reading-progress-pagination">{{ $progressEntries->links() }}</div>
                @endif
            </section>

            <section class="reading-progress-editor uiverse-container">
                <h2 class="uiverse-heading" id="editor-heading">{{ $isEditMode ? 'Atjaunot datus' : 'Pievienot datus' }}</h2>

                <div id="edit-mode-banner" class="edit-mode-banner" style="display:{{ $isEditMode ? 'flex' : 'none' }};">
                    <span>Rediģē: <strong class="edit-banner-title">{{ old('book_title', $prefill['book_title'] ?? '') }}</strong></span>
                    <a href="{{ route('reading-progress.index') }}" class="cancel-edit-btn" style="text-decoration:none;">✕ Atcelt</a>
                </div>

                <form action="{{ route('reading-progress.store') }}" method="POST" class="reading-progress-form form" lang="lv">
                    @csrf
                    <input type="hidden" name="entry_id" id="entry_id" value="{{ old('entry_id', $prefill['entry_id'] ?? '') }}">

                    <label>
                        Meklēt grāmatu (Google Books)
                        <div class="rp-search-row">
                            <input
                                type="text"
                                id="book-search-query"
                                placeholder="Piemēram: Fourth Wing"
                                class="reading-progress-input input"
                            >
                            <button type="button" id="book-search-btn" class="login-button">Meklēt</button>
                        </div>
                    </label>

                    <div id="book-search-status" class="rp-snapshot-meta"></div>
                    <div id="book-search-results" class="rp-book-search-results"></div>

                    <input type="hidden" name="google_volume_id" id="google_volume_id" value="{{ old('google_volume_id', $prefill['google_volume_id'] ?? '') }}">
                    <input type="hidden" name="book_cover_url" id="book_cover_url" value="{{ old('book_cover_url', $prefill['book_cover_url'] ?? '') }}">

                    @php $currStatus = old('reading_status', $prefillStatus); @endphp
                    <input type="hidden" name="reading_status" id="reading_status" value="{{ $currStatus }}">

                    <label class="rp-book-title-field">
                        Grāmata
                        <input type="text" name="book_title" id="book_title" maxlength="255" value="{{ old('book_title', $prefill['book_title'] ?? '') }}" required class="reading-progress-input input">
                    </label>

                    <label>
                        Lappušu izaicinājums (nav obligāti)
                        <select name="challenge_id" class="reading-progress-input input">
                            <option value="">Bez izaicinājuma</option>
                            @foreach ($challenges as $challenge)
                                <option value="{{ $challenge->id }}" @selected((string) old('challenge_id', $prefill['challenge_id'] ?? '') === (string) $challenge->id)>
                                    {{ $challenge->title }} ({{ $challenge->target_value }} lpp)
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div id="selected-book-preview" class="rp-book-preview" style="display: none;">
                        <div class="rp-selected-book-inner">
                            <img id="selected-book-image" src="" alt="Izvēlētās grāmatas vāks" class="rp-selected-cover" style="display: none;">
                            <div>
                                <strong id="selected-book-title"></strong>
                                <div id="selected-book-pages" class="rp-snapshot-meta"></div>
                                <button type="button" id="change-book-btn" class="login-button u-mt-8">Nomainīt grāmatu</button>
                            </div>
                        </div>
                    </div>

                    <label class="rp-pages-field">
                        Grāmatas kopējās lpp
                        <input type="number" name="total_pages" id="total_pages" min="1" value="{{ old('total_pages', $prefill['total_pages'] ?? '') }}" class="reading-progress-input input">
                        <small class="rp-snapshot-meta">Google Books skaits ir orientējošs. Vari to labot, uzspiežot uz šī lauka.</small>
                    </label>

                    <label class="rp-pages-field">
                        Izlasītās lpp
                        <input type="number" name="pages_read" id="pages_read" min="0" value="{{ old('pages_read', $prefill['pages_read'] ?? '') }}" required class="reading-progress-input input">
                    </label>

                    <div id="live-progress-preview" class="live-progress-preview" style="display:none;">
                        <div class="live-progress-bar-wrap">
                            <div class="live-progress-bar" id="live-progress-bar"></div>
                        </div>
                        <div class="live-progress-stats">
                            <span id="live-progress-pct" class="live-progress-pct"></span>
                            <span id="live-progress-pages" class="live-progress-pages-left"></span>
                            <span id="live-progress-status" class="live-progress-status-badge"></span>
                        </div>
                    </div>
                        <span class="reading-progress-emotion-label">Emocijas <span class="rp-snapshot-meta rp-emotion-note">(var izvēlēties vairākas)</span></span>
                        <input type="hidden" name="emotion" id="emotion_input" value="{{ old('emotion', $prefillEmotion) }}">
                        <div class="emotion-picker" id="emotion-picker">
                            @foreach ([
                                'Priecīgs'      => '😊',
                                'Mierīgs'       => '😌',
                                'Iedvesmots'    => '🤩',
                                'Skumīgs'       => '😢',
                                'Satraukts'     => '😱',
                                'Noguris'       => '😴',
                                'Pārsteigts'    => '😮',
                                'Sarūgtināts'   => '😠',
                                'Domājošs'      => '🤔',
                                'Aizkustināts'  => '🥺',
                                'Saspringts'    => '😤',
                                'Smieklīgs'     => '😂',
                                'Apjucis'       => '🤯',
                                'Skeptisks'     => '🙄',
                                'Motivēts'      => '💪',
                                'Mīlošs'        => '🥰',
                            ] as $label => $emoji)
                                <button
                                    type="button"
                                    class="emotion-tag {{ in_array($label, $selectedEmotions) ? 'selected' : '' }}"
                                    data-emotion="{{ $label }}"
                                >{{ $emoji }} {{ $label }}</button>
                            @endforeach
                        </div>

                        <div class="rp-datetime-row">
                            <div class="rp-datetime-label">
                                <span id="reading-date-label">Datums</span>
                                <div class="rp-date-control">
                                    <input type="text" id="reading-date-display" value="{{ \Illuminate\Support\Carbon::parse(old('reading_date', $prefill['reading_date'] ?? now()->toDateString()))->format('d.m.Y') }}" readonly class="reading-progress-input input rp-date-display" aria-label="Datums, diena mēnesis gads">
                                    <button type="button" id="reading-date-open" class="rp-date-open" aria-label="Atvērt datuma izvēlni">▣</button>
                                    <input type="hidden" name="reading_date" id="reading-date-picker" value="{{ old('reading_date', $prefill['reading_date'] ?? now()->toDateString()) }}" required>
                                    <div id="reading-date-calendar" class="rp-calendar" hidden>
                                        <div class="rp-calendar-header">
                                            <button type="button" class="rp-calendar-nav" id="reading-date-previous" aria-label="Iepriekšējais mēnesis">‹</button>
                                            <strong id="reading-date-month"></strong>
                                            <button type="button" class="rp-calendar-nav" id="reading-date-next" aria-label="Nākamais mēnesis">›</button>
                                        </div>
                                        <div class="rp-calendar-weekdays" aria-hidden="true">
                                            <span>P</span><span>O</span><span>T</span><span>C</span><span>P</span><span>S</span><span>Sv</span>
                                        </div>
                                        <div id="reading-date-days" class="rp-calendar-days"></div>
                                    </div>
                                </div>
                            </div>
                            @foreach ([
                                ['name' => 'start_time', 'label' => 'No cikiem', 'default' => '00:00', 'aria' => 'Sākuma laiks'],
                                ['name' => 'end_time', 'label' => 'Līdz cikiem', 'default' => '00:01', 'aria' => 'Beigu laiks'],
                            ] as $timeField)
                                @php $timeValue = substr((string) old($timeField['name'], $prefill[$timeField['name']] ?? $timeField['default']), 0, 5); @endphp
                                <div class="rp-datetime-label">
                                    <span>{{ $timeField['label'] }}</span>
                                    <div class="rp-time-control" data-time-control>
                                        <input type="text" value="{{ $timeValue }}" readonly class="reading-progress-input input rp-time-input rp-time-display" aria-label="{{ $timeField['aria'] }}, 24 stundu formāts">
                                        <button type="button" class="rp-date-open rp-time-open" aria-label="Atvērt laika izvēlni">◷</button>
                                        <input type="hidden" name="{{ $timeField['name'] }}" value="{{ $timeValue }}" required data-time-value>
                                        <div class="rp-time-popup" hidden>
                                            <div class="rp-time-column" data-time-part="hour" aria-label="Stundas"></div>
                                            <div class="rp-time-column" data-time-part="minute" aria-label="Minūtes"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="rp-form-footer">
                            <a href="{{ route('reading-shelf.show') }}" class="rp-back-button">Atpakaļ</a>
                            <button type="submit" id="progress-submit-btn" class="reading-progress-submit login-button">{{ $isEditMode ? 'Atjaunot progresu' : 'Saglabāt progresu' }}</button>
                        </div>
                </form>
            </section>
        </div>
    </div>

</x-layout>
