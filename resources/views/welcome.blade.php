<x-layout>
    <div class="welcome-container">
        <h1 class="welcome-title">Bookish jaunumi</h1>
        <p>Jaunākās ziņas un ieteikumi par grāmatām.</p>
        <meta name="bookish-authenticated" content="{{ auth()->check() ? '1' : '0' }}">
        <meta name="book-release-reminder-url" content="{{ route('book-release-reminders.store') }}">
        <meta name="book-login-url" content="{{ route('login') }}">

        <div class="welcome-grid">
            <section class="welcome-card welcome-card--wide">
                <div class="welcome-section-heading">
                    <div>
                        <p class="welcome-eyebrow">Izvēlēts Bookish</p>
                        <h2 class="welcome-card-title">Ieteikumi tev</h2>
                    </div>
                    <span class="welcome-section-note">No Google Books</span>
                </div>
                <div id="book-recommendations" class="book-recommendation-grid">
                    <p class="welcome-card-text">Ielādējam ieteikumus...</p>
                </div>
            </section>

            <section class="welcome-card welcome-card--wide">
                <div class="welcome-section-heading">
                    <div>
                        <p class="welcome-eyebrow">Kalendārs</p>
                        <h2 class="welcome-card-title">Drīzumā iznāks</h2>
                    </div>
                    <span class="welcome-section-note">Tuvākie izdevumi</span>
                </div>
                <div id="upcoming-releases" class="book-recommendation-grid">
                    <p class="welcome-card-text">Ielādējam gaidāmos izdevumus...</p>
                </div>
            </section>

            <article class="welcome-card">
                <h2 class="welcome-card-title">Kas jauns / kas plānojas</h2>
                <p class="welcome-card-text">Meklē pēc autora vai grāmatas nosaukuma un apskati gaidāmos izdevumus (Google Books dati).</p>

                <div class="weekly-top-controls">
                    <input
                        id="upcoming-book-search-input"
                        type="text"
                        class="weekly-genre-select"
                        placeholder="Autors vai grāmatas nosaukums"
                    >
                    <button id="upcoming-book-search" type="button" class="reading-progress-submit">Meklēt</button>
                </div>

                <div id="upcoming-book-search-results" class="weekly-top-books">
                    <p class="welcome-card-text">Ievadi autora vārdu vai grāmatas nosaukumu.</p>
                </div>
            </article>

        </div>
    </div>
</x-layout>