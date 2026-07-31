<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $data->invoice_number ?? $data->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f0f2f5;
            padding: 24px;
            line-height: 1.6;
            color: #1a1a2e;
        }

        .page-wrapper {
            max-width: 480px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .receipt-header {
            background: linear-gradient(135deg, #0d6b3a 0%, #1a9e5c 50%, #28c76f 100%);
            padding: 32px 28px 28px;
            text-align: center;
            position: relative;
        }

        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            right: 0;
            height: 24px;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0.15) 0%, transparent 70%);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            margin-bottom: 12px;
        }

        .store-name {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .store-tagline {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
        }

        .invoice-title {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 500;
            margin-top: 16px;
        }

        .invoice-number {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .body-content {
            padding: 28px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .info-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .info-item .label {
            font-size: 11px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .info-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .customer-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 24px;
        }

        .customer-card .customer-name {
            font-size: 15px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 4px;
        }

        .customer-card .customer-detail {
            font-size: 13px;
            color: #4ade80;
            font-weight: 500;
        }

        .customer-card .customer-detail span {
            color: #15803d;
        }

        .shipping-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 24px;
        }

        .shipping-card .shipping-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3b82f6;
            margin-bottom: 6px;
        }

        .shipping-card .shipping-content {
            font-size: 13px;
            color: #1e40af;
            font-weight: 500;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, #e2e8f0, transparent);
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
        }

        .items-table thead th {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 2px solid #f1f5f9;
        }

        .items-table thead th:last-child,
        .items-table thead th:nth-last-child(2) {
            text-align: right;
        }

        .items-table tbody tr {
            background: #f8fafc;
            border-radius: 12px;
            transition: background 0.15s ease;
        }

        .items-table tbody td {
            padding: 12px;
            font-size: 13px;
            color: #334155;
        }

        .items-table tbody td:first-child {
            border-radius: 10px 0 0 10px;
        }

        .items-table tbody td:last-child {
            border-radius: 0 10px 10px 0;
        }

        .items-table tbody td:nth-child(2) {
            text-align: center;
            font-weight: 500;
            color: #64748b;
        }

        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4) {
            text-align: right;
            font-weight: 600;
        }

        .item-name-main {
            font-weight: 600;
            color: #1a1a2e;
        }

        .parcel-details {
            padding: 6px 12px 12px !important;
            background: transparent !important;
        }

        .parcel-list {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 4px;
        }

        .parcel-list-title {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .parcel-item {
            font-size: 12px;
            color: #475569;
            padding: 2px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .parcel-item::before {
            content: '•';
            color: #22c55e;
            font-weight: bold;
        }

        .discount-row td {
            color: #ef4444 !important;
        }

        .discount-row .item-name-main {
            color: #ef4444 !important;
        }

        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
        }

        .summary-row .label {
            color: #64748b;
            font-weight: 500;
        }

        .summary-row .value {
            font-weight: 600;
            color: #334155;
        }

        .summary-row.divider {
            border-top: 1px dashed #cbd5e1;
            margin-top: 8px;
            padding-top: 12px;
        }

        .summary-row.total {
            padding: 10px 0 4px;
        }

        .summary-row.total .label {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .summary-row.total .value {
            font-size: 20px;
            font-weight: 800;
            color: #16a34a;
        }

        .payment-info {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-top: 14px;
        }

        .payment-info .summary-row .label {
            font-size: 13px;
        }

        .payment-info .summary-row .value {
            font-size: 13px;
        }

        .payment-info .summary-row.return .label {
            color: #16a34a;
        }

        .payment-info .summary-row.return .value {
            color: #16a34a;
        }

        .receipt-footer {
            text-align: center;
            padding: 24px 28px 28px;
            border-top: 1px solid #f1f5f9;
        }

        .thank-you {
            font-size: 18px;
            font-weight: 700;
            color: #16a34a;
            margin-bottom: 4px;
        }

        .footer-text {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            gap: 10px;
            padding: 0 28px 28px;
        }

        .btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background: #16a34a;
            color: #fff;
            box-shadow: 0 1px 3px rgba(22, 163, 74, 0.3);
        }

        .btn-primary:hover {
            background: #15803d;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-outline {
            background: transparent;
            color: #475569;
            border: 1.5px solid #e2e8f0;
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        @media (max-width: 520px) {
            body {
                padding: 0;
                background: #fff;
            }

            .card {
                border-radius: 0;
                box-shadow: none;
            }

            .receipt-header {
                padding: 28px 20px 24px;
            }

            .body-content {
                padding: 20px;
            }

            .info-grid {
                gap: 8px;
            }

            .info-item {
                padding: 10px 12px;
            }

            .actions {
                padding: 0 20px 24px;
                flex-direction: column;
            }

            .summary-row.total .value {
                font-size: 18px;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .page-wrapper {
                max-width: 100%;
            }

            .card {
                box-shadow: none;
                border-radius: 0;
            }

            .receipt-header {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .customer-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .shipping-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .summary-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .actions {
                display: none !important;
            }

            .btn {
                display: none !important;
            }

            .receipt-header::after {
                display: none;
            }

            .items-table tbody tr {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="card">
            <div class="receipt-header">
                <div class="status-badge">
                    {{ ucfirst($data->status ?? 'Paid') }}
                </div>
                <div class="store-name">
                    {{ $setting->brand_name ?? 'in!fruity' }}
                </div>
                <div class="store-tagline">
                    {{ $setting->brand_address ?? 'Fresh & Healthy Beverages' }}
                </div>
                <div class="invoice-title">Invoice</div>
                <div class="invoice-number">
                    #{{ $data->invoice_number ?? 'INV-' . str_pad($data->id, 6, '0', STR_PAD_LEFT) }}
                </div>
            </div>

            <div class="body-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Tanggal</div>
                        <div class="value">{{ $data->created_at->format('d M Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Waktu</div>
                        <div class="value">{{ $data->created_at->format('H:i') }} WIB</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Kasir</div>
                        <div class="value">{{ $data->user?->name ?? 'Admin' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Metode</div>
                        <div class="value">
                            @php
                                $getAmount = function($amountStr) {
                                    if (empty($amountStr)) return 0;
                                    if (is_numeric($amountStr)) return (float) $amountStr;
                                    $decoded = json_decode($amountStr, true);
                                    if (is_array($decoded) && count($decoded) > 0) return (float) $decoded[0];
                                    return 0;
                                };

                                $methods = collect();
                                if ($data->paymentDetails && $data->paymentDetails->count() > 0) {
                                    $methods = $data->paymentDetails->filter(function($p) use ($getAmount) {
                                        return $getAmount($p->payment_amount) > 0;
                                    })->map(function($p) {
                                        $m = $p->payment_method;
                                        if (empty($m) || strtolower($m) === 'tunai') return 'Tunai';
                                        if ($m === 'Split') return 'Split';
                                        $d = json_decode($m, true);
                                        if (is_array($d) && count($d) > 0) return collect($d)->implode(', ');
                                        return $m;
                                    })->filter()->unique()->values();
                                }
                                if ($methods->isEmpty()) $methods->push('Tunai');
                            @endphp
                            {{ $methods->implode(', ') }}
                        </div>
                    </div>
                </div>

                @if ($data->customer)
                    <div class="customer-card">
                        <div class="customer-name">{{ $data->customer?->name ?? 'Pelanggan' }}</div>
                        <div class="customer-detail">
                            @if ($data->customer->phone)
                                <span>&#9742;</span> {{ $data->customer->phone }}
                            @endif
                            @if ($data->customer->phone && $data->customer->email)
                                &nbsp;|&nbsp;
                            @endif
                            @if ($data->customer->email)
                                <span>&#9993;</span> {{ $data->customer->email }}
                            @endif
                        </div>
                    </div>
                @endif

                @if ($data->ongkir && $data->ongkir > 0)
                    <div class="shipping-card">
                        <div class="shipping-title">&#128666; Informasi Pengiriman</div>
                        <div class="shipping-content">
                            Kurir: {{ $data->courier->name ?? '-' }}<br>
                            Alamat: {{ $data->ongkir_address ?? '-' }}
                        </div>
                    </div>
                @endif

                <div class="section-title">Pesanan</div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $subtotal = 0; @endphp
                        @foreach ($detail as $item)
                            @php
                                $itemTotal = $item->subtotal + $item->discount;
                                $subtotal += $itemTotal;
                            @endphp
                            <tr>
                                <td>
                                    <div class="item-name-main">
                                        {{ $item->type == 'parcel' ? ($item->product?->description ?? 'Produk Dihapus') : ($item->product?->name ?? 'Produk Dihapus') }}
                                    </div>
                                    @if ($item->discount && $item->discount > 0)
                                        @php
                                            $originalSubTotal = $item->price * $item->quantity;
                                            $discountPercentPerItem = $originalSubTotal > 0 ? round(($item->discount / $originalSubTotal) * 100) : 0;
                                        @endphp
                                        <div style="font-size: 11px; color: #ef4444; margin-top: 4px;">
                                            Diskon ({{ $discountPercentPerItem }}%) (-Rp{{ number_format($item->discount, 0, ',', '.') }})
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                                <td>
                                    @if ($item->discount && $item->discount > 0)
                                        <div style="text-decoration: line-through; font-size: 11px; color: #94a3b8; margin-bottom: 2px;">
                                            Rp{{ number_format($itemTotal, 0, ',', '.') }}
                                        </div>
                                        <div>
                                            Rp{{ number_format($itemTotal - $item->discount, 0, ',', '.') }}
                                        </div>
                                    @else
                                        Rp{{ number_format($itemTotal, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            @if ($item->discount && $item->discount > 0)
                                @php
                                    $subtotal -= $item->discount;
                                @endphp
                            @endif
                            @if (isset($parcelDetail) && count($parcelDetail) > 0)
                                @php
                                    $filteredParcels = $parcelDetail->filter(function ($p) use ($item) {
                                        return $p->production_id == $item->product_id;
                                    });
                                @endphp
                                @if ($filteredParcels->count() > 0)
                                    <tr>
                                        <td colspan="4" class="parcel-details">
                                            <div class="parcel-list">
                                                <div class="parcel-list-title">Bahan</div>
                                                @foreach ($filteredParcels as $parcel)
                                                    <div class="parcel-item">
                                                        {{ $parcel->product?->name ?? '-' }}
                                                        ({{ $parcel->quantity }}
                                                        {{ $parcel->product?->unit?->abbreviation ?? 'pcs' }})
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endif
                        @endforeach
                    </tbody>
                </table>

                @php
                    $discount = $data->discount;
                    $ongkir_discount = $data->ongkir_discount;
                    $isDiscountPercentage = false;
                    if ($discount <= 100 && $discount > 0) {
                        $isDiscountPercentage = true;
                        $discountPercent = $discount;
                        $discount = ($discount / 100) * $subtotal;
                    }
                    if ($ongkir_discount <= 100 && $ongkir_discount > 0) {
                        $ongkir_discount = ($ongkir_discount / 100) * $data->ongkir;
                    }
                @endphp

                <div class="summary-card">
                    <div class="summary-row">
                        <span class="label">Subtotal</span>
                        <span class="value">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if ($discount > 0)
                        <div class="summary-row">
                            <span class="label">Diskon{{ $isDiscountPercentage ? ' (' . rtrim(rtrim(number_format($discountPercent, 2, ',', '.'), '0'), ',') . '%)' : '' }}</span>
                            <span class="value" style="color:#ef4444;">-Rp{{ number_format($discount, 0, ',', '.') }}</span>
                        </div>
                        @php $subtotal -= $discount; @endphp
                    @endif
                    @if ($data->ongkir && $data->ongkir > 0)
                        <div class="summary-row">
                            <span class="label">Ongkos Kirim</span>
                            <span class="value">Rp{{ number_format($data->ongkir, 0, ',', '.') }}</span>
                        </div>
                        @php $subtotal += $data->ongkir; @endphp
                    @endif
                    @if ($ongkir_discount > 0)
                        <div class="summary-row">
                            <span class="label">Diskon Ongkir</span>
                            <span class="value" style="color:#ef4444;">-Rp{{ number_format($ongkir_discount, 0, ',', '.') }}</span>
                        </div>
                        @php $subtotal -= $ongkir_discount; @endphp
                    @endif
                    <div class="summary-row divider"></div>
                    <div class="summary-row total">
                        <span class="label">Total Pembayaran</span>
                        <span class="value">Rp{{ number_format($data->total_amount ?? $subtotal, 0, ',', '.') }}</span>
                    </div>

                    @php
                        $totalPaymentAmount = $data->paymentDetails ? $data->paymentDetails->sum(function($p) use ($getAmount) { 
                            return $getAmount($p->payment_amount); 
                        }) : 0;
                    @endphp

                    @if ($data->paymentDetails && $totalPaymentAmount > 0)
                        <div class="payment-info">
                            @foreach ($data->paymentDetails as $payment)
                                @php $amt = $getAmount($payment->payment_amount); @endphp
                                @if ($amt > 0)
                                    @php
                                        $m = $payment->payment_method;
                                        if (empty($m) || strtolower($m) === 'tunai') $methodName = 'Tunai';
                                        elseif ($m === 'Split') $methodName = 'Split';
                                        else {
                                            $d = json_decode($m, true);
                                            if (is_array($d) && count($d) > 0) $methodName = collect($d)->implode(', ');
                                            else $methodName = $m;
                                        }
                                    @endphp
                                    <div class="summary-row">
                                        <span class="label">{{ $methodName }}</span>
                                        <span class="value">Rp{{ number_format($amt, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if ($data->return > 0)
                                <div class="summary-row return">
                                    <span class="label">Kembali</span>
                                    <span class="value">Rp{{ number_format($data->return, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    @elseif ($data->paid && $data->paid > 0)
                        <div class="payment-info">
                            <div class="summary-row">
                                <span class="label">Tunai</span>
                                <span class="value">Rp{{ number_format($data->paid, 0, ',', '.') }}</span>
                            </div>
                            <div class="summary-row return">
                                <span class="label">Kembali</span>
                                <span class="value">Rp{{ number_format(max($data->return, 0), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($data->note)
                    <div style="margin-top:16px; padding:14px 16px; background:#fffbeb; border:1px solid #fde68a; border-radius:12px;">
                        <div style="font-size:12px; font-weight:600; color:#d97706; margin-bottom:4px;">&#128221; Catatan</div>
                        <div style="font-size:13px; color:#92400e;">{{ $data->note }}</div>
                    </div>
                @endif
            </div>

            <div class="receipt-footer">
                <div class="thank-you">Terima Kasih</div>
                <div class="footer-text">
                    {{ $setting->brand_name ?? 'in!fruity' }}<br>
                    {{ $setting->brand_address ?? 'Fresh & Healthy Beverages' }}<br>
                    @if ($setting->brand_phone ?? false)
                        Telp: {{ $setting->brand_phone }}
                    @endif
                </div>
            </div>

            <div class="actions">
                @php
                    $previousUrl = url()->previous();
                    $parsedPath = parse_url($previousUrl, PHP_URL_PATH);
                    if ($parsedPath == '/pos' || $parsedPath == '/pos/' || $previousUrl == url()->current()) {
                        $backUrl = route('pos.index');
                    } else {
                        $backUrl = $previousUrl;
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-primary" style="flex:none; padding:12px 32px;">
                    &#8592; Kembali
                </a>
            </div>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.print();
        }
    </script>
</body>

</html>
