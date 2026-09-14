<x-layout>
    <div class="reading-progress-page book-listings-page">
        <a href="{{ route($listingType === 'exchange' ? 'book-exchange.index' : 'book-listings.index') }}" class="book-detail-back">← Atpakaļ</a>
        <section class="uiverse-container book-listing-form-card">
            <p class="book-detail-eyebrow">Bookish kopiena</p>
            <h1>{{ $listingType === 'exchange' ? 'Piedāvāt grāmatu apmaiņai' : 'Pievienot grāmatu pārdošanai' }}</h1>
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

            <form action="{{ route('book-listings.store') }}" method="POST" class="book-listing-form">
                @csrf
                <input type="hidden" name="listing_type" value="{{ $listingType }}">
                <label>Grāmatas nosaukums
                    <input type="text" name="book_title" value="{{ old('book_title') }}" required>
                </label>
                @if ($listingType === 'exchange')
                    <label>Grāmata, ko vēlies saņemt pretī
                        <input type="text" name="exchange_book_title" value="{{ old('exchange_book_title') }}" required>
                    </label>
                @endif
                <label>Autors
                    <input type="text" name="author" value="{{ old('author') }}">
                </label>
                <div class="book-listing-form-row">
                    <label>Stāvoklis
                        <select name="condition" required>
                            <option value="">Izvēlies stāvokli</option>
                            @foreach (['Jauna', 'Ļoti labs stāvoklis', 'Labs stāvoklis', 'Lietota'] as $condition)
                                <option value="{{ $condition }}" @selected(old('condition') === $condition)>{{ $condition }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Valoda
                        <input type="text" name="language" value="{{ old('language', 'Latviešu') }}" required>
                    </label>
                    @if ($listingType === 'sale')
                        <label>Cena (EUR)
                            <input type="number" name="price" value="{{ old('price') }}" min="0" max="999999.99" step="0.01" required>
                        </label>
                    @endif
                </div>
                <label>Apraksts
                    <textarea name="description" rows="5" maxlength="2000" placeholder="Piemēram, vai grāmatai ir locījumi, piezīmes vai citi defekti.">{{ old('description') }}</textarea>
                </label>
                <label>Kontakta e-pasts
                    <input type="email" name="contact_email" value="{{ old('contact_email', auth()->user()->email) }}" required>
                </label>
                <button type="submit" class="reading-progress-submit">{{ $listingType === 'exchange' ? 'Publicēt apmaiņas piedāvājumu' : 'Publicēt sludinājumu' }}</button>
            </form>
        </section>
    </div>
</x-layout>
