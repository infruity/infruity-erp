<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $data->invoice_number ?? $data->id }}</title>
    <link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/phosphor/phosphor.css') }}">
    <style>
        /* Nota Transaksi, matched to the reference (Kasir - POS): frosted top bar, thermal-style card, bottom action bar. */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { min-height:100vh; padding:24px 16px; background:#e5e9f3; color:#1f2937; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height:1.5; -webkit-font-smoothing:antialiased; }
        a { color:inherit; text-decoration:none; }
        .nota-page { display:flex; justify-content:center; }
        .nota-card { width:100%; max-width:576px; padding:28px; border:1px solid rgba(229,231,235,.9); border-radius:16px; background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.06), 0 6px 16px rgba(15,23,42,.06); font-variant-numeric:tabular-nums; letter-spacing:-.01em; }
        .nota-divider { border-bottom:1px solid #f1f5f9; }
        .nota-brand { padding:8px 0 24px; text-align:center; }
        .nota-brand h1 { color:#111827; font-size:30px; font-weight:900; letter-spacing:-.03em; line-height:1; }
        .nota-brand p { margin-top:6px; color:#4b5563; font-size:12px; font-weight:500; }
        .nota-customer { padding:2px 0 12px; }
        .nota-customer h2 { color:#111827; font-size:14px; font-weight:700; line-height:1.4; }
        .nota-customer p { margin-top:2px; color:#6b7280; font-size:12px; }
        .nota-meta { display:flex; flex-direction:column; gap:10px; padding:14px 0; color:#4b5563; font-size:14px; }
        .nota-row { display:flex; align-items:baseline; justify-content:space-between; gap:16px; }
        .nota-row > span:first-child { color:#9ca3af; }
        .nota-row > span:last-child { min-width:0; color:#1f2937; font-weight:500; text-align:right; overflow-wrap:anywhere; }
        .nota-status { padding:12px 0; color:#111827; font-size:12px; font-weight:900; letter-spacing:.14em; text-align:center; text-transform:uppercase; }
        .nota-items { display:flex; flex-direction:column; gap:14px; padding:14px 0; }
        .nota-item h4 { color:#111827; font-size:14px; font-weight:600; line-height:1.3; }
        .nota-item .nota-row { margin-top:2px; color:#6b7280; font-size:11px; }
        .nota-item .nota-row > span:first-child { color:#6b7280; }
        .nota-item .nota-row > span:last-child { color:#111827; font-size:14px; font-weight:700; }
        .nota-sub { display:flex; flex-direction:column; gap:2px; padding:4px 0 0 8px; color:#4b5563; font-size:10.5px; }
        .nota-sub > div { display:flex; justify-content:space-between; gap:12px; }
        .nota-sub b { color:#1f2937; font-weight:700; white-space:nowrap; }
        .nota-disc { display:flex; justify-content:space-between; gap:12px; margin-top:2px; color:#ef4444; font-size:10.5px; }
        .nota-sums { display:flex; flex-direction:column; gap:8px; padding:12px 0; color:#4b5563; font-size:14px; }
        .nota-sums .nota-row > span:first-child { color:#4b5563; }
        .nota-sums .is-disc > span:last-child { color:#ef4444; }
        .nota-total { display:flex; flex-direction:column; gap:6px; padding:12px 0; }
        .nota-total .is-grand { color:#111827; font-size:16px; font-weight:700; }
        .nota-total .is-grand > span:first-child, .nota-total .is-grand > span:last-child { color:#111827; font-weight:700; }
        .nota-total .is-grand > span:last-child { font-size:18px; }
        .nota-total .nota-row:not(.is-grand) { color:#4b5563; font-size:14px; }
        .nota-total .nota-row:not(.is-grand) > span:first-child { color:#4b5563; }
        .nota-note { margin-top:12px; padding:12px 14px; border:1px solid #fde68a; border-radius:12px; background:#fffbeb; color:#92400e; font-size:12px; }
        .nota-note b { display:block; margin-bottom:2px; color:#d97706; }
        .nota-foot { display:flex; flex-direction:column; align-items:center; gap:2px; padding:16px 0 4px; text-align:center; }
        .nota-foot h3 { color:#111827; font-size:11px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; }
        .nota-foot p { color:#6b7280; font-size:10px; }

        .nota-topbar, .nota-bar { display:none; }
        .nota-desktop-actions { display:flex; gap:10px; max-width:576px; margin:16px auto 0; }
        .nota-btn { display:flex; height:44px; flex:1; align-items:center; justify-content:center; gap:8px; border:1px solid #d1d5db; border-radius:12px; background:#fff; color:#1f2937; font-size:14px; font-weight:700; cursor:pointer; }
        .nota-btn--primary { border:0; background:#0b595b; color:#fff; }
        .nota-btn--primary:hover { background:#08484a; }
        .nota-btn:not(.nota-btn--primary):hover { border-color:#34a684; color:#0b595b; }

        @media (max-width: 575.98px) {
            body { padding:72px 16px calc(96px + env(safe-area-inset-bottom)); }
            .nota-card { padding:20px 16px; }
            .nota-topbar { position:fixed; z-index:1050; inset:0 0 auto; display:flex; height:56px; align-items:center; justify-content:center; border-bottom:1px solid rgba(229,231,235,.8); background:rgba(255,255,255,.92); padding:0 16px; backdrop-filter:blur(12px); }
            .nota-topbar h1 { color:#111827; font-size:16px; font-weight:700; }
            .nota-topbar__back { position:absolute; left:16px; display:grid; width:36px; height:36px; place-items:center; border:0; border-radius:12px; background:transparent; color:#0b595b; font-size:22px; cursor:pointer; }
            .nota-bar { position:fixed; z-index:1050; inset:auto 0 0; display:flex; align-items:center; gap:10px; border-top:1px solid #e5e7eb; background:#fff; padding:12px 16px calc(14px + env(safe-area-inset-bottom)); }
            .nota-bar__icon { display:grid; width:44px; height:44px; flex:none; place-items:center; border:1px solid #d1d5db; border-radius:12px; background:#fff; color:#1f2937; font-size:18px; cursor:pointer; }
            .nota-desktop-actions { display:none; }
        }

        @media print {
            body { padding:0; background:#fff; }
            .nota-card { max-width:100%; padding:10px 5px; border:0; border-radius:0; box-shadow:none; }
            .nota-topbar, .nota-bar, .nota-desktop-actions { display:none !important; }
        }
    </style>
</head>

@php
    $getAmount = function ($amountStr) {
        if (empty($amountStr)) return 0;
        if (is_numeric($amountStr)) return (float) $amountStr;
        $decoded = json_decode($amountStr, true);
        if (is_array($decoded) && count($decoded) > 0) return (float) $decoded[0];
        return 0;
    };
    $methodName = function ($m) {
        if (empty($m) || strtolower($m) === 'tunai') return 'Tunai';
        if ($m === 'Split') return 'Split';
        $d = json_decode($m, true);
        if (is_array($d) && count($d) > 0) return collect($d)->implode(', ');
        return $m;
    };
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $qtyLabel = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');

    $payments = ($data->paymentDetails ?? collect())->filter(fn ($p) => $getAmount($p->payment_amount) > 0);
    $methods = $payments->map(fn ($p) => $methodName($p->payment_method))->filter()->unique()->values();
    if ($methods->isEmpty()) $methods->push('Tunai');
    $totalPaid = $payments->isNotEmpty() ? $payments->sum(fn ($p) => $getAmount($p->payment_amount)) : (float) ($data->paid ?? 0);

    // Same wording as the Pesanan list: paid / partially paid / unpaid / pending (total_payment only exists on the list query).
    $statusLabel = match ($data->status) {
        'paid' => 'Sudah Lunas',
        'draft', 'temp' => 'Pending',
        'debt' => $totalPaid > 0 ? 'Kurang Bayar' : 'Belum Bayar',
        default => ucfirst((string) $data->status),
    };

    // Line subtotal is stored net of the line discount; the list price (gross) is subtotal + discount.
    $subtotal = $detail->sum(fn ($line) => $line->subtotal);
    $discount = $data->discount;
    $isDiscountPercentage = false;
    if ($discount <= 100 && $discount > 0) {
        $isDiscountPercentage = true;
        $discountPercent = $discount;
        $discount = ($discount / 100) * $subtotal;
    }
    $ongkir = (float) ($data->ongkir ?? 0);
    $ongkirDiscount = $data->ongkir_discount;
    if ($ongkirDiscount <= 100 && $ongkirDiscount > 0) {
        $ongkirDiscount = ($ongkirDiscount / 100) * $ongkir;
    }
    $grandTotal = $data->total_amount ?? ($subtotal - $discount + $ongkir - $ongkirDiscount);
    $change = max((float) ($data->return ?? 0), 0);

    $previousUrl = url()->previous();
    $parsedPath = parse_url($previousUrl, PHP_URL_PATH);
    $backUrl = ($parsedPath == '/pos' || $parsedPath == '/pos/' || $previousUrl == url()->current())
        ? route('pos.index')
        : $previousUrl;
    $phone = $setting->brand_phone ?? null;
@endphp

<body>
    <header class="nota-topbar" aria-label="Navigasi nota">
        <a href="{{ $backUrl }}" class="nota-topbar__back" aria-label="Kembali" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
        <h1>Nota Transaksi</h1>
    </header>

    <main class="nota-page">
        <article class="nota-card" id="pos-printable-receipt">
            <div class="nota-brand">
                <h1>{{ $setting->brand_name ?? 'in!fruity' }}</h1>
                @if ($setting?->brand_address)<p>{{ $setting->brand_address }}</p>@endif
            </div>

            @if ($data->customer)
                <div class="nota-customer">
                    <h2>{{ $data->customer->name ?? 'Pelanggan' }}</h2>
                    @if ($data->customer->phone)<p>{{ $data->customer->phone }}</p>@endif
                </div>
                <div class="nota-divider"></div>
            @endif

            <div class="nota-meta">
                <div class="nota-row"><span>No. Invoice</span><span>{{ $data->invoice_number ?? $data->id }}</span></div>
                <div class="nota-row"><span>Waktu</span><span>{{ $data->created_at ? $data->created_at->locale('id')->translatedFormat('d M Y, H:i') : '—' }}</span></div>
                <div class="nota-row"><span>Kasir</span><span>{{ $data->user?->nm_user ?? $data->user?->name ?? 'Admin' }}</span></div>
                <div class="nota-row"><span>Metode</span><span>{{ $methods->implode(', ') }}</span></div>
                @if ($ongkir > 0)
                    <div class="nota-row"><span>Kurir</span><span>{{ $data->courier->name ?? '-' }}</span></div>
                    <div class="nota-row"><span>Alamat</span><span>{{ $data->ongkir_address ?? '-' }}</span></div>
                @endif
            </div>

            <div class="nota-divider"></div>
            <div class="nota-status">*** {{ $statusLabel }} ***</div>
            <div class="nota-divider"></div>

            <div class="nota-items">
                @foreach ($detail as $item)
                    @php
                        $name = $item->type == 'parcel' ? ($item->product?->description ?? 'Produk Dihapus') : ($item->product?->name ?? 'Produk Dihapus');
                        $unit = $item->type == 'parcel' ? 'Paket' : ($item->product?->unit?->abbreviation ?? 'Pcs');
                        $gross = $item->subtotal + $item->discount;
                        $originalSubTotal = $item->price * $item->quantity;
                        $discountPercentPerItem = $originalSubTotal > 0 ? round(($item->discount / $originalSubTotal) * 100) : 0;
                        $parcels = isset($parcelDetail) ? $parcelDetail->filter(fn ($p) => $p->production_id == $item->product_id) : collect();
                    @endphp
                    <div class="nota-item">
                        <h4>{{ $name }}</h4>
                        <div class="nota-row">
                            <span>{{ $qtyLabel($item->quantity) }} {{ $unit }} x {{ $rupiah($item->price) }}</span>
                            <span>{{ $rupiah($gross) }}</span>
                        </div>
                        @if ($parcels->isNotEmpty())
                            <div class="nota-sub">
                                @foreach ($parcels as $parcel)
                                    <div><span>- {{ $parcel->product?->name ?? '-' }}</span><b>{{ $qtyLabel($parcel->quantity) }} {{ $parcel->product?->unit?->abbreviation ?? 'Pcs' }}</b></div>
                                @endforeach
                            </div>
                        @endif
                        @if ($item->discount && $item->discount > 0)
                            <div class="nota-disc"><span>Diskon ({{ $discountPercentPerItem }}%)</span><span>-{{ $rupiah($item->discount) }}</span></div>
                        @endif
                    </div>
                @endforeach
                @if ($detail->isEmpty())
                    <p style="color:#6b7280;font-size:13px;text-align:center;">Tidak ada rincian produk.</p>
                @endif
            </div>

            <div class="nota-divider"></div>
            <div class="nota-sums">
                <div class="nota-row"><span>Subtotal</span><span>{{ $rupiah($subtotal) }}</span></div>
                @if ($discount > 0)
                    <div class="nota-row is-disc"><span>Diskon{{ $isDiscountPercentage ? ' (' . rtrim(rtrim(number_format($discountPercent, 2, ',', '.'), '0'), ',') . '%)' : '' }}</span><span>-{{ $rupiah($discount) }}</span></div>
                @endif
                @if ($ongkir > 0)
                    <div class="nota-row"><span>Ongkos Kirim</span><span>{{ $rupiah($ongkir) }}</span></div>
                @endif
                @if ($ongkirDiscount > 0)
                    <div class="nota-row is-disc"><span>Diskon Ongkir</span><span>-{{ $rupiah($ongkirDiscount) }}</span></div>
                @endif
            </div>

            <div class="nota-divider"></div>
            <div class="nota-total">
                <div class="nota-row is-grand"><span>Total ({{ $detail->count() }} Produk)</span><span>{{ $rupiah($grandTotal) }}</span></div>
                @if ($payments->count() > 1)
                    @foreach ($payments as $payment)
                        <div class="nota-row"><span>{{ $methodName($payment->payment_method) }}</span><span>{{ $rupiah($getAmount($payment->payment_amount)) }}</span></div>
                    @endforeach
                @endif
                <div class="nota-row"><span>Bayar</span><span>{{ $rupiah($totalPaid) }}</span></div>
                <div class="nota-row"><span>Kembali</span><span>{{ $change > 0 ? $rupiah($change) : '-' }}</span></div>
            </div>

            @if ($data->note)
                <div class="nota-note"><b>Catatan</b>{{ $data->note }}</div>
            @endif

            <div class="nota-divider" style="margin-top:8px"></div>
            <div class="nota-foot">
                <h3>Terima kasih atas kunjungan Anda</h3>
                <p>Kritik dan saran hubungi layanan konsumen</p>
                @if ($phone)<p>Telp: {{ $phone }}</p>@endif
            </div>
        </article>
    </main>

    <div class="nota-desktop-actions">
        <a href="{{ $backUrl }}" class="nota-btn"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i> Kembali</a>
        <button type="button" class="nota-btn nota-btn--primary" onclick="window.print()"><i class="ph-bold ph-printer" aria-hidden="true"></i> Cetak Nota</button>
    </div>

    <nav class="nota-bar" aria-label="Aksi nota">
        <a href="{{ $backUrl }}" class="nota-bar__icon" aria-label="Kembali" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
        <button type="button" class="nota-btn nota-btn--primary" onclick="window.print()"><i class="ph-bold ph-printer" aria-hidden="true"></i> Cetak Nota</button>
    </nav>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.print();
        }
    </script>
</body>

</html>
