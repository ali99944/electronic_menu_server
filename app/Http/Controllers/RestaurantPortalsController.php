<?php

namespace App\Http\Controllers;

use App\Models\RestaurantPortals;
use App\Models\Restaurants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RestaurantPortalsController extends Controller
{
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'restaurants_id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 500);
        }

        $portal = RestaurantPortals::create([
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'restaurants_id' => $request->restaurants_id
        ]);

        return response()->json([
            'data' => $portal,
            'success' => true
        ]);
    }

    public function login_portal(Request $request)
{
    $validation = Validator::make($request->all(), [
        'username' => 'required',
        'password' => 'required',
    ]);

    if ($validation->fails()) {
        return response()->json([
            'errors' => $validation->errors()
        ], 500);
    }

    if (!Auth::guard('restaurant_portal')->attempt([
        'username' => $request->username,
        'password' => $request->password
    ])) {
        return response()->json('Invalid credentials', 500);
    }

    // Get the user directly from the guard
    $portal = Auth::guard('restaurant_portal')->user();

    if (!$portal) {
        return response()->json([
            'errors' => 'User not found after authentication'
        ]);
    }

    $token = $portal->createToken('auth_token')->plainTextToken;
    $restaurant = Restaurants::with('settings', 'styles')->where('id', $portal->restaurants_id)->first();

    return response()->json([
        'data' => [
            'token' => $token,
            'restaurant' => $restaurant
        ],
        'success' => true
    ]);
}
}
