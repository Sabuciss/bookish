<x-layout>
    <div class="reading-challenges-page">
        <h1>Izaicinājumu sadaļa</h1>
        <p>Izveido sev izaicinājumu: cik lapas izlasīt vai cik minūtes lasīt noteiktā periodā.</p>

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

        <div class="reading-challenges-layout">
            <section class="reading-challenges-list uiverse-container">
                <h2 class="uiverse-heading">Mani izaicinājumi</h2>

                @if ($challenges->isEmpty())
                    <p>Vēl nav neviena izaicinājuma.</p>
                @else
                    <div class="reading-challenges-cards">
                        @foreach ($challenges as $challenge)
                            <article class="reading-challenge-card">
                                <h3>{{ $challenge->title }}</h3>
                                <p><strong>Lietotājs:</strong> {{ $challenge->user?->name ?? 'Nezināms lietotājs' }}</p>
                                <p>
                                    Tips:
                                    <strong>{{ $challenge->challenge_type === 'pages' ? 'Lapas' : 'Laiks (min)' }}</strong>
                                </p>
                                <p>
                                    Mērķis:
                                    <strong>{{ $challenge->target_value }} {{ $challenge->challenge_type === 'pages' ? 'lpp' : 'min' }}</strong>
                                </p>
                                <p>
                                    Statuss:
                                    <strong>{{ $challenge->is_completed ? 'Izdarīts' : ($challenge->is_failed ? 'Nav izpildīts' : ($challenge->end_date->lt(today()) ? 'Termiņš beidzies' : 'Procesā')) }}</strong>
                                </p>
                                <p>Periods: {{ $challenge->start_date->format('Y-m-d') }} līdz {{ $challenge->end_date->format('Y-m-d') }}</p>
                                @if ($challenge->notes)
                                    <p class="reading-challenge-notes">{{ $challenge->notes }}</p>
                                @endif
                                @if ($challenge->completion_comment)
                                    <p class="reading-challenge-notes"><strong>Komentārs:</strong> {{ $challenge->completion_comment }}</p>
                                @endif
                                <p>Aprēķinātais progress: <strong>{{ $challenge->completion_value ?? 0 }} / {{ $challenge->target_value }} {{ $challenge->challenge_type === 'pages' ? 'lpp' : 'min' }}</strong></p>
                                <p>
                                    @if ($challenge->challenge_type === 'pages')
                                        <a href="{{ route('reading-progress.index', ['challenge_id' => $challenge->id]) }}">Pievienot lasīšanas ierakstu</a>
                                    @else
                                        <a href="{{ route('reading-timer.index') }}">Atvērt lasīšanas taimeri</a>
                                    @endif
                                </p>
                                <p>
                                    <a href="{{ route('reading-challenges.edit', $challenge->id) }}" class="reading-progress-submit" style="text-decoration: none;">Atjaunot</a>
                                </p>
                                <form method="POST" action="{{ route('reading-challenges.destroy', $challenge->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="book-listing-secondary-action">Dzēst izaicinājumu</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                    <div>{{ $challenges->links() }}</div>
                @endif
            </section>

            <section class="reading-challenges-editor uiverse-container">
                @php($isEditing = ! is_null($editingChallenge ?? null))
                @php($selectedType = old('challenge_type', $editingChallenge->challenge_type ?? 'pages'))
                <h2 class="uiverse-heading">{{ $isEditing ? 'Atjaunot izaicinājumu' : 'Izveidot izaicinājumu' }}</h2>

                <form action="{{ $isEditing ? route('reading-challenges.update', $editingChallenge->id) : route('reading-challenges.store') }}" method="POST" class="form">
                    @csrf
                    @if ($isEditing)
                        @method('PATCH')
                    @endif

                    <label>
                        Nosaukums
                        <input type="text" name="title" value="{{ old('title', $editingChallenge->title ?? '') }}" required class="input" placeholder="Piemēram: 7 dienu izaicinājums">
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
                        <input type="number" name="target_value" id="challenge-target-value" min="1" value="{{ old('target_value', $editingChallenge->target_value ?? '') }}" required class="input" placeholder="Piemēram: 200 lapas" aria-label="Mērķa vērtība lapās">
                    </label>

                    <label>
                        Sākuma datums
                        <input type="date" name="start_date" value="{{ old('start_date', optional($editingChallenge->start_date ?? null)->format('Y-m-d')) }}" required class="input">
                    </label>

                    <label>
                        Beigu datums
                        <input type="date" name="end_date" value="{{ old('end_date', optional($editingChallenge->end_date ?? null)->format('Y-m-d')) }}" required class="input">
                    </label>

                    <label>
                        Piezīmes (nav obligāti)
                        <textarea name="notes" rows="3" class="input" placeholder="Papildu piezīmes">{{ old('notes', $editingChallenge->notes ?? '') }}</textarea>
                    </label>

                    @if ($isEditing && $editingChallenge->end_date->lt(today()) && ! $editingChallenge->is_completed)
                        <label>
                            <input type="checkbox" name="mark_as_not_completed" value="1" @checked(old('mark_as_not_completed', $editingChallenge->is_failed))>
                            Atzīmēt kā neizpildītu
                        </label>
                    @endif

                    <button type="submit" class="login-button">{{ $isEditing ? 'Atjaunot izaicinājumu' : 'Saglabāt izaicinājumu' }}</button>
                    @if ($isEditing)
                        <a href="{{ route('reading-challenges.index') }}" class="reading-progress-submit" style="display: inline-block; text-decoration: none; margin-top: 8px;">Atcelt rediģēšanu</a>
                    @endif
                </form>
            </section>
        </div>
    </div>
</x-layout>
