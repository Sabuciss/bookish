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
            <div class="book-listing-grid {{ $listingType === 'exchange' ? 'book-listing-grid-exchange' : '' }}">
                @foreach ($listings as $listing)
                    <article id="book-listing-{{ $listing->id }}" class="book-listing-card">
                        @if ($listing->book_cover_url)
                            <img class="book-listing-cover" src="{{ $listing->book_cover_url }}" alt="{{ $listing->book_title }} vāks">
                        @endif
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
                                <div class="book-listing-wanted-book">
                                    <dt>Meklē pretī</dt>
                                    <dd>
                                        @if ($listing->exchange_book_cover_url)
                                            <img src="{{ $listing->exchange_book_cover_url }}" alt="{{ $listing->exchange_book_title }} vāks">
                                        @endif
                                        <span>{{ $listing->exchange_book_title }}</span>
                                        @if ($listing->exchange_book_author)
                                            <small>{{ $listing->exchange_book_author }}</small>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        </dl>
                        @if ($listing->description)
                            <p class="book-listing-description">{{ $listing->description }}</p>
                        @endif
                        @auth
                            @if ($listing->user_id !== auth()->id())
                                @php($myApplication = $listing->applications->firstWhere('user_id', auth()->id()))
                                @if ($myApplication)
                                    <div id="book-application-{{ $myApplication->id }}" class="book-listing-my-application">
                                        <strong>Tavs pieteikums: {{ match ($myApplication->status) { 'pending' => 'Gaida atbildi', 'accepted' => 'Pieņemts', 'rejected' => 'Noraidīts', 'cancelled' => 'Atcelts', 'completed' => 'Pabeigts', default => $myApplication->status } }}</strong>
                                        @if ($myApplication->status === 'pending')
                                            <form method="POST" action="{{ route('book-listings.applications.details.update', [$listing, $myApplication]) }}" class="book-listing-application-form">
                                                @csrf
                                                @method('PATCH')
                                                @if ($listing->isExchange())
                                                    <label>Piedāvātā grāmata
                                                        <input type="text" name="offered_book_title" maxlength="255" value="{{ old('offered_book_title', $myApplication->offered_book_title) }}" required>
                                                    </label>
                                                @endif
                                                <label>Pieteikuma ziņa
                                                    <textarea name="message" rows="2" maxlength="1000">{{ old('message', $myApplication->message) }}</textarea>
                                                </label>
                                                <button type="submit" class="reading-progress-submit">Labot pieteikumu</button>
                                            </form>
                                            <form method="POST" action="{{ route('book-listings.applications.destroy', [$listing, $myApplication]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="book-listing-secondary-action">Atcelt pieteikumu</button>
                                            </form>
                                        @elseif ($myApplication->status === 'accepted')
                                            <div class="book-listing-application-actions">
                                                <form method="POST" action="{{ route('book-listings.applications.complete', [$listing, $myApplication]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="reading-progress-submit">Atzīmēt darījumu kā pabeigtu</button>
                                                </form>
                                                <form method="POST" action="{{ route('book-listings.applications.destroy', [$listing, $myApplication]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="book-listing-secondary-action">Atcelt darījumu</button>
                                                </form>
                                            </div>
                                        @elseif (in_array($myApplication->status, ['rejected', 'cancelled'], true) && $listing->isAvailable())
                                            <form method="POST" action="{{ route('book-listings.apply', $listing) }}" class="book-listing-application-form">
                                                @csrf
                                                @if ($listing->isExchange())
                                                    <label>Grāmata, ko piedāvā apmaiņai
                                                        <input type="text" name="offered_book_title" maxlength="255" value="{{ $myApplication->offered_book_title }}" required>
                                                    </label>
                                                @endif
                                                <label>Ziņa
                                                    <textarea name="message" rows="2" maxlength="1000">{{ $myApplication->message }}</textarea>
                                                </label>
                                                <button type="submit" class="reading-progress-submit">Iesniegt pieteikumu vēlreiz</button>
                                            </form>
                                        @endif
                                        @if ($listing->isExchange() && $myApplication->offered_book_title)
                                            <p><strong>Piedāvātā grāmata:</strong> {{ $myApplication->offered_book_title }}</p>
                                        @endif
                                        @if ($myApplication->message)
                                            <p>{{ $myApplication->message }}</p>
                                        @endif
                                        @if ($myApplication->messages->isNotEmpty())
                                            <div class="book-listing-message-thread">
                                                @foreach ($myApplication->messages as $message)
                                                    <div id="book-message-{{ $message->id }}" class="book-listing-message {{ $message->user_id === auth()->id() ? 'is-mine' : '' }}">
                                                        <strong>{{ $message->user->name }}</strong>
                                                        <span>{{ $message->created_at->timezone('Europe/Riga')->format('d.m.Y H:i') }}</span>
                                                        <p>{{ $message->message }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if (in_array($myApplication->status, ['pending', 'accepted'], true))
                                            <form method="POST" action="{{ route('book-listings.applications.messages.store', [$listing, $myApplication]) }}" class="book-listing-message-form">
                                                @csrf
                                                <label>
                                                    Tava ziņa autoram
                                                    <textarea name="message" rows="2" maxlength="2000" required placeholder="Vienosimies par laiku un vietu..."></textarea>
                                                </label>
                                                <button type="submit" class="reading-progress-submit">Nosūtīt ziņu</button>
                                            </form>
                                        @endif
                                        @if ($myApplication->listing_snapshot || $myApplication->status_history)
                                            <details class="book-listing-audit">
                                                <summary>Darījuma dati un vēsture</summary>
                                                @foreach ($myApplication->listing_snapshot ?? [] as $snapshotEntry)
                                                    @php($snapshot = $snapshotEntry['snapshot']['listing'] ?? [])
                                                    <p>{{ match ($snapshotEntry['stage'] ?? '') { 'submitted' => 'Iesniegšanas brīdī', 'reapplied' => 'Atkārtotas iesniegšanas brīdī', 'accepted' => 'Pieņemšanas brīdī', default => 'Snapshot' } }}: {{ $snapshot['book_title'] ?? 'Grāmata' }} · {{ $snapshot['condition'] ?? '' }} · {{ $snapshot['language'] ?? '' }} · {{ $snapshotEntry['snapshot']['captured_at'] ?? '' }}</p>
                                                @endforeach
                                                @foreach ($myApplication->status_history ?? [] as $event)
                                                    <p>{{ \Illuminate\Support\Carbon::parse($event['at'])->format('d.m.Y H:i') }} · {{ $event['from'] ?? 'izveidots' }} → {{ $event['to'] ?? $event['event'] }} @if(!empty($event['reason']))({{ $event['reason'] }})@endif</p>
                                                @endforeach
                                            </details>
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
                                @if ($listing->applications->contains(fn ($application) => in_array($application->status, ['accepted', 'completed'], true)))
                                    <p class="book-listing-owner-note">Darījums ir pieņemts vai pabeigts; sludinājuma noteikumi ir bloķēti.</p>
                                @else
                                    <a href="{{ route('book-listings.edit', $listing) }}" class="book-listing-edit-link">Labot sludinājumu</a>
                                @endif
                                @if ($listing->applications->isNotEmpty())
                                    <div class="book-listing-applications">
                                        <strong>Pieteikušies interesenti ({{ $listing->applications->count() }})</strong>
                                        @foreach ($listing->applications as $application)
                                            <div id="book-application-{{ $application->id }}" class="book-listing-application">
                                                <div>
                                                    <strong>{{ $application->user->name }}</strong>
                                                </div>
                                                <span class="book-listing-application-status">{{ match ($application->status) { 'pending' => 'Gaida atbildi', 'accepted' => 'Pieņemts', 'rejected' => 'Noraidīts', 'cancelled' => 'Atcelts', 'completed' => 'Pabeigts', default => $application->status } }}</span>
                                                @if ($listing->isExchange() && $application->offered_book_title)
                                                    <p><strong>Piedāvā:</strong> {{ $application->offered_book_title }}</p>
                                                @endif
                                                @if ($application->message)
                                                    <p>{{ $application->message }}</p>
                                                @endif
                                                @if ($application->messages->isNotEmpty())
                                                    <div class="book-listing-message-thread">
                                                        @foreach ($application->messages as $message)
                                                            <div id="book-message-{{ $message->id }}" class="book-listing-message {{ $message->user_id === auth()->id() ? 'is-mine' : '' }}">
                                                                <strong>{{ $message->user->name }}</strong>
                                                                <span>{{ $message->created_at->timezone('Europe/Riga')->format('d.m.Y H:i') }}</span>
                                                                <p>{{ $message->message }}</p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if (in_array($application->status, ['pending', 'accepted'], true))
                                                    <form method="POST" action="{{ route('book-listings.applications.messages.store', [$listing, $application]) }}" class="book-listing-message-form">
                                                        @csrf
                                                        <label>
                                                            Ziņa interesentam
                                                            <textarea name="message" rows="2" maxlength="2000" required placeholder="Vienosimies par laiku un vietu..."></textarea>
                                                        </label>
                                                        <button type="submit" class="reading-progress-submit">Nosūtīt ziņu</button>
                                                    </form>
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
                                                @elseif ($application->status === 'accepted')
                                                    <div class="book-listing-application-actions">
                                                        <form method="POST" action="{{ route('book-listings.applications.complete', [$listing, $application]) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="reading-progress-submit">Atzīmēt kā pabeigtu</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('book-listings.applications.destroy', [$listing, $application]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="book-listing-secondary-action">Atcelt darījumu</button>
                                                        </form>
                                                    </div>
                                                @endif
                                                @if ($application->listing_snapshot || $application->status_history)
                                                    <details class="book-listing-audit">
                                                        <summary>Darījuma dati un vēsture</summary>
                                                        @foreach ($application->listing_snapshot ?? [] as $snapshotEntry)
                                                            @php($snapshot = $snapshotEntry['snapshot']['listing'] ?? [])
                                                            <p>{{ match ($snapshotEntry['stage'] ?? '') { 'submitted' => 'Iesniegšanas brīdī', 'reapplied' => 'Atkārtotas iesniegšanas brīdī', 'accepted' => 'Pieņemšanas brīdī', default => 'Snapshot' } }}: {{ $snapshot['book_title'] ?? 'Grāmata' }} · {{ $snapshot['condition'] ?? '' }} · {{ $snapshot['language'] ?? '' }} · {{ $snapshotEntry['snapshot']['captured_at'] ?? '' }}</p>
                                                        @endforeach
                                                        @foreach ($application->status_history ?? [] as $event)
                                                            <p>{{ \Illuminate\Support\Carbon::parse($event['at'])->format('d.m.Y H:i') }} · {{ $event['from'] ?? 'izveidots' }} → {{ $event['to'] ?? $event['event'] }} @if(!empty($event['reason']))({{ $event['reason'] }})@endif</p>
                                                        @endforeach
                                                    </details>
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
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="book-listings-pagination">{{ $listings->links() }}</div>
        @endif
    </div>
</x-layout>
