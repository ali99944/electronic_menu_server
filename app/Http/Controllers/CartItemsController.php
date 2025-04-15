<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\DishVariation;
use Illuminate\Http\Request;
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
        $cartItem = CartItems::where('session_code', $request->header('session_code'))
            ->where('food_dishes_id', $request->food_dishes_id)
            ->where('session_code', $request->header('session_code'))
            ->first();

        $dish_variant = DishVariation::where('id', $request->food_dish_variation_id)->first();

        if ($cartItem) {
            $cartItem->quantity = $cartItem->quantity + 1;
            $cartItem->save();
        } else {
            $cartItem = CartItems::create([
                'quantity' => 1,
                'session_code' => $request->header('session_code'),
                'food_dishes_id' => $request->food_dishes_id,
                'selected_dish_variant_name' => $dish_variant->name,
                'selected_dish_variant_value' => $dish_variant->price
            ]);
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
