<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\DishVariation;
use Illuminate\Http\Request;
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
     * Add an item (specific variation) to the cart,
     * incrementing quantity if the exact item+variation exists,
     * otherwise creating a new cart item entry.
     */
    public function store(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'food_dishes_id' => [
                'required',
                'integer',
                Rule::exists('food_dishes', 'id'), // Ensure the base dish exists
            ],
            'food_dish_variation_id' => [
                'required',
                'integer',
                // Ensure the variation exists AND belongs to the specified dish
                Rule::exists('dish_variations', 'id')->where(function ($query) use ($request) {
                    return $query->where('food_dish_id', $request->food_dishes_id);
                }),
            ],
        ]);

        // Also validate the session header existence (though usually handled by middleware/request lifecycle)
        $sessionCode = $request->header('session_code');
        if (empty($sessionCode)) {
            return response()->json(['message' => 'Session code is missing.'], 400);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Get Validated Data
        $foodDishId = $request->input('food_dishes_id');
        $dishVariationId = $request->input('food_dish_variation_id');

        // 3. Find the Specific Dish Variation Details (Validated above, safe to fetch)
        // You could potentially skip this query if you don't need name/price immediately
        // But it's good practice to fetch it for the create step.
        $dish_variant = DishVariation::find($dishVariationId);
        if (!$dish_variant) {
             // This shouldn't happen due to validation, but belt-and-suspenders
             return response()->json(['message' => 'Dish variation not found.'], 404);
        }


        // 4. Check for Existing Cart Item *with the same variation*
        $cartItem = CartItems::where('session_code', $sessionCode)
            ->where('food_dishes_id', $foodDishId)
            ->where('food_dish_variation_id', $dishVariationId) // <<<--- Key change: Check variation ID too
            ->first();

        // 5. Update Quantity or Create New Item
        if ($cartItem) {
            // --- Exact item (Dish + Variation) found, increment quantity ---
            $cartItem->quantity += 1; // Use += 1 for increment
            $cartItem->save();
        } else {
            // --- Item (Dish + Variation) not found, create a new entry ---
            $cartItem = CartItems::create([
                'quantity' => 1,
                'session_code' => $sessionCode,
                'food_dishes_id' => $foodDishId,
                'food_dish_variation_id' => $dishVariationId, // <<<--- Store the variation ID
                'selected_dish_variant_name' => $dish_variant->name, // Store variation name
                'selected_dish_variant_value' => $dish_variant->price // Store variation price
            ]);
        }

        // 6. Return Response (Consider returning the whole cart or just the item)
        // Eager load details if needed for the response
        // $cartItem->load(['dish', 'variation']); // Example relationship names

        return response()->json([
            'message' => $cartItem->wasRecentlyCreated ? 'Item added to cart.' : 'Item quantity updated.',
            'data' => $cartItem
        ], $cartItem->wasRecentlyCreated ? 201 : 200); // Use 201 for created, 200 for updated
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
