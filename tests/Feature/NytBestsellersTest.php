<?php

namespace Tests\Feature;

use App\Services\NytBestsellerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NytBestsellersTest extends TestCase
{
    private function fakeOverview(): array
    {
        return [
            'status' => 'OK',
            'results' => [
                'published_date' => '2026-11-15',
                'lists' => [
                    [
                        'list_name_encoded' => 'combined-print-and-e-book-fiction',
                        'books' => [
                            [
                                'rank' => 1, 'rank_last_week' => 3, 'weeks_on_list' => 12,
                                'title' => 'ONYX STORM', 'author' => 'Rebecca Yarros',
                                'book_image' => 'https://storage.googleapis.com/du-prd/books/images/onyx.jpg',
                                'primary_isbn13' => '9781649374172', 'description' => 'Violet returns.',
                            ],
                            [
                                'rank' => 2, 'rank_last_week' => 0, 'weeks_on_list' => 1,
                                'title' => 'A NEW STORY', 'author' => 'Jane Doe',
                                'book_image' => '', 'primary_isbn13' => '', 'description' => '',
                            ],
                        ],
                    ],
                    [
                        'list_name_encoded' => 'young-adult-hardcover',
                        'books' => [
                            ['rank' => 1, 'rank_last_week' => 1, 'weeks_on_list' => 30, 'title' => 'YA BOOK', 'author' => 'Ann Lee', 'book_image' => '', 'primary_isbn13' => ''],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.nyt_books.api_key' => 'test-key']);
    }

    public function test_home_page_shows_weekly_bestsellers(): void
    {
        Http::fake(['api.nytimes.com/*' => Http::response($this->fakeOverview())]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Populāri šonedēļ')
            ->assertSee('Onyx Storm') // LIELIE BURTI → "Onyx Storm"
            ->assertSee('Rebecca Yarros')
            ->assertSee('Jauns')
            ->assertSee('Daiļliteratūra')
            ->assertSee('Jauniešiem')
            ->assertSee('https://openlibrary.org/isbn/9781649374172', false);
    }

    public function test_lists_are_cached_so_api_is_called_once_per_day(): void
    {
        Http::fake(['api.nytimes.com/*' => Http::response($this->fakeOverview())]);
        $service = app(NytBestsellerService::class);

        $service->weeklyLists();
        $service->weeklyLists();
        $this->get('/')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_last_good_list_is_shown_when_api_fails(): void
    {
        Http::fake(['api.nytimes.com/*' => Http::sequence()
            ->push($this->fakeOverview())
            ->push(['fault' => 'rate limit'], 429)]);
        $service = app(NytBestsellerService::class);

        $service->weeklyLists();
        Cache::forget('nyt_bestsellers_overview_v1'); // simulē 24 h kešatmiņas beigas

        $lists = $service->weeklyLists();

        $this->assertSame('Onyx Storm', $lists['lists'][0]['books'][0]['title']);
    }

    public function test_section_is_hidden_and_api_not_called_without_key(): void
    {
        config(['services.nyt_books.api_key' => null]);
        Http::fake();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Populāri šonedēļ');

        Http::assertNothingSent();
    }
}
