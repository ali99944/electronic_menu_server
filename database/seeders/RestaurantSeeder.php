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

        RestaurantSetting::updateOrCreate(
            ['id' => 1000000],
            [
                'restaurants_id' => 1000000,
                'is_portal_active' => 1,
                'is_restaurant_active' => 1,
                'has_delivery' => 1,
                'has_orders' => 1,
            ]
        );

        RestaurantMenuStyle::updateOrCreate(
            ['id' => 1000000],
            [
                'restaurants_id' => 1000000,
                'font_id' => 1,
                'menu_background_color' => '#f5f5f5',
                'header_bg_color' => '#f5f5f5',
                'header_text_color' => '#000000',
                'banner_title_color' => '#000000',
                'banner_description_color' => '#000000',
                'primary_color' => '#ff9900',
                'primary_text_color' => '#ffffff',

            ]
        );

        RestaurantPortals::updateOrCreate(
            ['id' => 1000000],
            [
                'restaurants_id' => 1000000,
                'username' => 'admin',
                'password' => bcrypt('admin'),
            ]
        );
    }
}
