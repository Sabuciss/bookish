<x-guest-layout>
    <h1 class="guest-auth-title">Apstiprini e-pastu</h1>
    <p class="guest-auth-text">Paldies par reģistrāciju! Lūdzu, apstiprini savu e-pastu, uzspiežot uz saites, ko tikko nosūtījām. Ja e-pasts nav pienācis, varam to nosūtīt vēlreiz.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="guest-auth-alert guest-auth-alert--success">
            Jauna apstiprinājuma saite ir nosūtīta uz reģistrācijā norādīto e-pastu.
        </div>
    @endif

    @if (session('status') == 'verification-email-failed')
        <div class="guest-auth-alert guest-auth-alert--error" role="alert">
            Konts ir izveidots, bet apstiprinājuma e-pastu neizdevās nosūtīt. Lūdzu, mēģini vēlreiz vēlāk.
        </div>
    @endif

    <div class="guest-auth-form">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="guest-auth-submit">Sūtīt apstiprinājumu vēlreiz</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="guest-auth-switch">
            @csrf
            <button type="submit" class="guest-auth-link-button">Iziet</button>
        </form>
    </div>
</x-guest-layout>
