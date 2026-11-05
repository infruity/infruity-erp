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
@media(max-width:767px) { .erp-confirmation-page { padding:16px; } .erp-confirmation { padding:24px; } }
</style></head><body class="erp-ui"><main class="erp-confirmation-page"><section class="erp-confirmation"><header><i class="ph ph-check" aria-hidden="true"></i><h1>Transaksi berhasil</h1><p>{{ $data->created_at }}</p></header>        <div class="details">
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
                <button class="erp-action"
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
                <button class="erp-action" onclick="window.location.href='{{ $waUrl }}'">Kirim Struk</button>
            </div>
            <button class="erp-action erp-action--primary" onclick="window.location.href='{{ route('pos.create') }}'">Transaksi
                Baru</button>
            <button class="erp-action" onclick="window.location.href='{{ route('pos.index') }}'">Kembali
                ke Daftar</button>
        </div>
</section></main></body></html>