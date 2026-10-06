const missingCovers = document.querySelectorAll('[data-book-cover-fallback]');

const findCover = async (placeholder) => {
    const title = placeholder.dataset.bookTitle || '';
    const author = placeholder.dataset.bookAuthor || '';
    const queries = [
        `${title} ${author}`,
        `intitle:${title}`,
    ];

    for (const query of queries) {
        const params = new URLSearchParams({ q: query, maxResults: '5', remote: '1' });
        const response = await fetch(`/api/google-books/top?${params}`);

        if (!response.ok) {
            continue;
        }

        const data = await response.json();
        const books = data.items || [];
        const matchingBook = books.find((book) => {
            const volumeInfo = book.volumeInfo || {};
            const authors = (volumeInfo.authors || []).join(' ').toLowerCase();
            return (volumeInfo.imageLinks?.thumbnail || volumeInfo.imageLinks?.smallThumbnail)
                && authors.includes(author.toLowerCase());
        });
        const bookWithCover = matchingBook || books.find((book) => (
            book.volumeInfo?.imageLinks?.thumbnail || book.volumeInfo?.imageLinks?.smallThumbnail
        ));
        const volumeInfo = bookWithCover?.volumeInfo;
        const thumbnail = volumeInfo?.imageLinks?.thumbnail || volumeInfo?.imageLinks?.smallThumbnail;

        if (thumbnail) {
            const link = document.createElement('a');
            link.href = placeholder.dataset.bookUrl;

            const image = document.createElement('img');
            image.src = thumbnail.replace(/^http:\/\//i, 'https://');
            image.alt = `${title} vāks`;
            image.loading = 'lazy';
            image.width = 44;
            image.height = 66;
            image.style.objectFit = 'cover';
            image.style.borderRadius = '6px';
            link.append(image);
            placeholder.replaceWith(link);
            return;
        }
    }
};

const loadCover = (placeholder) => {
    if (placeholder.dataset.loading === 'true') {
        return;
    }

    placeholder.dataset.loading = 'true';
    findCover(placeholder).catch(() => {
        placeholder.dataset.loading = 'false';
    });
};

if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                observer.unobserve(entry.target);
                loadCover(entry.target);
            }
        });
    }, { rootMargin: '200px' });

    missingCovers.forEach((placeholder) => observer.observe(placeholder));
} else {
    missingCovers.forEach(loadCover);
}