<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\DishVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CartItemsController extends Controller
{
    public function index(Request $request)
    {
        $food_varities = CartItems::where('session_code', $request->header('session_code') )
            ->with('dish')
            ->get();

        return response()->json([
            'data' => $food_varities
        ]);
    }

    /**
     * Add an item (variation + optional extras) to the cart.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'food_dishes_id' => ['required', 'integer', Rule::exists('food_dishes', 'id')],
            'food_dish_variation_id' => [
                'required', 'integer',
                Rule::exists('dish_variations', 'id')->where(function ($query) use ($request) {
                    return $query->where('food_dish_id', $request->food_dishes_id);
                }),
            ],
            // --- NEW: Validate selected_extras ---
            'selected_extras' => 'nullable|array', // Allow null or an array
            'selected_extras.*' => [ // Validate each element in the array
                'integer',
                 // Ensure each extra ID exists AND belongs to the specified dish
                 Rule::exists('dish_extras', 'id')->where(function ($query) use ($request) {
                     return $query->where('food_dish_id', $request->food_dishes_id);
                 }),
            ]
        ]);

        $sessionCode = $request->header('session_code');
        if (empty($sessionCode)) {
            return response()->json(['message' => 'Session code is missing.'], 400);
        }

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $foodDishId = $request->input('food_dishes_id');
        $dishVariationId = $request->input('food_dish_variation_id');
        $selectedExtraIds = $request->input('selected_extras', []); // Default to empty array if null/missing
        // Ensure unique IDs and integers if needed (though validation should handle type)
         $selectedExtraIds = collect($selectedExtraIds)->map(fn($id) => (int)$id)->unique()->sort()->values()->all();


        // We need the DishVariation details anyway if creating a new item
        $dish_variant = DishVariation::find($dishVariationId);
        if (!$dish_variant) {
            return response()->json(['message' => 'Dish variation not found.'], 404);
        }

        // --- Find Existing Cart Item ---
        // Now, finding an existing item depends on whether extras should create a new line item or not.
        // OPTION 1: Treat items with DIFFERENT sets of extras as DIFFERENT cart items.
        //           (More complex query, usually not preferred for simple quantity increments)
        // OPTION 2: Treat items with the SAME dish + variation as the SAME cart item,
        //           regardless of extras, and just increment quantity. This is simpler
        //           and more common. Let's implement Option 2.

        $cartItem = CartItems::where('session_code', $sessionCode)
            ->where('food_dishes_id', $foodDishId)
            ->where('food_dish_variation_id', $dishVariationId)
             // --- IMPORTANT: Check if extras MATCH exactly ---
             // We only increment quantity if the *exact* same set of extras is already in the cart for this dish/variation.
             // If the extras are different, we MUST create a new cart item line.
             ->whereHas('selectedExtras', function ($query) use ($selectedExtraIds) {
                 // Check if the count of related extras matches the count of provided IDs
                 $query->havingRaw('COUNT(dish_extra_id) = ?', [count($selectedExtraIds)]);
                 // Ensure all provided IDs exist in the relationship
                 if (!empty($selectedExtraIds)) {
                     $query->whereIn('dish_extra_id', $selectedExtraIds);
                 }
             }, '=', count($selectedExtraIds)) // This ensures the relationship exists AND has the exact count
            ->first();


        DB::beginTransaction();
        try {
            if ($cartItem) {
                // --- Item with EXACT same variation and extras found: Increment Quantity ---
                $cartItem->quantity += 1;
                $cartItem->save();
                $message = 'Item quantity updated.';
                $statusCode = 200;

            } else {
                // --- Item not found OR extras are different: Create New ---
                $cartItem = CartItems::create([
                    'quantity' => 1,
                    'session_code' => $sessionCode,
                    'food_dishes_id' => $foodDishId,
                    'food_dish_variation_id' => $dishVariationId,
                    'selected_dish_variant_name' => $dish_variant->name,
                    'selected_dish_variant_value' => $dish_variant->price
                ]);

                // --- Sync selected extras using the relationship ---
                 if (!empty($selectedExtraIds)) {
                     // `sync` will attach only the IDs provided, detaching any others (perfect for create)
                    $cartItem->selectedExtras()->sync($selectedExtraIds);
                 }
                 $message = 'Item added to cart.';
                 $statusCode = 201;
            }

            DB::commit();

            // Load relationships for the response
            $cartItem->load(['dish', 'variation', 'selectedExtras']); // Load extras

            return response()->json([
                'message' => $message,
                'data' => $cartItem
            ], $statusCode);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing cart item: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to process cart item.'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        CartItems::where('id', $id)->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validate the quantity input
        $validation = Validator::make($request->all(), [
            'quantity' => 'required|numeric'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
        }

        // Retrieve the cart item
        $cartItem = CartItems::find($id);

        if (!$cartItem) {
            return response()->json([
                'error' => 'Cart item not found'
            ], 404);
        }

        // Calculate new quantity
        $newQuantity = $cartItem->quantity + $request->quantity;

        if ($newQuantity <= 0) {
            // If quantity becomes 0 or negative, delete the item
            $cartItem->delete();
            return response()->json([
                'status' => 'deleted'
            ]);
        }

        // Update the cart item quantity
        $cartItem->update([
            'quantity' => $newQuantity
        ]);

        return response()->json([
            'data' => $cartItem
        ]);
    }



    public function destroyCartItem(Request $request, $id)
    {
        CartItems::where('id', $id)->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function clearAllCartItems(Request $request)
    {
        CartItems::where('session_code', $request->header('session_code'))->delete();

        return response()->json([
            'status' => true
        ]);
    }

}
