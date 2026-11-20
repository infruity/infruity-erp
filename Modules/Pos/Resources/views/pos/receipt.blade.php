<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $data->invoice_number ?? $data->id }}</title>
    <link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/phosphor/phosphor.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
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
        .nota-meta { display:flex; flex-direction:column; gap:2px; padding:12px 0; color:#4b5563; font-size:12px; }
        .nota-row { display:flex; align-items:baseline; justify-content:space-between; gap:16px; }
        .nota-row > span:first-child { color:#9ca3af; }
        .nota-row > span:last-child { min-width:0; color:#1f2937; font-weight:500; text-align:right; overflow-wrap:anywhere; }
        /* Invoice details (up to Metode), the product name and the per-product total share the label grey, size and weight; so do the quantity line, Subtotal, Bayar and Kembali. */
        .nota-meta .nota-row > span:last-child { color:#9ca3af; font-size:12px; font-weight:400; }
        .nota-status { padding:12px 0; font-family:'Plus Jakarta Sans',sans-serif; color:#111827; font-size:12px; font-weight:700; letter-spacing:.14em; text-align:center; text-transform:uppercase; }
        .nota-items { display:flex; flex-direction:column; gap:14px; padding:14px 0; }
        .nota-item h4 { color:#9ca3af; font-size:12px; font-weight:400; line-height:1.3; }
        .nota-item .nota-row { margin-top:2px; color:#6b7280; font-size:11px; }
        .nota-item .nota-row > span:first-child { color:#9ca3af; font-size:12px; font-weight:400; }
        .nota-item .nota-row > span:last-child { color:#9ca3af; font-size:12px; font-weight:400; }
        /* Parcel contents drawn as a tree, like the variants in Master > Produk: a trunk line with a short branch to each item. */
        .nota-sub { display:flex; flex-direction:column; margin:4px 0 0 4px; border-left:2px solid #d1fae5; color:#9ca3af; font-size:11px; }
        .nota-sub > div { display:flex; align-items:center; gap:12px; line-height:18px; padding:1px 0; }
        .nota-sub > div::before { content:''; flex:none; width:12px; height:1px; margin-right:-6px; background:#d1fae5; }
        .nota-sub > div > span:first-child { flex:1; min-width:0; }
        .nota-sub > div > span:last-child { white-space:nowrap; }
        .nota-disc { display:flex; justify-content:space-between; gap:12px; margin-top:2px; color:#9ca3af; font-size:12px; }
        .nota-sums { display:flex; flex-direction:column; gap:2px; padding:12px 0; color:#4b5563; font-size:12px; }
        .nota-sums .nota-row > span:first-child { color:#9ca3af; font-weight:400; }
        .nota-sums .nota-row:not(.is-disc) > span:last-child { color:#9ca3af; font-weight:400; }
        .nota-sums .is-disc > span:last-child { color:#9ca3af; }
        .nota-total { display:flex; flex-direction:column; gap:2px; padding:12px 0; }
        .nota-total .is-grand { color:#111827; font-size:14px; font-weight:700; }
        .nota-total .is-grand > span:first-child, .nota-total .is-grand > span:last-child { color:#111827; font-weight:700; }
        .nota-total .is-grand > span:last-child { font-size:14px; }
        .nota-total .nota-row:not(.is-grand) { color:#4b5563; font-size:12px; }
        .nota-total .nota-row:not(.is-grand) > span { color:#9ca3af; font-weight:400; }
        .nota-note { margin-top:12px; padding:12px 14px; border:1px solid #fde68a; border-radius:12px; background:#fffbeb; color:#92400e; font-size:12px; }
        .nota-note b { display:block; margin-bottom:2px; color:#d97706; }
        .nota-foot { display:flex; flex-direction:column; align-items:center; gap:2px; padding:12px 0 4px; text-align:center; }
        .nota-foot h3 { color:#111827; font-size:10px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; }
        .nota-foot p { color:#6b7280; font-size:10px; }

        .nota-topbar, .nota-bar { display:none; }
        .nota-desktop-actions { display:flex; gap:10px; max-width:576px; margin:16px auto 0; }
        .nota-btn { display:flex; height:44px; flex:1; align-items:center; justify-content:center; gap:8px; border:1px solid #d1d5db; border-radius:12px; background:#fff; color:#1f2937; font-size:14px; font-weight:700; cursor:pointer; }
        .nota-btn--icon { flex:none; width:44px; font-size:18px; }
        .nota-btn--primary { border:0; background:#0b595b; color:#fff; }
        .nota-btn--primary:hover { background:#08484a; }
        .nota-btn:not(.nota-btn--primary):hover { border-color:#34a684; color:#0b595b; }

        @media (max-width: 575.98px) {
            /* As in the reference: the card fills the space between the top bar and the action bar, 20px clear of each, with equal side margins. */
            body { padding:81px 16px calc(95px + env(safe-area-inset-bottom)) !important; }
            .nota-page { width:100%; }
            .nota-card { display:flex; width:100%; height:calc(100dvh - 176px - env(safe-area-inset-bottom)); min-height:510px; flex-direction:column; overflow:hidden; padding:20px 16px 0; }
            .nota-card > :not(.nota-items) { flex-shrink:0; }
            .nota-foot { justify-content:center; padding:18px 0; }
            .nota-items { flex:1 1 auto; min-height:0; overflow-y:auto; scrollbar-width:thin; }
            .nota-topbar { position:fixed; z-index:1050; inset:0 0 auto; display:flex; height:61px; align-items:center; justify-content:center; border-bottom:1px solid rgba(229,231,235,.8); background:rgba(255,255,255,.92); padding:0 16px; backdrop-filter:blur(12px); }
            .nota-topbar h1 { color:#111827; font-size:16px; font-weight:700; }
            .nota-topbar__back { position:absolute; left:16px; display:grid; width:36px; height:36px; place-items:center; border:0; border-radius:12px; background:transparent; color:#0b595b; font-size:22px; cursor:pointer; }
            .nota-bar { position:fixed; z-index:1050; inset:auto 0 0; display:flex; align-items:center; gap:10px; border-top:1px solid #e5e7eb; background:#fff; padding:14px 16px calc(16px + env(safe-area-inset-bottom)); }
            .nota-bar__icon { display:grid; width:44px; height:44px; flex:none; place-items:center; border:1px solid #d1d5db; border-radius:12px; background:#fff; color:#1f2937; font-size:18px; cursor:pointer; }
            .nota-desktop-actions { display:none; }
        }

        @media (max-width:575.98px) and (max-height:700px) {
            .nota-card { height:auto; overflow:visible; }
            .nota-items { flex:1 0 auto; overflow:visible; }
        }

        @page { size:{{ ($setting?->paper_width ?? null) ? $setting->paper_width.'mm' : config('pos.nota_paper_width', '80mm') }} auto; margin:0; }
        @media print {
            body { padding:0; background:#fff; }
            .nota-card { height:auto; min-height:0; max-width:100%; overflow:visible; padding:10px 5px; border:0; border-radius:0; box-shadow:none; }
            .nota-items { overflow:visible; }
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

    // Receipt status follows the Plan, independently of the status labels on the transaction list.
    $statusLabel = match ($data->status) {
        'paid' => 'Sudah Lunas',
        'draft', 'temp' => 'Belum Lunas',
        'debt' => $totalPaid > 0 ? 'Bayar Sebagian' : 'Belum Lunas',
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
    $grandTotal = $data->total ?? $data->total_amount ?? ($subtotal - $discount + $ongkir - $ongkirDiscount);
    $change = max((float) ($data->return ?? 0), 0);

    $previousUrl = url()->previous();
    $parsedPath = parse_url($previousUrl, PHP_URL_PATH);
    $backUrl = ($parsedPath == '/pos' || $parsedPath == '/pos/' || $previousUrl == url()->current())
        ? route('pos.index')
        : $previousUrl;
    if ($receiptPreview ?? false) {
        $backUrl = $receiptBackUrl ?? route('pos.payment', $data->id);
    }
    $phone = ($setting?->is_using_phone ?? true) ? ($setting->brand_phone ?? null) : null;
    $social = ($setting?->is_using_social ?? true) ? trim(preg_replace('/^\s*(?:instagram|ig)\s*:\s*/i', '', (string) ($setting->brand_social_media ?? ''))) : '';
    $customerPhone = $data->customer->whatsapp ?? $data->customer->phone ?? null;
@endphp

<body>
    <header class="nota-topbar" aria-label="Navigasi nota">
        <a href="{{ $backUrl }}" class="nota-topbar__back" aria-label="Kembali" title="Kembali"><i class="ph-bold ph-caret-left" aria-hidden="true"></i></a>
        <h1>Nota Transaksi</h1>
    </header>

    <main class="nota-page">
        <article class="nota-card" id="pos-printable-receipt">
            <div class="nota-brand">
                <h1>{{ $setting->brand_name ?? 'in!fruity' }}</h1>
                @if (($setting?->is_using_address ?? true) && $setting?->brand_address)<p>{{ $setting->brand_address }}</p>@endif
            </div>

            @if ($data->customer && strcasecmp(trim((string) $data->customer->name), 'Pelanggan Umum') !== 0)
                <div class="nota-customer">
                    <h2>{{ $data->customer->name ?? 'Pelanggan' }}</h2>
                    @if ($customerPhone)<p>{{ $customerPhone }}</p>@endif
                </div>
                <div class="nota-divider"></div>
            @endif

            <div class="nota-meta">
                @if ($setting?->is_using_invoice_number ?? true)<div class="nota-row"><span>No. Invoice</span><span>{{ $data->invoice_number ?? $data->id }}</span></div>@endif
                @if ($setting?->is_using_date ?? true)<div class="nota-row"><span>Waktu</span><span>{{ $data->created_at ? $data->created_at->locale('id')->translatedFormat('d M Y, H:i') : '—' }}</span></div>@endif
                @if ($setting?->is_using_cashier ?? true)<div class="nota-row"><span>Kasir</span><span>{{ $data->user?->nm_user ?? $data->user?->name ?? 'Admin' }}</span></div>@endif
                @if ($setting?->is_using_payment_method ?? true)<div class="nota-row"><span>Metode</span><span>{{ $methods->implode(', ') }}</span></div>@endif
                @if ($ongkir > 0)
                    <div class="nota-row"><span>Kurir</span><span>{{ $data->courier->name ?? '-' }}</span></div>
                @endif
            </div>

            <div class="nota-divider"></div>
            <div class="nota-status">### {{ $statusLabel }} ###</div>
            <div class="nota-divider"></div>

            <div class="nota-items">
                @foreach ($detail as $item)
                    @php
                        $name = $item->type == 'parcel' ? ($item->product?->description ?? 'Produk Dihapus') : ($item->product?->name ?? 'Produk Dihapus');
                        $unit = $item->type == 'parcel' ? 'Paket' : ($item->product?->unit?->abbreviation ?? 'Pcs');
                        $originalSubTotal = $item->price * $item->quantity;
                        $gross = $originalSubTotal;
                        // The line stores only the discount amount, not how it was typed. A whole or half percent that reproduces the amount
                        // exactly is shown as a percentage; anything else (e.g. Rp 10.000 on Rp 35.000 = 28,57%) is a nominal discount.
                        $rawDiscountPercent = $originalSubTotal > 0 ? ($item->discount / $originalSubTotal) * 100 : 0;
                        $niceDiscountPercent = round($rawDiscountPercent * 2) / 2;
                        $discountPercentPerItem = ($rawDiscountPercent > 0 && abs($rawDiscountPercent - $niceDiscountPercent) < 0.001)
                            ? rtrim(rtrim(number_format($niceDiscountPercent, 1, ',', '.'), '0'), ',')
                            : null;
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
                                    <div><span>{{ $parcel->product?->name ?? '-' }}</span><span>{{ $qtyLabel($parcel->quantity) }} {{ $parcel->product?->unit?->abbreviation ?? 'Pcs' }}</span></div>
                                @endforeach
                            </div>
                        @endif
                        @if ($item->discount && $item->discount > 0)
                            <div class="nota-disc"><span>Diskon{{ $discountPercentPerItem !== null ? ' (' . $discountPercentPerItem . '%)' : '' }}</span><span>-{{ $rupiah($item->discount) }}</span></div>
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
                <div class="nota-row"><span>Bayar</span><span>{{ $totalPaid > 0 ? $rupiah($totalPaid) : '-' }}</span></div>
                <div class="nota-row"><span>Kembali</span><span>{{ $change > 0 ? $rupiah($change) : '-' }}</span></div>
            </div>

            @if ($data->note)
                <div class="nota-note"><b>Catatan</b>{{ $data->note }}</div>
            @endif

            <div class="nota-divider"></div>
            <div class="nota-foot">
                <h3>Terima kasih atas kunjungan Anda</h3>
                <p>Kritik dan saran hubungi layanan konsumen</p>
                @if ($phone || $social)<p>@if ($phone) WA : {{ $phone }} @endif @if ($phone && $social) / @endif @if ($social) IG : {{ $social }} @endif</p>@endif
            </div>
        </article>
    </main>

    <div class="nota-desktop-actions">
        <a href="{{ $backUrl }}" class="nota-btn"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i> Kembali</a>
        <button type="button" class="nota-btn nota-btn--icon" onclick="sendReceiptToWA()" aria-label="Kirim ke WhatsApp" title="Kirim nota via WhatsApp"><i class="ph-bold ph-share-network" aria-hidden="true"></i></button>
        <button type="button" class="nota-btn nota-btn--primary" onclick="window.print()">Cetak Nota</button>
    </div>

    <nav class="nota-bar" aria-label="Aksi nota">
        <a href="{{ $backUrl }}" class="nota-bar__icon" aria-label="Kembali" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
        <button type="button" class="nota-bar__icon" onclick="sendReceiptToWA()" aria-label="Kirim ke WhatsApp" title="Kirim nota via WhatsApp"><i class="ph-bold ph-share-network" aria-hidden="true"></i></button>
        <button type="button" class="nota-btn nota-btn--primary" onclick="window.print()">Cetak Nota</button>
    </nav>

    <script src="{{ asset('assets/js/nota-share.js') }}?v={{ filemtime(public_path('assets/js/nota-share.js')) }}"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.print();
        }

        // Bagikan: the customer-facing PNG is prepared in the background; the button shares it (see nota-share.js).
        const notaBlobPromise = (typeof NotaShare !== 'undefined')
            ? new Promise(resolve => window.addEventListener('load', () => resolve(NotaShare.buildReceiptBlob(document, window).catch(error => { console.error('Gagal membuat gambar nota:', error); return null; }))))
            : Promise.resolve(null);
        window.__notaBlobPromise = notaBlobPromise;

        async function sendReceiptToWA() {
            const info = {
                customer: @json((string) ($data->customer->name ?? 'Pelanggan Umum')),
                invoice: @json((string) ($data->invoice_number ?? $data->id)),
                total: @json($rupiah($grandTotal)),
            };
            await NotaShare.share({
                win: window,
                doc: document,
                blob: await notaBlobPromise,
                filename: 'Nota-' + info.invoice + '.png',
                text: NotaShare.buildMessage(info),
                phone: @json((string) ($customerPhone ?? '')),
                publish: blob => NotaShare.publish({
                    win: window,
                    endpoint: @json(route('pos.nota-share')),
                    csrf: @json(csrf_token()),
                    blob: blob,
                    filename: 'Nota-' + info.invoice + '.png',
                    paymentUuid: @json(! empty($payment->uuid) ? (string) $payment->uuid : null),
                    posUuid: @json(! empty($data->uuid) ? (string) $data->uuid : null),
                }),
            });
        }
    </script>
</body>

</html>
