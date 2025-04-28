<?php

namespace App\Http\Controllers;

use App\Events\FreeUpTableEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderStatusChanged;
use App\Models\CartItems;
use App\Models\OrderItem;
use App\Models\Orders;
use App\Models\Restaurants;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTables;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelPdf\Facades\Pdf;

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
// --- Dummy Data (Similar structure, maybe add more details) ---
$restaurant = [
    'name' => 'مطعم الذواقة الحديث',
    'logo_url' => 'https://img.freepik.com/free-vector/detailed-chef-logo-template_23-2148987940.jpg?ga=GA1.1.1880227217.1744728103&semt=ais_hybrid&w=740', // Public path
    'address_line1' => '123 شارع الأمير محمد بن عبدالعزيز',
    'address_line2' => 'حي العليا، الرياض 11564',
    'phone' => '+966 11 419 XXXX',
    'tax_number' => '300123456700003', // VAT Number
];

$order = [
    'id' => 1206,
    'order_code' => 'ORD-' . sprintf('%05d', 1206),
    'date' => now()->format('Y-m-d H:i:s'),
    'type' => 'داخل مطعم',
    'table_number' => null,
    'status' => 'مكتمل',
];

$customer = [ // Example for delivery
    'name' => 'خالد الأحمد',
    'phone' => '+966 50 XXX XXXX',
    'address' => 'حي النرجس، شارع الأمانة، مبنى 15، شقة 3، الرياض',
];

$items = [
    // ... (same items as before or add more complexity) ...
     [ 'id' => 1, 'dish_name' => 'ستيك ريب آي', 'variation_name' => 'ميديم ويل', 'quantity' => 1, 'unit_price' => 120.00, 'total_price' => 120.00, ],
     [ 'id' => 2, 'dish_name' => 'بطاطس مقلية', 'variation_name' => null, 'quantity' => 1, 'unit_price' => 15.00, 'total_price' => 15.00, ],
     [ 'id' => 3, 'dish_name' => 'عصير برتقال طازج', 'variation_name' => null, 'quantity' => 2, 'unit_price' => 18.00, 'total_price' => 36.00, ],
     [ 'id' => 4, 'dish_name' => 'تشيز كيك فراولة', 'variation_name' => null, 'quantity' => 1, 'unit_price' => 35.00, 'total_price' => 35.00, ],
];

// Calculate Totals
$subtotal = collect($items)->sum('total_price');
$taxRate = 0.15;
$taxAmount = $subtotal * $taxRate;
$deliveryCharge = ($order['type'] == 'توصيل') ? 15.00 : 0.00; // Example delivery charge
$discount = 0.00;
$grandTotal = $subtotal + $taxAmount + $deliveryCharge - $discount;

$totals = [
    'subtotal' => $subtotal,
    'tax_label' => 'ضريبة القيمة المضافة (15%)',
    'tax_amount' => $taxAmount,
    'delivery_charge' => $deliveryCharge,
    'discount_label' => 'خصم',
    'discount_amount' => $discount,
    'grand_total' => $grandTotal,
    'currency_icon' => $restaurant['currency_icon'] ?? 'جنيه', // Assume currency from restaurant
];

// --- Generate Simplified E-Invoice QR Code (ZATCA format - TLV) ---
// IMPORTANT: This requires the `php-tlv` library if you want full ZATCA compliance.
// For demonstration, we'll just encode basic info.
// For full ZATCA compliance: composer require sabic/php-tlv
$qrCodeString = $this->generateSimplifiedInvoiceQrCode(
    $restaurant['name'],
    $restaurant['tax_number'],
    $order['date'],
    $totals['grand_total'],
    $totals['tax_amount']
);

$qrCodeImage = null;
if ($qrCodeString) {
     try {
        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64'  => true, // Easier to embed directly in Blade
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 4, // Smaller scale for embedding
            'quietZoneSize'=> 1,
        ]);
         $qrCodeImage = (new QRCode($options))->render($qrCodeString);
     } catch (\Exception $e) {
         logger()->error('QR Code generation failed: ' . $e->getMessage());
         // Continue without QR code if generation fails
     }
}
// --- End QR Code Generation ---


$data = [
    'restaurant' => $restaurant,
    'order' => $order,
    'customer' => $customer,
    'items' => $items,
    'totals' => $totals,
    'qr_code_image' => $qrCodeImage, // Pass QR code image data URI
    'primary_color' => '#A70000',
];

Pdf::view('invoices.orbisq_professional', $data)
    ->format('a4')
    ->save(time() . 'invoice.pdf');

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

    private function generateSimplifiedInvoiceQrCode(string $sellerName, string $vatNumber, string $timestamp, float $totalAmount, float $vatAmount): ?string
    {
        try {
            // Function to convert string to TLV format (Tag, Length, Value) HEX
            $toTlv = function(int $tag, string $value): string {
                $valueBytes = mb_convert_encoding($value, 'UTF-8');
                $len = strlen($valueBytes);
                return sprintf('%02X%02X%s', $tag, $len, bin2hex($valueBytes));
            };

            $tlvString = $toTlv(1, $sellerName) .
                         $toTlv(2, $vatNumber) .
                         $toTlv(3, Carbon::parse($timestamp)->toIso8601String()) . // ISO8601 format timestamp
                         $toTlv(4, number_format($totalAmount, 2, '.', '')) . // Format number with 2 decimals
                         $toTlv(5, number_format($vatAmount, 2, '.', '')); // Format number with 2 decimals

            return base64_encode(hex2bin($tlvString)); // Encode the hex string to binary then base64

        } catch (\Exception $e) {
            logger()->error('QR Code TLV generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
