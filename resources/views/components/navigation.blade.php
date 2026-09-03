<nav class="app-surface-opaque bg-neutral-primary fixed w-full z-30 top-0 start-0 border-b border-default">
  <div class="max-w-screen-xl flex items-center justify-between mx-auto p-4">
    <div class="flex items-center gap-2">
      <button type="button" data-drawer-target="drawer-navigation" data-drawer-show="drawer-navigation" aria-controls="drawer-navigation" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-body rounded-base hover:bg-neutral-secondary-soft hover:text-heading focus:outline-none focus:ring-2 focus:ring-neutral-tertiary">
        <span class="sr-only">Atvērt navigāciju</span>
        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/></svg>
      </button>
      <a href="{{ url('/') }}" class="flex items-center space-x-2 rtl:space-x-reverse">
        <span class="self-center text-xl text-heading font-semibold whitespace-nowrap">Bookish jaunumi</span>
      </a>
    </div>

    <div class="flex items-center gap-3">
      @auth
        <span class="hidden sm:inline text-sm text-body">{{ Auth::user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
          @csrf
          <button type="submit" class="text-sm text-body hover:text-heading transition-colors">Log out</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="hidden sm:inline text-sm text-body hover:text-heading transition-colors">Log in</a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="hidden sm:inline text-sm text-body hover:text-heading transition-colors">Register</a>
        @endif
      @endauth

      <button id="theme-toggle" type="button" class="theme-toggle-nav" aria-label="Pārslēgt tēmu">
        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
      </button>
    </div>
  </div>
</nav>

<div id="drawer-overlay" class="hidden fixed inset-x-0 bottom-0 z-30 bg-black/20" style="top: 73px;" data-drawer-hide="drawer-navigation" aria-hidden="true"></div>

<aside id="drawer-navigation" class="app-surface-opaque fixed left-0 z-40 w-64 p-4 overflow-y-auto transition-transform -translate-x-full bg-neutral-primary border-e border-default" style="top: 73px; height: calc(100vh - 73px);" tabindex="-1" aria-labelledby="drawer-navigation-label">
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
      <li>
        <a href="{{ route('reading-shelf.show') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Grāmatu plaukts</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-progress.index') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Pievienot progresu</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-highlights.index') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Highlights apskate</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-highlights.create') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Izveidot highlight</span>
        </a>
      </li>
      <li>
        <a href="{{ url('/#nedelas-gramata') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Jaunumi</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-timer.index') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Laika sadaļa</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-challenges.index') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Izaicinājums</span>
        </a>
      </li>
      <li>
        <a href="{{ route('reading-challenges.results') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">Laika rezultāti</span>
        </a>
      </li>
      <li>
        <a href="{{ route('booktok.index') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-neutral-tertiary hover:text-fg-brand group">
          <span class="ms-1">BookTok Tops</span>
        </a>
      </li>
    </ul>
  </div>
</aside>
