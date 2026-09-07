<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Admin pārskats') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Pārraugi lietotājus un kopienas saturu vienuviet.') }}
                </p>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ __('Administrators') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Lietotāji', 'value' => $stats['users']],
                    ['label' => 'Izcēlumi', 'value' => $stats['highlights']],
                    ['label' => 'Progresa ieraksti', 'value' => $stats['progressEntries']],
                    ['label' => 'Izaicinājumi', 'value' => $stats['challenges']],
                ] as $stat)
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($stat['label']) }}</p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Visi lietotāji') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($users as $user)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-xs font-medium {{ $user->isAdmin() ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $user->isAdmin() ? __('Admin') : __('Lietotājs') }}
                                    </span>
                                    @if (! Auth::user()->is($user))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Vai tiešām dzēst šo lietotāju? Viņa dati tiks dzēsti.') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                {{ __('Noņemt') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Tu') }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-gray-500">{{ __('Nav lietotāju.') }}</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Pēdējie izcēlumi') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($highlights as $highlight)
                            <div class="flex items-start justify-between gap-4 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $highlight->book_title }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{{ $highlight->quote_text }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $highlight->user?->name ?? __('Nezināms lietotājs') }} · {{ $highlight->is_public ? __('Publisks') : __('Privāts') }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('admin.highlights.destroy', $highlight) }}" onsubmit="return confirm('{{ __('Vai tiešām dzēst šo izcēlumu?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="shrink-0 text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        {{ __('Dzēst') }}
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-gray-500">{{ __('Nav izcēlumu.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
