<?php

namespace Tests\Feature;

use App\Models\ReadingChallengeSession;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_dashboard_shows_reading_statistics(): void
    {
        $user = User::factory()->create(['name' => 'Anna']);

        // Vakar: 45 min progresa sesija, grāmata pusē.
        ReadingProgress::query()->create([
            'user_id' => $user->id,
            'book_title' => 'Pusē izlasīta',
            'pages_read' => 100,
            'total_pages' => 200,
            'reading_status' => 'in_progress',
            'emotion' => 'Priecīgs, Mierīgs',
            'reading_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:45',
            'duration_minutes' => 45,
        ]);

        // Šodien: 30 min taimera sesija.
        ReadingChallengeSession::query()->create([
            'user_id' => $user->id,
            'planned_minutes' => 30,
            'elapsed_seconds' => 30 * 60,
            'pages_read' => 10,
            'timer_status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'ended_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk()
            ->assertSee('Anna')
            ->assertSee('1<small>h</small> 15<small>min</small>', false) // 45 + 30 min
            ->assertSee('75 min lasīšanas')
            ->assertSee('2 dienas pēc kārtas')
            ->assertSee('Priecīgs')
            ->assertSee('Pusē izlasīta')
            ->assertSee('--p: 50;', false);
    }

    public function test_profile_dashboard_renders_without_any_reading_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Šobrīd nelasi nevienu grāmatu');
    }
}
