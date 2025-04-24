<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {{-- Use order code or ID for title --}}
    <title>فاتورة طلب #{{ $order['order_code'] ?? $order['id'] }}</title>
    <style>
        /* Basic Reset & Body */
        body {
            font-family: 'Tahoma', 'Helvetica', sans-serif; /* Common font */
            direction: rtl;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            font-size: 14px; /* Base font size */
        }
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            border-top: 5px solid {{ $primary_color ?? '#A70000' }};
        }

        /* Header */
        .invoice-header {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* Two columns */
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .restaurant-details h1 {
            margin: 0 0 5px 0;
            color: #000;
            font-size: 1.6em;
        }
        .restaurant-details p {
            margin: 2px 0;
            font-size: 0.9em;
            color: #555;
        }
        .invoice-details {
            text-align: left; /* Align invoice details to the left */
        }
         .invoice-details h2 {
            margin: 0 0 10px 0;
            color: {{ $primary_color ?? '#A70000' }};
            font-size: 1.8em;
        }
         .invoice-details p {
            margin: 3px 0;
            font-size: 0.95em;
         }
         .invoice-details p strong {
             display: inline-block;
             min-width: 80px; /* Ensure alignment */
             color: #444;
         }
        .logo {
            max-height: 80px;
            max-width: 150px;
            margin-bottom: 10px;
        }

        /* Customer & Order Info */
        .info-section {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
             gap: 20px;
             margin-bottom: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 5px;
        }
         .info-box h3 {
             margin: 0 0 10px 0;
             font-size: 1.1em;
             color: #333;
             border-bottom: 1px solid #ddd;
             padding-bottom: 5px;
         }
         .info-box p {
             margin: 4px 0;
             font-size: 0.9em;
             color: #555;
         }
         .info-box p strong { color: #333; }

        /* Items Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 10px 12px; /* Slightly more padding */
            text-align: right;
            font-size: 0.9em;
        }
        thead {
            background-color: #f1f3f5; /* Lighter header */
            color: #333;
            font-weight: bold;
             border-bottom: 2px solid #ced4da;
        }
        tbody tr:nth-child(even) {
            background-color: #f8f9fa; /* Subtle row striping */
        }
        td.price, th.price { text-align: left; } /* Align prices left */
        td.qty, th.qty { text-align: center; } /* Align quantity center */
        .item-name { font-weight: 500; color: #222; }
        .item-variation { font-size: 0.8em; color: #666; display: block; margin-top: 2px; }

        /* Totals Section */
        .totals-section {
            width: 40%; /* Control width */
            margin-right: auto; /* Push to left in RTL */
            margin-left: 0;
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.95em;
        }
        .totals-row.grand-total {
            font-weight: bold;
            font-size: 1.1em;
            color: #000;
            border-top: 1px solid #ccc;
            margin-top: 5px;
            padding-top: 10px;
        }
        .totals-label { color: #555; }
        .totals-value { color: #000; font-weight: 500; text-align: left; }

        /* Footer */
        .invoice-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.85em;
            color: #777;
        }
        .orbis-branding { margin-top: 10px; }
        .orbis-branding img { height: 20px; vertical-align: middle; margin-right: 5px; }

        /* Print Styles */
        @media print {
            body {
                background-color: #fff;
                font-size: 10pt; /* Smaller font for print */
            }
            .invoice-container {
                box-shadow: none;
                border: none;
                margin: 0;
                max-width: 100%;
                padding: 10px;
                border-top: none;
            }
             th, td { padding: 6px 8px; }
            .info-box { background-color: #fff; border-color: #ccc; }
            thead { background-color: #eee; }
            /* Hide elements not needed for print if any */
            /* .some-button { display: none; } */
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- Invoice Header --}}
        <div class="invoice-header">
            <div class="restaurant-details">
                @if($restaurant['logo_url'])
                    <img src="{{ asset($restaurant['logo_url']) }}" alt="{{ $restaurant['name'] }} Logo" class="logo">
                @endif
                <h1>{{ $restaurant['name'] }}</h1>
                <p>{{ $restaurant['address'] ?? '' }}</p>
                @if($restaurant['phone']) <p>الهاتف: {{ $restaurant['phone'] }}</p> @endif
                @if($restaurant['email']) <p>البريد: {{ $restaurant['email'] }}</p> @endif
                @if($restaurant['tax_number']) <p>الرقم الضريبي: {{ $restaurant['tax_number'] }}</p> @endif
            </div>
            <div class="invoice-details">
                <h2>فاتورة طلب</h2>
                <p><strong>رقم الفاتورة:</strong> {{ $order['order_code'] ?? $order['id'] }}</p>
                <p><strong>التاريخ:</strong> {{ \Carbon\Carbon::parse($order['date'])->format('Y/m/d H:i') }}</p>
                <p><strong>الحالة:</strong> {{ $order['status'] ?? 'غير محدد' }}</p>
                {{-- Add Due Date if applicable --}}
                {{-- <p><strong>تاريخ الاستحقاق:</strong> DATE_HERE</p> --}}
            </div>
        </div>

        {{-- Customer & Order Info Section --}}
        <div class="info-section">
            {{-- Customer Details (Optional) --}}
            @if($customer)
                <div class="info-box customer-info">
                    <h3>بيانات العميل</h3>
                    <p><strong>الاسم:</strong> {{ $customer['name'] }}</p>
                    @if($customer['phone'])<p><strong>الهاتف:</strong> {{ $customer['phone'] }}</p>@endif
                    @if($customer['address'])<p><strong>العنوان:</strong> {{ $customer['address'] }}</p>@endif
                </div>
            @else
                 <div class="info-box customer-info">
                     <h3>بيانات العميل</h3>
                     <p>زبون عام</p>
                 </div>
            @endif

            {{-- Order Details --}}
            <div class="info-box order-details">
                <h3>تفاصيل الطلب</h3>
                <p><strong>رقم الطلب:</strong> {{ $order['order_code'] ?? $order['id'] }}</p>
                <p><strong>نوع الطلب:</strong> {{ $order['type'] }}</p>
                @if($order['type'] == 'داخل المطعم' && $order['table_number'])
                    <p><strong>رقم الطاولة:</strong> {{ $order['table_number'] }}</p>
                @endif
                {{-- Add Payment Method if available --}}
                {{-- <p><strong>طريقة الدفع:</strong> Cash</p> --}}
            </div>
        </div>


        {{-- Items Table --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>الصنف</th>
                    <th class="qty" style="width: 10%;">الكمية</th>
                    <th class="price" style="width: 20%;">سعر الوحدة</th>
                    <th class="price" style="width: 20%;">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <span class="item-name">{{ $item['dish_name'] }}</span>
                            @if($item['variation_name'] && strtolower($item['variation_name']) != 'standard')
                                <span class="item-variation">({{ $item['variation_name'] }})</span>
                            @endif
                        </td>
                        <td class="qty">{{ $item['quantity'] }}</td>
                        <td class="price">{{ number_format($item['unit_price'], 2) }} {{ $restaurant['currency_icon'] ?? 'ر.س' }}</td>
                        <td class="price">{{ number_format($item['total_price'], 2) }} {{ $restaurant['currency_icon'] ?? 'ر.س' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center;">لا توجد أصناف في هذا الطلب.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals Section --}}
        <div class="totals-section">
            <div class="totals-row">
                <span class="totals-label">المجموع الفرعي:</span>
                <span class="totals-value">{{ number_format($totals['subtotal'], 2) }} {{ $restaurant['currency_icon'] ?? 'ر.س' }}</span>
            </div>
            @if(isset($totals['tax_amount']) && $totals['tax_amount'] > 0)
                 <div class="totals-row">
                     <span class="totals-label">{{ $totals['tax_label'] ?? 'الضريبة' }}:</span>
                     <span class="totals-value">{{ number_format($totals['tax_amount'], 2) }} {{ $restaurant['currency_icon'] ?? 'ر.س' }}</span>
                 </div>
            @endif
             @if(isset($totals['discount']) && $totals['discount'] > 0)
                 <div class="totals-row">
                     <span class="totals-label">الخصم:</span>
                     <span class="totals-value">-{{ number_format($totals['discount'], 2) }} {{ $restaurant['currency_icon'] ?? 'ر.س' }}</span>
                 </div>
            @endif
            <div class="totals-row grand-total">
                <span class="totals-label">المجموع الإجمالي:</span>
                <span class="totals-value">{{ number_format($totals['grand_total'], 2) }} {{ $restaurant['currency_icon'] ?? 'ر.س' }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="invoice-footer">
            <p>شكراً لزيارتكم {{ $restaurant['name'] }}!</p>
            {{-- Optional QR Code - Placeholder --}}
            {{-- <div>[QR Code Placeholder]</div> --}}
            <p class="orbis-branding">
                 مدعوم بواسطة <img src="{{ asset('/orbis-q-logo-small.png') }}" alt="Orbis Q"> Orbis Q
                 {{-- Replace with your actual small logo path --}}
            </p>
        </div>
    </div>
</body>
</html>
