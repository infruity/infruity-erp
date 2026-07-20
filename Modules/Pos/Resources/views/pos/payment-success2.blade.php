<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berhasil</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 540px;
            width: 100%;
            margin: 0 auto;
            background-color: white;
            border-radius: 0;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 768px) {
            body {
                padding: 20px;
            }

            .container {
                border-radius: 8px;
                min-height: calc(100vh - 40px);
            }
        }

        .header {
            background-color: white;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e0e0e0;
        }

        .time {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .icons {
            display: flex;
            gap: 8px;
            align-items: center;
            color: #666;
            font-size: 14px;
        }

        .content {
            padding: 40px 20px 20px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .content {
                padding: 30px 16px 16px;
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 768px) {
            .success-icon {
                width: 120px;
                height: 120px;
                margin: 0 auto 24px;
            }
        }

        .success-circle {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            animation: successPulse 2s ease-in-out infinite;
        }

        .success-circle::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
            top: 0;
            left: 0;
        }

        .checkmark {
            width: 45px;
            height: 25px;
            border-left: 4px solid white;
            border-bottom: 4px solid white;
            transform: rotate(-45deg);
            margin-top: -8px;
            animation: checkmarkDraw 0.8s ease-in-out 0.3s both;
            opacity: 0;
        }

        @media (min-width: 768px) {
            .checkmark {
                width: 55px;
                height: 30px;
                border-left: 5px solid white;
                border-bottom: 5px solid white;
                margin-top: -10px;
            }
        }

        @keyframes successPulse {

            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 8px 32px rgba(34, 197, 94, 0.3);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 12px 40px rgba(34, 197, 94, 0.4);
            }
        }

        @keyframes checkmarkDraw {
            0% {
                opacity: 0;
                transform: rotate(-45deg) scale(0.5);
            }

            50% {
                opacity: 1;
                transform: rotate(-45deg) scale(1.1);
            }

            100% {
                opacity: 1;
                transform: rotate(-45deg) scale(1);
            }
        }

        .success-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: particleFloat 3s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            top: 10%;
            left: 20%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 0.5s;
        }

        .particle:nth-child(3) {
            bottom: 15%;
            left: 15%;
            animation-delay: 1s;
        }

        .particle:nth-child(4) {
            bottom: 20%;
            right: 20%;
            animation-delay: 1.5s;
        }

        @keyframes particleFloat {

            0%,
            100% {
                transform: translateY(0) scale(1);
                opacity: 0.7;
            }

            50% {
                transform: translateY(-20px) scale(1.2);
                opacity: 1;
            }
        }

        h1 {
            font-size: 20px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        @media (min-width: 768px) {
            h1 {
                font-size: 24px;
            }
        }

        .date {
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .date {
                margin-bottom: 30px;
            }
        }

        .details {
            padding: 0 16px 20px;
            flex: 1;
        }

        @media (min-width: 768px) {
            .details {
                padding: 0 20px 30px;
            }
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            /* border-bottom: 1px solid #f0f0f0; */
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .label {
            color: #666;
            font-size: 14px;
        }

        .value {
            color: #333;
            font-size: 14px;
            font-weight: 600;
        }

        .buttons {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: auto;
            background-color: white;
            border-top: 1px solid #f0f0f0;
        }

        .button-row {
            display: flex;
            gap: 12px;
        }

        .button-row.receipt-buttons {
            /* Cetak Struk dan Kirim Struk tetap dalam 1 baris */
        }

        @media (min-width: 768px) {
            .buttons {
                padding: 20px;
            }
        }

        .button-row {
            display: flex;
            gap: 12px;
        }

        @media (max-width: 480px) {
            .button-row:not(.receipt-buttons) {
                flex-direction: column;
                gap: 8px;
            }
        }

        button {
            flex: 1;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            min-height: 44px;
        }

        @media (min-width: 768px) {
            button {
                padding: 14px;
                font-size: 15px;
            }
        }

        .btn-outline {
            background-color: white;
            border: 1.5px solid #d1d5db;
            color: #333;
        }

        .btn-outline:hover {
            background-color: #f9fafb;
        }

        .btn-primary {
            background-color: #22c55e;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #16a34a;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="content">
            <div class="success-icon">
                <div class="success-circle">
                    <div class="checkmark"></div>
                </div>
                <div class="success-particles">
                    <div class="particle"></div>
                    <div class="particle"></div>
                    <div class="particle"></div>
                    <div class="particle"></div>
                </div>
            </div>
            <h1>Transaksi Berhasil</h1>
            <p class="date">{{ $data->created_at }}</p>
        </div>

        <div class="details">
            <div class="detail-row">
                <span class="label">Pembayaran</span>
                <span class="value">
                    @foreach (json_decode($data->payment_method) as $method)
                        {{ strtoupper($method) }} @if (!$loop->last)
                            ,
                        @endif
                    @endforeach
                </span>
            </div>
            <div class="detail-row">
                <span class="label">Total Tagihan</span>
                <span class="value">{{ toNumber($data->pos->total ?? 0) }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Diterima</span>
                <span class="value">{{ toNumber($data->total ?? 0) }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Voucher</span>
                <span class="value">-{{ toNumber($data->pos->voucher ?? 0) }}</span>
            </div>
            @if (isset($data->return) && $data->return > 0)
                <div class="detail-row">
                    <span class="label">Kembalian</span>
                    <span class="value">{{ toNumber($data->return ?? 0) }}</span>
                </div>
            @else
                <div class="detail-row">
                    <span class="label">Kurang</span>
                    <span class="value">{{ toNumber($data->remaining ?? 0) }}</span>
                </div>
            @endif
        </div>

        <div class="buttons">
            <div class="button-row receipt-buttons">
                <button class="btn-outline"
                    onclick="window.location.href='{{ url('pos/printNota') . '/' . $data->id }}'">Cetak Struk</button>
                @php
                    $url = isset($data->uuid)
                        ? url('/cek-nota/' . $data->uuid)
                        : url('/cek-nota/draft/' . $data->pos->uuid);
                    $message = urlencode("Halo, berikut bukti transaksi Anda:\n{$url}");
                    $phone = $data->pos->customer->whatsapp ?? '';
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (str_starts_with($phone, '0')) {
                        $phone = '62' . substr($phone, 1);
                    } elseif (str_starts_with($phone, '8')) {
                        $phone = '62' . $phone;
                    }
                    $waUrl = "https://wa.me/{$phone}?text={$message}";
                @endphp
                <button class="btn-outline" onclick="window.location.href='{{ $waUrl }}'">Kirim Struk</button>
            </div>
            <button class="btn-primary" onclick="window.location.href='{{ route('pos.create') }}'">Transaksi
                Baru</button>
            <button class="btn-outline" onclick="window.location.href='{{ route('pos.index') }}'">Kembali
                ke Daftar</button>
        </div>
    </div>
</body>

</html>
