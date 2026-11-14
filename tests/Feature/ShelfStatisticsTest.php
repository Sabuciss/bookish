<?php

namespace Tests\Feature;

use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShelfStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function entry(User $user, string $title, int $read, int $total, int $minutes, string $date): void
    {
        ReadingProgress::query()->create([
            'user_id' => $user->id,
            'book_title' => $title,
            'pages_read' => $read,
            'total_pages' => $total,
            'reading_status' => ReadingProgress::deriveReadingStatus($read, $total),
            'emotion' => $read > 0 ? 'Priecīgs' : 'Want to Read',
            'reading_date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => $minutes,
        ]);
    }

    public function test_shelf_shows_statistics_and_status_columns(): void
    {
        $user = User::factory()->create();
        $this->entry($user, 'Izlasītā grāmata', 500, 500, 600, now()->subDays(2)->toDateString());
        $this->entry($user, 'Pusē izlasīta', 150, 300, 120, now()->subDay()->toDateString());
        $this->entry($user, 'Nākamā grāmata', 0, 400, 0, now()->toDateString());

        $this->actingAs($user)
            ->get(route('reading-shelf.show'))
            ->assertOk()
            ->assertSee('3 grāmatas plauktā')
            ->assertSee('650') // izlasītās lappuses: 500 + 150
            ->assertSee('12<small>h</small> 0<small>min</small>', false) // 600 + 120 min
            ->assertSee('--p: 50;', false) // vidējais progress lasāmajām
            ->assertSee('150') // atlikušās lappuses
            ->assertSee('54') // lpp stundā: 650 / 12 h
            ->assertSee('Izlasītā grāmata')
            ->assertSee('Pusē izlasīta')
            ->assertSee('Nākamā grāmata')
            ->assertSee('data-confirm', false);
    }

    public function test_empty_shelf_renders_friendly_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reading-shelf.show'))
            ->assertOk()
            ->assertSee('0 grāmatas plauktā')
            ->assertSee('Pievieno grāmatas no BookTok vai Jaunumiem.');
    }
}
