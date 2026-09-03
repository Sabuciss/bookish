<x-layout>
    <div class="reading-progress-page">
        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <section class="reading-progress-history uiverse-container">
            <a href="{{ route('booktok.index') }}" class="weekly-top-action">← Atpakaļ uz BookTok topu</a>

            <div class="weekly-top-item">
                @if(!empty($book->google_thumbnail))
                    <img
                        class="weekly-top-cover"
                        src="{{ $book->google_thumbnail }}"
                        alt="{{ $book->title }} vāks"
                    >
                @else
                    <div class="weekly-top-cover weekly-top-cover--empty">Nav vāka</div>
                @endif

                <div class="weekly-top-content">
                    <h1 class="uiverse-heading">{{ $book->title }}</h1>
                    <p class="welcome-card-text"><strong>Autors:</strong> {{ $book->author }}</p>
                    <p class="welcome-card-text weekly-top-meta">
                        <strong>Top vieta:</strong> #{{ $book->rank_position }}
                        @if($book->published_year)
                            · <strong>Publicēta:</strong> {{ $book->published_year }}
                        @endif
                    </p>

                    @if(!empty($book->google_volume_id))
                        <p class="welcome-card-text weekly-top-meta">
                            <strong>Google izdošana:</strong> {{ $book->google_published_date ?? 'Nav norādīts' }} ·
                            <strong>Izdevējs:</strong> {{ $book->google_publisher ?? 'Nav norādīts' }}
                        </p>
                        <p class="welcome-card-text weekly-top-meta">
                            <strong>Lapas:</strong> {{ $book->google_page_count ?? 'Nav norādīts' }}
                        </p>

                        @if(!empty($book->google_categories))
                            <p class="welcome-card-text weekly-top-meta">
                                <strong>Kategorijas:</strong> {{ $book->google_categories }}
                            </p>
                        @endif

                        @if(!empty($book->google_average_rating))
                            <p class="welcome-card-text weekly-top-meta">
                                <strong>Reitings:</strong> {{ $book->google_average_rating }}/5
                                @if(!empty($book->google_ratings_count))
                                    ({{ $book->google_ratings_count }} vērtējumi)
                                @endif
                            </p>
                        @endif

                        <div style="margin-top: 12px;">
                            @auth
                                @if ($currentBookStatus === 'read')
                                    <span style="background: #dcfce7; color: #166534; border-radius: 999px; padding: 6px 12px; font-size: 0.85rem; font-weight: 700;">READ</span>
                                @elseif ($currentBookStatus === 'in_progress')
                                    <span style="background: #dbeafe; color: #1e3a8a; border-radius: 999px; padding: 6px 12px; font-size: 0.85rem; font-weight: 700;">In Progress</span>
                                @elseif ($currentBookStatus === 'want_to_read')
                                    <span style="background: #fef3c7; color: #92400e; border-radius: 999px; padding: 6px 12px; font-size: 0.85rem; font-weight: 700;">Added to Want to Read</span>
                                @else
                                    <form action="{{ route('reading-progress.want-to-read.store') }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        <input type="hidden" name="book_title" value="{{ $book->title }}">
                                        <input type="hidden" name="google_volume_id" value="{{ $book->google_volume_id ?? '' }}">
                                        <input type="hidden" name="book_cover_url" value="{{ $book->google_thumbnail ?? '' }}">
                                        <input type="hidden" name="total_pages" value="{{ $book->google_page_count ?? '' }}">
                                        <button type="submit" class="reading-progress-submit">Pievienot Want to Read</button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="reading-progress-submit" style="text-decoration: none; display: inline-block;">Ielogojies</a>
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="reading-progress-history uiverse-container">
            @if(!empty($book->google_description))
                <h2 class="uiverse-heading">Apraksts</h2>
                <p class="welcome-card-text">{{ $book->google_description }}</p>
            @else
                <p class="welcome-card-text">Šai grāmatai Google Books apraksts nav pieejams.</p>
            @endif

            @if(!empty($book->google_volume_id))
                <div class="weekly-top-controls">
                    @if(!empty($book->google_preview_link))
                        <a class="weekly-top-action" href="{{ $book->google_preview_link }}" target="_blank" rel="noopener noreferrer">Skatīt priekšskatījumu</a>
                    @endif

                    @if(!empty($book->google_info_link))
                        <a class="weekly-top-action" href="{{ $book->google_info_link }}" target="_blank" rel="noopener noreferrer">Google Books lapa</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-layout>
