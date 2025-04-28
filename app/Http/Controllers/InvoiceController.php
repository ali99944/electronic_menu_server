<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Spatie\LaravelPdf\Facades\Pdf;// To check if view exists

class InvoiceController extends Controller
{
    /**
     * Display a test Orbis Q basic invoice.
     */
    public function showTestInvoice()
    {
        // --- Dummy Data ---
        $restaurant = [
            'name' => 'مطعم الذواقة الحديث',
            'logo_url' => 'https://img.freepik.com/free-vector/detailed-chef-logo-template_23-2148987940.jpg?ga=GA1.1.1880227217.1744728103&semt=ais_hybrid&w=740', // Replace with actual path or URL if available
            'address' => '123 شارع الأمير محمد، حي العليا، الرياض 11564',
            'phone' => '+96611419XXXX',
            'email' => 'info@gourmet.sa',
            'tax_number' => '300123456700003', // Example VAT/Tax Number
        ];

        $order = [
            'id' => 1205,
            'order_code' => 'ORD-'.sprintf('%05d', 1205), // Formatted order code
            'date' => now()->format('Y-m-d H:i:s'),
            'type' => 'داخل المطعم', // or 'توصيل'
            'table_number' => 5, // Nullable if delivery
            'status' => 'مكتمل', // Or other relevant status
        ];

        // Customer is optional, might be null for dine-in walk-ins
        $customer = null;
        // Example for delivery:
        // $customer = [
        //     'name' => 'عبدالله محمد',
        //     'phone' => '+966555123XXX',
        //     'address' => 'حي النرجس، شارع الياسمين، فيلا 7، الرياض'
        // ];

        $items = [
            [
                'id' => 1,
                'dish_name' => 'بيتزا مارجريتا',
                'variation_name' => 'كبير',
                'quantity' => 1,
                'unit_price' => 45.00,
                'total_price' => 45.00,
            ],
            [
                'id' => 2,
                'dish_name' => 'سلطة سيزر',
                'variation_name' => 'عادي', // Or null/empty if no variation
                'quantity' => 2,
                'unit_price' => 25.00,
                'total_price' => 50.00,
            ],
            [
                'id' => 3,
                'dish_name' => 'مشروب غازي',
                'variation_name' => 'بيبسي',
                'quantity' => 3,
                'unit_price' => 5.00,
                'total_price' => 15.00,
            ],
        ];

        // Calculate Totals
        $subtotal = collect($items)->sum('total_price');
        $taxRate = 0.15; // Example VAT rate (15%)
        $taxAmount = $subtotal * $taxRate;
        $discount = 0.00; // Example discount
        $grandTotal = $subtotal + $taxAmount - $discount;

        $totals = [
            'subtotal' => $subtotal,
            'tax_label' => 'ضريبة القيمة المضافة (15%)',
            'tax_amount' => $taxAmount,
            'discount' => $discount,
            'grand_total' => $grandTotal,
        ];

        $data = [
            'restaurant' => $restaurant,
            'order' => $order,
            'customer' => $customer,
            'items' => $items,
            'totals' => $totals,
            'primary_color' => '#A70000', // Orbis Q Red
        ];

        $viewPath = 'invoices.orbisq_basic';

        // Check if the view exists before returning
        if (!View::exists($viewPath)) {
            abort(404, "Invoice template not found.");
        }

        return view($viewPath, $data);
    }


    /**
     * Display a professional test Orbis Q invoice.
     */
    public function showProfessionalInvoice()
    {
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
            'type' => 'توصيل',
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

        $viewPath = 'invoices.orbisq_professional'; // New view name

        if (!View::exists($viewPath)) {
            abort(404, "Invoice template '$viewPath' not found.");
        }

        $pdf_path =  time() . ' - invoice.pdf';
        Pdf::view('invoices.orbisq_professional', $data)
        ->format('a4')
        ->save($pdf_path);

        return view($viewPath, $data);
    }


    /**
     * Generates a simplified Base64 encoded string for QR Code (Basic TLV format).
     * For full ZATCA compliance, use a dedicated library like sabic/php-tlv.
     *
     * Ref: https://zatca.gov.sa/ar/E-Invoicing/SystemsDevelopers/Pages/E-invoice-specifications.aspx
     */
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

/**
     * Display an advanced, detailed Orbis Q invoice.
     */
    public function showAdvancedInvoice()
    {
        // --- Dummy Data (More comprehensive) ---
        $restaurant = [
            'name' => 'مطعم الذواقة الحديث',
            'logo_url' => 'https://img.freepik.com/free-vector/detailed-chef-logo-template_23-2148987940.jpg?ga=GA1.1.1880227217.1744728103&semt=ais_hybrid&w=740', // Public path
            'address_line1' => '123 شارع الأمير محمد بن عبدالعزيز',
            'address_line2' => 'حي العليا، الرياض 11564',
            'phone' => '+966 11 419 XXXX',
            'website' => 'www.gourmet-eatery.sa', // Example website
            'tax_number' => '300123456700003',
            'currency_code' => 'SAR', // ISO currency code
            'currency_icon' => 'ر.س',
        ];

        $order = [
            'id' => 1207,
            'order_code' => 'ORD-' . sprintf('%05d', 1207),
            'date' => now()->subHours(1)->format('Y-m-d H:i:s'), // Order placed an hour ago
            'due_date' => now()->addDays(7)->format('Y-m-d'), // Example due date if applicable
            'type' => 'توصيل',
            'table_number' => null,
            'status' => 'مدفوع', // Payment status
            'payment_method' => 'بطاقة ائتمانية (Visa **** 1234)', // Example payment method
            'notes_from_customer' => 'الرجاء وضع الصوص على الجانب. لا يوجد بصل.', // Example notes
        ];

        // Billing address might be different from shipping for some business models, but usually same for restaurants
        $customer_billing = [
            'name' => 'خالد الأحمد',
            'address' => 'حي النرجس، شارع الأمانة، مبنى 15، الرياض', // Billing Address
            'phone' => '+966 50 XXX XXXX',
        ];
        // Shipping address only relevant for delivery
        $customer_shipping = ($order['type'] == 'توصيل') ? [
            'name' => 'خالد الأحمد', // Can be different name if shipping to someone else
            'address' => 'حي الملقا، طريق الملك فهد، برج رافال، مكتب 101، الرياض', // Shipping Address
            'phone' => '+966 50 XXX XXXX',
            'delivery_instructions' => 'اتصل عند الوصول. الدور الأول.', // Specific instructions
        ] : null;

        $items = [
             // ... (same items as professional or add more) ...
             [ 'id' => 1, 'dish_name' => 'ستيك ريب آي', 'variation_name' => 'ميديم ويل', 'quantity' => 1, 'unit_price' => 120.00, 'total_price' => 120.00, ],
             [ 'id' => 2, 'dish_name' => 'بطاطس مقلية', 'variation_name' => 'بالجبنة', 'quantity' => 1, 'unit_price' => 20.00, 'total_price' => 20.00, ],
             [ 'id' => 3, 'dish_name' => 'عصير برتقال طازج', 'variation_name' => null, 'quantity' => 2, 'unit_price' => 18.00, 'total_price' => 36.00, ],
             [ 'id' => 4, 'dish_name' => 'تشيز كيك فراولة', 'variation_name' => null, 'quantity' => 1, 'unit_price' => 35.00, 'total_price' => 35.00, ],
        ];

        // Calculate Totals
        $subtotal = collect($items)->sum('total_price');
        $taxRate = 0.15;
        $taxAmount = $subtotal * $taxRate;
        $deliveryCharge = ($order['type'] == 'توصيل') ? 20.00 : 0.00; // Slightly higher delivery
        $discount = 10.00; // Example discount
        $grandTotal = $subtotal + $taxAmount + $deliveryCharge - $discount;

        $totals = [
            'subtotal' => $subtotal,
            'tax_label' => 'ضريبة القيمة المضافة (15%)',
            'tax_amount' => $taxAmount,
            'delivery_charge_label' => 'رسوم التوصيل',
            'delivery_charge_amount' => $deliveryCharge,
            'discount_label' => 'خصم (كود SUMMER)',
            'discount_amount' => $discount,
            'grand_total' => $grandTotal,
            'currency_icon' => $restaurant['currency_icon'],
        ];

        // Generate QR Code (same logic as professional)
        $qrCodeString = $this->generateSimplifiedInvoiceQrCode(
            $restaurant['name'], $restaurant['tax_number'], $order['date'], $totals['grand_total'], $totals['tax_amount']
        );
        $qrCodeImage = null;
        if ($qrCodeString) {
             try {
                 $options = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG, 'imageBase64' => true, 'eccLevel' => QRCode::ECC_L, 'scale' => 4, 'quietZoneSize' => 1]);
                 $qrCodeImage = (new QRCode($options))->render($qrCodeString);
             } catch (\Exception $e) { logger()->error('QR Code generation failed: ' . $e->getMessage()); }
        }

        $data = [
            'restaurant' => $restaurant,
            'order' => $order,
            'customer_billing' => $customer_billing,
            'customer_shipping' => $customer_shipping,
            'items' => $items,
            'totals' => $totals,
            'qr_code_image' => $qrCodeImage,
            'primary_color' => '#A70000', // Orbis Q Red
            'secondary_color' => '#FDECEC', // Light Red Accent
        ];

        $viewPath = 'invoices.orbisq_advanced'; // New view name

        if (!View::exists($viewPath)) {
            abort(404, "Invoice template '$viewPath' not found.");
        }

        return view($viewPath, $data);
    }
}
