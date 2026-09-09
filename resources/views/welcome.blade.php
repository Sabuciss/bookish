<x-layout>
    <div class="welcome-container">
        <h1 class="welcome-title">Bookish jaunumi</h1>
        <p>Jaunākās ziņas un ieteikumi par grāmatām.</p>
        <meta name="bookish-authenticated" content="{{ auth()->check() ? '1' : '0' }}">
        <meta name="book-release-reminder-url" content="{{ route('book-release-reminders.store') }}">
        <meta name="book-login-url" content="{{ route('login') }}">

        <div class="welcome-grid">
            <article class="welcome-card welcome-card--wide welcome-card--search">
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

            <section class="welcome-card welcome-card--wide welcome-genres-section">
                <div class="welcome-section-heading">
                    <div>
                        <p class="welcome-eyebrow">Atrodi savu nākamo lasījumu</p>
                        <h2 class="welcome-card-title">Pārlūko pēc žanra</h2>
                    </div>
                    <span class="welcome-section-note">No Google Books</span>
                </div>

                <div class="book-genre-filter" role="group" aria-label="Izvēlēties grāmatu žanru">
                    <button type="button" class="book-genre-filter-button is-active" data-genre-filter="all">Visi žanri</button>
                    <button type="button" class="book-genre-filter-button" data-genre-filter="fantasy">Fantāzija (Fantasy)</button>
                    <button type="button" class="book-genre-filter-button" data-genre-filter="science-fiction">Zinātniskā fantastika (Science Fiction)</button>
                    <button type="button" class="book-genre-filter-button" data-genre-filter="mystery-thriller">Detektīvi un trilleri (Mystery &amp; Thriller)</button>
                    <button type="button" class="book-genre-filter-button" data-genre-filter="romance">Romantika (Romance)</button>
                    <button type="button" class="book-genre-filter-button" data-genre-filter="historical-fiction">Vēsturiskā proza (Historical Fiction)</button>
                    <button type="button" class="book-genre-filter-button" data-genre-filter="horror">Šausmu literatūra (Horror)</button>
                </div>

                <div class="book-genre-sections">
                    <section class="book-genre-section" data-genre="fantasy" data-query="subject:fantasy">
                        <div class="book-genre-heading">
                            <div>
                                <h3>Fantāzija <span>(Fantasy)</span></h3>
                                <p>Stāsti ar maģiju, mītiskām būtnēm un pārdabiskām pasaulēm.</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books">
                            <p class="welcome-card-text">Ielādējam fantāzijas grāmatas...</p>
                        </div>
                    </section>

                    <section class="book-genre-section" data-genre="science-fiction" data-query="subject:science fiction">
                        <div class="book-genre-heading">
                            <div>
                                <h3>Zinātniskā fantastika <span>(Science Fiction)</span></h3>
                                <p>Stāsti par nākotnes zinātni, kosmosa ceļojumiem un attīstītām tehnoloģijām.</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books">
                            <p class="welcome-card-text">Ielādējam zinātniskās fantastikas grāmatas...</p>
                        </div>
                    </section>

                    <section class="book-genre-section" data-genre="mystery-thriller" data-query="subject:mystery">
                        <div class="book-genre-heading">
                            <div>
                                <h3>Detektīvi un trilleri <span>(Mystery &amp; Thriller)</span></h3>
                                <p>Saspringti sižeti par detektīviem, noziegumiem un augstām likmēm.</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books">
                            <p class="welcome-card-text">Ielādējam detektīvus un trillerus...</p>
                        </div>
                    </section>

                    <section class="book-genre-section" data-genre="romance" data-query="subject:romance">
                        <div class="book-genre-heading">
                            <div>
                                <h3>Romantika <span>(Romance)</span></h3>
                                <p>Stāsti, kuros galvenā loma ir mīlestības attiecībām.</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books">
                            <p class="welcome-card-text">Ielādējam romantikas grāmatas...</p>
                        </div>
                    </section>

                    <section class="book-genre-section" data-genre="historical-fiction" data-query="subject:historical fiction">
                        <div class="book-genre-heading">
                            <div>
                                <h3>Vēsturiskā proza <span>(Historical Fiction)</span></h3>
                                <p>Izdomāti stāsti, kas norisinās īstos vēstures periodos.</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books">
                            <p class="welcome-card-text">Ielādējam vēsturiskās prozas grāmatas...</p>
                        </div>
                    </section>

                    <section class="book-genre-section" data-genre="horror" data-query="subject:horror">
                        <div class="book-genre-heading">
                            <div>
                                <h3>Šausmu literatūra <span>(Horror)</span></h3>
                                <p>Tumši stāsti, kas radīti, lai lasītāju nobiedētu vai satricinātu.</p>
                            </div>
                        </div>
                        <div class="book-recommendation-grid book-genre-books">
                            <p class="welcome-card-text">Ielādējam šausmu literatūras grāmatas...</p>
                        </div>
                    </section>
                </div>
            </section>

        </div>
    </div>
</x-layout>