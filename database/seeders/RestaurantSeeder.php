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
        Restaurants::truncate();
        Restaurants::create([
            'id' => 1,
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
        ]);

        Restaurants::create([
            'id' => 2,
            'logo' => 'images/restaurants/logos/1742943302.png',
            'image' => 'images/restaurants/images/1742943302.png',
            'name' => 'شاورما سما الخريف',
            'description' => 'شاورما',
            'currency' => 'egp',
            'currency_icon' => '£',
            'code' => 'sharwma-sma-al-kharif',
            'phone' => '01234567890',
            'email' => '2kxwU@example.com',
            'whatsapp' => '01234567890'
        ]);


        RestaurantSetting::truncate();
        RestaurantSetting::create([
            'restaurants_id' => 1,
            'is_portal_active' => true,
            'has_meals' => true,
            'has_orders' => true,
            'is_restaurant_active' => true
        ]);

        RestaurantSetting::create([
            'restaurants_id' => 2,
            'is_portal_active' => true,
            'has_meals' => false,
            'has_orders' => false,
            'is_restaurant_active' => true
        ]);


        RestaurantMenuStyle::truncate();
        RestaurantMenuStyle::create([
            'banner_title_color' => '#ffffff',
            'banner_description_color' => '#ABABAA',
            'font_id' => 1,
            'header_bg_color' => '#1B525C',
            'header_text_color' => '#ffffff',
            'menu_background_color' => '#F3F5F7',
            'primary_color' => '#1B525C',
            'primary_text_color' => '#ffffff',
            'restaurants_id' => 1
        ]);

        RestaurantMenuStyle::create([
            'banner_title_color' => '#ffffff',
            'banner_description_color' => '#ABABAA',
            'font_id' => 1,
            'header_bg_color' => '#1B525C',
            'header_text_color' => '#ffffff',
            'menu_background_color' => '#F3F5F7',
            'primary_color' => '#1B525C',
            'primary_text_color' => '#ffffff',
            'restaurants_id' => 2
        ]);


        RestaurantPortals::truncate();

        RestaurantPortals::create([
            'username' => 'asmaak-najel',
            'password' => bcrypt('asmaak-najel'),
            'restaurants_id' => 1
        ]);

        RestaurantPortals::create([
            'username' => 'sharwma-sma-al-kharif',
            'password' => bcrypt('sharwma-sma-al-kharif'),
            'restaurants_id' => 2
        ]);
    }
}
