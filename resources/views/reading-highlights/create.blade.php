<x-layout>
    <div class="reading-progress-page">
        <h1>Izveidot highlight</h1>
        <p>Pievieno savu jauno citātu vai piezīmi atsevišķā izveides lapā.</p>

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
            <h2 class="uiverse-heading">Pievienot highlight</h2>

            <form action="{{ route('reading-highlights.store') }}" method="POST" class="reading-progress-form form">
                @csrf

                <label>
                    Grāmata
                    <input type="text" name="book_title" maxlength="255" value="{{ old('book_title') }}" class="reading-progress-input input" placeholder="Nav obligāti">
                </label>

                <label>
                    Kurš to teica (varonis)
                    <input type="text" name="character" maxlength="255" value="{{ old('character') }}" class="reading-progress-input input" placeholder="Nav obligāti">
                </label>

                <label>
                    Kas tika teikts (citāts)
                    <textarea name="quote_text" rows="5" maxlength="5000" required class="reading-progress-input input">{{ old('quote_text') }}</textarea>
                </label>

                <input type="hidden" name="is_public" value="0">
                <label>
                    <input type="checkbox" name="is_public" value="1" @checked(old('is_public', true))>
                    Atļaut citiem lietotājiem redzēt šo highlight
                </label>

                <button type="submit" class="reading-progress-submit login-button">Saglabāt highlight</button>
            </form>
        </section>
    </div>
</x-layout>
