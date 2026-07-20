<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Infruity')</title>
    <meta name="description" content="@yield('meta_description', 'UMKM Jual Buah Segar dan Sehat Secara Online')">
    <meta name="keywords" content="@yield('meta_keywords', 'jual buah online, buah segar, infruity, infruity.com, buah sehat')">
    <meta name="author" content="Infruity">
    <link rel="canonical" href="{{ url()->current() }}" />
    <meta property="og:type" content="article">
    <meta property="og:title" content="@yield('title', 'UMKM Jual Buah Segar dan Sehat Secara Online')">
    <meta property="og:site_name" content="UMKM Jual Buah Segar dan Sehat Secara Online">
    <meta property="og:description" content="@yield('meta_description', 'UMKM Jual Buah Segar dan Sehat Secara Online')">
    <meta property="og:image" content="@yield('og_image', asset('images/logo-infruity.png'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo-infruity.png') }}" />
    <script src="https://cdn.jsdelivr.net/npm/easyqrcodejs@4.4.10/dist/easy.qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            /* font-family: 'Roboto'; */
            background-color: #f3f4f6;
            padding: 20px;
            line-height: 1.5;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 40px);
        }

        .receipt {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
        }

        /* Header */
        .header {
            /* background: linear-gradient(135deg, #2563eb, #1d4ed8); */
            /* color: white; */
            text-align: center;
            padding: 24px;
        }

        .brand-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .tagline {
            /* color: #bfdbfe; */
            font-size: 14px;
            font-weight: 400;
        }

        /* Customer Section */
        .customer-section {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatar {
            width: 48px;
            height: 48px;
            /* background: #2563eb; */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .star-icon {
            width: 24px;
            height: 24px;
            color: white;
        }

        .customer-details {
            flex: 1;
        }

        .customer-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            /* margin-bottom: 4px; */
        }

        .level-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 8px;
            overflow: visible;
            position: relative;
        }

        .progress-fill {
            width: var(--progress);
            height: 100%;
            /* background: linear-gradient(90deg, #f97316, #eab308, #22c55e, #16a34a);             */
            /* background: var(--fill-color); */
            background: #16a34a;
            border-radius: 8px;
            position: relative;
            transition: width 0.3s ease;
        }

        /* .progress-fill::before {
            content: attr(data-percent) '%';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translate(50%, -50%);
            width: 24px;
            height: 24px;
            background: #16a34a;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        } */

        /* .progress-fill::before {
            content: attr(data-percent) '%';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            background: #16a34a;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        } */

        .progress-fill::before {
            content: attr(data-percent) '%';
            position: absolute;
            top: 50%;
            transform: translate({{ request()->mode == 2 ? '-50%' : '50%' }}, -50%);
            left: {{ request()->mode == 2 ? '0' : 'auto' }};
            right: {{ request()->mode == 2 ? 'auto' : '0' }};
            width: 24px;
            height: 24px;
            background: #16a34a;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
            color: white;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }



        /* Receipt Details */
        .receipt-details {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            font-size: 14px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item:nth-child(even) {
            text-align: right;
        }

        .label {
            color: #6b7280;
            margin-bottom: 2px;
        }

        .value {
            /* font-weight: 500; */
            font-size: 14px;
            /* color: #1f2937; */
            color: #6b7280;
        }

        /* Status */
        .status-section {
            padding: 16px 24px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .status-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 8px 24px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .status-badge-danger {
            display: inline-block;
            background: #f56969;
            color: #850505;
            padding: 8px 24px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        /* Items */
        .items-section {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .item {
            margin-bottom: 20px;
        }

        .item:last-child {
            margin-bottom: 0;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            /* margin-bottom: 4px; */
        }

        .item-name {
            font-weight: 500;
            color: #1f2937;
        }

        .item-total {
            /* font-weight: 500; */
            color: #6b7280;
        }

        .item-price {
            font-size: 14px;
            color: #6b7280;
            /* margin-bottom: 4px; */
        }

        .discount {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            /* color: #dc2626; */
            color: #6b7280;
        }

        /* Totals */
        .totals-section {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            /* margin-bottom: 8px; */
            font-size: 14px;
        }

        .total-label {
            color: #6b7280;
        }

        .total-value {
            /* font-weight: 500; */
            color: #6b7280;
        }

        .discount-line .total-value {
            color: #dc2626;
        }

        .grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* padding-top: 12px; */
            /* border-top: 1px solid #e5e7eb; */
            /* margin-top: 8px; */
        }

        .grand-total-label {
            font-weight: 600;
            font-size: 18px;
            color: #1f2937;
        }

        .grand-total-value {
            font-weight: 700;
            font-size: 18px;
            /* color: #16a34a; */
            color: #1f2937;
        }

        /* Payment */
        .payment-section {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .payment-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .payment-line:last-child {
            margin-bottom: 0;
        }

        .payment-label {
            color: #6b7280;
        }

        .payment-value {
            /* font-weight: 500; */
            color: #6b7280;
        }

        /* Footer */
        .footer {
            background: #f9fafb;
            padding: 24px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .contact-info {
            flex: 1;
            text-align: right;
        }

        .footer-title {
            font-size: 12px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .footer-text {
            text-align: right;
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .contact-details {
            font-size: 11px;
            color: #6b7280;
        }

        .qr-code {
            width: 100px;
            height: 100px;
            background: #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qr-code svg {
            width: 100px;
            height: 100px;
            color: #6b7280;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .receipt {
                max-width: 100%;
            }

            .brand-name {
                font-size: 24px;
            }

            .customer-section,
            .receipt-details,
            .items-section,
            .totals-section,
            .payment-section,
            .footer {
                padding: 20px;
            }

            .detail-grid {
                gap: 12px;
            }

            /* .footer-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 16px;
            } */
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .print-button {
            background: #ffffff;
            color: #333;
            border: 2px solid #333;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .print-button:hover {
            background-color: #333;
            color: #fff;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                /* min-height: auto; */
                border: none;
                /* max-width: 100%; */
                min-width: auto;
                max-width: 100%;
            }

            .receipt {
                box-shadow: none;
                padding: 10px 5px;
                max-width: 100%;
                /* border: 1px solid #e5e7eb; */
            }

            .button-group {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="receipt">
            <!-- Header -->
            <div class="header">
                <h1 class="brand-name">{{ $setting->brand_name }}</h1>
                <p class="tagline">{{ $setting->brand_address }}</p>
                <p class="tagline">{{ $setting->brand_social_media }}</p>
            </div>

            <!-- Customer Profile -->
            <div class="customer-section">
                <div class="customer-info">
                    @isset($tier->tier_name)
                        <div class="avatar">
                            @php
                                $assetPath = asset('images/icon/bronze-icon.png');
                                // if (isset($tier->tier_name)) {
                                //     switch ($tier->tier_name) {
                                //         case 'Bronze':
                                //             $assetPath = asset('images/icon/bronze-icon.png');
                                //             break;
                                //         case 'Silver':
                                //             $assetPath = asset('images/icon/silver-icon.png');
                                //             break;
                                //         case 'Gold':
                                //             $assetPath = asset('images/icon/gold-icon.png');
                                //             break;
                                //         case 'Platinum':
                                //             $assetPath = asset('images/icon/platinum-icon.png');
                                //             break;
                                //         default:
                                //             $assetPath = asset('images/icon/bronze-icon.png');
                                //             break;
                                //     }
                                // }
                                if (isset($tier->icon)) {
                                    $assetPath = asset('storage/' . $tier->icon);
                                }
                            @endphp
                            <img src="{{ $assetPath }}" alt="icon" width="48">
                        </div>
                    @endisset
                    @php
                        $currentExp = 0;
                        $maxExp = 0;
                        if (isset($tier)) {
                            $currentExp = $tier->customer_exp;
                            $maxExp = $tier->max_exp ?? $tier->min_exp;
                            $percent = min(100, ($currentExp / $maxExp) * 100);
                        } else {
                            $percent = 0;
                        }

                        $color = match (true) {
                            $percent <= 25 => '#dc2626', // merah
                            $percent <= 50 => '#eab308', // kuning
                            $percent <= 75 => '#f97316', // orange
                            default => '#16a34a', // hijau
                        };
                    @endphp
                    <div class="customer-details">
                        <h2 class="customer-name">{{ $data->customer?->name ?? 'Pelanggan Umum' }}</h2>
                        @isset($tier->tier_name)
                            <p class="level-text">Level {{ $tier->tier_name ?? 'Bronze' }}
                                ({{ tonumberround(floatval($currentExp)) ?? 0 }} /
                                {{ tonumberround(floatval($maxExp)) ?? 0 }})</p>
                            <div class="progress-bar">
                                <div class="progress-fill"
                                    style="--progress: {{ $percent }}%; --fill-color: {{ $color }};"
                                    data-percent="{{ round($percent) }}"></div>
                            </div>
                        @endisset
                    </div>
                </div>
            </div>

            <!-- Receipt Details -->
            <div class="receipt-details">
                <div class="detail-grid">
                    <div class="detail-item">
                        @if ($setting->is_using_invoice_number)
                            <span class="label">No. Struk</span>
                        @endif
                        @if ($setting->is_using_date)
                            <span class="label">Waktu</span>
                        @endif
                        @if ($setting->is_using_cashier)
                            <span class="label">Kasir</span>
                        @endif
                        <span class="label">Jenis Pembayaran</span>
                    </div>
                    <div class="detail-item">
                        @if ($setting->is_using_invoice_number)
                            <span class="value">{{ $data->invoice_number }}</span>
                        @endif
                        @if ($setting->is_using_date)
                            <span class="value">{{ date('d M Y, H:i', strtotime($data->created_at)) }}</span>
                        @endif
                        @if ($setting->is_using_cashier)
                            <span
                                class="value">{{ Str::limit(ucwords(strtolower($data->user->nm_user ?? 'Admin')), 15, '...') }}</span>
                        @endif
                        <span class="value">{{ isset($payment) ? ucwords(strtolower($payment->paymentMethod->name ?? '-')) : '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- <!-- Status -->
            <div class="status-section">
                @if ($data->status == 'paid')
                    <div class="status-badge">### LUNAS ###</div>
                @else
                    <div class="status-badge-danger">### BELUM LUNAS ###</div>
                @endif
            </div> --}}

            <!-- Items -->
            <div class="items-section">
                @php
                    $total = 0;
                @endphp
                @isset($detail)
                    {{-- {{ dd($detail) }} --}}
                    @foreach ($detail as $item)
                        <div class="item">
                            <div class="item-details">
                                <div class="item-header">
                                    <span
                                        class="item-name">{{ $item->type == 'parcel' ? ($item->product?->description ?? 'Produk Dihapus') : ($item->product?->name ?? 'Produk Dihapus') }}</span>
                                </div>
                                <div class="item-price" style=" display: flex;justify-content: space-between;">
                                    <span>
                                        {{ tonumberround($item->price) }} x
                                        {{ $item->quantity . ' (' . ($item->product?->unit?->abbreviation ?? '-') . ')' }}
                                    </span>
                                    @php
                                        $subTotal = $item->subtotal;
                                    @endphp
                                    <span class="item-total">Rp
                                        {{ tonumberround($subTotal) }}</span>
                                    @php
                                        $total += $subTotal;
                                    @endphp
                                </div>
                                <div class="discount">
                                    @isset($item->discount)
                                        @if ($item->discount > 0)
                                            @php
                                                $originalSubTotal = $item->price * $item->quantity;
                                                $discountPercentPerItem = $originalSubTotal > 0 ? round(($item->discount / $originalSubTotal) * 100) : 0;
                                            @endphp
                                            <span>Diskon
                                                ({{ $discountPercentPerItem }}%)
                                                per Item
                                            </span>
                                            <span>- {{ tonumberround($item->discount) }}</span>
                                        @endif
                                    @endisset
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endisset
                {{-- <div class="item">
                    <div class="item-details">
                        <div class="item-header">
                            <span class="item-name">Pisang Cavendish</span>
                            <span class="item-total">Rp 30.000</span>
                        </div>
                        <div class="item-price">Rp 30.000 x 1 (kg)</div>
                        <div class="discount">
                            <span>Diskon (10%)</span>
                            <span>- Rp 3.000</span>
                        </div>
                    </div>
                </div>
                 --}}
            </div>

            <!-- Totals -->
            @php
                $discount = $data->discount;
                $ongkir_discount = $data->ongkir_discount;
                $subtotal = $total;
                $isDiscountPercentage = false;
                if ($discount <= 100 && $discount > 0) {
                    $isDiscountPercentage = true;
                    $discountPercent = $discount;
                    $discount = ($discount / 100) * $subtotal;
                }
                if ($ongkir_discount <= 100) {
                    $ongkir_discount = ($ongkir_discount / 100) * $data->ongkir;
                }
            @endphp
            <div class="totals-section">
                <div class="total-line">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">{{ tonumberround($subtotal) }}</span>
                </div>
                @if ($discount > 0)
                    <div class="total-line discount-line">
                        <span class="total-label">Diskon{{ $isDiscountPercentage ? ' (' . rtrim(rtrim(number_format($discountPercent, 2, ',', '.'), '0'), ',') . '%)' : '' }}</span>
                        <span class="total-value">-{{ tonumberround($discount) }}</span>
                    </div>
                    @php
                        $subtotal -= $discount;
                    @endphp
                @endif
                @if ($data->ongkir > 0)
                    <div class="total-line">
                        <span class="total-label">Biaya Pengiriman</span>
                        <span class="total-value">{{ tonumberround($data->ongkir) }}</span>
                    </div>
                    @php
                        $subtotal += $data->ongkir;
                    @endphp
                @endif
                @if ($ongkir_discount > 0)
                    <div class="total-line discount-line">
                        <span class="total-label">Diskon Ongkir</span>
                        <span class="total-value">-{{ tonumberround($ongkir_discount) }}</span>
                    </div>
                    @php
                        $subtotal -= $ongkir_discount;
                    @endphp
                @endif
                @if ($data->voucher > 0)
                    <div class="total-line">
                        <span class="total-label">Voucher</span>
                        <span class="total-value">-{{ tonumberround($data->voucher) }}</span>
                    </div>
                    @php
                        $subtotal -= $data->voucher;
                    @endphp
                @endif
                @if (isset($deposito))
                    <div class="total-line">
                        <span class="total-label">Voucher</span>
                        <span class="total-value">-{{ tonumberround($deposito->voucher) }}</span>
                    </div>
                    @php
                        $subtotal -= $deposito->voucher;
                    @endphp
                @endif
            </div>
            <div class="totals-section">
                <div class="grand-total">
                    <span class="grand-total-label">Total ({{ count($detail) }} Produk)</span>
                    @if (isset($deposito))
                        <span class="grand-total-value">{{ tonumberround($data->total - $deposito->voucher) }}</span>
                    @else
                        <span class="grand-total-value">{{ tonumberround($data->total - $data->voucher) }}</span>
                    @endif
                </div>
            </div>

            <!-- Payment -->
            @if (isset($payment))
                <div class="payment-section">
                    <div class="payment-line">
                        <span class="payment-label"><b>Bayar</b></span>
                        <span class="payment-value"><b>Rp {{ tonumberround($payment->total) }}</b></span>
                    </div>
                    @php
                        $methods = json_decode($payment->payment_method, true);
                        $amounts = json_decode($payment->payment_amount, true);

                        // gabungkan jadi list
                        $list_payment = [];
                        if (is_array($methods) && is_array($amounts)) {
                            foreach ($methods as $i => $method) {
                                $list_payment[] = [
                                    'payment_method' => $method,
                                    'payment_amount' => $amounts[$i] ?? null, // antisipasi mismatch
                                ];
                            }
                        }
                    @endphp
                    @foreach ($list_payment as $key => $item)
                        <div class="payment-line" style="margin-top: -10px">
                            <span class="payment-label"> {{ $key + 1 }}.
                                {{ ucwords($item['payment_method']) }}</span>
                            <span class="payment-value">Rp {{ $item['payment_amount'] }}</span>
                        </div>
                    @endforeach
                    @if (isset($payment->return) && $payment->return > 0)
                        <div class="payment-line">
                            <span class="payment-label">Kembali</span>
                            <span class="payment-value">Rp {{ tonumberround($payment->return) }} -</span>
                        </div>
                    @else
                        {{-- <div class="payment-line">
                            <span class="payment-label">Kurang</span>
                            <span class="payment-value">Rp {{ tonumberround($payment->remaining) }} -</span>
                        </div> --}}
                    @endif
                </div>
            @endif
            @if (isset($data->courier_id))
                <div class="totals-section" style="line-height: 14px">
                    <span class="label" style="font-size: 14px;">Barang akan dikirimkan ke :
                        <br>{{ $data->ongkir_address ?? '-' }} <br>oleh :
                        {{ $data->courier->name ?? '-' }}</span>
                </div>
            @endif
            <!-- Footer -->
            <div class="footer">
                <div class="footer-content">
                    <div class="contact-info">
                        <h3 class="footer-title">{{ $setting->brand_greeting }}</h3>
                        <p class="footer-text">{{ $setting->note }}</p>
                        <p class="contact-details">
                            {{ $setting->brand_phone }} (WA/SMS)<br>
                            Instagram: {{ $setting->brand_social_media }}
                        </p>
                    </div>
                    <div class="qr-code" id="qrcode"></div>
                </div>
            </div>
        </div>
    </div>
    @if (Request::segment(1) != 'cek-nota')
        <div class="button-group">
            <button class="print-button" onclick="window.print()">Cetak Receipt</button>
            <button class="print-button" onclick="window.location.href='{{ route('pos.index') }}'">Kembali</button>
            <button class="print-button" onclick="downloadReceiptAsPNG()">Download PNG</button>
            <button class="print-button" onclick="sendReceiptToWA()">Kirim ke WhatsApp</button>
        </div>
    @endif

    <script>
        // Auto print jika ada parameter print=true di URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            setTimeout(() => {
                window.print();
            }, 500);
        }

        // Fungsi untuk print dengan auto close
        function printAndClose() {
            window.print();
            setTimeout(() => {
                window.close();
            }, 1000);
        }

        function downloadReceiptAsPNG() {
            const receipt = document.querySelector('.container');
            html2canvas(receipt, {
                scale: 2, // Lebih tajam
                backgroundColor: '#fff',
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'receipt_{{ $data->invoice_number ?? 'invoice' }}.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }

        function sendReceiptToWA() {
            const url = "{{ url('/cek-nota') . '/' . (isset($payment->uuid) ? $payment->uuid : 'draft/' . $data->uuid) }}";
            const message = encodeURIComponent(
                `Halo, berikut bukti transaksi Anda:\n${url}`);
            let phone = '{{ $data->customer->whatsapp ?? '' }}'; // Ganti dengan nomor tujuan
            phone = phone.replace(/[^0-9]/g, '');
            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
            } else if (phone.startsWith('8')) {
                phone = '62' + phone;
            }
            const waUrl = `https://wa.me/${phone}?text=${message}`;
            window.open(waUrl, '_blank');
        }

        // function sendReceiptToWA() {
        //     const receipt = document.querySelector('.container');
        //     html2canvas(receipt, {
        //         scale: 2,
        //         backgroundColor: '#fff',
        //     }).then(canvas => {
        //         const base64Image = canvas.toDataURL('image/png');

        //         fetch('/upload-receipt', {
        //                 method: 'POST',
        //                 headers: {
        //                     'Content-Type': 'application/json',
        //                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
        //                 },
        //                 body: JSON.stringify({
        //                     image: base64Image
        //                 }),
        //             })
        //             .then(res => res.json())
        //             .then(data => {
        //                 if (data.url) {
        //                     const message = encodeURIComponent(
        //                         `Halo, berikut bukti transaksi Anda:\n${data.url}`);
        //                     const phone = '6281230607050'; // Ganti dengan nomor tujuan
        //                     const waUrl = `https://wa.me/${phone}?text=${message}`;
        //                     window.open(waUrl, '_blank');
        //                 } else {
        //                     alert('Gagal upload gambar.');
        //                 }
        //             })
        //             .catch(err => {
        //                 console.error(err);
        //                 alert('Terjadi kesalahan saat mengirim ke WhatsApp.');
        //             });
        //     });
        // }

        document.addEventListener("DOMContentLoaded", function() {
            var options = {
                text: "{{ url('/cek-nota') . '/' . (isset($payment->uuid) ? $payment->uuid : 'draft/' . $data->uuid) }}",
                width: 100,
                height: 100,
                quietZone: 5,
            };

            new QRCode(document.getElementById("qrcode"), options);
        });
    </script>
</body>

</html>
