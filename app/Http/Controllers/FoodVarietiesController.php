<?php

namespace App\Http\Controllers;

use App\Models\FoodDishes;
use App\Models\FoodVarieties;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FoodVarietiesController extends Controller
{
    public function index($restaurant_id)
    {
        $food_varities = FoodVarieties::with('dishes')
            ->where('restaurants_id', $restaurant_id)
            ->get();

        return response()->json([
            'data' => $food_varities
        ]);
    }


    public function getOne(Request $request, $id)
    {
        $food_varity = FoodVarieties::with('dishes')
            ->where('id', $id)
            ->first();

        return response()->json([
            'data' => $food_varity
        ]);
    }
    
    public function getFoodDishesByCategory (Request $request, $id)
    {
        $food_dishes = FoodDishes::with('variations')
            ->where('food_varieties_id', $id)
            ->get();

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);


        $portal = request()->user();


        $varient = FoodVarieties::create([
            'name' => $request->name,
            'restaurants_id' => $portal->restaurants_id
        ]);

        return response()->json([
            'data' => $varient
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $portal = request()->user();

        $variety = FoodVarieties::find($id);

        if (!$variety) {
            return response()->json([
                'error' => 'Food variety not found'
            ], 404);
        }

        if ($variety->restaurants_id !== $portal->restaurants_id) {
            return response()->json([
                'error' => 'Unauthorized action'
            ], 403);
        }

        $variety->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        // Find the food variety by ID
        $variety = FoodVarieties::find($id);
        $portal = request()->user();

        if (!$variety) {
            return response()->json([
                'error' => 'Food variety not found'
            ], 404);
        }

        if ($variety->restaurants_id !== $portal->restaurants_id) {
            return response()->json([
                'error' => 'Unauthorized action'
            ], 403);
        }

        // Validate the incoming request data
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255', // Adjust validation rules as needed
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Only validate image if provided
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
        }

        // Update the name
        $variety->name = $request->name;

        // If an image is provided, handle image upload
        if ($request->hasFile('image')) {
            // Delete the old image from the server if it exists
            if (file_exists(public_path($variety->image))) {
                unlink(public_path($variety->image));
            }

            // Upload the new image
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images/varieties'), $imageName);
            $variety->image = 'images/varieties/' . $imageName;
        }

        // Save the updated variety
        $variety->save();

        return response()->json([
            'data' => $variety
        ]);
    }
}
