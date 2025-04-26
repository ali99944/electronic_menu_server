<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\DishVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    public function store(Request $request)
    {
        $selectedExtraIds = collect($request->input('selected_extras', []))
        ->map(fn($id) => (int)$id) // Ensure integer
        ->filter() // Remove any falsy values (like 0 if sent accidentally)
        ->unique()
        ->sort()
        ->values() // Reset keys after sort
        ->all();

        $cartItem = CartItems::where('session_code', $request->header('session_code'))
            ->where('food_dishes_id', $request->food_dishes_id)
            ->where('session_code', $request->header('session_code'))
            ->whereHas('selectedExtras', function ($query) use ($selectedExtraIds) {
                if (!empty($selectedExtraIds)) {
                    $query->whereIn('dish_extra_id', $selectedExtraIds);
                }
            }, '=', count($selectedExtraIds)) // Ensure the count matches exactly
            // Also ensure it *doesn't* have extras that are *not* in the current selection
            // (This second whereHas might be redundant if the first one works correctly with count)
            // ->whereDoesntHave('selectedExtras', function ($query) use ($selectedExtraIds) {
            //     if (!empty($selectedExtraIds)) {
            //         $query->whereNotIn('dish_extra_id', $selectedExtraIds);
            //     } else {
            //         // If selectedExtraIds is empty, ensure the cart item has NO extras
            //         // This condition is implicitly handled by the first whereHas count check being 0
            //     }
            // })
            ->first();

        $dish_variant = DishVariation::where('id', $request->food_dish_variation_id)->first();
        $sessionCode = $request->header('session_code');
        $foodDishId = $request->input('food_dishes_id');
        $dishVariationId = $request->input('food_dish_variation_id');

        // if ($cartItem && $cartItem->selected_dish_variant_name == $dish_variant->name) {
        //     $cartItem->quantity = $cartItem->quantity + 1;
        //     $cartItem->save();
        // } else {
        //     $cartItem = CartItems::create([
        //         'quantity' => 1,
        //         'session_code' => $request->header('session_code'),
        //         'food_dishes_id' => $request->food_dishes_id,
        //         'selected_dish_variant_name' => $dish_variant->name,
        //         'selected_dish_variant_value' => $dish_variant->price
        //     ]);
        // }

        DB::beginTransaction();
        try {
            if ($cartItem) {
                // --- Exact Match Found: Increment Quantity ---
                $cartItem->increment('quantity'); // Efficient way to increment
                $message = 'Item quantity updated.';
                $statusCode = 200;
            } else {
                // --- No Exact Match (New item or different extras): Create New Item ---
                $cartItem = CartItems::create([
                    'quantity' => 1,
                    'session_code' => $sessionCode,
                    'food_dishes_id' => $foodDishId,
                    'food_dish_variation_id' => $dishVariationId,
                    // Store name/price at time of addition for potential history/reporting
                    'selected_dish_variant_name' => $dish_variant->name,
                    'selected_dish_variant_value' => $dish_variant->price
                ]);

                // Attach the selected extras (sync ensures only these are attached)
                if (!empty($selectedExtraIds)) {
                    $cartItem->selectedExtras()->sync($selectedExtraIds);
                }
                // If $selectedExtraIds is empty, sync([]) will detach any existing ones (though it's a new item)

                $message = 'Item added to cart.';
                $statusCode = 201;
            }

            DB::commit();

            // Load relationships for the response
            $cartItem->load(['dish', 'variation', 'selectedExtras']);

            return response()->json([
                'message' => $message,
                'data' => $cartItem
            ], $statusCode);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing cart item store: " . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to process cart item.'], 500);
        }

        return response()->json([
            'data' => $cartItem
        ]);
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
