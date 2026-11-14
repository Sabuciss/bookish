<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Dzēst kontu
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Pēc konta dzēšanas visi tā dati tiks neatgriezeniski izdzēsti. Pirms dzēšanas saglabā informāciju, kuru vēlies paturēt.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Dzēst kontu</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Vai tiešām vēlies dzēst savu kontu?
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Pēc konta dzēšanas visi tā dati tiks neatgriezeniski izdzēsti. Ievadi savu paroli, lai apstiprinātu dzēšanu.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Parole" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Parole"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Atcelt
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Dzēst kontu
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
