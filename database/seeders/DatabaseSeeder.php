<?php

namespace Database\Seeders;

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
        $this->call([
            BooktokTopBookSeeder::class,
            ReadingHighlightSeeder::class,
        ]);
    }
}
