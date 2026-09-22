const picker = document.querySelector('[data-listing-book-picker]');

if (picker) {
    const queryInput = document.getElementById('listing-book-search');
    const status = document.getElementById('listing-book-search-status');
    const results = document.getElementById('listing-book-search-results');
    const titleInput = document.getElementById('listing-book-title');
    const authorInput = document.getElementById('listing-book-author');
    const volumeIdInput = document.getElementById('listing-google-volume-id');
    const coverInput = document.getElementById('listing-book-cover-url');
    const selected = document.getElementById('listing-selected-book');
    const selectedCover = document.getElementById('listing-selected-book-cover');
    const selectedTitle = document.getElementById('listing-selected-book-title');
    const selectedAuthor = document.getElementById('listing-selected-book-author');
    let requestId = 0;
    let searchTimer = null;
    const searchCache = new Map();

    const normalizeCoverUrl = (url) => String(url || '').replace(/^http:\/\//i, 'https://');

    const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const selectBook = (book) => {
        titleInput.value = book.title;
        authorInput.value = book.author;
        volumeIdInput.value = book.id;
        coverInput.value = normalizeCoverUrl(book.cover);
        selectedTitle.textContent = book.title;
        selectedAuthor.textContent = book.author || 'Autors nav norādīts';
        selected.style.display = 'flex';
        selectedCover.hidden = !book.cover;
        selectedCover.src = normalizeCoverUrl(book.cover);
        selectedCover.alt = `${book.title} vāks`;
        results.innerHTML = '';
        status.textContent = `Izvēlēta grāmata: ${book.title}`;
    };

    const renderResults = (books) => {
        results.innerHTML = '';

        if (!books.length) {
            status.textContent = 'Grāmatas netika atrastas.';
            return;
        }

        status.textContent = `Atrastas ${books.length} grāmatas. Izvēlies vienu.`;

        books.forEach((book) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rp-book-search-result';
            button.innerHTML = `${book.cover ? `<img class="listing-search-cover" src="${escapeHtml(book.cover)}" alt="">` : '<span class="listing-search-cover listing-search-cover-empty" aria-hidden="true">Nav vāka</span>'}<span><strong>${escapeHtml(book.title)}</strong><span class="rp-book-search-meta">${escapeHtml(book.author || 'Autors nav norādīts')}</span></span>`;
            button.addEventListener('click', () => selectBook(book));
            results.appendChild(button);
        });
    };

    const searchBooks = async () => {
        const query = queryInput.value.trim();
        if (!query) {
            status.textContent = 'Ievadi grāmatas nosaukumu vai autora vārdu.';
            results.innerHTML = '';
            return;
        }

        const currentRequestId = ++requestId;
        status.textContent = 'Meklēju grāmatas...';
        results.innerHTML = '';

        try {
            const cacheKey = query.toLowerCase();
            if (searchCache.has(cacheKey)) {
                renderResults(searchCache.get(cacheKey));
                return;
            }

            const response = await fetch(`/api/google-books/top?q=${encodeURIComponent(query)}&maxResults=5&remote=1`);
            if (!response.ok) {
                throw new Error('Google Books API error');
            }

            const payload = await response.json();
            const books = (payload.items || []).map((item) => {
                const info = item.volumeInfo || {};
                return {
                    id: item.id || '',
                    title: info.title || 'Bez nosaukuma',
                    author: Array.isArray(info.authors) ? info.authors.join(', ') : '',
                    cover: normalizeCoverUrl(info.imageLinks?.thumbnail || info.imageLinks?.smallThumbnail || ''),
                };
            });

            searchCache.set(cacheKey, books);

            if (currentRequestId === requestId) {
                renderResults(books);
            }
        } catch {
            status.textContent = 'Neizdevās ielādēt grāmatu datus. Mēģini vēlreiz.';
        }
    };

    queryInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchBooks();
        }
    });

    queryInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        titleInput.value = '';

        if (queryInput.value.trim().length < 2) {
            results.innerHTML = '';
            status.textContent = '';
            return;
        }

        searchTimer = window.setTimeout(searchBooks, 700);
    });

    queryInput.addEventListener('blur', () => {
        if (queryInput.value.trim() && !titleInput.value) {
            searchBooks();
        }
    });
}
