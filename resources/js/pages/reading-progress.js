const root = document.getElementById('reading-progress-page');

if (root) {
    const latestPagesByBook = JSON.parse(root.dataset.latestPages || '{}');
    const searchBtn = document.getElementById('book-search-btn');
    const queryInput = document.getElementById('book-search-query');
    const statusEl = document.getElementById('book-search-status');
    const resultsEl = document.getElementById('book-search-results');
    const volumeIdInput = document.getElementById('google_volume_id');
    const coverInput = document.getElementById('book_cover_url');
    const titleInput = document.getElementById('book_title');
    const statusInput = document.getElementById('reading_status');
    const statusTags = Array.from(document.querySelectorAll('.status-tag'));
    const totalPagesInput = document.getElementById('total_pages');
    const pagesReadInput = document.getElementById('pages_read');
    const previewWrap = document.getElementById('selected-book-preview');
    const previewImage = document.getElementById('selected-book-image');
    const previewTitle = document.getElementById('selected-book-title');
    const previewPages = document.getElementById('selected-book-pages');
    const changeBookBtn = document.getElementById('change-book-btn');
    const loadBookButtons = Array.from(document.querySelectorAll('.js-load-book'));
    const datePicker = document.getElementById('reading-date-picker');
    const dateDisplay = document.getElementById('reading-date-display');
    const dateOpenButton = document.getElementById('reading-date-open');
    let searchTimer = null;
    let searchRequestId = 0;

    const formatEuropeanDate = (isoDate) => {
        const [year, month, day] = String(isoDate || '').split('-');
        return year && month && day ? `${day}.${month}.${year}` : '';
    };

    datePicker?.addEventListener('change', () => {
        if (dateDisplay) {
            dateDisplay.value = formatEuropeanDate(datePicker.value);
        }
    });

    dateOpenButton?.addEventListener('click', () => {
        if (typeof datePicker?.showPicker === 'function') {
            datePicker.showPicker();
        } else {
            datePicker?.focus();
            datePicker?.click();
        }
    });

    dateDisplay?.addEventListener('click', () => dateOpenButton?.click());

    const livePreview       = document.getElementById('live-progress-preview');
    const liveBar            = document.getElementById('live-progress-bar');
    const livePct            = document.getElementById('live-progress-pct');
    const livePagesLeft      = document.getElementById('live-progress-pages');
    const liveStatusBadge    = document.getElementById('live-progress-status');

    const statusLabels = {
        want_to_read: { text: ' Want to Read', cls: 'status-want' },
        in_progress:  { text: ' In Progress',  cls: 'status-prog' },
        read:         { text: ' Read',          cls: 'status-read' },
    };

    const updateLivePreview = () => {
        const pagesRead  = Number(pagesReadInput?.value  || 0);
        const totalPages = Number(totalPagesInput?.value || 0);

        if (!livePreview) return;

        if (pagesRead <= 0 && totalPages <= 0) {
            livePreview.style.display = 'none';
            return;
        }

        livePreview.style.display = 'block';

        let pct = 0;
        if (totalPages > 0) {
            pct = Math.min(100, Math.round((pagesRead / totalPages) * 100));
        }

        if (liveBar) {
            liveBar.style.width = pct + '%';
            liveBar.style.background = pct >= 100
                ? 'linear-gradient(90deg, #15803d, #22c55e)'
                : 'linear-gradient(90deg, #8E2DE2, #4A00E0)';
        }

        if (livePct) {
            livePct.textContent = totalPages > 0 ? pct + '%' : pagesRead + ' lpp';
        }

        if (livePagesLeft) {
            if (totalPages > 0 && pct < 100) {
                const left = totalPages - pagesRead;
                livePagesLeft.textContent = left > 0 ? `atliēk ${left} lpp` : '';
            } else {
                livePagesLeft.textContent = '';
            }
        }

        const currentStatus = statusInput?.value || 'in_progress';
        if (liveStatusBadge && statusLabels[currentStatus]) {
            const { text, cls } = statusLabels[currentStatus];
            liveStatusBadge.textContent = text;
            liveStatusBadge.className = 'live-progress-status-badge ' + cls;
        }
    };

    const normalizeBookKey = (title) => (title || '').trim().toLowerCase();

    const syncStatusTags = () => {
        if (!statusInput || !statusTags.length) {
            return;
        }

        statusTags.forEach((tag) => {
            tag.classList.toggle('selected', tag.dataset.status === statusInput.value);
        });
    };

    const setStatus = (status) => {
        if (!statusInput) {
            return;
        }

        statusInput.value = status;
        syncStatusTags();
    };

    const syncStatusWithPages = () => {
        if (!statusInput || !pagesReadInput || !totalPagesInput) {
            return;
        }

        const pagesRead = Number(pagesReadInput.value || 0);
        const totalPages = Number(totalPagesInput.value || 0);

        if (totalPages > 0 && pagesRead >= totalPages) {
            setStatus('read');
            updateLivePreview();
            return;
        }

        if (pagesRead <= 0) {
            setStatus('want_to_read');
            updateLivePreview();
            return;
        }

        setStatus('in_progress');
        updateLivePreview();
    };

    const showSelectedPreview = (book) => {
        if (!previewWrap || !previewTitle || !previewPages || !previewImage) {
            return;
        }

        previewWrap.style.display = 'block';
        previewTitle.textContent = book.title || '';
        previewPages.textContent = book.pageCount
            ? `Google Books: ${book.pageCount} lpp`
            : 'Google Books lpp nav norādītas';

        if (book.thumbnail) {
            previewImage.style.display = 'block';
            previewImage.src = book.thumbnail;
        } else {
            previewImage.style.display = 'none';
            previewImage.src = '';
        }
    };

    const applyBookSelection = (book, selectedStatus = null) => {
        if (!titleInput || !volumeIdInput || !coverInput || !totalPagesInput || !pagesReadInput) {
            return;
        }

        const key = book.id || normalizeBookKey(book.title);
        const currentRead = latestPagesByBook[key] || 0;

        titleInput.value = book.title || '';
        if (queryInput) {
            queryInput.value = book.title || '';
        }
        volumeIdInput.value = book.id || '';
        coverInput.value = book.thumbnail || '';

        if (book.pageCount) {
            totalPagesInput.value = String(book.pageCount);
        }

        if (!pagesReadInput.value || Number(pagesReadInput.value) < currentRead) {
            pagesReadInput.value = currentRead > 0 ? String(currentRead) : pagesReadInput.value;
        }

        if (selectedStatus && statusInput) {
            setStatus(selectedStatus);
        }

        syncStatusWithPages();
        showSelectedPreview(book);

        if (resultsEl) {
            resultsEl.innerHTML = '';
        }

        if (statusEl) {
            statusEl.textContent = `Grāmata "${book.title || 'Bez nosaukuma'}" pievienota formai.`;
        }
    };

    const loadExistingBook = (button) => {
        const { dataset } = button;

        applyBookSelection({
            id: dataset.googleVolumeId || '',
            title: dataset.bookTitle || '',
            pageCount: dataset.totalPages ? Number(dataset.totalPages) : null,
            thumbnail: dataset.bookCoverUrl || '',
        }, dataset.readingStatus || 'in_progress');

        if (pagesReadInput) {
            pagesReadInput.value = dataset.pagesRead || '0';
        }

        if (statusEl) {
            statusEl.textContent = 'Esošās grāmatas dati ielādēti formā. Vari veikt update.';
        }
    };

    const renderResults = (books) => {
        if (!resultsEl || !statusEl) {
            return;
        }

        resultsEl.innerHTML = '';

        if (!books.length) {
            statusEl.textContent = 'Nekas netika atrasts. Pamēģini citu vaicājumu.';
            return;
        }

        statusEl.textContent = `Atrastas ${books.length} grāmatas. Izvēlies vienu.`;

        books.forEach((book) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rp-book-search-result';
            button.innerHTML = `
                ${book.thumbnail ? `<img src="${book.thumbnail}" alt="${book.title} vāks" style="width:44px;height:64px;object-fit:cover;border-radius:6px;">` : ''}
                <span>
                    <strong>${book.title || 'Bez nosaukuma'}</strong><br>
                    <span style="font-size:0.9rem;color:#4b5563;">${book.authors || 'Autors nav norādīts'}${book.pageCount ? ` • ${book.pageCount} lpp` : ''}</span>
                </span>
            `;

            button.addEventListener('click', () => applyBookSelection(book));
            resultsEl.appendChild(button);
        });
    };

    const searchBooks = async () => {
        if (!queryInput || !statusEl || !resultsEl) {
            return;
        }

        const query = queryInput.value.trim();

        if (!query) {
            statusEl.textContent = 'Ievadi grāmatas nosaukumu meklēšanai.';
            return;
        }

        const requestId = ++searchRequestId;
        statusEl.textContent = 'Meklēju grāmatas...';
        resultsEl.innerHTML = '';

        try {
            const response = await fetch(`/api/google-books/top?q=${encodeURIComponent(query)}&maxResults=8`);

            if (!response.ok) {
                throw new Error('Google Books API kļūda.');
            }

            const payload = await response.json();
            const books = (payload.items || []).map((item) => {
                const info = item.volumeInfo || {};

                return {
                    id: item.id || '',
                    title: info.title || '',
                    authors: Array.isArray(info.authors) ? info.authors.join(', ') : '',
                    pageCount: info.pageCount || null,
                    thumbnail: (info.imageLinks && (info.imageLinks.thumbnail || info.imageLinks.smallThumbnail)) || '',
                };
            });

            if (requestId !== searchRequestId || query !== queryInput.value.trim()) {
                return;
            }

            renderResults(books);
        } catch (error) {
            statusEl.textContent = 'Neizdevās ielādēt datus no Google Books. Mēģini vēlreiz.';
        }
    };

    loadBookButtons.forEach((button) => {
        button.addEventListener('click', () => loadExistingBook(button));
    });

    statusTags.forEach((tag) => {
        tag.addEventListener('click', () => {
            setStatus(tag.dataset.status || 'in_progress');
            updateLivePreview();
        });
    });

    // Emotion picker – multi-select
    const emotionInput = document.getElementById('emotion_input');
    const emotionTags = Array.from(document.querySelectorAll('.emotion-tag'));

    const syncEmotionInput = () => {
        if (!emotionInput) return;
        emotionInput.value = emotionTags
            .filter((t) => t.classList.contains('selected'))
            .map((t) => t.dataset.emotion)
            .join(',');
    };

    emotionTags.forEach((tag) => {
        tag.addEventListener('click', () => {
            tag.classList.toggle('selected');
            syncEmotionInput();
        });
    });

    pagesReadInput?.addEventListener('input', syncStatusWithPages);
    totalPagesInput?.addEventListener('input', syncStatusWithPages);
    pagesReadInput?.addEventListener('input', updateLivePreview);
    totalPagesInput?.addEventListener('input', updateLivePreview);

    syncStatusTags();
    syncStatusWithPages();
    updateLivePreview();

    searchBtn?.addEventListener('click', searchBooks);
    const scheduleBookSearch = (value) => {
        window.clearTimeout(searchTimer);

        if (value.trim().length < 2) {
            searchRequestId += 1;
            resultsEl.innerHTML = '';
            statusEl.textContent = '';
            return;
        }

        statusEl.textContent = 'Meklēju grāmatas...';
        searchTimer = window.setTimeout(searchBooks, 350);
    };

    queryInput?.addEventListener('input', () => {
        scheduleBookSearch(queryInput.value);
    });

    titleInput?.addEventListener('input', () => {
        if (queryInput && queryInput.value !== titleInput.value) {
            queryInput.value = titleInput.value;
        }

        scheduleBookSearch(titleInput.value);
    });
    queryInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchBooks();
        }
    });

    changeBookBtn?.addEventListener('click', () => {
        if (statusEl) {
            statusEl.textContent = 'Ievadi vai koriģē grāmatas nosaukumu un izvēlies citu grāmatu.';
        }

        queryInput?.focus();

        if (queryInput && queryInput.value.trim()) {
            searchBooks();
        }
    });
}

