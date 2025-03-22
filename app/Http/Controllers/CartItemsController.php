<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
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
        $validation = Validator::make($request->all(), [

        ]);


        $cartItem = CartItems::create([
            'quantity' => 1,
            'session_code' => $request->header('session_code'),
            'food_dishes_id' => $request->food_dishes_id
        ]);

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
            'quantity' => 'required|integer'
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

        // Determine new quantity
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

}
