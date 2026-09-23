<x-layout>
    <div class="profile-page">
        <header class="profile-page-heading">
            <p class="welcome-eyebrow">Bookish lasītāja profils</p>
            <h1>Tavs profils</h1>
            <p>Pārvaldi kontu un apskati savu lasīšanas ceļu vienuviet.</p>
        </header>

            <section class="profile-statistics uiverse-container">
                <div class="profile-statistics-heading">
                    <div>
                        <p class="welcome-eyebrow">Tava lasīšana</p>
                        <h2 class="uiverse-heading">Lasīšanas statistika</h2>
                    </div>
                    <a href="{{ route('reading-shelf.show') }}" class="profile-statistics-link">Atvērt plauktu</a>
                </div>

                <div class="profile-statistics-grid">
                    <div class="profile-statistic">
                        <strong>{{ number_format($profileStats['booksRead'], 0, ',', ' ') }}</strong>
                        <span>Izlasītas grāmatas</span>
                    </div>
                    <div class="profile-statistic">
                        <strong>{{ number_format($profileStats['pagesRead'], 0, ',', ' ') }}</strong>
                        <span>Izlasītas lappuses</span>
                    </div>
                    <div class="profile-statistic">
                        <strong>{{ number_format($profileStats['booksOnShelf'], 0, ',', ' ') }}</strong>
                        <span>Grāmatas plauktā</span>
                    </div>
                    <div class="profile-statistic">
                        <strong>{{ number_format($profileStats['booksInProgress'], 0, ',', ' ') }}</strong>
                        <span>Pašlaik lasu</span>
                    </div>
                    <div class="profile-statistic">
                        <strong>{{ number_format($profileStats['favoriteAuthors'], 0, ',', ' ') }}</strong>
                        <span>Iecienītie autori</span>
                    </div>
                </div>

                <div class="profile-statistics-shelf">
                    <span>Plauktā:</span>
                    <span>Want to Read: {{ $profileStats['booksWantToRead'] }}</span>
                    <span>In Progress: {{ $profileStats['booksInProgress'] }}</span>
                    <span>Read: {{ $profileStats['booksRead'] }}</span>
                </div>
            </section>

            <div class="profile-settings-grid">
                <section class="profile-card uiverse-container">
                    @include('profile.partials.update-profile-information-form')
                </section>

                <section class="profile-card uiverse-container">
                    @include('profile.partials.update-password-form')
                </section>

                <section class="profile-card profile-card-danger uiverse-container">
                    @include('profile.partials.delete-user-form')
                </section>
            </div>
        </div>
</x-layout>
