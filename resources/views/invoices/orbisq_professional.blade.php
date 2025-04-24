<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>فاتورة ضريبية مبسطة - {{ $order['order_code'] ?? $order['id'] }}</title>
    <style>
        /* Using a modern font stack - consider adding a custom font */
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');

        body {
            font-family: 'Tajawal', 'Tahoma', sans-serif;
            direction: rtl;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7; /* Lighter grey */
            color: #2d3748; /* Darker gray */
            line-height: 1.7;
            font-size: 10pt; /* Base size for print/PDF friendliness */
        }
        .invoice-box {
            max-width: 800px;
            margin: 30px auto;
            padding: 35px;
            background-color: #fff;
            border: 1px solid #e2e8f0; /* Lighter border */
            /* box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); */
            border-radius: 8px; /* Rounded corners */
        }

        /* Header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Align items to top */
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .header-section .logo img {
            max-height: 65px; /* Slightly smaller logo */
            max-width: 180px;
        }
        .header-section .invoice-title {
            text-align: left;
        }
        .invoice-title h1 {
            margin: 0;
            color: {{ $primary_color ?? '#A70000' }};
            font-size: 1.7em; /* Adjusted size */
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .invoice-title p {
            margin: 4px 0 0 0;
            font-size: 0.85em;
            color: #718096; /* Medium gray */
        }
         .invoice-title p strong { color: #4a5568; } /* Slightly darker key */

        /* Restaurant & Customer Info */
        .details-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Responsive columns */
            gap: 25px;
            margin-bottom: 35px;
        }
        .details-box h3 {
            margin: 0 0 10px 0;
            font-size: 0.95em;
            font-weight: 700;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
         .details-box p {
            margin: 3px 0;
            font-size: 0.85em; /* Smaller detail text */
            color: #4a5568;
         }
         .details-box strong { font-weight: 500; color: #2d3748; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th, .items-table td {
            border-bottom: 1px solid #e2e8f0; /* Bottom border only */
            padding: 12px 8px; /* Adjusted padding */
            text-align: right;
            font-size: 0.9em;
        }
        .items-table thead th {
            background-color: #f8f9fa; /* Very light header */
            color: #4a5568; /* Header text color */
            font-weight: 700; /* Bold header */
            text-transform: uppercase;
            font-size: 0.75em; /* Smaller header text */
            letter-spacing: 0.5px;
             border-bottom-width: 2px; /* Thicker bottom border for header */
        }
         /* Remove border from last row */
         .items-table tbody tr:last-child td { border-bottom: none; }

        .items-table td.price, .items-table th.price { text-align: left; font-feature-settings: 'tnum'; /* Tabular nums */}
        .items-table td.qty, .items-table th.qty { text-align: center; }
        .item-name { font-weight: 500; color: #2d3748; }
        .item-variation { font-size: 0.85em; color: #718096; margin-top: 1px; }

        /* Totals Section */
        .summary-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Align QR top */
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e0; /* Dashed separator */
        }
        .totals { width: 45%; } /* Adjust width as needed */
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 0.9em;
        }
        .totals-row.grand-total {
            font-weight: 700;
            font-size: 1.15em;
            color: #000;
            border-top: 2px solid #2d3748;
            margin-top: 8px;
            padding-top: 8px;
        }
        .totals-label { color: #718096; }
        .totals-value { color: #2d3748; font-weight: 500; text-align: left; font-feature-settings: 'tnum'; }

        /* QR Code Section */
        .qr-code {
            text-align: left; /* Align QR to the left */
        }
        .qr-code img {
            display: block;
             width: 110px; /* Adjust size */
             height: 110px;
             border: 1px solid #e2e8f0;
             padding: 4px;
             background-color: #fff;
        }
        .qr-code p {
            font-size: 0.75em;
            color: #718096;
            margin-top: 5px;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8em;
            color: #a0aec0; /* Lighter footer text */
        }
        .invoice-footer img {
            height: 18px; vertical-align: middle; margin-right: 4px; opacity: 0.7;
        }

        /* Print Styles */
        @media print {
            body { background-color: #fff; font-size: 9pt; }
            .invoice-box { box-shadow: none; border: none; margin: 0; max-width: 100%; padding: 5px; border-radius: 0; }
            .items-table th, .items-table td { padding: 5px; }
            .header-section, .details-section, .summary-section, .invoice-footer { margin-bottom: 15px; padding-bottom: 10px;}
            /* Add page break avoidance if needed */
             table { page-break-inside: auto }
             tr    { page-break-inside: avoid; page-break-after: auto }
             thead { display: table-header-group }
             tbody { display: table-row-group }
             .qr-code img { width: 80px; height: 80px; }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        {{-- Header --}}
        <div class="header-section">
            <div class="logo">
                @if($restaurant['logo_url'])
                    <img src="{{ asset($restaurant['logo_url']) }}" alt="{{ $restaurant['name'] }} Logo">
                @else
                    {{-- Placeholder if no logo --}}
                    <h2 style="margin:0; font-size: 1.5em; color: #333;">{{ $restaurant['name'] }}</h2>
                @endif
            </div>
            <div class="invoice-title">
                <h1>فاتورة ضريبية مبسطة</h1>
                <p><strong>رقم الفاتورة:</strong> {{ $order['order_code'] ?? $order['id'] }}</p>
                <p><strong>تاريخ الفاتورة:</strong> {{ \Carbon\Carbon::parse($order['date'])->format('Y/m/d') }}</p>
                <p><strong>وقت الفاتورة:</strong> {{ \Carbon\Carbon::parse($order['date'])->format('H:i A') }}</p>
            </div>
        </div>

        {{-- Restaurant & Customer Details --}}
        <div class="details-section">
            <div class="details-box restaurant-info">
                <h3>بيانات البائع</h3>
                <p><strong>{{ $restaurant['name'] }}</strong></p>
                <p>{{ $restaurant['address_line1'] ?? '' }}</p>
                <p>{{ $restaurant['address_line2'] ?? '' }}</p>
                @if($restaurant['phone'])<p><strong>الهاتف:</strong> {{ $restaurant['phone'] }}</p>@endif
                <p><strong>الرقم الضريبي:</strong> {{ $restaurant['tax_number'] ?? 'غير محدد' }}</p>
            </div>
            <div class="details-box customer-info">
                <h3>بيانات المشتري</h3>
                @if($customer)
                    <p><strong>الاسم:</strong> {{ $customer['name'] }}</p>
                    @if($customer['phone'])<p><strong>الهاتف:</strong> {{ $customer['phone'] }}</p>@endif
                    @if($customer['address'])<p><strong>العنوان:</strong> {{ $customer['address'] }}</p>@endif
                 @else
                    <p>زبون عام</p>
                 @endif
                 {{-- Add Order details here if preferred over separate section --}}
                  <p style="margin-top:10px; border-top: 1px dashed #ccc; padding-top: 5px;">
                      <strong>نوع الطلب:</strong> {{ $order['type'] }}
                      @if($order['table_number']) / <strong>طاولة:</strong> {{ $order['table_number'] }} @endif
                  </p>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>الوصف</th>
                    <th class="qty" style="width: 10%;">الكمية</th>
                    <th class="price" style="width: 18%;">سعر الوحدة</th>
                    <th class="price" style="width: 18%;">المبلغ (غ/ض)</th>
                    <th class="price" style="width: 18%;">قيمة الضريبة</th>
                    <th class="price" style="width: 18%;">الإجمالي (ش/ض)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $index => $item)
                 @php
                    // Calculate tax per item (assuming price doesn't include tax)
                    $itemTaxRate = 0.15; // Use global rate or fetch per item if needed
                    $itemSubtotal = $item['total_price'];
                    $itemTaxAmount = $itemSubtotal * $itemTaxRate;
                    $itemTotalWithTax = $itemSubtotal + $itemTaxAmount;
                 @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <span class="item-name">{{ $item['dish_name'] }}</span>
                            @if($item['variation_name'] && strtolower($item['variation_name']) != 'standard')
                                <span class="item-variation">({{ $item['variation_name'] }})</span>
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

        {{-- Summary Section (Totals & QR Code) --}}
        <div class="summary-section">
            <div class="totals">
                <div class="totals-row">
                    <span class="totals-label">إجمالي المبلغ (غير شامل الضريبة):</span>
                    <span class="totals-value">{{ number_format($totals['subtotal'], 2) }} {{ $totals['currency_icon'] }}</span>
                </div>
                @if(isset($totals['discount_amount']) && $totals['discount_amount'] > 0)
                <div class="totals-row">
                    <span class="totals-label">{{ $totals['discount_label'] ?? 'الخصم' }}:</span>
                    <span class="totals-value">-{{ number_format($totals['discount_amount'], 2) }} {{ $totals['currency_icon'] }}</span>
                </div>
                @endif
                <div class="totals-row">
                    <span class="totals-label">{{ $totals['tax_label'] ?? 'إجمالي ضريبة القيمة المضافة' }}:</span>
                    <span class="totals-value">{{ number_format($totals['tax_amount'], 2) }} {{ $totals['currency_icon'] }}</span>
                </div>
                 @if(isset($totals['delivery_charge']) && $totals['delivery_charge'] > 0)
                 <div class="totals-row">
                     <span class="totals-label">رسوم التوصيل:</span>
                     <span class="totals-value">{{ number_format($totals['delivery_charge'], 2) }} {{ $totals['currency_icon'] }}</span>
                 </div>
                @endif
                <div class="totals-row grand-total">
                    <span class="totals-label">إجمالي المبلغ المستحق:</span>
                    <span class="totals-value">{{ number_format($totals['grand_total'], 2) }} {{ $totals['currency_icon'] }}</span>
                </div>
            </div>
            <div class="qr-code">
                @if($qr_code_image)
                    {{-- Embed Base64 image directly --}}
                    <img src="{{ $qr_code_image }}" alt="QR Code E-Invoice Zatca compliant KSA Saudi Arabia">
                    <p>امسح الرمز للفاتورة الإلكترونية</p>
                 @else
                     {{-- Placeholder if QR generation failed --}}
                     <div style="width: 110px; height: 110px; border: 1px dashed #ccc; display:flex; align-items:center; justify-content:center; text-align:center; font-size:9px; color:#999;">QR Code <br> غير متوفر </div>
                 @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="invoice-footer">
             <p>هذه فاتورة ضريبية مبسطة.</p>
             <p>
                 مدعوم بواسطة <img src="https://img.freepik.com/premium-vector/global-travel-logo-design-template-illustration_884294-103.jpg?ga=GA1.1.1880227217.1744728103&semt=ais_hybrid&w=740" alt="Orbis Q"> Orbis Q
             </p>
        </div>
    </div>
</body>
</html>
