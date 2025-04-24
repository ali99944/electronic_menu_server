<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>فاتورة {{ $order['order_code'] ?? $order['id'] }} - {{ $restaurant['name'] }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');

        :root {
            --primary-color: {{ $primary_color ?? '#A70000' }};
            --secondary-color: {{ $secondary_color ?? '#FDECEC' }};
            --text-dark: #1a202c;       /* Equivalent to Tailwind gray-900 */
            --text-medium: #4a5568;     /* Equivalent to Tailwind gray-700 */
            --text-light: #718096;      /* Equivalent to Tailwind gray-600 */
            --border-color: #e2e8f0;    /* Equivalent to Tailwind gray-200 */
            --bg-light: #f7fafc;        /* Equivalent to Tailwind gray-100 */
        }

        body {
            font-family: 'Tajawal', 'Tahoma', sans-serif;
            direction: rtl;
            margin: 0;
            padding: 0;
            background-color: #eef2f7; /* Slightly blue-ish gray background */
            color: var(--text-medium);
            line-height: 1.6;
            font-size: 10pt;
        }
        .invoice-wrapper {
            max-width: 850px; /* Slightly wider */
            margin: 30px auto;
            background-color: #fff;
            /* box-shadow: 0 10px 15px -3px rgba(0,0,0,0.07), 0 4px 6px -2px rgba(0,0,0,0.05); */
            border-radius: 8px;
            border-radius: 8px;
            overflow: hidden; /* To contain borders/backgrounds */
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 35px;
            background-color: var(--bg-light);
            border-bottom: 1px solid var(--border-color);
        }
        .header-logo img { max-height: 60px; max-width: 170px; }
        .header-details { text-align: left; }
        .header-details h2 {
            margin: 0; color: var(--primary-color); font-size: 1.6em; font-weight: 700;
        }
        .header-details p { margin: 2px 0; font-size: 0.8em; color: var(--text-light); }
        .header-details strong { color: var(--text-medium); }

        .invoice-body { padding: 35px; }

        .addresses {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Equal columns */
            gap: 30px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .address-box h3 {
             margin: 0 0 8px 0; font-size: 0.9em; font-weight: 700; color: var(--text-dark);
             text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;
        }
        .address-box p { margin: 2px 0; font-size: 0.85em; color: var(--text-medium); line-height: 1.5; }
        .address-box strong { font-weight: 500; }

        .order-summary-box {
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Responsive summary */
            gap: 10px 20px; /* Row and column gap */
        }
        .summary-item { font-size: 0.85em; }
        .summary-item .label { color: var(--text-light); display: block; font-size: 0.9em; margin-bottom: 2px; }
        .summary-item .value { color: var(--text-dark); font-weight: 500; }
        .status-badge {
            display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.75em; font-weight: 500;
        }
        .status-paid { background-color: #c6f6d5; color: #2f855a; } /* Green */
        .status-pending { background-color: #feebc8; color: #975a16; } /* Orange */
        .status-cancelled { background-color: #fed7d7; color: #c53030; } /* Red */

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th, .items-table td {
            padding: 10px 8px; text-align: right; font-size: 0.85em;
            border-bottom: 1px solid var(--border-color);
        }
        .items-table thead th {
            background-color: #fff; /* White header */
            color: var(--text-light); font-weight: 500; text-transform: uppercase; font-size: 0.75em; letter-spacing: 0.5px;
            border-bottom-width: 2px; border-color: var(--border-color);
        }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table .price, .items-table th.price { text-align: left; font-feature-settings: 'tnum'; }
        .items-table .qty, .items-table th.qty { text-align: center; }
        .items-table .item-desc { font-weight: 500; color: var(--text-dark); }
        .items-table .item-var { font-size: 0.9em; color: var(--text-light); margin-top: 1px; }

        .invoice-summary {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);
        }
        .summary-notes { width: 55%; font-size: 0.8em; color: var(--text-light); }
        .summary-notes h4 { margin: 0 0 8px 0; color: var(--text-medium); font-size: 1.1em; font-weight: 500; }
        .summary-totals { width: 40%; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 0.9em; }
        .totals-row.grand-total {
            font-weight: 700; font-size: 1.2em; color: var(--text-dark);
            border-top: 2px solid var(--text-dark); margin-top: 10px; padding-top: 10px;
        }
        .totals-label { color: var(--text-medium); }
        .totals-value { color: var(--text-dark); font-weight: 500; text-align: left; font-feature-settings: 'tnum'; }

        .invoice-footer {
            text-align: center; margin-top: 30px; padding: 20px 35px; font-size: 0.75em;
            color: #a0aec0; background-color: var(--bg-light); border-top: 1px solid var(--border-color);
        }
         .footer-qr { text-align: center; margin-bottom: 15px; }
         .footer-qr img { display: inline-block; width: 90px; height: 90px; border: 1px solid #e2e8f0; padding: 3px; background: #fff; }
         .footer-qr p { font-size: 0.9em; color: #718096; margin-top: 5px; }
         .footer-branding img { height: 16px; vertical-align: middle; margin-right: 4px; opacity: 0.8; }

        @media print {
             body { background-color: #fff; font-size: 9pt; color: #000; }
             .invoice-wrapper { box-shadow: none; border: none; margin: 0; max-width: 100%; padding: 0; border-radius: 0; }
             .invoice-header, .invoice-body, .invoice-footer { padding: 15px; }
             th, td { padding: 5px; font-size: 8pt; }
             .qr-code img, .footer-qr img { width: 75px; height: 75px; }
             .summary-notes, .totals { width: 48%; }
             /* Ensure backgrounds print correctly if needed (often browser-dependent) */
             thead th { background-color: #f0f0f0 !important; print-color-adjust: exact; }
             .status-badge { border: 1px solid #ccc !important; print-color-adjust: exact; }
             /* Hide unnecessary elements for print */
             /* .some-button-class { display: none; } */
        }
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        {{-- Header --}}
        <div class="invoice-header">
            <div class="header-logo">
                 @if($restaurant['logo_url'])
                    <img src="{{ asset($restaurant['logo_url']) }}" alt="{{ $restaurant['name'] }} Logo">
                @else
                    <h2 style="margin:0; font-size: 1.5em; color: var(--text-dark);">{{ $restaurant['name'] }}</h2>
                @endif
            </div>
            <div class="header-details">
                <h2>فاتورة ضريبية</h2>
                <p><strong>رقم الفاتورة:</strong> {{ $order['order_code'] ?? $order['id'] }}</p>
                <p><strong>تاريخ الإصدار:</strong> {{ \Carbon\Carbon::parse($order['date'])->format('Y/m/d') }}</p>
                @if(isset($order['due_date']))
                <p><strong>تاريخ الاستحقاق:</strong> {{ \Carbon\Carbon::parse($order['due_date'])->format('Y/m/d') }}</p>
                @endif
            </div>
        </div>

        <div class="invoice-body">
            {{-- Addresses --}}
            <div class="addresses">
                <div class="address-box restaurant-info">
                    <h3>من:</h3>
                    <p><strong>{{ $restaurant['name'] }}</strong></p>
                    <p>{{ $restaurant['address_line1'] ?? '' }}</p>
                    <p>{{ $restaurant['address_line2'] ?? '' }}</p>
                    @if($restaurant['phone'])<p>{{ $restaurant['phone'] }}</p>@endif
                    @if($restaurant['website'])<p>{{ $restaurant['website'] }}</p>@endif
                    <p><strong>الرقم الضريبي:</strong> {{ $restaurant['tax_number'] ?? 'غير محدد' }}</p>
                </div>
                <div class="address-box customer-info">
                    <h3>إلى:</h3>
                    @if($customer_billing)
                        <p><strong>{{ $customer_billing['name'] }}</strong></p>
                        @if($customer_billing['address'])<p>{{ $customer_billing['address'] }}</p>@endif
                        @if($customer_billing['phone'])<p>{{ $customer_billing['phone'] }}</p>@endif
                    @else
                        <p>زبون عام</p>
                    @endif
                    {{-- Optional: Show distinct shipping address if delivery and different --}}
                     @if($customer_shipping && $customer_shipping['address'] != $customer_billing['address'])
                        <h3 style="margin-top: 15px;">الشحن إلى:</h3>
                        <p><strong>{{ $customer_shipping['name'] }}</strong></p>
                        <p>{{ $customer_shipping['address'] }}</p>
                        @if($customer_shipping['phone'])<p>{{ $customer_shipping['phone'] }}</p>@endif
                     @endif
                </div>
            </div>

            {{-- Order Summary Box --}}
            <div class="order-summary-box">
                <div class="summary-item">
                    <span class="label">رقم الطلب</span>
                    <span class="value">{{ $order['order_code'] ?? $order['id'] }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">تاريخ الطلب</span>
                    <span class="value">{{ \Carbon\Carbon::parse($order['date'])->format('Y/m/d H:i') }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">نوع الطلب</span>
                    <span class="value">{{ $order['type'] }}
                        @if($order['table_number']) (طاولة {{ $order['table_number'] }}) @endif
                    </span>
                </div>
                <div class="summary-item">
                    <span class="label">طريقة الدفع</span>
                    <span class="value">{{ $order['payment_method'] ?? 'غير محدد' }}</span>
                </div>
                 <div class="summary-item">
                    <span class="label">حالة الدفع</span>
                    <span class="value">
                        <span class="status-badge {{ $order['status'] == 'مدفوع' ? 'status-paid' : ($order['status'] == 'ملغي' ? 'status-cancelled' : 'status-pending') }}">
                            {{ $order['status'] ?? 'غير محدد' }}
                        </span>
                    </span>
                </div>
            </div>


            {{-- Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th>الوصف</th>
                        <th class="qty" style="width: 8%;">الكمية</th>
                        <th class="price" style="width: 15%;">السعر</th>
                        <th class="price" style="width: 15%;">المبلغ (غ/ض)</th>
                        <th class="price" style="width: 15%;">الضريبة</th>
                        <th class="price" style="width: 17%;">الإجمالي (ش/ض)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $item)
                         @php
                            $itemTaxRate = 0.15; // Assuming global rate
                            $itemSubtotal = $item['total_price'];
                            $itemTaxAmount = $itemSubtotal * $itemTaxRate;
                            $itemTotalWithTax = $itemSubtotal + $itemTaxAmount;
                         @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="item-desc">{{ $item['dish_name'] }}</span>
                                @if($item['variation_name'] && strtolower($item['variation_name']) != 'standard')
                                    <span class="item-var">({{ $item['variation_name'] }})</span>
                                @endif
                            </td>
                            <td class="qty">{{ $item['quantity'] }}</td>
                            <td class="price">{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="price">{{ number_format($itemSubtotal, 2) }}</td>
                            <td class="price">{{ number_format($itemTaxAmount, 2) }}</td>
                            <td class="price">{{ number_format($itemTotalWithTax, 2) }}</td>
                        </tr>
                    @empty
                        <tr> <td colspan="7" style="text-align: center; padding: 20px;">لا توجد أصناف.</td> </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Summary & QR --}}
            <div class="invoice-summary">
                 {{-- Notes Section --}}
                 <div class="summary-notes">
                     @if($order['notes_from_customer'] || ($customer_shipping && $customer_shipping['delivery_instructions']))
                     <h4>ملاحظات:</h4>
                     @endif
                     @if($order['notes_from_customer'])
                        <p><strong>ملاحظات العميل:</strong> {{ $order['notes_from_customer'] }}</p>
                     @endif
                      @if($customer_shipping && $customer_shipping['delivery_instructions'])
                        <p><strong>تعليمات التوصيل:</strong> {{ $customer_shipping['delivery_instructions'] }}</p>
                     @endif
                     {{-- Add general terms here --}}
                     {{-- <p style="margin-top: 15px;"><strong>الشروط:</strong> يجب الدفع خلال 7 أيام.</p> --}}
                 </div>

                 {{-- Totals Section --}}
                <div class="summary-totals">
                    <div class="totals-row">
                        <span class="totals-label">المجموع الفرعي:</span>
                        <span class="totals-value">{{ number_format($totals['subtotal'], 2) }} {{ $totals['currency_icon'] }}</span>
                    </div>
                     @if($totals['discount_amount'] > 0)
                    <div class="totals-row">
                        <span class="totals-label">{{ $totals['discount_label'] }}:</span>
                        <span class="totals-value">-{{ number_format($totals['discount_amount'], 2) }} {{ $totals['currency_icon'] }}</span>
                    </div>
                    @endif
                    <div class="totals-row">
                        <span class="totals-label">{{ $totals['tax_label'] }}:</span>
                        <span class="totals-value">{{ number_format($totals['tax_amount'], 2) }} {{ $totals['currency_icon'] }}</span>
                    </div>
                     @if($totals['delivery_charge_amount'] > 0)
                    <div class="totals-row">
                        <span class="totals-label">{{ $totals['delivery_charge_label'] }}:</span>
                        <span class="totals-value">{{ number_format($totals['delivery_charge_amount'], 2) }} {{ $totals['currency_icon'] }}</span>
                    </div>
                    @endif
                    <div class="totals-row grand-total">
                        <span class="totals-label">الإجمالي المستحق:</span>
                        <span class="totals-value">{{ number_format($totals['grand_total'], 2) }} {{ $totals['currency_icon'] }}</span>
                    </div>
                </div>
            </div>
        </div> {{-- End Invoice Body --}}

        {{-- Footer --}}
        <div class="invoice-footer">
            {{-- QR Code moved to footer for more standard placement --}}
            <div class="footer-qr">
                 @if($qr_code_image)
                    <img src="{{ $qr_code_image }}" alt="QR Code E-Invoice">
                    <p>الفاتورة الإلكترونية (متوافقة مع متطلبات هيئة الزكاة والضريبة والجمارك)</p>
                 @endif
            </div>
            <p>شكراً لطلبكم من {{ $restaurant['name'] }}. نتمنى رؤيتكم قريباً!</p>
            <p class="footer-branding">
                مدعوم بواسطة <img src="{{ asset('/orbis-q-logo-small.png') }}" alt="Orbis Q"> Orbis Q
            </p>
        </div>
    </div> {{-- End Invoice Wrapper --}}
</body>
</html>
