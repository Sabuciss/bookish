<x-guest-layout>
    <h1 class="guest-auth-title">Aizmirsi paroli?</h1>
    <p class="guest-auth-text">Nekas traks! Ieraksti savu e-pastu, un mēs atsūtīsim saiti jaunas paroles izveidei.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="guest-auth-form">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-pasts" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="tavs@epasts.lv" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="guest-auth-submit">Sūtīt paroles atjaunošanas saiti</x-primary-button>

        <p class="guest-auth-switch"><a href="{{ route('login') }}">← Atpakaļ uz ielogošanos</a></p>
    </form>
</x-guest-layout>
