<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DishExtra;
use App\Models\FoodDish; // Import FoodDish
use App\Models\FoodDishes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class DishExtraController extends Controller
{
    // Typically, index/show for individual extras aren't needed via API,
    // they are usually fetched via the FoodDish relationship.
    // We'll focus on store and destroy linked to a FoodDish.

    /**
     * Store a new extra for a specific food dish.
     * Route: POST /api/v1/food-dishes/{dish}/extras
     */
    public function store(Request $request, FoodDishes $dish) // Use Route Model Binding for dish
    {
        // TODO: Add Authorization check - does user own this dish/restaurant?

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                 // Optional: Ensure extra name is unique *for this specific dish*
                 // Rule::unique('dish_extras')->where(function ($query) use ($dish) {
                 //     return $query->where('food_dish_id', $dish->id);
                 // }),
            ],
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Create the extra directly using the relationship
        $extra = $dish->extras()->create($validator->validated());

        return response()->json([
            'message' => 'Dish extra added successfully.',
            'data' => $extra
        ], 201);
    }


    /**
     * Remove the specified extra from a food dish.
     * Route: DELETE /api/v1/food-dishes/{dish}/extras/{extra}
     */
    public function destroy(FoodDishes $dish, DishExtra $extra) // Route Model Binding for both
    {
        // TODO: Add Authorization check

        // Ensure the extra belongs to the dish provided in the URL
        if ($extra->food_dish_id !== $dish->id) {
            return response()->json(['message' => 'Extra not found for this dish.'], 404);
        }

        $extra->delete();

        return response()->json(['message' => 'Dish extra deleted successfully.'], 200); // Or 204
    }

    // Optional: Update method if you need to edit individual extras later
    // public function update(Request $request, FoodDish $dish, DishExtra $extra) { ... }
}
