<x-layout>
    <div class="welcome-container">
        <h1 class="welcome-title">Bookish jaunumi</h1>
        <p>Jaunākās ziņas un ieteikumi par grāmatām.</p>

        <div class="welcome-grid">
            <article class="welcome-card">
                <h2 class="welcome-card-title">Kas jauns / kas plānojas</h2>
                <p class="welcome-card-text">Ievadi autoru un apskati, kuras viņa grāmatas drīzumā plānojas iznākt (Google Books dati).</p>

                <div class="weekly-top-controls">
                    <input
                        id="upcoming-author-input"
                        type="text"
                        class="weekly-genre-select"
                        placeholder="Autors (piem., Sarah J. Maas)"
                    >
                    <button id="upcoming-author-search" type="button" class="reading-progress-submit">Meklēt</button>
                </div>

                <div id="upcoming-author-results" class="weekly-top-books">
                    <p class="welcome-card-text">Ievadi autora vārdu, lai ielādētu gaidāmos izdevumus.</p>
                </div>
            </article>

        </div>
    </div>
</x-layout>