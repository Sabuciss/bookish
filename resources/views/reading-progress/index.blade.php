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
        <h1>{{ $isEditMode ? 'Atjaunot reading progress' : 'Pievienot reading progress' }}</h1>
        <p>Šī ir atsevišķa lapa reading progress pievienošanai un atjaunošanai. Grāmatu plaukts ir atdalīts atsevišķā skatā.</p>

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

        <div class="reading-progress-layout">
            <section class="reading-progress-history uiverse-container">
                <h2 class="uiverse-heading">Iepriekšējie ieraksti</h2>

                @if ($progressEntries->isEmpty())
                    <p>Vēl nav neviena progresa ieraksta.</p>
                @else
                    <div class="reading-progress-table-wrap">
                        <table class="reading-progress-table">
                            <thead>
                                <tr>
                                    <th>Datums</th>
                                    <th>Grāmata</th>
                                    <th>Vāks</th>
                                    <th>Izlasītās lpp</th>
                                    <th>Kopā lpp</th>
                                    <th>Progress</th>
                                    <th>Statuss</th>
                                    <th>Emocijas</th>
                                    <th>No cikiem</th>
                                    <th>Līdz cikiem</th>
                                    <th>Ilgums</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                    <tr>
                                        <td>{{ $entry->reading_date->format('Y-m-d') }}</td>
                                        <td>{{ $entry->book_title }}</td>
                                        <td>
                                            @if ($entry->book_cover_url)
                                                <img src="{{ $entry->book_cover_url }}" alt="{{ $entry->book_title }} vāks" style="width: 38px; height: 56px; object-fit: cover; border-radius: 6px;">
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $entry->pages_read }}</td>
                                        <td>{{ $entry->total_pages ?? '-' }}</td>
                                        <td>{{ $progressPercent !== null ? $progressPercent.'%' : '-' }}</td>
                                        <td>
                                            @if ($entry->reading_status === 'read')
                                                <span class="reading-progress-status-badge">READ</span>
                                            @elseif ($entry->reading_status === 'want_to_read')
                                                Want to Read
                                            @else
                                                In Progress
                                            @endif
                                        </td>
                                        <td>
                                            @if ($emotionNames->isNotEmpty())
                                                <div class="rp-emotion-icons" aria-label="Ieraksta emocijas">
                                                    @foreach ($emotionNames as $emotionName)
                                                        @if (isset($emotionToEmoji[$emotionName]))
                                                            <span class="rp-emotion-icon" title="{{ $emotionName }}">{{ $emotionToEmoji[$emotionName] }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $entry->start_time ? \Illuminate\Support\Carbon::parse($entry->start_time)->format('H:i') : '-' }}</td>
                                        <td>{{ $entry->end_time ? \Illuminate\Support\Carbon::parse($entry->end_time)->format('H:i') : '-' }}</td>
                                        <td>{{ $entry->duration_minutes }} min</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
                    <div id="book-search-results" style="display: grid; gap: 8px;"></div>

                    <input type="hidden" name="google_volume_id" id="google_volume_id" value="{{ old('google_volume_id', $prefill['google_volume_id'] ?? '') }}">
                    <input type="hidden" name="book_cover_url" id="book_cover_url" value="{{ old('book_cover_url', $prefill['book_cover_url'] ?? '') }}">

                    @php $currStatus = old('reading_status', $prefillStatus); @endphp
                    <input type="hidden" name="reading_status" id="reading_status" value="{{ $currStatus }}">

                    <label class="rp-book-title-field">
                        Grāmata
                        <input type="text" name="book_title" id="book_title" maxlength="255" value="{{ old('book_title', $prefill['book_title'] ?? '') }}" required class="reading-progress-input input">
                    </label>

                    <div id="selected-book-preview" class="rp-book-preview" style="display: none;">
                        <div class="rp-selected-book-inner">
                            <img id="selected-book-image" src="" alt="Izvēlētās grāmatas vāks" style="width: 60px; height: 88px; object-fit: cover; border-radius: 8px; display: none;">
                            <div>
                                <strong id="selected-book-title"></strong>
                                <div id="selected-book-pages" class="rp-snapshot-meta"></div>
                                <button type="button" id="change-book-btn" class="login-button" style="margin-top: 8px;">Nomainīt grāmatu</button>
                            </div>
                        </div>
                    </div>

                    <label class="rp-pages-field">
                        Grāmatas kopējās lpp
                        <input type="number" name="total_pages" id="total_pages" min="1" value="{{ old('total_pages', $prefill['total_pages'] ?? '') }}" class="reading-progress-input input">
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
                            <label style="flex: 1.5;">
                                Datums
                                <div class="rp-date-control">
                                    <input type="text" id="reading-date-display" value="{{ \Illuminate\Support\Carbon::parse(old('reading_date', $prefill['reading_date'] ?? now()->toDateString()))->format('d.m.Y') }}" readonly class="reading-progress-input input rp-date-display" aria-label="Datums, diena mēnesis gads">
                                    <button type="button" id="reading-date-open" class="rp-date-open" aria-label="Atvērt datuma izvēlni">▣</button>
                                    <input type="date" name="reading_date" id="reading-date-picker" value="{{ old('reading_date', $prefill['reading_date'] ?? now()->toDateString()) }}" required class="rp-date-picker" lang="lv-LV">
                                </div>
                            </label>
                            <label style="flex: 1;">
                                No cikiem
                                <input type="time" name="start_time" value="{{ old('start_time', $prefill['start_time'] ?? '00:00') }}" required class="reading-progress-input input rp-time-input" lang="lv-LV" step="60" aria-label="Sākuma laiks, 24 stundu formāts">
                            </label>
                            <label style="flex: 1;">
                                Līdz cikiem
                                <input type="time" name="end_time" value="{{ old('end_time', $prefill['end_time'] ?? '00:01') }}" required class="reading-progress-input input rp-time-input" lang="lv-LV" step="60" aria-label="Beigu laiks, 24 stundu formāts">
                            </label>
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
