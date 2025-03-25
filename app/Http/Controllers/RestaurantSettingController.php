<?php

namespace App\Http\Controllers;

use App\Models\RestaurantSetting;
use Illuminate\Http\Request;

class RestaurantSettingController extends Controller
{
    public function settings(Request $request, $id)
    {
        $restaurant_settings = RestaurantSetting::where('id', $id)->first();


        return response()->json([
            'data' => $restaurant_settings
        ]);
    }
    public function update_settings(Request $request, $id)
    {
        $restaurant_settings = RestaurantSetting::where('id', $id)->first();

        $restaurant_settings->update([
            'is_portal_active' => $request->is_portal_active,
            'is_restaurant_active' => $request->is_restaurant_active,
            'is_meals_activated' => $request->is_meals_activated,
        ]);

        return response()->json([
            'message' => 'Settings updated successfully'
        ]);

    }
}
