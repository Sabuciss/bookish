<?php

namespace Tests\Feature;

use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingProgressDurationTest extends TestCase
{
    use RefreshDatabase;

    private function saveSession(User $user, array $overrides = []): void
    {
        $this->actingAs($user)
            ->post(route('reading-progress.store'), array_merge([
                'book_title' => 'Laika grāmata',
                'pages_read' => 20,
                'total_pages' => 300,
                'emotion' => 'Mierīgs',
                'reading_date' => now()->subDays(2)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '10:30',
            ], $overrides))
            ->assertSessionHasNoErrors();
    }

    public function test_new_sessions_add_up_to_total_reading_time(): void
    {
        $user = User::factory()->create();

        $this->saveSession($user);
        $entry = ReadingProgress::query()->where('user_id', $user->id)->firstOrFail();

        $this->saveSession($user, [
            'entry_id' => $entry->id,
            'pages_read' => 50,
            'reading_date' => now()->subDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '19:15',
        ]);

        $entry->refresh();
        $this->assertSame(30 + 75, (int) $entry->duration_minutes);
        $this->assertSame(75, $entry->lastSessionMinutes());
        $this->assertSame(50, (int) $entry->pages_read);
        $this->assertSame(1, ReadingProgress::query()->where('user_id', $user->id)->count());
    }

    public function test_correcting_the_same_session_does_not_count_it_twice(): void
    {
        $user = User::factory()->create();

        $this->saveSession($user);
        $entry = ReadingProgress::query()->where('user_id', $user->id)->firstOrFail();

        // Tas pats datums un sākuma laiks, labots tikai beigu laiks.
        $this->saveSession($user, [
            'entry_id' => $entry->id,
            'end_time' => '10:45',
        ]);

        $this->assertSame(45, (int) $entry->fresh()->duration_minutes);
    }

    public function test_edit_form_loads_entry_data_from_database(): void
    {
        $user = User::factory()->create();
        $this->saveSession($user);
        $entry = ReadingProgress::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('reading-progress.index', [
                'entry_id' => $entry->id,
                'book_title' => 'Viltots nosaukums no URL',
            ]))
            ->assertOk()
            ->assertSee('value="Laika grāmata"', false)
            ->assertDontSee('Viltots nosaukums no URL')
            ->assertSee('value="10:00"', false);
    }

    public function test_new_session_link_prefills_today_and_empty_times(): void
    {
        $user = User::factory()->create();
        $this->saveSession($user);
        $entry = ReadingProgress::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('reading-progress.index', ['entry_id' => $entry->id, 'session' => 'new']))
            ->assertOk()
            ->assertSee('value="' . now()->toDateString() . '"', false)
            ->assertSee('value="00:00"', false);
    }

    public function test_user_cannot_open_another_users_entry_for_editing(): void
    {
        $owner = User::factory()->create();
        $this->saveSession($owner);
        $entry = ReadingProgress::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('reading-progress.index', ['entry_id' => $entry->id]))
            ->assertNotFound();
    }
}
