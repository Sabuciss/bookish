<nav class="app-surface-opaque bg-neutral-primary fixed w-full z-30 top-0 start-0 border-b border-default">
  <div class="max-w-screen-xl flex items-center justify-between mx-auto p-4">
    <div class="flex items-center gap-2">
      <button id="drawer-navigation-opener" type="button" data-drawer-target="drawer-navigation" data-drawer-show="drawer-navigation" aria-controls="drawer-navigation" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-body rounded-base hover:bg-neutral-secondary-soft hover:text-heading focus:outline-none focus:ring-2 focus:ring-neutral-tertiary">
        <span class="sr-only">Atvērt navigāciju</span>
        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
      </button>
      <a href="{{ url('/') }}" class="flex items-center space-x-2 rtl:space-x-reverse">
        <img src="{{ asset('images/logo3.png') }}" alt="Bookish" class="bookish-brand-logo">
      </a>
    </div>

    <div class="flex items-center gap-3">
      @auth
        @php
          $releaseReminders = Auth::user()->bookReleaseReminders()
            ->whereNotNull('notified_at')
            ->latest('notified_at')
            ->get();
          $listingNotifications = Auth::user()->notifications()
            ->where('type', \App\Notifications\BookListingApplicationReceived::class)
            ->whereNull('read_at')
            ->latest()
            ->get();
          $listingStatusNotifications = Auth::user()->notifications()
            ->where('type', \App\Notifications\BookListingApplicationStatusChanged::class)
            ->whereNull('read_at')
            ->latest()
            ->get();
          $listingMessageNotifications = Auth::user()->notifications()
            ->where('type', \App\Notifications\BookListingMessageReceived::class)
            ->whereNull('read_at')
            ->latest()
            ->get();
          $latestTimerNotification = Auth::user()->notifications()
            ->where('type', \App\Notifications\ReadingTimerSessionSaved::class)
            ->whereNull('read_at')
            ->latest()
            ->first();
          $notificationCount = $releaseReminders->count() + $listingNotifications->count() + $listingStatusNotifications->count() + $listingMessageNotifications->count() + (int) (bool) $latestTimerNotification;
        @endphp
        <div class="book-notification" x-data="{ open: false }">
          <button type="button" class="book-notification-button" @click="open = !open" :aria-expanded="open.toString()" aria-label="Atvērt paziņojumus">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @if($notificationCount)
              <span class="book-notification-count">{{ $notificationCount }}</span>
            @endif
          </button>
          <div x-show="open" x-cloak @click.outside="open = false" class="book-notification-panel">
            <div class="book-notification-heading">
              <strong>Paziņojumi</strong>
              <span>{{ $notificationCount }}</span>
            </div>
            @if($notificationCount)
              <form method="POST" action="{{ route('notifications.read-all') }}" class="book-notification-read-all-form">
                @csrf
                <button type="submit" class="book-notification-read-all">Atzīmēt visu kā izlasītu</button>
              </form>
            @endif
            @foreach($listingNotifications as $notification)
              <div class="book-notification-item">
                <a class="book-notification-link" href="{{ route('book-listings.index') }}">
                  <span class="book-notification-cover">!</span>
                  <span>
                    <strong>Jauns pieteikums</strong>
                    <small>{{ $notification->data['applicant_name'] }} pieteicās uz “{{ $notification->data['title'] }}”</small>
                  </span>
                </a>
              </div>
            @endforeach
            @foreach($listingStatusNotifications as $notification)
              <div class="book-notification-item">
                <a class="book-notification-link" href="{{ route('book-listings.index') }}">
                  <span class="book-notification-cover">!</span>
                  <span>
                    @if($notification->data['status'] === 'accepted')
                      <strong>Pieteikums pieņemts</strong>
                      <small>Tavs piedāvājums par “{{ $notification->data['title'] }}” ir pieņemts.</small>
                    @elseif(($notification->data['reason'] ?? 'rejected') === 'unavailable')
                      <strong>Grāmata vairs nav pieejama</strong>
                      <small>Pieteikums par “{{ $notification->data['title'] }}” ir noraidīts.</small>
                    @else
                      <strong>Pieteikums noraidīts</strong>
                      <small>Tavs pieteikums par “{{ $notification->data['title'] }}” netika pieņemts.</small>
                    @endif
                  </span>
                </a>
              </div>
            @endforeach
            @foreach($listingMessageNotifications as $notification)
              <div class="book-notification-item">
                <a class="book-notification-link" href="{{ route('book-listings.index') }}">
                  <span class="book-notification-cover">Z</span>
                  <span>
                    <strong>Jauna ziņa par sludinājumu</strong>
                    <small>Tev ir atsūtīta jauna ziņa par “{{ $notification->data['title'] }}”.</small>
                  </span>
                </a>
              </div>
            @endforeach
            @if($latestTimerNotification)
              <div class="book-notification-item">
                <a class="book-notification-link" href="{{ route('reading-challenges.results') }}">
                  <span class="book-notification-cover">T</span>
                  <span>
                    <strong>Laika sesija saglabāta</strong>
                    <small>{{ $latestTimerNotification->data['elapsed_minutes'] }} min, {{ $latestTimerNotification->data['pages_read'] }} lpp</small>
                    <small>Atvērt visu rezultātu kopsavilkumu</small>
                  </span>
                </a>
              </div>
            @endif
            @if($releaseReminders->count())
              <div class="book-notification-heading">
                <strong>Izlaistās grāmatas</strong>
                <span>{{ $releaseReminders->count() }}</span>
              </div>
            @endif
            @foreach($releaseReminders as $reminder)
              <div class="book-notification-item">
                <a class="book-notification-link" href="{{ $reminder->info_link ?: route('booktok.index') }}" target="{{ $reminder->info_link ? '_blank' : '_self' }}" rel="noopener noreferrer">
                  @if($reminder->cover_url)
                    <img class="book-notification-cover" src="{{ $reminder->cover_url }}" alt="{{ $reminder->title }} vāks">
                  @else
                    <span class="book-notification-cover">{{ mb_strtoupper(mb_substr($reminder->title, 0, 1)) }}</span>
                  @endif
                  <span>
                    <strong>{{ $reminder->title }}</strong>
                    <small>{{ $reminder->author ?: 'Autors nav norādīts' }}</small>
                    <small>Izlaista: {{ $reminder->release_date->format('d.m.Y') }}</small>
                  </span>
                </a>
                <form method="POST" action="{{ route('book-release-reminders.destroy', $reminder->google_volume_id) }}" class="book-notification-delete-form">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="book-notification-delete" aria-label="Dzēst paziņojumu par {{ $reminder->title }}" title="Dzēst paziņojumu">×</button>
                </form>
              </div>
            @endforeach
            @if(!$notificationCount)
              <p class="book-notification-empty">Paziņojumu vēl nav.</p>
            @endif
          </div>
        </div>
        <span class="hidden sm:inline text-sm text-body">{{ Auth::user()->name }}</span>
        @if (Auth::user()->isAdmin())
          <a href="{{ route('admin.dashboard') }}" class="hidden sm:inline text-sm text-body hover:text-heading transition-colors">Admin panelis</a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
          @csrf
          <button type="submit" class="text-sm text-body hover:text-heading transition-colors">Log out</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="book-notification-login" aria-label="Ielogoties, lai skatītu paziņojumus">
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a href="{{ route('login') }}" class="hidden sm:inline text-sm text-body hover:text-heading transition-colors">Log in</a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="hidden sm:inline text-sm text-body hover:text-heading transition-colors">Register</a>
        @endif
      @endauth
        @auth
          <a href="{{ route('profile.edit') }}" class="hidden sm:inline text-sm text-body hover:text-heading transition-colors">Profils</a>
        @endauth

      <button id="theme-toggle" type="button" class="theme-toggle-nav" aria-label="Pārslēgt tēmu">
        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
      </button>
    </div>
  </div>
</nav>

<div id="drawer-overlay" class="hidden fixed inset-x-0 bottom-0 z-30 bg-black/20" style="top: 73px;" data-drawer-hide="drawer-navigation" aria-hidden="true"></div>

<aside id="drawer-navigation" class="app-surface-opaque fixed left-0 z-40 w-64 max-w-[calc(100vw-1rem)] p-4 overflow-y-auto transition-transform -translate-x-full bg-neutral-primary border-e border-default" style="top: 73px; height: calc(100vh - 73px);" tabindex="-1" aria-hidden="true" aria-labelledby="drawer-navigation-label">
  <div class="border-b border-default pb-4 flex items-center">
    <a href="{{ url('/') }}" class="flex items-center space-x-2 rtl:space-x-reverse">
      <span class="self-center text-lg font-semibold whitespace-nowrap text-heading">Bookish jaunumi</span>
    </a>
    <button type="button" data-drawer-hide="drawer-navigation" aria-controls="drawer-navigation" class="text-body bg-transparent hover:text-heading hover:bg-neutral-tertiary rounded-base w-9 h-9 absolute top-2.5 end-2.5 flex items-center justify-center">
      <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
      <span class="sr-only">Aizvērt izvēlni</span>
    </button>
  </div>

  <div class="py-5 overflow-y-auto">
    <div class="mb-4 px-2 py-2 rounded-base bg-neutral-tertiary-soft text-sm text-body">
      @auth
        <div class="font-medium text-heading">Ielogojies kā: {{ Auth::user()->name }}</div>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
          @csrf
          <button type="submit" class="text-body hover:text-heading transition-colors">Log out</button>
        </form>
      @else
        <div>Tu neesi ielogojies.</div>
        <div class="mt-2 flex gap-3">
          <a href="{{ route('login') }}" class="text-body hover:text-heading transition-colors">Log in</a>
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="text-body hover:text-heading transition-colors">Register</a>
          @endif
        </div>
      @endauth
    </div>

    <ul class="space-y-2 font-medium">
      @auth
      <li>
        <a href="{{ route('profile.edit') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('profile.*'), 'text-body' => !request()->routeIs('profile.*')]) @if (request()->routeIs('profile.*')) aria-current="page" @endif>
          <span class="ms-1">Profils</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-shelf.show') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('reading-shelf.show'), 'text-body' => !request()->routeIs('reading-shelf.show')]) @if (request()->routeIs('reading-shelf.show')) aria-current="page" @endif>
          <span class="ms-1">Grāmatu plaukts</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-highlights.index') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('reading-highlights.index'), 'text-body' => !request()->routeIs('reading-highlights.index')]) @if (request()->routeIs('reading-highlights.index')) aria-current="page" @endif>
          <span class="ms-1">Highlights</span>
        </a>
      </li>
      @endauth
      <li>
        <a href="{{ url('/#nedelas-gramata') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->is('/'), 'text-body' => !request()->is('/')]) @if (request()->is('/')) aria-current="page" @endif>
          <span class="ms-1">Jaunumi</span>
        </a>
      </li>
      @auth
      <li>
        <a href="{{ route('book-release-reminders.index') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('book-release-reminders.index'), 'text-body' => !request()->routeIs('book-release-reminders.index')]) @if (request()->routeIs('book-release-reminders.index')) aria-current="page" @endif>
          <span class="ms-1">Izdošanas kalendārs</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-challenges.index') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('reading-challenges.index'), 'text-body' => !request()->routeIs('reading-challenges.index')]) @if (request()->routeIs('reading-challenges.index')) aria-current="page" @endif>
          Izaicinājums
        </a>
      </li>
      <li>
        <a href="{{ route('reading-timer.index') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('reading-timer.*'), 'text-body' => !request()->routeIs('reading-timer.*')]) @if (request()->routeIs('reading-timer.*')) aria-current="page" @endif>
          Laika sadaļa
        </a>
      </li>
      @endauth
      <li>
        <a href="{{ route('booktok.index') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('booktok.*'), 'text-body' => !request()->routeIs('booktok.*')]) @if (request()->routeIs('booktok.*')) aria-current="page" @endif>
          <span class="ms-1">BookTok Tops</span>
        </a>
      </li>
      @auth
        @if (Auth::user()->isAdmin())
          <li>
            <a href="{{ route('admin.dashboard') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('admin.*'), 'text-body' => !request()->routeIs('admin.*')]) @if (request()->routeIs('admin.*')) aria-current="page" @endif>
              <span class="ms-1">Admin panelis</span>
            </a>
          </li>
        @endif
      <li>
        <a href="{{ route('book-listings.index') }}" @class(['flex items-center px-2 py-1.5 rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group', 'bg-neutral-tertiary text-fg-brand' => request()->routeIs('book-listings.*') || request()->routeIs('book-exchange.*'), 'text-body' => !request()->routeIs('book-listings.*') && !request()->routeIs('book-exchange.*')]) @if (request()->routeIs('book-listings.*') || request()->routeIs('book-exchange.*')) aria-current="page" @endif>
          <span class="ms-1">Sludinājumi</span>
        </a>
      </li>
      @endauth
    </ul>
  </div>
</aside>
