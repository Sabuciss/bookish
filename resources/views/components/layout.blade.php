<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? "bookish" }}</title>
    @include('components.theme-init-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body>
  <x-navigation></x-navigation>

  <main class="p-4 bookish-main {{ request()->routeIs('reading-progress.index') ? 'bookish-main--wide' : '' }}">
    {{ $slot }}
  </main>
</body>
</html>
