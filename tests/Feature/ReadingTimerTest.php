<?php

namespace Tests\Feature;

use App\Models\ReadingChallenge;
use App\Models\ReadingChallengeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingTimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_timer_duration_is_calculated_by_server_and_excludes_paused_time(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        $user = User::factory()->create();
        $challenge = ReadingChallenge::query()->create([
            'user_id' => $user->id,
            'title' => 'Laika mērķis',
            'challenge_type' => 'time',
            'target_value' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);

        $started = $this->actingAs($user)
            ->postJson(route('reading-timer.sessions.start'), [
                'planned_minutes' => 2,
                'challenge_id' => $challenge->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'running')
            ->json();

        Carbon::setTestNow(now()->addSeconds(40));
        $this->postJson(route('reading-timer.sessions.pause', $started['id']))
            ->assertOk()
            ->assertJsonPath('elapsedSeconds', 40);

        Carbon::setTestNow(now()->addSeconds(80));
        $this->postJson(route('reading-timer.sessions.resume', $started['id']))
            ->assertOk()
            ->assertJsonPath('status', 'running');

        Carbon::setTestNow(now()->addSeconds(30));
        $this->postJson(route('reading-timer.sessions.complete', $started['id']), [
            'pages_read' => 15,
            'notes' => 'Pārbaudīta sesija',
            'is_public' => false,
            'elapsed_seconds' => 7200,
            'started_at' => '2000-01-01 00:00:00',
            'ended_at' => '2099-01-01 00:00:00',
        ])->assertOk();

        $session = ReadingChallengeSession::query()->findOrFail($started['id']);
        $this->assertSame(70, $session->elapsed_seconds);
        $this->assertSame('completed', $session->timer_status);
        $this->assertSame(15, $session->pages_read);
        $this->assertTrue($challenge->fresh()->is_completed);
        $this->assertSame(1, $challenge->fresh()->completion_value);

        Carbon::setTestNow();
    }

    public function test_timer_rejects_excess_page_count_and_only_one_open_timer_per_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $started = $this->postJson(route('reading-timer.sessions.start'), [
            'planned_minutes' => 1,
        ])->assertOk()->json();

        $this->postJson(route('reading-timer.sessions.start'), [
            'planned_minutes' => 1,
        ])->assertUnprocessable();

        $this->travel(2)->seconds();
        $this->postJson(route('reading-timer.sessions.complete', $started['id']), [
            'pages_read' => 2001,
            'is_public' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pages_read');

        $this->postJson(route('reading-timer.sessions.complete', $started['id']), [
            'pages_read' => 2000,
            'is_public' => false,
        ])->assertOk();

        $this->assertDatabaseHas('reading_challenge_sessions', [
            'id' => $started['id'],
            'elapsed_seconds' => 2,
            'pages_read' => 2000,
        ]);
    }

    public function test_user_cannot_control_another_users_timer_session(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $session = ReadingChallengeSession::query()->create([
            'user_id' => $owner->id,
            'planned_minutes' => 25,
            'elapsed_seconds' => 0,
            'pages_read' => 0,
            'is_public' => false,
            'started_at' => now(),
            'active_started_at' => now(),
            'timer_status' => 'running',
        ]);

        $this->actingAs($attacker)
            ->postJson(route('reading-timer.sessions.pause', $session->id))
            ->assertNotFound();
    }

    public function test_results_summary_aggregates_all_filtered_sessions_and_excludes_legacy_unknown_time(): void
    {
        $user = User::factory()->create();

        for ($index = 0; $index < 21; $index++) {
            ReadingChallengeSession::query()->create([
                'user_id' => $user->id,
                'planned_minutes' => 25,
                'elapsed_seconds' => 60 + $index,
                'pages_read' => 10,
                'is_public' => true,
                'started_at' => now()->subDays(2),
                'ended_at' => now()->subDays(2)->addSeconds(60 + $index),
                'timer_status' => 'completed',
            ]);
        }

        ReadingChallengeSession::query()->create([
            'user_id' => $user->id,
            'planned_minutes' => 30,
            'elapsed_seconds' => null,
            'pages_read' => 500,
            'is_public' => true,
            'timer_status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('reading-challenges.results', ['scope' => 'mine', 'period' => 'all']))
            ->assertOk()
            ->assertViewHas('totalSessions', 22)
            ->assertViewHas('totalPages', 710)
            ->assertViewHas('totalElapsedSeconds', 21 * 60 + 210)
            ->assertViewHas('sessions', fn ($sessions) => $sessions->count() === 20 && $sessions->hasMorePages());
    }

    public function test_duration_format_is_identical_for_all_saved_session_lengths(): void
    {
        $this->assertSame('1 min 05 s', ReadingChallengeSession::formatDuration(65));
        $this->assertSame('1 h 00 min 05 s', ReadingChallengeSession::formatDuration(3605));
    }
}
