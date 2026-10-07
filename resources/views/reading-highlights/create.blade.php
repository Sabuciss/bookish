<x-layout>
    <div class="reading-progress-page">
        <h1>{{ $highlight ? 'Rediģēt highlight' : 'Izveidot highlight' }}</h1>
        <p>{{ $highlight ? 'Atjauno savu citātu vai piezīmi.' : 'Pievieno savu jauno citātu vai piezīmi atsevišķā izveides lapā.' }}</p>

        @if ($errors->any())
            <div class="reading-progress-alert reading-progress-alert-error">
                <ul class="reading-progress-errors-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
            <a href="{{ route('reading-highlights.index') }}" class="reading-progress-submit" style="text-decoration: none;">Skatīt visus highlights</a>
        </div>

        <section class="reading-progress-editor uiverse-container">
            <h2 class="uiverse-heading">{{ $highlight ? 'Rediģēt highlight' : 'Pievienot highlight' }}</h2>

            <form action="{{ $highlight ? route('reading-highlights.update', $highlight) : route('reading-highlights.store') }}" method="POST" class="reading-progress-form form">
                @csrf
                @if ($highlight)
                    @method('PATCH')
                @endif

                <label>
                    Kas to teica?
                    <input type="text" name="character" maxlength="255" value="{{ old('character', $highlight?->character) }}" class="reading-progress-input input" placeholder="Piemēram, varonis vai autors">
                </label>

                <label>
                    Grāmata
                    <input type="text" name="book_title" maxlength="255" value="{{ old('book_title', $highlight?->book_title) }}" class="reading-progress-input input" placeholder="Grāmatas nosaukums">
                </label>

                <label>
                    Kas tika teikts?
                    <textarea name="quote_text" rows="5" maxlength="5000" required class="reading-progress-input input">{{ old('quote_text', $highlight?->quote_text) }}</textarea>
                </label>

                <input type="hidden" name="is_public" value="0">
                <label for="highlight-is-public" class="highlight-visibility-control">
                    <input id="highlight-is-public" type="checkbox" name="is_public" value="1" aria-describedby="highlight-visibility-help" @checked(old('is_public', $highlight?->is_public ?? true))>
                    <span class="highlight-visibility-checkbox" aria-hidden="true"></span>
                    <span class="highlight-visibility-status" aria-live="polite">
                        <span class="highlight-visibility-public">Publisks</span>
                        <span class="highlight-visibility-private">Privāts</span>
                    </span>
                    Publiskot highlight sadaļā “Visi”
                    <span id="highlight-visibility-help" class="highlight-visibility-tooltip" role="tooltip">Publisks highlight būs redzams sadaļā “Visi”. Ja neatzīmēsi, to redzēsi tikai tu.</span>
                </label>

                <button type="submit" class="reading-progress-submit login-button">{{ $highlight ? 'Saglabāt izmaiņas' : 'Saglabāt highlight' }}</button>
            </form>
        </section>
    </div>
</x-layout>
