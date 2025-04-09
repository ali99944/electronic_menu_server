<?php

namespace Database\Seeders;

use App\Models\Font;
use Illuminate\Database\Seeder;

class FontSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Font::truncate();
        Font::create([
            'name' => 'Alilato',
            'link' => 'fonts/alfont_com_Alilato-ExtraLight.ttf',
            'description' => 'Alilato',
            'font_category_id' => 1
        ]);
    }
}
