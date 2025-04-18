<?php

namespace App\Http\Controllers;

use App\Events\OrderCreatedEvent;
use App\Models\CartItems;
use App\Models\OrderItem;
use App\Models\Orders;
use App\Models\Restaurants;
use App\Models\RestaurantTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    public function index()
    {
        $portal = request()->user();

        $orders = Orders::with('order_items')
            ->where('restaurants_id', $portal->restaurants_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders
        ]);
    }

    public function get_one(Request $request, $id)
    {
        $order = Orders::where('id', $id)->with('order_items')->first();

        return response()->json($order);
    }

    public function get_client_orders(Request $request, $phone)
    {
        $orders = Orders::where('client_phone', $phone)->with('order_items')->orderByDesc('created_at')->get();

        return response()->json($orders);
    }


    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [

        ]);


        $cart_items = CartItems::where('session_code', $request->header('session_code'))->with('dish')->get();

        if($cart_items->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "You haven't selected anything yet"
            ], 500);
        }


        $order = Orders::create([
            'notes' => $request->notes ?? null,
            'status' => 'pending',
            'cost_price' => $cart_items->sum(fn(CartItems $cart_item) => $cart_item->selected_dish_variant_value * $cart_item->quantity),
            'restaurant_table_number' => 1,
            'client_name' => $request->client_name ?? 'لا يوجد',
            'client_location' => $request->client_location ?? 'لا يوجد',
            'client_location_landmark' => $request->client_location_landmark ?? 'لا يوجد',
            'client_phone' => $request->client_phone ?? 'لا يوجد',
            'restaurants_id' => $request->query('restaurants_id'),
        ]);

        $cart_items->each(function (CartItems $cart_item) use ($order) {
            OrderItem::create([
                'name' => $cart_item->dish->name,
                'price' => $cart_item->selected_dish_variant_value,
                'image' => $cart_item->dish->image,
                'quantity' => $cart_item->quantity,
                'orders_id' => $order->id
            ]);
        });

        CartItems::where('session_code', $request->header('session_code'))->truncate();


        // event(new OrderCreatedEvent(
        //     Orders::where('id', $order->id)
        //         ->with('order_items', 'restaurant_table')
        //         ->first()
        // ));

        return response()->json([
            'data' => $order
        ]);
    }

    // Update Order Status
    public function updateStatus(Request $request, $orderId)
    {
        // Validate the input status
        $validation = Validator::make($request->all(), [
            'status' => 'required|in:pending,rejected,completed,in-progress'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
        }

        // Find the order by its ID
        $order = Orders::find($orderId);

        if (!$order) {
            return response()->json([
                'error' => 'Order not found'
            ], 404);
        }

        // Update the status of the order
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'data' => $order
        ]);
    }

    public function destroy(Request $request, $id)
    {
        Orders::where('id', $id)->delete();

        return response()->json([
            'status' => true
        ]);
    }
}
