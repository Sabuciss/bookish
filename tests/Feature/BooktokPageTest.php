<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BooktokTopBookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BooktokPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // nekādu ārēju API izsaukumu testos
        $this->seed(BooktokTopBookSeeder::class);
    }

    public function test_books_view_shows_podium_and_ranked_list(): void
    {
        $this->get(route('booktok.index'))
            ->assertOk()
            ->assertSee('Grāmatas, par kurām', false)
            ->assertSee('bt-podium-card', false)
            ->assertSee('bt-row', false)
            ->assertSee('Ielogojies');
    }

    public function test_authors_view_lists_authors_with_favorite_buttons(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('booktok.index', ['view' => 'authors']))
            ->assertOk()
            ->assertSee('Top autori')
            ->assertSee('Sarah J. Maas')
            ->assertSee('Pievienot favorītiem', false);
    }

    public function test_logged_in_user_sees_want_to_read_buttons(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('booktok.index'))
            ->assertOk()
            ->assertSee('+ Gribu izlasīt');
    }

    public function test_filter_without_results_shows_empty_state(): void
    {
        $this->get(route('booktok.index', ['title' => 'Šādas grāmatas noteikti nav']))
            ->assertOk()
            ->assertSee('Izvēlētajiem filtriem nav atrastas top grāmatas.');
    }
}
