<x-layout>
    <div class="reading-challenges-page">
        <h1>Atjaunot izaicinājumu</h1>
        <p>Atjauno izaicinājuma mērķi, periodu, statusu un komentāru.</p>

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

        @php($selectedType = old('challenge_type', $challenge->challenge_type))

        <section class="reading-challenges-editor uiverse-container">
            <h2 class="uiverse-heading">Izaicinājuma rediģēšana</h2>

            <form action="{{ route('reading-challenges.update', $challenge->id) }}" method="POST" class="form">
                @csrf
                @method('PATCH')

                <label>
                    Nosaukums
                    <input type="text" name="title" value="{{ old('title', $challenge->title) }}" required class="input" placeholder="Piemēram: 7 dienu izaicinājums">
                </label>

                <label>
                    Izaicinājuma tips
                    <select name="challenge_type" id="challenge-type" required class="input">
                        <option value="pages" @selected($selectedType === 'pages')>Lapas</option>
                        <option value="time" @selected($selectedType === 'time')>Laiks (minūtes)</option>
                    </select>
                </label>

                <label id="challenge-target-label" for="challenge-target-value">
                    Mērķa vērtība (lapas)
                    <input type="number" name="target_value" id="challenge-target-value" min="1" value="{{ old('target_value', $challenge->target_value) }}" required class="input" placeholder="Piemēram: 200 lapas" aria-label="Mērķa vērtība lapās">
                </label>

                <label>
                    Sākuma datums
                    <input type="date" name="start_date" value="{{ old('start_date', $challenge->start_date->format('Y-m-d')) }}" required class="input">
                </label>

                <label>
                    Beigu datums
                    <input type="date" name="end_date" value="{{ old('end_date', $challenge->end_date->format('Y-m-d')) }}" required class="input">
                </label>

                <label>
                    Piezīmes (nav obligāti)
                    <textarea name="notes" rows="3" class="input" placeholder="Papildu piezīmes">{{ old('notes', $challenge->notes) }}</textarea>
                </label>

                <label>
                    Statuss
                    <select name="is_completed" class="input">
                        <option value="0" @selected((string) old('is_completed', (int) $challenge->is_completed) === '0')>Procesā</option>
                        <option value="1" @selected((string) old('is_completed', (int) $challenge->is_completed) === '1')>Izdarīts</option>
                    </select>
                </label>

                <label>
                    Komentārs (nav obligāti)
                    <textarea name="completion_comment" rows="2" class="input" placeholder="Piemēram: izpildīju 7 dienu mērķi">{{ old('completion_comment', $challenge->completion_comment) }}</textarea>
                </label>

                <button type="submit" class="login-button">Saglabāt izmaiņas</button>
                <a href="{{ route('reading-challenges.index') }}" class="reading-progress-submit" style="display: inline-block; text-decoration: none; margin-top: 8px;">Atpakaļ uz izaicinājumiem</a>
            </form>
        </section>
    </div>
</x-layout>
