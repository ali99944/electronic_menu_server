<?php

namespace App\Http\Controllers;

use App\Models\FoodDish; // Correct namespace if models are in App\Models
use App\Models\DishVariation; // Import the new model
use App\Models\FoodDishes;
use App\Models\FoodVarieties;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB facade for transactions
use Illuminate\Support\Facades\Log; // Optional: for logging errors
use Illuminate\Support\Facades\Storage; // Better for file handling
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // For more complex rules

class FoodDishesController extends Controller
{
    /**
     * Display dishes for a specific restaurant, including their variations.
     */
    public function index($restaurants_id) // Use clear variable names
    {
        // Eager load variations
        $food_dishes = FoodDishes::with('variations', 'variety', 'restaurant')
            ->where('restaurants_id', $restaurants_id) // Assuming column is restaurants_id
            ->get();

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    /**
     * Display dishes for a specific food variety, including their variations.
     */
    public function variety_dishes($variety_id) // Renamed for clarity, was varient_dishes
    {
        $variety = FoodVarieties::find($variety_id);
        // Eager load variations
        $food_dishes = FoodDishes::with('variations', 'variety')
            ->where('food_varieties_id', $variety_id)
            ->where('restaurants_id', $variety->restaurants_id)
            ->get(); // Use get(), not all()

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    /**
     * Store a new food dish along with its variations.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string', // Often description is optional
            'food_varieties_id' => 'required|exists:food_varieties,id',
            'restaurants_id' => 'required|exists:restaurants,id', // Assuming table name is restaurants
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // Validate the variations array and its contents
            'variations' => 'required|array|min:1', // Must be an array with at least one variation
            'variations.*.name' => 'required|string|max:100', // Each variation needs a name (e.g., Small, Large, Standard)
            'variations.*.price' => 'required|numeric|min:0', // Each variation needs a numeric price
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validation->errors()
            ], 422); // Use 422 for validation errors
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Use Storage facade for better flexibility
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images/dishes'), $imageName);
        }

        // Use a transaction to ensure data consistency
        DB::beginTransaction();
        try {
            // Create the main dish
            $dish = FoodDishes::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => "images/dishes/$imageName",
                'food_varieties_id' => $request->food_varieties_id,
                'restaurants_id' => $request->restaurants_id // Use correct column name
            ]);

            // Create variations using the relationship
            foreach ($request->variations as $variationData) {
                $dish->variations()->create([
                    'name' => $variationData['name'],
                    'price' => $variationData['price'],
                ]);
            }

            DB::commit(); // Everything went well

            // Load variations for the response
            $dish->load('variations');

            return response()->json([
                'message' => 'Food dish created successfully',
                'data' => $dish
            ], 201); // Use 201 for created

        } catch (\Exception $e) {
            DB::rollBack(); // Something went wrong, rollback changes

            // Optional: Log the error
            Log::error("Error creating food dish: " . $e->getMessage());

            // Delete uploaded image if creation failed
            if ($imagePath && Storage::exists(str_replace('storage/', 'public/', $imagePath))) {
                Storage::delete(str_replace('storage/', 'public/', $imagePath));
            }

            return response()->json([
                'message' => 'Failed to create food dish',
                'error' => $e->getMessage() // Provide error in development/debug mode
            ], 500); // Use 500 for server errors
        }
    }

    /**
     * Update an existing food dish and its variations.
     */
    public function update(Request $request, $id)
    {
        // Find the food dish by ID, or fail
        $dish = FoodDishes::find($id);

        if (!$dish) {
            return response()->json([
                'message' => 'Food dish not found'
            ], 404);
        }

        // Validate the request data (allow fields to be optional for update)
        $validation = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'food_varieties_id' => 'sometimes|required|exists:food_varieties,id',
            // 'restaurants_id' => 'sometimes|required|exists:restaurants,id', // Usually you don't change the restaurant
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // Variations array is optional on update, but if provided, must be valid
            'variations' => 'sometimes|required|array|min:1',
            'variations.*.name' => 'required_with:variations|string|max:100', // Required only if 'variations' array is present
            'variations.*.price' => 'required_with:variations|numeric|min:0', // Required only if 'variations' array is present
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validation->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // --- Update Main Dish Details ---
            $dishData = $request->only(['name', 'description', 'food_varieties_id']);
            if (!empty($dishData)) {
                $dish->update($dishData);
            }

            // --- Handle Image Update ---
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->extension();
                $image->move(public_path('images/dishes'), $imageName);

                $dish->image = "images/dishes/$imageName";
                $dish->save(); // Save image path update
            }

            // --- Handle Variations Update (if provided) ---
            if ($request->has('variations')) {
                // Strategy: Delete existing variations and create new ones
                $dish->variations()->delete();

                foreach ($request->variations as $variationData) {
                    $dish->variations()->create([
                        'name' => $variationData['name'],
                        'price' => $variationData['price'],
                    ]);
                }
            }

            DB::commit(); // Commit changes

            // Reload the dish with potentially updated variations
            $dish->load('variations');

            return response()->json([
                'message' => 'Food dish updated successfully',
                'data' => $dish
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error

            Log::error("Error updating food dish ID {$id}: " . $e->getMessage());

            // Note: We don't automatically delete the *newly* uploaded image on update failure,
            // as the original state might be hard to restore perfectly.
            // Consider your strategy here.

            return response()->json([
                'message' => 'Failed to update food dish',
                'error' => $e->getMessage() // Provide error in development/debug mode
            ], 500);
        }
    }


    /**
     * Delete a food dish (and its variations via cascade).
     */
    public function destroy($id) // Removed Request $request as it wasn't used
    {
        // Use findOrFail to automatically handle not found
        $dish = FoodDishes::find($id);

         if (!$dish) {
            return response()->json([
                'message' => 'Food dish not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
             // --- Delete Image ---
             $imagePath = $dish->image ? str_replace('storage/', 'public/', $dish->image) : null;
             if ($imagePath && Storage::exists($imagePath)) {
                 Storage::delete($imagePath);
             }

             // --- Delete Dish (Variations should cascade) ---
             $dish->delete();

             DB::commit();

             return response()->json([
                 'message' => 'Food dish deleted successfully',
                 'status' => true
             ]);
        } catch (\Exception $e) {
             DB::rollBack();
             Log::error("Error deleting food dish ID {$id}: " . $e->getMessage());
             return response()->json([
                'message' => 'Failed to delete food dish',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
