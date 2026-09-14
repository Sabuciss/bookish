<x-layout>
    <div class="reading-progress-page book-listings-page">
        <div class="book-listings-heading">
            <div>
                <p class="book-detail-eyebrow">Bookish kopiena</p>
                <h1>{{ $listingType === 'exchange' ? 'Grāmatu apmaiņa' : 'Grāmatu sludinājumi' }}</h1>
                <p>{{ $listingType === 'exchange' ? 'Atrodi lasītāju, ar kuru apmainīties ar grāmatām.' : 'Atrodi savu nākamo grāmatu vai publicē kādu no savas kolekcijas pārdošanai.' }}</p>
            </div>
            @auth
                <a href="{{ route($listingType === 'exchange' ? 'book-exchange.create' : 'book-listings.create') }}" class="reading-progress-submit">{{ $listingType === 'exchange' ? 'Piedāvāt grāmatu' : 'Pievienot sludinājumu' }}</a>
            @else
                <a href="{{ route('login') }}" class="reading-progress-submit">Ielogoties, lai pievienotu</a>
            @endauth
        </div>

        <nav class="book-listings-tabs" aria-label="Grāmatu sadaļas">
            <a href="{{ route('book-listings.index') }}" @class(['is-active' => $listingType === 'sale'])>Pārdošana</a>
            <a href="{{ route('book-exchange.index') }}" @class(['is-active' => $listingType === 'exchange'])>Apmaiņa</a>
        </nav>

        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">{{ session('status') }}</div>
        @endif

        @if ($listings->isEmpty())
            <section class="uiverse-container book-listings-empty">
                <h2 class="uiverse-heading">Pagaidām nav sludinājumu</h2>
                <p>{{ $listingType === 'exchange' ? 'Esi pirmais, kas piedāvā grāmatu apmaiņai.' : 'Esi pirmais, kas pievieno grāmatu pārdošanai.' }}</p>
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
                            <div class="book-listing-card-badges">
                                <strong class="book-listing-price">{{ $listing->isExchange() ? 'APMAIŅA' : number_format((float) $listing->price, 2, ',', ' ') . ' EUR' }}</strong>
                                <span class="book-listing-availability {{ $listing->isAvailable() ? 'is-available' : 'is-unavailable' }}">{{ $listing->isAvailable() ? 'Pieejams' : 'Nav pieejams' }}</span>
                            </div>
                        </div>
                        <dl class="book-listing-details">
                            <div><dt>Stāvoklis</dt><dd>{{ $listing->condition }}</dd></div>
                            <div><dt>Valoda</dt><dd>{{ $listing->language }}</dd></div>
                            @if ($listing->isExchange())
                                <div><dt>Meklē pretī</dt><dd>{{ $listing->exchange_book_title }}</dd></div>
                            @endif
                        </dl>
                        @if ($listing->description)
                            <p class="book-listing-description">{{ $listing->description }}</p>
                        @endif
                        @auth
                            @if ($listing->user_id !== auth()->id())
                                @php($myApplication = $listing->applications->firstWhere('user_id', auth()->id()))
                                @if ($myApplication)
                                    <div class="book-listing-my-application">
                                        <strong>Tavs pieteikums: {{ $myApplication->status === 'pending' ? 'Gaida atbildi' : ($myApplication->status === 'accepted' ? 'Pieņemts' : 'Noraidīts') }}</strong>
                                        @if ($listing->isExchange() && $myApplication->offered_book_title)
                                            <p><strong>Piedāvātā grāmata:</strong> {{ $myApplication->offered_book_title }}</p>
                                        @endif
                                        @if ($myApplication->message)
                                            <p>{{ $myApplication->message }}</p>
                                        @endif
                                        @if ($myApplication->status === 'accepted')
                                            <p>Sazinies ar autoru: <a href="mailto:{{ $listing->contact_email }}">{{ $listing->contact_email }}</a></p>
                                        @endif
                                    </div>
                                @elseif ($listing->isAvailable())
                                    <form method="POST" action="{{ route('book-listings.apply', $listing) }}" class="book-listing-application-form">
                                        @csrf
                                        @if ($listing->isExchange())
                                            <label>
                                                Grāmata, ko piedāvā apmaiņai
                                                <input type="text" name="offered_book_title" maxlength="255" required>
                                            </label>
                                        @endif
                                        <label>
                                            Ziņa {{ $listing->isExchange() ? 'grāmatas īpašniekam' : 'pārdevējam' }} (pēc izvēles)
                                            <textarea name="message" rows="2" maxlength="1000" placeholder="Piemēram, kad vari grāmatu saņemt.">{{ old('message') }}</textarea>
                                        </label>
                                        <button type="submit" class="reading-progress-submit">{{ $listing->isExchange() ? 'Piedāvāt apmaiņu' : 'Pieteikties uz grāmatu' }}</button>
                                    </form>
                                @else
                                    <p class="book-listing-application-sent">Šis sludinājums vairs nav pieejams.</p>
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
                                                <span class="book-listing-application-status">{{ match ($application->status) { 'pending' => 'Gaida atbildi', 'accepted' => 'Pieņemts', 'rejected' => 'Noraidīts', default => $application->status } }}</span>
                                                @if ($listing->isExchange() && $application->offered_book_title)
                                                    <p><strong>Piedāvā:</strong> {{ $application->offered_book_title }}</p>
                                                @endif
                                                @if ($application->message)
                                                    <p>{{ $application->message }}</p>
                                                @endif
                                                @if ($application->status === 'pending')
                                                    <div class="book-listing-application-actions">
                                                        <form method="POST" action="{{ route('book-listings.applications.update', [$listing, $application]) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="accepted">
                                                            <button type="submit" class="reading-progress-submit">{{ $listing->isExchange() ? 'Pieņemt apmaiņu' : 'Pieņemt pircēju' }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('book-listings.applications.update', [$listing, $application]) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit" class="book-listing-secondary-action">Noraidīt</button>
                                                        </form>
                                                    </div>
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
