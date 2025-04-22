<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FontCategorySeeder::class,
            FontSeeder::class,
            RestaurantSeeder::class,
            // FeatureSeeder::class,
            // ChangelogSeeder::class
        ]);
    }
}
