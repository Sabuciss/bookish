<?php

namespace Tests\Feature;

use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_hero_with_signup_call_to_action(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('brīnuma', false)
            ->assertSee('id="upcoming-book-search-input"', false)
            ->assertSee('data-genre="fantasy"', false)
            ->assertSee('Sākt savu plauktu');
    }

    public function test_logged_in_reader_sees_continue_reading_card(): void
    {
        $user = User::factory()->create();

        ReadingProgress::query()->create([
            'user_id' => $user->id,
            'book_title' => 'Iron Flame',
            'pages_read' => 358,
            'total_pages' => 795,
            'reading_status' => 'in_progress',
            'emotion' => 'Saspringts',
            'reading_date' => now()->subDay()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'duration_minutes' => 120,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Turpini lasīt')
            ->assertSee('Iron Flame')
            ->assertSee('45%');
    }
}
