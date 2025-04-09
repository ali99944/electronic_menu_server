<?php

namespace App\Http\Controllers;

use App\Models\RestaurantMenuStyle;
use App\Models\Restaurants;
use App\Models\RestaurantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    public function index()
    {
        $restaurants = Restaurants::with('settings', 'styles')->get();

        return response()->json([
            'data' => $restaurants
        ]);
    }

    public function get_restaurant($id)
    {
        $restaurant = Restaurants::with('settings', 'styles')->find($id);
        return response()->json([
            'data' => $restaurant
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'code' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()
            ]);
        }

        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $image->move(public_path('images/restaurants/images'), $imageName);

        $logo = $request->file('logo');
        $logoName = time() . '.' . $logo->extension();
        $logo->move(public_path('images/restaurants/logos'), $logoName);

        $restaurant = Restaurants::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => "images/restaurants/images/{$imageName}",
            'logo' => "images/restaurants/logos/{$logoName}",
            'code' => $request->code,
            'currency' => $request->currency ?? 'egp',
            'currency_icon' => $request->currency_icon ?? '£',
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email
        ]);

        $settings = RestaurantSetting::create([
            'restaurants_id' => $restaurant->id,
        ]);

        $styles = RestaurantMenuStyle::create([
            'restaurants_id' => $restaurant->id,
            'font_id' => 1
        ]);


        return response()->json([
            'data' => [
                'restaurant' => $restaurant,
                'styles' => $styles,
                'settings' => $settings,
            ]
        ]);
    }


}
