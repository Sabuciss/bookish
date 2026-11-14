<?php

namespace Tests\Feature;

use App\Models\BooktokTopBook;
use App\Models\ReadingProgress;
use App\Models\User;
use Database\Seeders\BooktokTopBookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "Dūmu tests": katra galvenā lapa atveras bez servera kļūdas.
 * Noķer regresijas, piemēram, pēc Laravel atjauninājuma (getRelation() izmaiņa).
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // bez ārējiem API izsaukumiem
        $this->seed(BooktokTopBookSeeder::class);
    }

    public function test_public_pages_open_without_errors(): void
    {
        $book = BooktokTopBook::query()->firstOrFail();

        foreach ([
            '/',
            route('booktok.index'),
            route('booktok.index', ['view' => 'authors']),
            route('booktok.show', $book),
            route('book-listings.index'),
            route('login'),
            route('register'),
            route('password.request'),
            route('privacy-policy'),
            route('terms'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_authenticated_pages_open_without_errors(): void
    {
        $user = User::factory()->create();
        $entry = ReadingProgress::query()->create([
            'user_id' => $user->id,
            'book_title' => 'Iron Flame',
            'pages_read' => 100,
            'total_pages' => 400,
            'reading_status' => 'in_progress',
            'emotion' => 'Priecīgs',
            'reading_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
        ]);
        $book = BooktokTopBook::query()->firstOrFail();

        $this->actingAs($user);

        foreach ([
            '/',
            route('reading-shelf.show'),
            route('reading-progress.index'),
            route('reading-progress.index', ['entry_id' => $entry->id]),
            route('profile.edit'),
            route('reading-highlights.index'),
            route('reading-highlights.create'),
            route('reading-challenges.index'),
            route('reading-challenges.results'),
            route('reading-timer.index'),
            route('book-release-reminders.index'),
            route('book-listings.create'),
            route('booktok.index'),
            route('booktok.show', $book),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
