<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase as BaseTestCase;

class GoogleBooksGenreFallbackTest extends BaseTestCase
{
    public function test_genre_search_uses_open_library_when_google_books_is_rate_limited(): void
    {
        Cache::flush();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 429),
            'https://openlibrary.org/search.json*' => Http::response([
                'docs' => [[
                    'key' => '/works/OL123W',
                    'title' => 'A Fantasy Book',
                    'author_name' => ['Example Author'],
                    'first_publish_year' => 2024,
                    'cover_i' => 123,
                ]],
            ]),
        ]);

        $this->getJson(route('google-books.top', [
            'q' => 'subject:fantasy',
            'maxResults' => 20,
            'remote' => 1,
        ]))
            ->assertOk()
            ->assertJsonPath('items.0.volumeInfo.title', 'A Fantasy Book')
            ->assertJsonPath('items.0.volumeInfo.publishedDate', 2024);

        Http::assertSentCount(2);
    }
}
