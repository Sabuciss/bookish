<x-layout>
    <div class="reading-progress-page">
        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <section class="book-detail uiverse-container">
            <a href="{{ route('booktok.index') }}" class="book-detail-back">← Atpakaļ uz BookTok topu</a>

            <div class="book-detail-hero">
                <div class="book-detail-cover-wrap">
                @if(!empty($book->google_thumbnail))
                    <img
                        class="book-detail-cover"
                        src="{{ $book->google_thumbnail }}"
                        alt="{{ $book->title }} vāks"
                    >
                @else
                    <div class="book-detail-cover book-detail-cover--empty">Nav vāka</div>
                @endif
                </div>

                <div class="book-detail-content">
                    <p class="book-detail-eyebrow">BookTok tops #{{ $book->rank_position }}</p>
                    <h1 class="book-detail-title">{{ $book->title }}</h1>
                    <p class="book-detail-author">{{ $book->author }}</p>
                    <div class="book-detail-rating">
                        @if(!empty($book->google_average_rating))
                            <span class="book-detail-stars">★★★★★</span>
                            <strong>{{ $book->google_average_rating }}/5</strong>
                            @if(!empty($book->google_ratings_count))
                                <span>({{ $book->google_ratings_count }} vērtējumi)</span>
                            @endif
                        @else
                            <span>Vērtējums nav pieejams</span>
                        @endif
                    </div>
                    <div class="book-detail-meta">
                        <span><strong>Publicēta</strong> {{ $book->published_year ?? 'Nav norādīts' }}</span>
                        <span><strong>Izdevējs</strong> {{ $book->google_publisher ?? 'Nav norādīts' }}</span>
                        <span><strong>Lapas</strong> {{ $book->google_page_count ?? 'Nav norādīts' }}</span>
                    </div>
                    @if(!empty($book->google_categories))
                        <p class="book-detail-categories">{{ $book->google_categories }}</p>
                    @endif

                    <div class="book-detail-actions">
                        @auth
                            @if ($currentBookStatus === 'read')
                                <span class="book-detail-status book-detail-status--read">Izlasīta</span>
                            @elseif ($currentBookStatus === 'in_progress')
                                <span class="book-detail-status book-detail-status--progress">Lasu</span>
                            @elseif ($currentBookStatus === 'want_to_read')
                                <span class="book-detail-status book-detail-status--want">Gribu izlasīt</span>
                            @else
                                <form action="{{ route('reading-progress.want-to-read.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="book_title" value="{{ $book->title }}">
                                    <input type="hidden" name="google_volume_id" value="{{ $book->google_volume_id ?? '' }}">
                                    <input type="hidden" name="book_cover_url" value="{{ $book->google_thumbnail ?? '' }}">
                                    <input type="hidden" name="total_pages" value="{{ $book->google_page_count ?? '' }}">
                                    <button type="submit" class="book-detail-primary">Pievienot lasāmajām</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="book-detail-primary">Ielogoties, lai pievienotu</a>
                        @endauth

                        @if(!empty($book->google_preview_link))
                            <a class="book-detail-secondary" href="{{ $book->google_preview_link }}" target="_blank" rel="noopener noreferrer">Priekšskatījums</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="book-detail-description uiverse-container">
            <div class="book-detail-description-heading">
                <p class="book-detail-eyebrow">Par grāmatu</p>
                <h2 class="book-detail-section-title">Apraksts</h2>
            </div>
            @if(!empty($book->google_description))
                <p class="book-detail-description-text">{{ $book->google_description }}</p>
            @else
                <p class="book-detail-description-text">Šai grāmatai Google Books apraksts nav pieejams.</p>
            @endif

            @if(!empty($book->google_info_link))
                <a class="book-detail-link" href="{{ $book->google_info_link }}" target="_blank" rel="noopener noreferrer">Uzzināt vairāk Google Books</a>
            @endif
        </section>
    </div>
</x-layout>
