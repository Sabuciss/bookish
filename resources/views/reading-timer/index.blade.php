<x-layout>
    <div class="reading-challenges-page">
        <h1>Laika sadaļa</h1>
        <p>Uzliec lasīšanas taimeri, lasa noteikto laiku un pēc tam saglabā rezultātu ar izlasītajām lapām un piezīmēm.</p>

        <nav class="book-listings-tabs" aria-label="Laika sadaļas">
            <a href="{{ route('reading-timer.index') }}" @class(['is-active' => request()->routeIs('reading-timer.*')])>Laika sadaļa</a>
            <a href="{{ route('reading-challenges.results') }}" @class(['is-active' => request()->routeIs('reading-challenges.results')])>Laika rezultāti</a>
        </nav>

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

        <section class="reading-timer-section uiverse-container">
            <h2 class="uiverse-heading">Lasīšanas taimeris</h2>

            <form action="{{ route('reading-timer.sessions.start') }}" method="POST" class="form" id="reading-timer-form"
                data-active-session-id="{{ $activeSession?->id ?? '' }}"
                data-active-status="{{ $activeSession?->timer_status ?? '' }}"
                data-active-elapsed="{{ $activeElapsedSeconds }}"
                data-pause-url="{{ route('reading-timer.sessions.pause', ['session' => '__SESSION__']) }}"
                data-resume-url="{{ route('reading-timer.sessions.resume', ['session' => '__SESSION__']) }}"
                data-complete-url="{{ route('reading-timer.sessions.complete', ['session' => '__SESSION__']) }}">
                @csrf

                <label>
                    Izaicinājums (nav obligāti)
                    <select name="challenge_id" id="timer-challenge" class="input">
                        <option value="">Bez izaicinājuma</option>
                        @foreach ($challenges as $challenge)
                            <option value="{{ $challenge->id }}" @selected($activeSession?->challenge_id === $challenge->id)>{{ $challenge->title }} ({{ $challenge->target_value }} min)</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Uzliec laiku (minūtēs)
                    <input type="number" min="1" max="1440" name="planned_minutes" id="timer-minutes" value="{{ old('planned_minutes', $activeSession?->planned_minutes ?? 25) }}" class="input" required>
                </label>

                <div class="reading-timer-display" id="reading-timer-display">25:00</div>

                <div class="reading-timer-actions">
                    <button type="button" id="timer-start" class="login-button">Starts</button>
                    <button type="button" id="timer-pause" class="reading-progress-submit">Pauze</button>
                    <button type="button" id="timer-reset" class="reading-progress-submit">Reset</button>
                </div>

                <p id="timer-error" class="reading-progress-alert reading-progress-alert-error" role="alert" hidden></p>

                <label>
                    Cik lpp izlasīji šajā laikā
                    <input type="number" min="0" max="2000" name="pages_read" value="{{ old('pages_read') }}" class="input" required>
                </label>

                <label>
                    Notes / piezīmes
                    <textarea name="notes" rows="3" class="input" placeholder="Ko velies pierakstīt?">{{ old('notes') }}</textarea>
                </label>

                <input type="hidden" name="is_public" value="0">
                <label for="session-is-public" class="session-visibility-control">
                    <input id="session-is-public" type="checkbox" name="is_public" value="1" aria-describedby="session-visibility-help" @checked(old('is_public', false))>
                    <span class="session-visibility-checkbox" aria-hidden="true"></span>
                    <span>Publiskot sesiju rezultātu sadaļā</span>
                    <span id="session-visibility-help" class="session-visibility-tooltip" role="tooltip">Publiska sesija būs redzama sadaļā “Visi”. Ja neatzīmēsi, sesiju redzēsi tikai tu.</span>
                </label>

                <button type="submit" class="login-button reading-timer-save" id="timer-save">Saglabāt sesiju</button>
            </form>

            @if ($activeSession)
                <form method="POST" action="{{ route('reading-timer.sessions.destroy', $activeSession->id) }}" class="reading-timer-cancel-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="book-listing-secondary-action">Atcelt taimera sesiju</button>
                </form>
            @endif

            <h3>Pēdējās sesijas</h3>
            @if ($sessions->isEmpty())
                <p>Vēl nav nevienas taimera sesijas.</p>
            @else
                <div class="reading-challenges-cards">
                    @foreach ($sessions as $session)
                        <article class="reading-challenge-card">
                            @if ($session->challenge)
                                <p><strong>Izaicinājums:</strong> {{ $session->challenge->title }}</p>
                            @endif
                            <p><strong>Plānots:</strong> {{ $session->planned_minutes }} min</p>
                            <p><strong>Faktiski:</strong> {{ is_null($session->elapsed_seconds) ? 'Nav zināms' : \App\Models\ReadingChallengeSession::formatDuration($session->elapsed_seconds) }}</p>
                            <p><strong>Izlasīts:</strong> {{ $session->pages_read }} lpp</p>
                            <p><strong>Redzamība:</strong> {{ $session->is_public ? 'Publiska' : 'Privāta' }}</p>
                            <form method="POST" action="{{ route('reading-timer.sessions.destroy', $session->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="book-listing-secondary-action">Dzēst sesiju</button>
                            </form>
                            @if ($session->notes)
                                <p class="reading-challenge-notes">{{ $session->notes }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layout>
