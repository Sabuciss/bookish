<x-layout>
    <div class="reading-progress-page book-listings-page">
        <div class="book-listings-heading">
            <div>
                <p class="book-detail-eyebrow">Bookish tirgus</p>
                <h1>Grāmatu sludinājumi</h1>
                <p>Atrodi savu nākamo grāmatu vai publicē kādu no savas kolekcijas pārdošanai.</p>
            </div>
            @auth
                <a href="{{ route('book-listings.create') }}" class="reading-progress-submit">Pievienot sludinājumu</a>
            @else
                <a href="{{ route('login') }}" class="reading-progress-submit">Ielogoties, lai pārdotu</a>
            @endauth
        </div>

        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">{{ session('status') }}</div>
        @endif

        @if ($listings->isEmpty())
            <section class="uiverse-container book-listings-empty">
                <h2 class="uiverse-heading">Pagaidām nav sludinājumu</h2>
                <p>Esi pirmais, kas pievieno grāmatu pārdošanai.</p>
            </section>
        @else
            <div class="book-listing-grid">
                @foreach ($listings as $listing)
                    <article class="book-listing-card">
                        <div class="book-listing-card-heading">
                            <div>
                                <h2>{{ $listing->book_title }}</h2>
                                @if ($listing->author)
                                    <p>{{ $listing->author }}</p>
                                @endif
                            </div>
                            <strong class="book-listing-price">{{ number_format((float) $listing->price, 2, ',', ' ') }} EUR</strong>
                        </div>
                        <dl class="book-listing-details">
                            <div><dt>Stāvoklis</dt><dd>{{ $listing->condition }}</dd></div>
                            <div><dt>Valoda</dt><dd>{{ $listing->language }}</dd></div>
                        </dl>
                        @if ($listing->description)
                            <p class="book-listing-description">{{ $listing->description }}</p>
                        @endif
                        @auth
                            @if ($listing->user_id !== auth()->id())
                                @if (!$listing->applications->contains('user_id', auth()->id()))
                                    <form method="POST" action="{{ route('book-listings.apply', $listing) }}" class="book-listing-application-form">
                                        @csrf
                                        <label>
                                            Ziņa pārdevējam (pēc izvēles)
                                            <textarea name="message" rows="2" maxlength="1000" placeholder="Piemēram, kad vari grāmatu saņemt.">{{ old('message') }}</textarea>
                                        </label>
                                        <button type="submit" class="reading-progress-submit">Pieteikties uz grāmatu</button>
                                    </form>
                                @else
                                    <p class="book-listing-application-sent">Pieteikums jau nosūtīts.</p>
                                @endif
                            @else
                                <p class="book-listing-owner-note">Šis ir tavs sludinājums.</p>
                                @if ($listing->applications->isNotEmpty())
                                    <div class="book-listing-applications">
                                        <strong>Pieteikušies interesenti ({{ $listing->applications->count() }})</strong>
                                        @foreach ($listing->applications as $application)
                                            <div class="book-listing-application">
                                                <div>
                                                    <strong>{{ $application->user->name }}</strong>
                                                    <span>{{ $application->user->email }}</span>
                                                </div>
                                                <span class="book-listing-application-status">{{ $application->status === 'pending' ? 'Gaida atbildi' : $application->status }}</span>
                                                @if ($application->message)
                                                    <p>{{ $application->message }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="book-listing-apply-login">Ielogojies, lai pieteiktos</a>
                        @endauth
                        <div class="book-listing-footer">
                            <span>Publicēja {{ $listing->user->name }}</span>
                            <a href="mailto:{{ $listing->contact_email }}?subject=Par grāmatu: {{ rawurlencode($listing->book_title) }}">Sazināties</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="book-listings-pagination">{{ $listings->links() }}</div>
        @endif
    </div>
</x-layout>
