<?php

namespace App\Http\Controllers;

// Correct Model Names (Singular Convention)
use App\Models\FoodDish;
use App\Models\DishVariation;
use App\Models\DishExtra; // Import DishExtra
use App\Models\FoodDishes;
use App\Models\FoodVarieties;
use App\Models\FoodVariety; // Use singular if model is FoodVariety
use App\Models\Restaurant; // Use singular if model is Restaurant

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File; // Use File facade for deletion
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FoodDishesController extends Controller
{
    /**
     * Display dishes for a specific restaurant, including variations and extras.
     */
    public function index($restaurants_id) // Changed param name for convention
    {
        // Eager load relationships
        $food_dishes = FoodDishes::with(['variations', 'extras', 'variety', 'restaurant']) // Load extras
            ->where('restaurants_id', $restaurants_id) // Use correct column name
            ->latest() // Order by latest first
            ->get();

        // Note: Accessor `public_image_url` will be appended automatically due to $appends in Model

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    /**
     * Display a single food dish with variations and extras.
     */
    public function show($id) // Renamed from get_one, use Route Model Binding if preferred
    {
        $food_dish = FoodDishes::with(['variations', 'extras', 'variety', 'restaurant']) // Load extras
            ->find($id);

        if (!$food_dish) {
            return response()->json(['message' => 'Food dish not found'], 404);
        }

        return response()->json([
            'data' => $food_dish
        ]);
    }

    /**
     * Display dishes for a specific food variety, including variations and extras.
     */
    public function variety_dishes($variety_id)
    {
        // Find variety first to potentially get restaurant ID if needed for complex auth later
        $variety = FoodVarieties::find($variety_id);
        if (!$variety) {
             return response()->json(['message' => 'Food variety not found'], 404);
        }

        $food_dishes = FoodDishes::with(['variations', 'extras', 'variety']) // Load extras
            ->where('food_variety_id', $variety_id)
            // Optional: ->where('restaurants_id', $variety->restaurants_id) // Add if needed
            ->latest()
            ->get();

        return response()->json([
            'data' => $food_dishes
        ]);
    }

    /**
     * Store a new food dish along with its variations and extras.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'food_variety_id' => 'required|exists:food_varieties,id',
            'restaurants_id' => 'required|exists:restaurants,id', // Ensure table/column names match
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Added webp
            // Variations Validation
            'variations' => 'required|array|min:1',
            'variations.*.name' => 'required|string|max:100',
            'variations.*.price' => 'required|numeric|min:0',
            // --- NEW: Extras Validation ---
            'extras' => 'nullable|array', // Allow extras array, can be empty
            'extras.*.name' => 'required_with:extras|string|max:100', // Required if extras array exists
            'extras.*.price' => 'required_with:extras|numeric|min:0', // Required if extras array exists
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $imagePath = null; // Store the path relative to public/
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = 'dish_' . time() . '_' . Str::random(5) . '.' . $image->extension();
            $destinationPath = public_path('images/dishes'); // Define path
             if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0775, true, true);
             }
            if($image->move($destinationPath, $imageName)) {
                 $imagePath = "images/dishes/" . $imageName; // Path to store in DB
             } else {
                 // Handle failed move
                 Log::error("Failed to move uploaded dish image.");
                  return response()->json(['message' => 'Failed to process image upload.'], 500);
             }
        }

        DB::beginTransaction();
        try {
            // Create the main dish
            $dish = FoodDishes::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $imagePath, // Store the relative path
                'food_variety_id' => $request->food_variety_id,
                'restaurants_id' => $request->restaurants_id, // Use correct column name
            ]);

            // Create variations
            foreach ($request->variations as $variationData) {
                $dish->variations()->create([
                    'name' => $variationData['name'],
                    'price' => $variationData['price'],
                ]);
            }

            // --- NEW: Create extras (if provided) ---
            if ($request->has('extras') && is_array($request->extras)) {
                foreach ($request->extras as $extraData) {
                     // Basic check if name and price are provided for this extra item
                     if (!empty($extraData['name']) && isset($extraData['price'])) {
                        $dish->extras()->create([
                            'name' => $extraData['name'],
                            'price' => $extraData['price'],
                        ]);
                     }
                }
            }
            // --- End NEW ---

            DB::commit();

            // Load relationships for the response
            $dish->load(['variations', 'extras', 'variety']); // Load extras

            return response()->json([
                'message' => 'Food dish created successfully',
                'data' => $dish
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating food dish: " . $e->getMessage(), ['exception' => $e]);

            // Delete uploaded image if creation failed and path exists
            if ($imagePath && File::exists(public_path($imagePath))) {
                File::delete(public_path($imagePath));
            }

            return response()->json(['message' => 'Failed to create food dish.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing food dish and its variations/extras.
     * NOTE: Assumes frontend sends POST with _method=PUT or _method=PATCH for FormData
     */
    public function update(Request $request, $id)
    {
        $dish = FoodDishes::find($id);
        if (!$dish) {
            return response()->json(['message' => 'Food dish not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'food_variety_id' => 'sometimes|required|exists:food_varieties,id',
            // No 'restaurants_id' update usually
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048', // Allow image update
             // Use sometimes validation for arrays on update
            'variations' => 'sometimes|required|array|min:1',
            'variations.*.name' => 'required_with:variations|string|max:100',
            'variations.*.price' => 'required_with:variations|numeric|min:0',
            // --- NEW: Extras Validation ---
            'extras' => 'sometimes|nullable|array', // Allow sending null/empty array to clear
            'extras.*.name' => 'required_with:extras|string|max:100',
            'extras.*.price' => 'required_with:extras|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $oldImagePath = $dish->image;
            $newImagePath = $oldImagePath; // Keep old path unless new image uploaded

            // --- Handle Image Update ---
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = 'dish_' . time() . '_' . Str::random(5) . '.' . $image->extension();
                $destinationPath = public_path('images/dishes');
                if (!File::isDirectory($destinationPath)) {
                   File::makeDirectory($destinationPath, 0775, true, true);
                }
                if ($image->move($destinationPath, $imageName)) {
                    $newImagePath = "images/dishes/" . $imageName; // New path
                 } else {
                     throw new \Exception("Failed to move updated dish image.");
                 }
            }

            // --- Update Main Dish Details (including potential new image path) ---
            $dishData = $validator->safe()->except(['variations', 'extras', 'image']); // Get validated data except arrays/file
            $dishData['image'] = $newImagePath; // Set image path (new or old)
            $dish->update($dishData);

            // --- Handle Variations Update (Delete & Recreate) ---
            if ($request->has('variations')) {
                $dish->variations()->delete(); // Delete existing
                foreach ($request->variations as $variationData) {
                    $dish->variations()->create([ // Recreate
                        'name' => $variationData['name'],
                        'price' => $variationData['price'],
                    ]);
                }
            }

            // --- NEW: Handle Extras Update (Delete & Recreate) ---
            if ($request->has('extras')) { // Check if 'extras' key exists in the request
                $dish->extras()->delete(); // Delete existing
                 // Only recreate if the array is not empty
                 if (is_array($request->extras) && count($request->extras) > 0) {
                    foreach ($request->extras as $extraData) {
                        if (!empty($extraData['name']) && isset($extraData['price'])) {
                            $dish->extras()->create([ // Recreate
                                'name' => $extraData['name'],
                                'price' => $extraData['price'],
                            ]);
                        }
                    }
                 }
                 // If 'extras' was sent as an empty array or null, they are simply deleted above.
            }
            // --- End NEW ---

            // --- Delete Old Image ONLY if update was successful & path changed ---
            if ($newImagePath !== $oldImagePath && $oldImagePath && File::exists(public_path($oldImagePath))) {
                File::delete(public_path($oldImagePath));
            }

            DB::commit();

            // Reload relationships
            $dish->load(['variations', 'extras', 'variety']);

            return response()->json([
                'message' => 'Food dish updated successfully',
                'data' => $dish
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating food dish ID {$id}: " . $e->getMessage(), ['exception' => $e]);

            // If a *new* image was uploaded but transaction failed, delete the *new* image
            if ($newImagePath !== $oldImagePath && $newImagePath && File::exists(public_path($newImagePath))) {
                 File::delete(public_path($newImagePath));
            }

            return response()->json(['message' => 'Failed to update food dish.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a food dish.
     */
    public function destroy($id)
    {
        $dish = FoodDishes::find($id);
        if (!$dish) {
            return response()->json(['message' => 'Food dish not found'], 404);
        }

        DB::beginTransaction();
        try {
            $imagePath = $dish->image; // Get path before deleting record

            // Delete Dish (Relations like variations/extras should cascade if set up in DB migration)
            // If cascade delete is not set on DB level, delete manually first:
            // $dish->variations()->delete();
            // $dish->extras()->delete();
            $dish->delete();

            // Delete Image from public path after DB record deleted
            if ($imagePath && File::exists(public_path($imagePath))) {
                 if(!File::delete(public_path($imagePath))) {
                      // Log error if deletion fails, but don't necessarily fail the request
                      Log::warning("Could not delete image file after deleting dish {$id}: " . public_path($imagePath));
                 }
            }

            DB::commit();

            return response()->json(['message' => 'Food dish deleted successfully.', 'status' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting food dish ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to delete food dish.', 'error' => $e->getMessage()], 500);
        }
    }
}
