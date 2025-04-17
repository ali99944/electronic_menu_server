<?php

namespace Database\Seeders;

use App\Models\FontCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FontCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FontCategory::updateOrCreate(
            [
                'id' => 1,
            ],
            [
                'name' => 'Basic Fonts',
                'description' => 'Basic Fonts',
                'total_fonts' => 0
            ]
        );
    }
}
