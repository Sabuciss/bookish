const upcomingBookSearchInput = document.getElementById('upcoming-book-search-input');
const upcomingBookSearch = document.getElementById('upcoming-book-search');
const upcomingBookSearchResults = document.getElementById('upcoming-book-search-results');
const bookRecommendations = document.getElementById('book-recommendations');
const upcomingReleases = document.getElementById('upcoming-releases');
const genreSections = document.querySelectorAll('[data-genre]');
const genreFilterButtons = document.querySelectorAll('[data-genre-filter]');

const parsePublishedDate = (rawDate) => {
    const value = String(rawDate || '').trim();

    if (!value) {
        return null;
    }

    if (/^\d{4}$/.test(value)) {
        return new Date(Number(value), 0, 1);
    }

    if (/^\d{4}-\d{2}$/.test(value)) {
        const [year, month] = value.split('-').map(Number);
        return new Date(year, month - 1, 1);
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    return null;
};

const escapeBookHtml = (text) => String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const normalizeBookItems = (items) => (items || []).map((entry) => {
    const info = entry.volumeInfo || {};

    return {
        volumeId: entry.id || '',
        title: info.title || 'Bez nosaukuma',
        author: Array.isArray(info.authors) ? info.authors.join(', ') : 'Autors nav norādīts',
        thumbnail: info.imageLinks?.thumbnail || info.imageLinks?.smallThumbnail || '',
        publishedDate: info.publishedDate || '',
        infoLink: info.infoLink || info.previewLink || '',
    };
});

const renderBookCards = (container, items, emptyText, allowReminders = false) => {
    if (!container) {
        return;
    }

    if (!items.length) {
        container.innerHTML = `<p class="welcome-card-text">${emptyText}</p>`;
        return;
    }

    container.innerHTML = items.map((book) => {
        const link = book.volumeId ? `/books/${encodeURIComponent(book.volumeId)}` : book.infoLink;
        const cover = book.thumbnail
            ? `<img src="${escapeBookHtml(book.thumbnail)}" alt="${escapeBookHtml(book.title)} vāks">`
            : '<div class="book-recommendation-cover book-recommendation-cover--empty">Nav vāka</div>';

        const reminderButton = allowReminders && book.parsedDate && book.volumeId
            ? `<button type="button" class="book-reminder-button" data-volume-id="${escapeBookHtml(book.volumeId)}" data-title="${escapeBookHtml(book.title)}" data-author="${escapeBookHtml(book.author)}" data-release-date="${book.parsedDate.toISOString().slice(0, 10)}" data-info-link="${escapeBookHtml(book.infoLink)}" data-cover-url="${escapeBookHtml(book.thumbnail)}">Atgādināt</button>`
            : '';

        return `<article class="book-recommendation-card">${link ? `<a href="${escapeBookHtml(link)}">${cover}</a>` : cover}<div><h3>${link ? `<a href="${escapeBookHtml(link)}">${escapeBookHtml(book.title)}</a>` : escapeBookHtml(book.title)}</h3><p>${escapeBookHtml(book.author)}</p>${book.publishedDate ? `<span>${escapeBookHtml(book.publishedDate)}</span>` : ''}${reminderButton}</div></article>`;
    }).join('');
};

const fetchBookResults = async (query, orderBy = 'relevance') => {
    const url = `/api/google-books/top?q=${encodeURIComponent(query)}&maxResults=40&orderBy=${orderBy}`;
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error('Google Books API error');
    }

    const data = await response.json();
    return normalizeBookItems(data.items || []);
};

const loadBookishHighlights = async () => {
    const [recommendationsResult, upcomingResult] = await Promise.allSettled([
        fetchBookResults('subject:fiction', 'relevance'),
        fetchBookResults(`subject:fiction publishedDate:${new Date().getFullYear()}`, 'newest'),
    ]);

    if (recommendationsResult.status === 'fulfilled') {
        renderBookCards(bookRecommendations, recommendationsResult.value.slice(0, 6), 'Ieteikumus neizdevās atrast.');
    } else {
        renderBookCards(bookRecommendations, [], 'Google Books ieteikumi īslaicīgi nav pieejami.');
    }

    if (upcomingResult.status === 'fulfilled') {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const upcoming = upcomingResult.value
            .map((book) => ({ ...book, parsedDate: parsePublishedDate(book.publishedDate) }))
            .filter((book) => book.parsedDate && book.parsedDate >= today)
            .sort((first, second) => first.parsedDate - second.parsedDate)
            .slice(0, 6);

        renderBookCards(upcomingReleases, upcoming, 'Tuvākie izdevumi nav atrasti.', true);
    } else {
        renderBookCards(upcomingReleases, [], 'Google Books izdošanas dati īslaicīgi nav pieejami.');
    }
};

const loadGenreSections = async () => {
    if (!genreSections.length) {
        return;
    }

    await Promise.all([...genreSections].map(async (section) => {
        const genreBooks = section.querySelector('.book-genre-books');

        try {
            const books = await fetchBookResults(section.dataset.query, 'relevance');
            renderBookCards(genreBooks, books.slice(0, 6), 'Šī žanra grāmatas neizdevās atrast.');
        } catch {
            renderBookCards(genreBooks, [], 'Google Books žanra dati īslaicīgi nav pieejami.');
        }
    }));
};

genreFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const selectedGenre = button.dataset.genreFilter;

        genreFilterButtons.forEach((filterButton) => {
            filterButton.classList.toggle('is-active', filterButton === button);
        });

        genreSections.forEach((section) => {
            section.hidden = selectedGenre !== 'all' && section.dataset.genre !== selectedGenre;
        });
    });
});

if (bookRecommendations || upcomingReleases) {
    loadBookishHighlights();
}

loadGenreSections();

const reminderToken = document.querySelector('meta[name="csrf-token"]')?.content;
const reminderUrl = document.querySelector('meta[name="book-release-reminder-url"]')?.content;
const loginUrl = document.querySelector('meta[name="book-login-url"]')?.content;
const isAuthenticated = document.querySelector('meta[name="bookish-authenticated"]')?.content === '1';

upcomingReleases?.addEventListener('click', async (event) => {
    const button = event.target.closest('.book-reminder-button');

    if (!button) {
        return;
    }

    if (!isAuthenticated) {
        window.location.href = loginUrl;
        return;
    }

    button.disabled = true;
    try {
        const response = await fetch(reminderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': reminderToken,
            },
            body: JSON.stringify({
                google_volume_id: button.dataset.volumeId,
                title: button.dataset.title,
                author: button.dataset.author,
                release_date: button.dataset.releaseDate,
                info_link: button.dataset.infoLink || null,
                cover_url: button.dataset.coverUrl || null,
            }),
        });

        if (!response.ok) {
            throw new Error('Reminder could not be saved');
        }

        button.textContent = 'Paziņojums iestatīts';
        button.classList.add('book-reminder-button--saved');
    } catch {
        button.disabled = false;
        button.textContent = 'Mēģināt vēlreiz';
    }
});

if (upcomingBookSearchInput && upcomingBookSearch && upcomingBookSearchResults) {
    const UPCOMING_CACHE_TTL_MS = 6 * 60 * 60 * 1000;

    const escapeHtml = (text) => String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const normalizeUpcomingItems = (items) => (items || []).map((entry) => {
        const info = entry.volumeInfo || {};

        return {
            volumeId: entry.id || info.id || '',
            title: info.title || 'Bez nosaukuma',
            author: Array.isArray(info.authors) ? info.authors.join(', ') : 'Autors nav norādīts',
            thumbnail: info.imageLinks?.thumbnail || info.imageLinks?.smallThumbnail || '',
            publishedDate: info.publishedDate || '',
            previewLink: info.previewLink || '',
            infoLink: info.infoLink || '',
        };
    });

    const renderUpcomingItems = (items, searchTerm) => {
        if (!items.length) {
            upcomingBookSearchResults.innerHTML = `<p class="welcome-card-text">Meklējumam <strong>${escapeHtml(searchTerm)}</strong> drīzumā iznākošas grāmatas netika atrastas.</p>`;
            return;
        }

        upcomingBookSearchResults.innerHTML = items.map((entry) => {
            const internalLink = entry.volumeId ? `/books/${encodeURIComponent(entry.volumeId)}` : '';
            const externalLink = entry.infoLink || entry.previewLink;
            const link = internalLink || externalLink;
            const target = internalLink ? '_self' : '_blank';
            const titleHtml = link
                ? `<a class="weekly-top-title-link" href="${escapeHtml(link)}" target="${target}" rel="noopener noreferrer">${escapeHtml(entry.title)}</a>`
                : escapeHtml(entry.title);
            const coverHtml = entry.thumbnail
                ? (link
                    ? `<a href="${escapeHtml(link)}" target="${target}" rel="noopener noreferrer"><img class="weekly-top-cover" src="${escapeHtml(entry.thumbnail)}" alt="${escapeHtml(entry.title)} vāks"></a>`
                    : `<img class="weekly-top-cover" src="${escapeHtml(entry.thumbnail)}" alt="${escapeHtml(entry.title)} vāks">`)
                : '<div class="weekly-top-cover weekly-top-cover--empty">Nav vāka</div>';

            return `<div class="weekly-top-item">${coverHtml}<div class="weekly-top-content"><p class="welcome-card-text"><strong>${titleHtml}</strong></p><p class="welcome-card-text weekly-top-meta">Autors: ${escapeHtml(entry.author)} · Izdošana: ${escapeHtml(entry.publishedDate || 'Nav norādīts')}</p></div></div>`;
        }).join('');
    };

    const getUpcomingCacheKey = (searchTerm) => `upcoming-book-search-cache-v1-${searchTerm.toLowerCase()}`;

    const readUpcomingCache = (searchTerm) => {
        const raw = localStorage.getItem(getUpcomingCacheKey(searchTerm));

        if (!raw) {
            return null;
        }

        try {
            const parsed = JSON.parse(raw);
            if (!parsed?.timestamp || !Array.isArray(parsed?.items)) {
                return null;
            }

            if (Date.now() - parsed.timestamp > UPCOMING_CACHE_TTL_MS) {
                return null;
            }

            return parsed.items;
        } catch {
            return null;
        }
    };

    const writeUpcomingCache = (searchTerm, items) => {
        localStorage.setItem(getUpcomingCacheKey(searchTerm), JSON.stringify({
            timestamp: Date.now(),
            items,
        }));
    };

    const fetchUpcomingBooks = async (searchTerm) => {
        const aggregated = [];

        for (let page = 0; page < 3; page += 1) {
            const startIndex = page * 20;
            const url = `/api/google-books/top?q=${encodeURIComponent(searchTerm)}&maxResults=20&startIndex=${startIndex}&orderBy=newest`;
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Google Books API error');
            }

            const data = await response.json();
            const pageItems = normalizeUpcomingItems(data.items || []);

            if (!pageItems.length) {
                break;
            }

            aggregated.push(...pageItems);
        }

        return aggregated;
    };

    const loadUpcomingReleases = async () => {
        const searchTerm = String(upcomingBookSearchInput.value || '').trim();

        if (!searchTerm) {
            upcomingBookSearchResults.innerHTML = '<p class="welcome-card-text">Ievadi autora vārdu vai grāmatas nosaukumu.</p>';
            return;
        }

        upcomingBookSearchResults.innerHTML = '<p class="welcome-card-text">Ielādējam gaidāmos izdevumus...</p>';

        let items = readUpcomingCache(searchTerm);
        if (!items) {
            items = await fetchUpcomingBooks(searchTerm);
            writeUpcomingCache(searchTerm, items);
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const upcoming = items
            .map((entry) => ({
                ...entry,
                parsedDate: parsePublishedDate(entry.publishedDate),
            }))
            .filter((entry) => entry.parsedDate && entry.parsedDate >= today)
            .sort((a, b) => a.parsedDate - b.parsedDate)
            .slice(0, 10);

        renderUpcomingItems(upcoming, searchTerm);
    };

    const handleLoad = () => {
        loadUpcomingReleases().catch(() => {
            upcomingBookSearchResults.innerHTML = '<p class="welcome-card-text">Neizdevās ielādēt datus no Google Books API.</p>';
        });
    };

    upcomingBookSearch.addEventListener('click', handleLoad);
    upcomingBookSearchInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        handleLoad();
    });
}
