<x-guest-layout>
    <h1 class="guest-auth-title">Apstiprini paroli</h1>
    <p class="guest-auth-text">Šī ir aizsargāta sadaļa. Lūdzu, ievadi savu paroli, lai turpinātu.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="guest-auth-form">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Parole" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="guest-auth-submit">Apstiprināt</x-primary-button>
    </form>
</x-guest-layout>
