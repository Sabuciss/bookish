<x-guest-layout>
    <h1 class="guest-auth-title">Izveido savu plauktu</h1>
    <p class="guest-auth-text">Seko izlasītajām grāmatām, saglabā citātus un piedalies izaicinājumos.</p>

    <form method="POST" action="{{ route('register') }}" class="guest-auth-form">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Vārds" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-pasts" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="tavs@epasts.lv" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Parole" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Apstiprini paroli" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="guest-auth-submit">Reģistrēties</x-primary-button>

        <p class="guest-auth-switch">Jau ir konts? <a href="{{ route('login') }}">Ielogoties</a></p>
    </form>
</x-guest-layout>
