<?php

namespace App\Http\Controllers;

use App\Events\FreeUpTableEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderStatusChanged;
use App\Models\CartItems;
use App\Models\OrderItem;
use App\Models\Orders;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    public function index()
    {
        $portal = request()->user();

        $orders = Orders::with('order_items')
            ->where('restaurants_id', $portal->restaurants_id)
            ->orderByDesc('created_at')
            ->with('order_items.selected_extras')
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

        $restaurant_settings = RestaurantSetting::where('restaurants_id', $request->query('restaurants_id'))->first();

        $order = Orders::create([
            'notes' => $request->notes ?? null,
            'status' => 'pending',
            'cost_price' => $cart_items->sum(fn(CartItems $cart_item) => $cart_item->selected_dish_variant_value * $cart_item->quantity),
            'restaurant_table_number' => $request->restaurant_table_number ?? 0,
            'client_name' => $request->client_name,
            'client_location' => $request->client_location,
            'client_location_landmark' => $request->client_location_landmark,
            'client_phone' => $request->client_phone,
            'restaurants_id' => $request->query('restaurants_id'),
            'order_type' => $restaurant_settings->has_delivery == true ? 'delivery' : 'inside'
        ]);

        $cart_items->each(function (CartItems $cart_item) use ($order) {
            $order_item = OrderItem::create([
                'name' => $cart_item->dish->name . ' - ' . $cart_item->selected_dish_variant_name,
                'price' => $cart_item->selected_dish_variant_value,
                'image' => $cart_item->dish->image,
                'quantity' => $cart_item->quantity,
                'orders_id' => $order->id
            ]);

            CartItems::where('id', $cart_item->id)->delete();
            $order_item->selected_extras()->attach($cart_item->selected_extras()->pluck('id'));

            DB::table('cart_item_dish_extra')->where('cart_items_id', $cart_item->id)->delete();

        });

        // CartItems::where('session_code', $request->header('session_code'))->truncate();


        event(new OrderCreatedEvent(
            Orders::where('id', $order->id)
                ->with('order_items')
                ->first()
        ));


        RestaurantTables::where('table_number', $order->restaurant_table_number)->update(['status' => 'busy']);

        return response()->json([
            'data' => $order
        ]);
    }

    // Update Order Status
    public function updateStatus(Request $request, $orderId)
    {
        // Validate the input status
        $validation = Validator::make($request->all(), [
            'status' => 'required|in:pending,rejected,completed,in-progress,paid'
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

        event(new OrderStatusChanged(
            $order->client_phone
        ));

        if($request->status == 'paid') {
            event(new FreeUpTableEvent($order->restaurant_table_number));
            RestaurantTables::where('table_number', $order->restaurant_table_number)->update(['status' => 'free']);
        }

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
