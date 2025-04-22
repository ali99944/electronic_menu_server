<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderItemController extends Controller
{
    public function index()
    {
        $food_varities = OrderItem::all();

        return response()->json([
            'data' => $food_varities
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [

        ]);

        $varient = OrderItem::create([
            'name' => $request->name,

        ]);

        return response()->json([
            'data' => $varient
        ]);
    }

    /**
     * Update the status of a specific OrderItem.
     *
     * @param Request $request
     * @param int $id The ID of the OrderItem to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        // 1. Validate the incoming status
        $validation = Validator::make($request->all(), [
            'status' => [
                'required',
                Rule::in(['completed', 'cancelled', 'in-progress', 'pending']), // Ensure status is one of the allowed values
            ],
        ]);

        // 2. Return error if validation fails
        if ($validation->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validation->errors()
            ], 422); // Use 422 for validation errors
        }

        // 3. Find the OrderItem by ID
        $orderItem = OrderItem::find($id);

        // 4. Return 404 if OrderItem not found
        if (!$orderItem) {
            return response()->json([
                'message' => 'Order item not found'
            ], 404);
        }

        // 5. Update the status
        $orderItem->status = $request->input('status');
        $orderItem->save(); // Persist the change to the database

        // 6. Return success response with updated data
        return response()->json([
            'message' => 'Order item status updated successfully.',
            'data' => $orderItem // Return the updated order item
        ]);
    }

}
