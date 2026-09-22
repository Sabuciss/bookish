const pickers = document.querySelectorAll('[data-listing-book-picker]');

const normalizeCoverUrl = (url) => String(url || '').replace(/^http:\/\//i, 'https://');
const escapeHtml = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

pickers.forEach((picker) => {
    const wanted = picker.dataset.listingBookPicker === 'wanted';
    const prefix = wanted ? 'exchange' : 'listing';
    const queryInput = document.getElementById(`${prefix}-book-search`);
    const status = document.getElementById(`${prefix}-book-search-status`);
    const results = document.getElementById(`${prefix}-book-search-results`);
    const titleInput = document.getElementById(`${prefix}-book-title`);
    const authorInput = document.getElementById(wanted ? 'exchange-book-author' : 'listing-book-author');
    const volumeIdInput = document.getElementById(wanted ? 'exchange-google-volume-id' : 'listing-google-volume-id');
    const coverInput = document.getElementById(wanted ? 'exchange-book-cover-url' : 'listing-book-cover-url');
    const selected = document.getElementById(`${prefix}-selected-book`);
    const selectedCover = document.getElementById(`${prefix}-selected-book-cover`);
    const selectedTitle = document.getElementById(`${prefix}-selected-book-title`);
    const selectedAuthor = document.getElementById(`${prefix}-selected-book-author`);
    const searchCache = new Map();
    let requestId = 0;
    let searchTimer = null;

    if (!queryInput || !status || !results || !titleInput) return;

    const selectBook = (book) => {
        const cover = normalizeCoverUrl(book.cover);
        titleInput.value = book.title;
        if (authorInput) authorInput.value = book.author || '';
        if (volumeIdInput) volumeIdInput.value = book.id || '';
        if (coverInput) coverInput.value = cover;
        if (selectedTitle) selectedTitle.textContent = book.title;
        if (selectedAuthor) selectedAuthor.textContent = book.author || 'Autors nav norādīts';
        if (selected) selected.style.display = 'flex';
        if (selectedCover) {
            selectedCover.hidden = !cover;
            selectedCover.src = cover;
            selectedCover.alt = `${book.title} vāks`;
        }
        results.innerHTML = '';
        status.textContent = `Izvēlēta grāmata: ${book.title}`;
    };

    const renderResults = (books) => {
        results.innerHTML = '';
        status.textContent = books.length
            ? `Atrastas ${books.length} grāmatas. Izvēlies vienu.`
            : 'Grāmatas netika atrastas.';

        books.forEach((book) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rp-book-search-result';
            const cover = normalizeCoverUrl(book.cover);
            button.innerHTML = `${cover ? `<img class="listing-search-cover" src="${escapeHtml(cover)}" alt="">` : '<span class="listing-search-cover listing-search-cover-empty" aria-hidden="true">Nav vāka</span>'}<span><strong>${escapeHtml(book.title)}</strong><span class="rp-book-search-meta">${escapeHtml(book.author || 'Autors nav norādīts')}</span></span>`;
            button.addEventListener('click', () => selectBook(book));
            results.appendChild(button);
        });
    };

    const searchBooks = async () => {
        const query = queryInput.value.trim();
        if (!query) return;

        const cacheKey = query.toLowerCase();
        const currentRequestId = ++requestId;
        status.textContent = 'Meklēju grāmatas...';
        results.innerHTML = '';

        if (searchCache.has(cacheKey)) {
            renderResults(searchCache.get(cacheKey));
            return;
        }

        try {
            const response = await fetch(`/api/google-books/top?q=${encodeURIComponent(query)}&maxResults=5&remote=1`);
            if (!response.ok) throw new Error('Google Books API error');

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
            if (currentRequestId === requestId) renderResults(books);
        } catch {
            status.textContent = 'Neizdevās ielādēt grāmatu datus. Mēģini vēlreiz.';
        }
    };

    queryInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        if (queryInput.value.trim().length < 2) {
            results.innerHTML = '';
            status.textContent = '';
            return;
        }
        searchTimer = setTimeout(searchBooks, 700);
    });

    queryInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchBooks();
        }
    });
});
