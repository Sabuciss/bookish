<x-guest-layout>
    <h1 class="guest-auth-title">Sveika atpakaļ!</h1>
    <p class="guest-auth-text">Ielogojies, lai turpinātu savu lasīšanas ceļu.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="guest-auth-form">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-pasts" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="tavs@epasts.lv" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Parole" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="guest-auth-row">
            <label for="remember_me" class="guest-auth-check">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Atcerēties mani</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Aizmirsi paroli?</a>
            @endif
        </div>

        <x-primary-button class="guest-auth-submit">Ielogoties</x-primary-button>

        @if (Route::has('register'))
            <p class="guest-auth-switch">Vēl nav konta? <a href="{{ route('register') }}">Reģistrēties</a></p>
        @endif
    </form>
</x-guest-layout>
