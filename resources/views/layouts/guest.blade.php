<!DOCTYPE html>
<html lang="lv-LV">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bookish') }}</title>

        @include('components.theme-init-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bm antialiased">
        <div class="guest-auth-page">
            <a href="{{ url('/') }}" class="guest-auth-brand" aria-label="Bookish sākumlapa">
                <img src="{{ asset('images/logo3.png') }}" alt="Bookish" class="bookish-brand-logo">
            </a>

            <div class="guest-auth-card">
                <p class="guest-auth-eyebrow"><span></span> Tava lasīšanas dienasgrāmata</p>
                {{ $slot }}
            </div>

            <p class="guest-auth-footer">
                <a href="{{ route('privacy-policy') }}">Privātuma politika</a> ·
                <a href="{{ route('terms') }}">Lietošanas noteikumi</a>
            </p>
        </div>
    </body>
</html>
