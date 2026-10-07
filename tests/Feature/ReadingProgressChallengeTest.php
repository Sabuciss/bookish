<?php

namespace Tests\Feature;

use App\Models\ReadingChallenge;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingProgressChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_assign_reading_progress_to_own_challenge(): void
    {
        $user = User::factory()->create();
        $challenge = ReadingChallenge::query()->create([
            'user_id' => $user->id,
            'title' => 'Lappušu izaicinājums',
            'challenge_type' => 'pages',
            'target_value' => 100,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('reading-progress.store'), [
                'challenge_id' => $challenge->id,
                'book_title' => 'Pārbaudāmā grāmata',
                'pages_read' => 20,
                'total_pages' => 200,
                'emotion' => 'Mierīgs',
                'reading_date' => now()->subDay()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '10:30',
            ])
            ->assertSessionHasNoErrors();

        $progress = ReadingProgress::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($challenge->id, $progress->challenge_id);
        $this->assertSame(20, $challenge->fresh()->completion_value);
        $this->assertFalse($challenge->fresh()->is_completed);
    }

    public function test_user_cannot_assign_progress_to_another_users_challenge(): void
    {
        $user = User::factory()->create();
        $challengeOwner = User::factory()->create();
        $challenge = ReadingChallenge::query()->create([
            'user_id' => $challengeOwner->id,
            'title' => 'Cita lietotāja izaicinājums',
            'challenge_type' => 'pages',
            'target_value' => 100,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($user)
            ->from(route('reading-progress.index'))
            ->post(route('reading-progress.store'), [
                'challenge_id' => $challenge->id,
                'book_title' => 'Pārbaudāmā grāmata',
                'pages_read' => 20,
                'total_pages' => 200,
                'emotion' => 'Mierīgs',
                'reading_date' => now()->subDay()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '10:30',
            ])
            ->assertSessionHasErrors('challenge_id');

        $this->assertDatabaseCount('reading_progresses', 0);
    }

    public function test_challenge_completion_is_derived_from_progress_instead_of_request_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reading-challenges.store'), [
                'title' => 'Lappušu mērķis',
                'challenge_type' => 'pages',
                'target_value' => 50,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'is_completed' => true,
                'completion_value' => 999999,
                'completion_date' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $challenge = ReadingChallenge::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($challenge->is_completed);
        $this->assertSame(0, $challenge->completion_value);
        $this->assertNull($challenge->completion_date);
    }

    public function test_page_progress_completes_an_attached_challenge_automatically(): void
    {
        $user = User::factory()->create();
        $challenge = ReadingChallenge::query()->create([
            'user_id' => $user->id,
            'title' => 'Lappušu mērķis',
            'challenge_type' => 'pages',
            'target_value' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('reading-progress.store'), [
                'challenge_id' => $challenge->id,
                'book_title' => 'Mērķa grāmata',
                'pages_read' => 20,
                'total_pages' => 200,
                'emotion' => 'Mierīgs',
                'reading_date' => now()->subDay()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '10:30',
            ])
            ->assertSessionHasNoErrors();

        $completedChallenge = $challenge->fresh();
        $this->assertTrue($completedChallenge->is_completed);
        $this->assertSame(20, $completedChallenge->completion_value);
        $this->assertSame(now()->subDay()->toDateString(), $completedChallenge->completion_date->toDateString());
    }
}