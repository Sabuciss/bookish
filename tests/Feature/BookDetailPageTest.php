<?php

namespace Tests\Feature;

use App\Models\BooktokTopBook;
use App\Services\BookRecommendationService;
use Database\Seeders\BooktokTopBookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApis(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes/vol-123*' => Http::response([
                'id' => 'vol-123',
                'volumeInfo' => [
                    'title' => 'The Little Prince',
                    'authors' => ['Antoine de Saint-Exupéry'],
                    'description' => 'Mazais princis apceļo planētas.',
                    'publishedDate' => '1943-04-06',
                    'pageCount' => 96,
                    'categories' => ['Juvenile Fiction / Classics'],
                    'averageRating' => 4.5,
                    'ratingsCount' => 120,
                ],
            ]),
            'www.googleapis.com/books/v1/volumes?*' => Http::response([
                'items' => [['id' => 'found-42', 'volumeInfo' => ['title' => 'Night Flight']]],
            ]),
            'openlibrary.org/search.json*' => function (Request $request) {
                $isAuthor = str_contains(urldecode($request->url()), 'author:');

                return Http::response(['docs' => [
                    ['title' => 'The Little Prince', 'author_name' => ['Antoine de Saint-Exupéry'], 'cover_i' => 1, 'language' => ['eng']],
                    ['title' => $isAuthor ? 'Night Flight' : 'Matilda', 'author_name' => [$isAuthor ? 'Antoine de Saint-Exupéry' : 'Roald Dahl'], 'cover_i' => 2, 'first_publish_year' => 1988],
                    ['title' => 'Bez vāka', 'author_name' => ['Kāds'], 'language' => ['eng']],
                ]]);
            },
        ]);
    }

    public function test_google_book_page_shows_hero_and_recommendations(): void
    {
        $this->fakeApis();

        $this->get(route('books.show', 'vol-123'))
            ->assertOk()
            ->assertSee('The Little Prince')
            ->assertSee('Juvenile Fiction')
            ->assertSee('Classics')
            ->assertSee('96')
            ->assertSee('★ 4.5', false)
            ->assertSee('Tev varētu patikt')
            ->assertSee('Night Flight')           // tā paša autora
            ->assertSee('Matilda')                // līdzīgs žanrs
            ->assertDontSee('Bez vāka')           // bez vāka neiesakām
            ->assertSee('covers.openlibrary.org/b/id/2-M.jpg', false);

        // pati grāmata ieteikumos neparādās (tikai virsrakstā, vāka alt un formā)
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'volumes?'));
    }

    public function test_recommendation_link_resolves_google_volume_and_is_cached(): void
    {
        $this->fakeApis();

        $url = route('books.find', ['title' => 'Night Flight', 'author' => 'Antoine de Saint-Exupéry']);

        $this->get($url)->assertRedirect(route('books.show', 'found-42'));
        $this->get($url)->assertRedirect(route('books.show', 'found-42'));

        Http::assertSentCount(1); // otrais klikšķis no keša, kvota netiek tērēta
    }

    public function test_recommendation_link_prefers_local_booktok_book(): void
    {
        Http::fake();
        $this->seed(BooktokTopBookSeeder::class);
        $book = BooktokTopBook::query()->firstOrFail();

        $this->get(route('books.find', ['title' => mb_strtoupper($book->title)]))
            ->assertRedirect(route('booktok.show', $book));

        Http::assertNothingSent();
    }

    public function test_booktok_book_page_falls_back_to_booktok_recommendations(): void
    {
        Http::fake(); // nav ārējo datu
        $this->seed(BooktokTopBookSeeder::class);
        $book = BooktokTopBook::query()->orderBy('rank_position')->firstOrFail();

        $this->get(route('booktok.show', $book))
            ->assertOk()
            ->assertSee('BookTok tops #' . $book->rank_position)
            ->assertSee('Tev varētu patikt')
            ->assertSee('bd-rec-badge', false);
    }

    public function test_open_library_rating_is_used_when_google_has_none(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes/vol-777*' => Http::response([
                'id' => 'vol-777',
                'volumeInfo' => ['title' => 'Fourth Wing', 'authors' => ['Rebecca Yarros']],
            ]),
            'openlibrary.org/search.json*' => function (Request $request) {
                if (str_contains($request->url(), 'ratings_average')) {
                    return Http::response(['docs' => [
                        ['title' => 'Fourth Wing (Collection)', 'ratings_average' => 5.0, 'ratings_count' => 2],
                        ['title' => 'Fourth Wing', 'ratings_average' => 4.2654867, 'ratings_count' => 113],
                    ]]);
                }

                return Http::response(['docs' => []]);
            },
        ]);

        $this->get(route('books.show', 'vol-777'))
            ->assertOk()
            ->assertSee('★ 4.3', false)
            ->assertSee('113 vērtējumi')
            ->assertDontSee('Open Library');

        // otrreiz no keša — Open Library vērtējumu vairs neprasa
        $this->get(route('books.show', 'vol-777'))->assertOk();
        Http::assertSentCount(4); // Google + vērtējums + apraksta meklēšana + ieteikumi (viss kešots)
    }

    public function test_open_library_description_is_used_when_google_has_none(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes/vol-888*' => Http::response([
                'id' => 'vol-888',
                'volumeInfo' => ['title' => 'The Book Thief', 'authors' => ['Markus Zusak'], 'averageRating' => 4.5],
            ]),
            'openlibrary.org/works/OL5819456W.json' => Http::response([
                'description' => ['type' => '/type/text', 'value' => "When Death has a [story](https://x.test) to tell, you listen.\r\n\r\n----------\r\nAvoti: [1]: https://x.test"],
            ]),
            'openlibrary.org/search.json*' => function (Request $request) {
                return str_contains($request->url(), 'fields=key')
                    ? Http::response(['docs' => [['key' => '/works/OL5819456W', 'title' => 'The Book Thief']]])
                    : Http::response(['docs' => []]);
            },
        ]);

        $this->get(route('books.show', 'vol-888'))
            ->assertOk()
            ->assertSee('When Death has a story to tell, you listen.')
            ->assertDontSee('Avoti')
            ->assertDontSee('apraksts nav pieejams');
    }

    public function test_subject_mapping_from_google_categories(): void
    {
        $service = app(BookRecommendationService::class);

        $this->assertSame('fantasy', $service->subjectFromCategories('Fiction / Fantasy / Epic'));
        $this->assertSame('romance', $service->subjectFromCategories('Fiction / Romance / Contemporary'));
        $this->assertSame('fiction', $service->subjectFromCategories('Fiction / General'));
        $this->assertNull($service->subjectFromCategories('Nav norādītas'));
    }
}
