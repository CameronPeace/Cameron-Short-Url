<?php

namespace Database\Seeders;

use App\Models\ShortUrlClick;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        ShortUrlClick::factory(30)->create();
    }
}
