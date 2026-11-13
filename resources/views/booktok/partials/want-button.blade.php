{{-- "Gribu izlasīt" poga vai statusa birka vienai BookTok grāmatai. Mainīgie: $book, $userBookStatuses --}}
@php
    $bookStatusKey = $book->google_volume_id ?: mb_strtolower(trim($book->title));
    $bookStatus = $userBookStatuses[$bookStatusKey] ?? null;
@endphp

@auth
    @if ($bookStatus === 'read')
        <span class="bt-status bt-status--read">✓ Izlasīta</span>
    @elseif ($bookStatus === 'in_progress')
        <span class="bt-status bt-status--progress">📖 Lasu</span>
    @elseif ($bookStatus === 'want_to_read')
        <span class="bt-status">★ Gribu izlasīt</span>
    @else
        <form action="{{ route('reading-progress.want-to-read.store') }}" method="POST" class="bt-want-form">
            @csrf
            <input type="hidden" name="book_title" value="{{ $book->title }}">
            <input type="hidden" name="google_volume_id" value="{{ $book->google_volume_id }}">
            <input type="hidden" name="book_cover_url" value="{{ $book->google_thumbnail }}">
            <input type="hidden" name="total_pages" value="{{ $book->google_page_count }}">
            <button type="submit" class="bt-want-button">+ Gribu izlasīt</button>
        </form>
    @endif
@else
    <a href="{{ route('login') }}" class="bt-want-button">Ielogojies</a>
@endauth
