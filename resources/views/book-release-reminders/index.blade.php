<x-layout>
    <div class="reading-progress-page">
        <h1>Izdošanas kalendārs</h1>
        <p>Te redzamas grāmatas, kurām esi iestatījis atgādinājumu, sakārtotas pēc izdošanas datuma.</p>

        <script id="release-calendar-reminders" type="application/json">@json($reminders->map(fn ($reminder) => ['date' => $reminder->release_date->toDateString(), 'title' => $reminder->title]))</script>

        <section class="uiverse-container release-calendar-layout">
            <div class="release-calendar-list">
            @if ($reminders->isEmpty())
                <p class="welcome-card-text">Kalendārā vēl nav gaidāmu grāmatu. Atrodi grāmatu sākumlapā un nospied “Atgādināt”.</p>
            @else
                <div class="weekly-top-books">
                    @foreach ($reminders as $reminder)
                        <article class="weekly-top-item release-calendar-item">
                            <time class="release-calendar-date" datetime="{{ $reminder->release_date->toDateString() }}">
                                <strong>{{ $reminder->release_date->format('d') }}</strong>
                                <span>{{ $reminder->release_date->translatedFormat('M') }}</span>
                                <small>{{ $reminder->release_date->format('Y') }}</small>
                            </time>
                            @if ($reminder->cover_url)
                                <a href="{{ route('books.show', $reminder->google_volume_id) }}">
                                    <img class="weekly-top-cover" src="{{ $reminder->cover_url }}" alt="{{ $reminder->title }} vāks">
                                </a>
                            @else
                                <div class="weekly-top-cover weekly-top-cover--empty">Nav vāka</div>
                            @endif
                            <div class="weekly-top-content">
                                <h2 class="welcome-card-title"><a class="weekly-top-title-link" href="{{ route('books.show', $reminder->google_volume_id) }}">{{ $reminder->title }}</a></h2>
                                <p class="weekly-top-meta">{{ $reminder->author ?: 'Autors nav norādīts' }}</p>
                                <p class="release-calendar-release">Iznāks {{ $reminder->release_date->format('d.m.Y') }}</p>
                                <a class="weekly-top-action" href="{{ route('books.show', $reminder->google_volume_id) }}">Apskatīt grāmatu</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
            </div>

            <div class="release-calendar-month" aria-label="Grāmatu izdošanas datumu kalendārs">
                <div class="release-calendar-month-heading">
                    <button type="button" class="release-calendar-nav" data-release-calendar-previous aria-label="Iepriekšējais mēnesis">&lt;</button>
                    <h2 id="release-calendar-month-title" class="uiverse-heading"></h2>
                    <button type="button" class="release-calendar-nav" data-release-calendar-next aria-label="Nākamais mēnesis">&gt;</button>
                </div>
                <div class="release-calendar-weekdays" aria-hidden="true">
                    <span>P</span><span>O</span><span>T</span><span>C</span><span>P</span><span>S</span><span>Sv</span>
                </div>
                <div id="release-calendar-days" class="release-calendar-days"></div>
            </div>
        </section>
    </div>
</x-layout>