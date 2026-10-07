<?php

namespace Tests\Feature;

use App\Models\BooktokTopBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeding_is_repeatable_and_does_not_create_known_login_credentials(): void
    {
        Http::fake();

        $this->seed();
        $bookCount = BooktokTopBook::query()->count();

        $this->seed();

        Http::assertNothingSent();
        $this->assertSame($bookCount, BooktokTopBook::query()->count());
        $this->assertDatabaseHas('booktok_top_books', [
            'rank_position' => 1,
            'title' => 'A Court of Mist and Fury',
            'google_volume_id' => null,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'admin@gmail.com']);

        $sampleUser = User::query()->where('email', 'seeded-highlights@bookish.local')->firstOrFail();
        $this->assertFalse(Hash::check('seeded-highlights', $sampleUser->password));
    }
}
