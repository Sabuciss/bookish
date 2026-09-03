<x-layout>
    <div class="reading-challenges-page">
        <h1>Laika sadaļa</h1>
        <p>Uzliec lasīšanas taimeri, lasa noteikto laiku un pēc tam saglabā rezultātu ar izlasītajām lapām un piezīmēm.</p>

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

            <form action="{{ route('reading-challenges.sessions.store') }}" method="POST" class="form" id="reading-timer-form">
                @csrf

                <label>
                    Uzliec laiku (minūtēs)
                    <input type="number" min="1" max="1440" name="planned_minutes" id="timer-minutes" value="{{ old('planned_minutes', 25) }}" class="input" required>
                </label>

                <div class="reading-timer-display" id="reading-timer-display">25:00</div>

                <div class="reading-timer-actions">
                    <button type="button" id="timer-start" class="login-button">Starts</button>
                    <button type="button" id="timer-pause" class="reading-progress-submit">Pauze</button>
                    <button type="button" id="timer-reset" class="reading-progress-submit">Reset</button>
                </div>

                <input type="hidden" name="elapsed_seconds" id="timer-elapsed-seconds" value="{{ old('elapsed_seconds') }}">
                <input type="hidden" name="started_at" id="timer-started-at" value="{{ old('started_at') }}">
                <input type="hidden" name="ended_at" id="timer-ended-at" value="{{ old('ended_at') }}">

                <label>
                    Cik lpp izlasīji šajā laikā
                    <input type="number" min="0" name="pages_read" value="{{ old('pages_read') }}" class="input" required>
                </label>

                <label>
                    Notes / piezīmes
                    <textarea name="notes" rows="3" class="input" placeholder="Ko velies pierakstīt?">{{ old('notes') }}</textarea>
                </label>

                <button type="submit" class="login-button">Saglabāt sesiju</button>
            </form>

            <h3>Pēdējās sesijas</h3>
            @if ($sessions->isEmpty())
                <p>Vēl nav nevienas taimera sesijas.</p>
            @else
                <div class="reading-challenges-cards">
                    @foreach ($sessions as $session)
                        <article class="reading-challenge-card">
                            <p><strong>Plānots:</strong> {{ $session->planned_minutes }} min</p>
                            <p><strong>Faktiski:</strong> {{ $session->elapsed_seconds ? floor($session->elapsed_seconds / 60) : $session->planned_minutes }} min</p>
                            <p><strong>Izlasīts:</strong> {{ $session->pages_read }} lpp</p>
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
