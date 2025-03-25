<?php

namespace App\Http\Controllers;

use App\Models\FoodDishes;
use App\Models\FoodVarieties;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoodDishesController extends Controller
{
    public function index($id)
    {
        $food_dishes = FoodDishes::with('variety')->where('restaurants_id', $id)->get();

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    public function varient_dishes(Request $request, $variant_id)
    {
        $food_dishes = FoodDishes::where('food_varieties_id', $variant_id)->all();

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [

        ]);

        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $image->move(public_path('images/dishes'), $imageName);

        // $varient = FoodVarieties::find($request->food_varieties_id)->first();

        $dish = FoodDishes::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image' => 'images/dishes/' . $imageName,
            'food_varieties_id' => $request->food_varieties_id,
            'restaurants_id' => $request->restaurant_id
        ]);

        return response()->json([
            'data' => $dish
        ]);
    }

    public function destroy(Request $request, $id)
    {
        FoodDishes::where('id', $id)->delete();

        return response()->json([
            'status' => true
        ]);
    }

       // Update Food Dish
       public function update(Request $request, $id)
       {
           // Find the food dish by ID
           $dish = FoodDishes::find($id);

           if (!$dish) {
               return response()->json([
                   'error' => 'Food dish not found'
               ], 404);
           }

           // Validate the request data
           $validation = Validator::make($request->all(), [
               'name' => 'nullable|string|max:255',
               'price' => 'nullable|numeric',
               'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Only validate if an image is provided
               'food_varieties_id' => 'nullable|exists:food_varieties,id' // Ensure the variety ID exists
           ]);

           if ($validation->fails()) {
               return response()->json([
                   'errors' => $validation->errors()
               ], 400);
           }

           // Update the name and price if provided
           if ($request->has('name')) {
               $dish->name = $request->name;
           }

           if ($request->has('price')) {
               $dish->price = $request->price;
           }

           // Handle image upload if provided
           if ($request->hasFile('image')) {
               // Delete the old image if it exists
               if (file_exists(public_path($dish->image))) {
                   unlink(public_path($dish->image));
               }

               // Upload the new image
               $image = $request->file('image');
               $imageName = time() . '.' . $image->extension();
               $image->move(public_path('images/dishes'), $imageName);
               $dish->image = 'images/dishes/' . $imageName;
           }

           // Update the food variety ID if provided
           if ($request->has('food_varieties_id')) {
               $dish->food_varieties_id = $request->food_varieties_id;
           }

           // Save the updated dish
           $dish->save();

           return response()->json([
               'data' => $dish
           ]);
       }
}
