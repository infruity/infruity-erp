<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infruity - UMKM Jual Buah Terbaik di Negeri Ini</title>
    <meta charset="utf-8" />
    <meta name="description" content="UMKM jual buah dengan harga terjangkau" />
    <meta name="keywords" content="buah, umkm, pasar" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Infruity - UMKM Olahan Buah Terbesar di Abad Ini" />
    <meta property="og:url" content="https://infruity.com" />
    <meta property="og:site_name" content="Infruity" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="https://infruity.com" />
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
            background: var(--fill-color);
            border-radius: 8px;
            position: relative;
            transition: width 0.3s ease;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translate(50%, -50%);
            width: 14px;
            height: 14px;
            /* background-color: #16a34a; */
            background-color: var(--fill-color);
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
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
                            <span class="value">{{ $data->invoice_number ?? '#123' }}</span>
                        @endif
                        @if ($setting->is_using_date)
                            <span
                                class="value">{{ date('d M Y, H:i', strtotime($data->created_at ?? date('Y-m-d'))) }}</span>
                        @endif
                        @if ($setting->is_using_cashier)
                            <span
                                class="value">{{ Str::limit(ucwords(strtolower($data->user->nm_user ?? 'Admin')), 15, '...') }}</span>
                        @endif
                        <span class="value">{{ ucwords(strtolower($payment->paymentMethod->name ?? '-')) }}</span>
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
                <div class="item">
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

            </div>
            <div class="totals-section">
                <div class="total-line">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">30.000</span>
                </div>
                <div class="total-line discount-line">
                    <span class="total-label">Diskon</span>
                    <span class="total-value">10.000</span>
                </div>
                <div class="total-line">
                    <span class="total-label">Biaya Pengiriman</span>
                    <span class="total-value">10.000</span>
                </div>
            </div>
            <div class="totals-section">
                <div class="grand-total">
                    <span class="grand-total-label">Total (1 Produk)</span>
                    <span class="grand-total-value">30.000</span>
                </div>
            </div>
            <div class="payment-section">
                <div class="payment-line">
                    <span class="payment-label">Bayar</span>
                    <span class="payment-value">Rp 10.000</span>
                </div>
                <div class="payment-line">
                    <span class="payment-label">Kembali</span>
                    <span class="payment-value">Rp 10.000</span>
                </div>
            </div>
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
            const url = "{{ url('/cek-nota') }}";
            const message = encodeURIComponent(
                `Halo, berikut bukti transaksi Anda:\n${url}`);
            const phone = '6281230607050'; // Ganti dengan nomor tujuan
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
                text: "{{ url('/cek-nota') }}",
                width: 100,
                height: 100,
                quietZone: 5,
            };

            new QRCode(document.getElementById("qrcode"), options);
        });
    </script>
</body>

</html>
