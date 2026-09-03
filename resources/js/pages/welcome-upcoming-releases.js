const upcomingAuthorInput = document.getElementById('upcoming-author-input');
const upcomingAuthorSearch = document.getElementById('upcoming-author-search');
const upcomingAuthorResults = document.getElementById('upcoming-author-results');

if (upcomingAuthorInput && upcomingAuthorSearch && upcomingAuthorResults) {
    const UPCOMING_CACHE_TTL_MS = 6 * 60 * 60 * 1000;

    const escapeHtml = (text) => String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

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

    const renderUpcomingItems = (items, authorName) => {
        if (!items.length) {
            upcomingAuthorResults.innerHTML = `<p class="welcome-card-text">Autoram <strong>${escapeHtml(authorName)}</strong> drīzumā iznākošas grāmatas netika atrastas.</p>`;
            return;
        }

        upcomingAuthorResults.innerHTML = items.map((entry) => {
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

    const getUpcomingCacheKey = (authorName) => `upcoming-author-cache-v1-${authorName.toLowerCase()}`;

    const readUpcomingCache = (authorName) => {
        const raw = localStorage.getItem(getUpcomingCacheKey(authorName));

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

    const writeUpcomingCache = (authorName, items) => {
        localStorage.setItem(getUpcomingCacheKey(authorName), JSON.stringify({
            timestamp: Date.now(),
            items,
        }));
    };

    const fetchUpcomingByAuthor = async (authorName) => {
        const aggregated = [];

        for (let page = 0; page < 3; page += 1) {
            const startIndex = page * 20;
            const url = `/api/google-books/top?q=${encodeURIComponent(`inauthor:${authorName}`)}&maxResults=20&startIndex=${startIndex}&orderBy=newest`;
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

    const loadUpcomingAuthorReleases = async () => {
        const authorName = String(upcomingAuthorInput.value || '').trim();

        if (!authorName) {
            upcomingAuthorResults.innerHTML = '<p class="welcome-card-text">Ievadi autora vārdu.</p>';
            return;
        }

        upcomingAuthorResults.innerHTML = '<p class="welcome-card-text">Ielādējam gaidāmos izdevumus...</p>';

        let items = readUpcomingCache(authorName);
        if (!items) {
            items = await fetchUpcomingByAuthor(authorName);
            writeUpcomingCache(authorName, items);
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

        renderUpcomingItems(upcoming, authorName);
    };

    const handleLoad = () => {
        loadUpcomingAuthorReleases().catch(() => {
            upcomingAuthorResults.innerHTML = '<p class="welcome-card-text">Neizdevās ielādēt datus no Google Books API.</p>';
        });
    };

    upcomingAuthorSearch.addEventListener('click', handleLoad);
    upcomingAuthorInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        handleLoad();
    });
}
