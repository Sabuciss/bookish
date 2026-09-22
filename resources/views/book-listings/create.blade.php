<x-layout>
    <div class="reading-progress-page book-listings-page">
        <a href="{{ route($listingType === 'exchange' ? 'book-exchange.index' : 'book-listings.index') }}" class="book-detail-back">← Atpakaļ</a>
        <section class="uiverse-container book-listing-form-card">
            <p class="book-detail-eyebrow">Bookish kopiena</p>
            <h1>{{ $listing ? 'Labot sludinājumu' : ($listingType === 'exchange' ? 'Piedāvāt grāmatu apmaiņai' : 'Pievienot grāmatu pārdošanai') }}</h1>
            <p>{{ $listingType === 'exchange' ? 'Norādi, ko piedāvā un kādu grāmatu vēlētos saņemt pretī.' : 'Norādi galveno informāciju, lai citi lasītāji varētu saprast, ko piedāvā.' }}</p>

            @if ($errors->any())
                <div class="reading-progress-alert reading-progress-alert-error">
                    <ul class="reading-progress-errors-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ $listing ? route('book-listings.update', $listing) : route('book-listings.store') }}" method="POST" class="book-listing-form">
                @csrf
                @if ($listing)
                    @method('PATCH')
                @endif
                <input type="hidden" name="listing_type" value="{{ $listingType }}">
                <div class="listing-book-picker" data-listing-book-picker>
                    <label for="listing-book-search">Meklē grāmatu pēc nosaukuma vai autora
                        <input id="listing-book-search" type="search" autocomplete="off" value="{{ old('book_title', $listing?->book_title) }}" placeholder="Piemēram, Harijs Poters">
                    </label>
                    <div id="listing-book-search-status" class="rp-book-search-status" aria-live="polite"></div>
                    <div id="listing-book-search-results" class="rp-book-search-results" aria-live="polite"></div>
                    <input type="hidden" name="book_title" id="listing-book-title" value="{{ old('book_title', $listing?->book_title) }}" required>
                    <input type="hidden" name="google_volume_id" id="listing-google-volume-id" value="{{ old('google_volume_id', $listing?->google_volume_id) }}">
                    <input type="hidden" name="book_cover_url" id="listing-book-cover-url" value="{{ old('book_cover_url', $listing?->book_cover_url) }}">
                    <div id="listing-selected-book" class="listing-selected-book" @style(['display: flex' => old('book_title', $listing?->book_title), 'display: none' => ! old('book_title', $listing?->book_title)])>
                        <img id="listing-selected-book-cover" src="{{ old('book_cover_url', $listing?->book_cover_url) }}" alt="Izvēlētās grāmatas vāks" @if (! old('book_cover_url', $listing?->book_cover_url)) hidden @endif>
                        <div>
                            <strong id="listing-selected-book-title">{{ old('book_title', $listing?->book_title) }}</strong>
                            <span id="listing-selected-book-author">{{ old('author', $listing?->author) }}</span>
                        </div>
                    </div>
                </div>
                @if ($listingType === 'exchange')
                    <label>Grāmata, ko vēlies saņemt pretī (pēc izvēles)
                        <input type="text" name="exchange_book_title" value="{{ old('exchange_book_title', $listing?->exchange_book_title) }}">
                    </label>
                @endif
                <label>Autors
                    <input type="text" name="author" id="listing-book-author" value="{{ old('author', $listing?->author) }}" readonly>
                </label>
                <div class="book-listing-form-row">
                    <label>Stāvoklis
                        <select name="condition" required>
                            <option value="">Izvēlies stāvokli</option>
                            @foreach (['Jauna', 'Ļoti labs stāvoklis', 'Labs stāvoklis', 'Lietota'] as $condition)
                                <option value="{{ $condition }}" @selected(old('condition', $listing?->condition) === $condition)>{{ $condition }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Valoda
                        <input type="text" name="language" value="{{ old('language', $listing?->language ?? 'Latviešu') }}" required>
                    </label>
                    @if ($listingType === 'sale')
                        <label>Cena (EUR)
                            <input type="number" name="price" value="{{ old('price', $listing?->price) }}" min="0" max="999999.99" step="0.01" required>
                        </label>
                    @endif
                </div>
                <label>Apraksts
                    <textarea name="description" rows="5" maxlength="2000" placeholder="Piemēram, vai grāmatai ir locījumi, piezīmes vai citi defekti.">{{ old('description', $listing?->description) }}</textarea>
                </label>
                <button type="submit" class="reading-progress-submit">{{ $listing ? 'Saglabāt izmaiņas' : ($listingType === 'exchange' ? 'Publicēt apmaiņas piedāvājumu' : 'Publicēt sludinājumu') }}</button>
            </form>
        </section>
    </div>
</x-layout>
