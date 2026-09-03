<x-layout>
    <div class="reading-progress-page">
        @if (session('status'))
            <div class="reading-progress-alert reading-progress-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <div class="reading-progress-history">
            <a href="{{ url()->previous() }}" class="weekly-top-action">← Atpakaļ</a>

            <div class="weekly-top-item" style="margin-top: 12px;">
                @if($book['thumbnail'])
                    <img
                        class="weekly-top-cover"
                        src="{{ $book['thumbnail'] }}"
                        alt="{{ $book['title'] }} vāks"
                    >
                @else
                    <div class="weekly-top-cover weekly-top-cover--empty">Nav vāka</div>
                @endif

                <div class="weekly-top-content">
                    <h1 class="welcome-card-title" style="margin-bottom: 8px;">{{ $book['title'] }}</h1>
                    <p class="welcome-card-text"><strong>Autors:</strong> {{ $book['authors'] }}</p>
                    <p class="welcome-card-text weekly-top-meta" style="margin-top: 6px;">
                        <strong>Izdošana:</strong> {{ $book['publishedDate'] }} ·
                        <strong>Izdevējs:</strong> {{ $book['publisher'] }}
                    </p>
                    <p class="welcome-card-text weekly-top-meta" style="margin-top: 6px;">
                        <strong>Lapas:</strong> {{ $book['pageCount'] ?? 'Nav norādīts' }} ·
                        <strong>Valoda:</strong> {{ $book['language'] }}
                    </p>
                    <p class="welcome-card-text weekly-top-meta" style="margin-top: 6px;">
                        <strong>Kategorijas:</strong> {{ $book['categories'] }}
                    </p>

                    @if($book['averageRating'])
                        <p class="welcome-card-text weekly-top-meta" style="margin-top: 6px;">
                            <strong>Reitings:</strong> {{ $book['averageRating'] }}/5
                            @if($book['ratingsCount'])
                                ({{ $book['ratingsCount'] }} vērtējumi)
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            @if($book['description'])
                <div style="margin-top: 16px;">
                    <h2 class="welcome-card-title">Apraksts</h2>
                    <p class="welcome-card-text" style="line-height: 1.6; white-space: pre-line;">{{ $book['description'] }}</p>
                </div>
            @endif

            <div style="margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
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
                            <input type="hidden" name="book_title" value="{{ $book['title'] }}">
                            <input type="hidden" name="google_volume_id" value="{{ $book['id'] ?? '' }}">
                            <input type="hidden" name="book_cover_url" value="{{ $book['thumbnail'] ?? '' }}">
                            <input type="hidden" name="total_pages" value="{{ $book['pageCount'] ?? '' }}">
                            <button type="submit" class="reading-progress-submit">Pievienot Want to Read</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="reading-progress-submit" style="text-decoration: none; display: inline-block;">Ielogojies</a>
                @endauth

                @if($book['previewLink'])
                    <a class="weekly-top-action" href="{{ $book['previewLink'] }}" target="_blank" rel="noopener noreferrer">Skatīt priekšskatījumu</a>
                @endif

                @if($book['infoLink'])
                    <a class="weekly-top-action" href="{{ $book['infoLink'] }}" target="_blank" rel="noopener noreferrer">Google Books lapa</a>
                @endif
            </div>
        </div>
    </div>
</x-layout>
