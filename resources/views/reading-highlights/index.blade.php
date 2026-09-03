<x-layout>
    <div class="reading-progress-page">
        <h1>Highlights un piezīmes</h1>
        <p>Šeit vari redzēt visus highlights vienā sarakstā.</p>

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

        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
            <a href="{{ route('reading-highlights.create') }}" class="reading-progress-submit" style="text-decoration: none;">Izveidot highlight</a>
        </div>

        <section class="reading-progress-history uiverse-container">
            <h2 class="uiverse-heading">Visi highlights</h2>

            @if ($highlights->isEmpty())
                <p>Highlights vēl nav pieejami.</p>
            @else
                <div class="highlight-grid">
                    @foreach ($highlights as $highlight)
                        <div class="highlight-card">
                            <blockquote class="highlight-quote">{{ $highlight->quote_text }}</blockquote>
                            <div class="highlight-meta">
                                @if ($highlight->book_title)
                                    <span class="highlight-book"> {{ $highlight->book_title }}</span>
                                @endif
                                @if ($highlight->character)
                                    <span class="highlight-character">— {{ $highlight->character }}</span>
                                @endif
                                <span class="highlight-character">{{ $highlight->is_public ? 'Publisks' : 'Privāts' }}</span>
                                <span class="highlight-character">Autors: {{ $highlight->user?->name ?? 'Nezināms lietotājs' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-layout>