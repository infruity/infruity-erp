<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Transaksi Berhasil</title><link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}"><link rel="stylesheet" href="{{ asset('assets/plugins/phosphor/phosphor.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/erp-theme.css') }}"><style>
body { margin:0; }
.erp-confirmation { box-sizing:border-box; min-width:0; width:min(100%,520px); margin:auto; background:white; border-radius:16px; padding:32px; }
.erp-confirmation-page { min-height:100dvh; display:grid; grid-template-columns:minmax(0,1fr); place-items:center; padding:24px; box-sizing:border-box; }
.erp-confirmation header { text-align:center; margin-bottom:28px; }
.erp-confirmation header > i { display:grid; place-items:center; width:64px; height:64px; margin:0 auto 20px; background:var(--erp-success-soft); border-radius:50%; color:var(--erp-success); font-size:32px; }
.erp-confirmation h1 { font-size:22px; margin:0 0 8px; }
.erp-confirmation header p { color:var(--erp-muted); font-size:12px; }
.erp-confirmation .detail-row { display:flex; justify-content:space-between; gap:20px; padding:12px 0; border-bottom:1px solid var(--erp-border-subtle); font-size:13px; }
.erp-confirmation .label { color:var(--erp-muted); }
.erp-confirmation .value { text-align:right; font-weight:600; }
.erp-confirmation .buttons { display:grid; gap:12px; margin-top:24px; }
.erp-confirmation .button-row { display:flex; flex-wrap:wrap; gap:12px; }
.erp-confirmation .button-row > button { flex:1; }
.pos-success-mobile { display:none; }
@media(max-width:767px) { .erp-confirmation-page { padding:16px; } .erp-confirmation { padding:24px; } }
@media(max-width:767px) {
  .erp-confirmation-page { display:none; }
  .pos-success-mobile { box-sizing:border-box; display:flex; min-height:100dvh; padding:16px; background:#e5e9f3; font-family:'Plus Jakarta Sans',sans-serif; }
  .pos-success-card { box-sizing:border-box; display:flex; flex:1; flex-direction:column; justify-content:space-between; min-width:0; max-width:448px; margin:0 auto; padding:20px; border:1px solid #f3f4f6; border-radius:24px; background:#fff; box-shadow:0 8px 30px rgba(0,0,0,.06); }
  .pos-success-header { display:flex; flex-direction:column; align-items:center; border-bottom:1px solid rgba(243,244,246,.9); padding:4px 0 20px; text-align:center; }
  .pos-success-check { display:flex; width:56px; height:56px; align-items:center; justify-content:center; margin-bottom:14px; border:1px solid rgba(167,243,208,.9); border-radius:50%; background:#ecfdf5; color:#0b595b; font-size:24px; }
  .pos-success-header h1 { margin:0; color:#1e293b; font-size:24px; font-weight:900; line-height:1.25; letter-spacing:-.025em; }
  .pos-success-header p { margin:6px 0 0; color:#64748b; font-size:12px; font-weight:500; line-height:18px; }
  .pos-success-details { display:flex; flex-direction:column; gap:16px; padding:24px 4px; font-size:14px; line-height:20px; font-variant-numeric:tabular-nums; }
  .pos-success-details > div { display:flex; align-items:center; justify-content:space-between; gap:16px; }
  .pos-success-details dt { color:#475569; font-weight:400; }
  .pos-success-details dd { overflow:hidden; margin:0; color:#0f172a; font-weight:700; text-align:right; text-overflow:ellipsis; white-space:nowrap; }
  .pos-success-actions { display:flex; align-items:center; gap:8px; border-top:1px solid #f3f4f6; padding-top:16px; }
  .pos-success-icon, .pos-success-new { display:flex; height:44px; align-items:center; justify-content:center; border-radius:16px; font-weight:700; text-decoration:none; }
  .pos-success-icon { box-sizing:border-box; width:44px; flex:none; border:1px solid #e5e7eb; background:#fff; color:#374151; font-size:18px; }
  .pos-success-new { flex:1; padding:0 12px; border:0; background:#0b595b; color:#fff; font-size:12px; white-space:nowrap; box-shadow:0 6px 16px -2px rgba(11,89,91,.28); }
}
</style></head><body class="erp-ui">
@php
    $successMethods = json_decode((string) $data->payment_method, true);
    $successMethods = is_array($successMethods) ? $successMethods : array_filter([(string) $data->payment_method]);
    $successMethodLabel = collect($successMethods)->filter()->map(fn ($method) => ucfirst(mb_strtolower((string) $method)))->implode(', ') ?: 'Tunai';
    $successMoney = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $successPhone = preg_replace('/[^0-9]/', '', (string) ($data->pos->customer->whatsapp ?? ''));
    if (str_starts_with($successPhone, '0')) $successPhone = '62' . substr($successPhone, 1);
    elseif (str_starts_with($successPhone, '8')) $successPhone = '62' . $successPhone;
@endphp
<main class="pos-success-mobile">
    <section class="pos-success-card" aria-labelledby="pos-success-title">
        <div>
            <header class="pos-success-header">
                <span class="pos-success-check"><i class="ph-bold ph-check" aria-hidden="true"></i></span>
                <h1 id="pos-success-title">Transaksi Berhasil</h1>
                <p>{{ $data->created_at ? $data->created_at->locale('id')->translatedFormat('l, d M Y H:i') : '' }}</p>
            </header>
            <dl class="pos-success-details">
                <div><dt>Pembayaran</dt><dd>{{ $successMethodLabel }}</dd></div>
                <div><dt>Total Tagihan</dt><dd>{{ $successMoney($data->pos->total ?? 0) }}</dd></div>
                <div><dt>Diterima</dt><dd>{{ $successMoney($data->total ?? 0) }}</dd></div>
                <div><dt>{{ (float) ($data->remaining ?? 0) > 0 ? 'Kurang' : 'Kembalian' }}</dt><dd>{{ $successMoney((float) ($data->remaining ?? 0) > 0 ? $data->remaining : ($data->return ?? 0)) }}</dd></div>
            </dl>
        </div>
        <nav class="pos-success-actions" aria-label="Aksi transaksi">
            <a class="pos-success-icon" href="{{ route('pos.index') }}" aria-label="Daftar Transaksi" title="Daftar Transaksi"><i class="ph-bold ph-list-bullets" aria-hidden="true"></i></a>
            <button type="button" class="pos-success-icon" id="pos-success-print" data-url="{{ route('pos.printNota', ['id' => $data->id, 'preview' => 'mobile', 'print' => 'frame']) }}" aria-label="Cetak nota" title="Cetak nota"><i class="ph-bold ph-printer" aria-hidden="true"></i></button>
            <button type="button" class="pos-success-icon" id="pos-success-share" data-url="{{ route('pos.printNota', ['id' => $data->id, 'preview' => 'mobile', 'print' => 'frame']) }}" data-phone="{{ $successPhone }}" data-endpoint="{{ route('pos.nota-share') }}" data-csrf="{{ csrf_token() }}" data-payment-uuid="{{ $data->uuid ?? '' }}" data-pos-uuid="{{ $data->pos->uuid ?? '' }}" data-customer="{{ $data->pos->customer->name ?? 'Pelanggan Umum' }}" data-invoice="{{ $data->pos->invoice_number ?? $data->pos->id }}" data-total="{{ $successMoney($data->pos->total ?? 0) }}" aria-label="Bagikan nota" title="Bagikan nota"><i class="ph-bold ph-share-network" aria-hidden="true"></i></button>
            <a class="pos-success-new" href="{{ route('pos.create') }}">Transaksi Baru</a>
        </nav>
    </section>
</main>
<main class="erp-confirmation-page"><section class="erp-confirmation"><header><i class="ph ph-check" aria-hidden="true"></i><h1>Transaksi berhasil</h1><p>{{ $data->created_at }}</p></header>        <div class="details">
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
                <button class="erp-button"
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
                <button class="erp-button" onclick="window.location.href='{{ $waUrl }}'">Kirim Struk</button>
            </div>
            <button class="erp-button erp-button--primary" onclick="window.location.href='{{ route('pos.create') }}'">Transaksi
                Baru</button>
            <button class="erp-button" onclick="window.location.href='{{ route('pos.index') }}'">Kembali
                ke Daftar</button>
        </div>
</section></main><script>
// Cetak nota prints in place: the nota loads in a hidden frame and the print dialog opens, nobody is sent to another page.
(function () {
    var button = document.getElementById('pos-success-print');
    if (!button) return;
    button.addEventListener('click', function () {
        if (button.disabled) return;
        button.disabled = true;
        var stale = document.getElementById('pos-success-frame');
        if (stale) stale.remove();
        var frame = document.createElement('iframe');
        frame.id = 'pos-success-frame';
        frame.setAttribute('aria-hidden', 'true');
        frame.tabIndex = -1;
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
        var done = function () { button.disabled = false; setTimeout(function () { frame.remove(); }, 1000); };
        frame.onload = function () {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.addEventListener('afterprint', done);
                frame.contentWindow.print();
            } catch (e) { done(); }
            setTimeout(function () { button.disabled = false; }, 4000);
        };
        frame.src = button.dataset.url;
        document.body.appendChild(frame);
    });
})();
</script>
<script src="{{ asset('assets/js/nota-share.js') }}?v={{ filemtime(public_path('assets/js/nota-share.js')) }}"></script>
<script>
// Bagikan: the nota PNG is prepared in a hidden frame as soon as the page opens, so the tap shares it right away (system share sheet, or copy + WhatsApp chat).
(function () {
    var button = document.getElementById('pos-success-share');
    if (!button) return;
    var frame = document.createElement('iframe');
    frame.id = 'pos-success-share-frame';
    frame.setAttribute('aria-hidden', 'true');
    frame.tabIndex = -1;
    frame.style.cssText = 'position:fixed;right:0;bottom:0;width:400px;height:600px;border:0;visibility:hidden;pointer-events:none';
    frame.src = button.dataset.url + '&share=1';
    document.body.appendChild(frame);
    button.addEventListener('click', async function () {
        if (button.disabled) return;
        button.disabled = true;
        try {
            var promise = frame.contentWindow && frame.contentWindow.__notaBlobPromise;
            var blob = promise ? await promise : null;
            await NotaShare.share({
                win: window,
                doc: document,
                blob: blob,
                filename: 'Nota-' + button.dataset.invoice + '.png',
                text: NotaShare.buildMessage({ customer: button.dataset.customer, invoice: button.dataset.invoice, total: button.dataset.total }),
                phone: button.dataset.phone,
                publish: function (shared) {
                    return NotaShare.publish({
                        win: window,
                        endpoint: button.dataset.endpoint,
                        csrf: button.dataset.csrf,
                        blob: shared,
                        filename: 'Nota-' + button.dataset.invoice + '.png',
                        paymentUuid: button.dataset.paymentUuid || null,
                        posUuid: button.dataset.posUuid || null,
                    });
                },
            });
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
</body></html>
