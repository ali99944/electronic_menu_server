<?php

namespace Database\Seeders;

use App\Models\RestaurantMenuStyle;
use App\Models\RestaurantPortals;
use App\Models\Restaurants;
use App\Models\RestaurantSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Restaurants::updateOrCreate(
            ['id' => 1000000],
            [
                'logo' => 'images/restaurants/logos/1742943302.png',
                'image' => 'images/restaurants/images/1742943302.png',
                'name' => 'Admin',
                'description' => 'Account for testing',
                'currency' => 'egp',
                'currency_icon' => '£',
                'code' => 'admin',
                'phone' => '01234567890',
                'email' => '2kxwU@example.com',
                'whatsapp' => '01234567890'
            ]
        );

        Restaurants::updateOrCreate(
            ['id' => 1],
            [
                'logo' => 'images/restaurants/logos/1742943302.png',
                'image' => 'images/restaurants/images/1742943302.png',
                'name' => 'اسماك ناجل',
                'description' => 'افضل انواع الاسماك',
                'currency' => 'egp',
                'currency_icon' => '£',
                'code' => 'asmaak-najel',
                'phone' => '01234567890',
                'email' => '2kxwU@example.com',
                'whatsapp' => '01234567890'
            ]
        );
    }
}
