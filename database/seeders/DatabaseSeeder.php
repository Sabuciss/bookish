<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\BooktokTopBookSeeder;
use Database\Seeders\ReadingHighlightSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        $this->call([
            BooktokTopBookSeeder::class,
            ReadingHighlightSeeder::class,
        ]);

        // User::factory(10)->create();
    }
}
