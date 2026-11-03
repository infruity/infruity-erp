<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Struk {{ $data->invoice_number }}</title><link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/erp-theme.css') }}"><style>
body { margin:0; padding:24px; background:var(--erp-canvas); }
.thermal-receipt { box-sizing:border-box; max-width:380px; width:100%; margin:0 auto; padding:24px 20px; background:white; color:#111; font:12px/1.6 'Courier New',monospace; }
.thermal-receipt header { text-align:center; border-bottom:1px dashed #666; padding-bottom:16px; margin-bottom:16px; }
.thermal-receipt h1 { font-size:18px; margin:0 0 8px; }
.thermal-receipt p { margin:4px 0; overflow-wrap:anywhere; }
.thermal-receipt dl { margin:16px 0; }
.thermal-receipt dl > div { display:flex; justify-content:space-between; gap:16px; }
.thermal-receipt dt { font-weight:400; }
.thermal-receipt dd { margin:0; text-align:right; overflow-wrap:anywhere; }
.thermal-receipt table { width:100%; border-collapse:collapse; table-layout:fixed; }
.thermal-receipt th { border-block:1px dashed #666; padding:8px 0; font-size:11px; text-align:left; }
.thermal-receipt th:last-child,.thermal-receipt td:last-child { text-align:right; width:35%; }
.thermal-receipt td { padding:10px 0; vertical-align:top; overflow-wrap:anywhere; }
.thermal-receipt td small { display:block; }
.thermal-receipt footer { padding-top:16px; margin-top:16px; border-top:1px dashed #666; text-align:center; }
.receipt-screen-actions { max-width:380px; margin:20px auto 0; display:flex; gap:12px; }
.receipt-screen-actions > * { flex:1; }
@media(max-width:430px) { body { padding:16px; } }
@media print { body { padding:0; background:white; } .thermal-receipt { padding:0; max-width:100%; } .receipt-screen-actions { display:none; } }
</style></head><body class="erp-ui"><main class="thermal-receipt"><header><h1>{{ $setting->brand_name ?? 'Infruity' }}</h1>@if($setting?->brand_address)<p>{{ $setting->brand_address }}</p>@endif @if($setting?->brand_social_media)<p>{{ $setting->brand_social_media }}</p>@endif</header><dl>
@if($setting?->is_using_invoice_number ?? true)<div><dt>Nota</dt><dd>{{ $data->invoice_number }}</dd></div>@endif
@if($setting?->is_using_date ?? true)<div><dt>Tanggal</dt><dd>{{ $data->date ? \Carbon\Carbon::parse($data->date)->format('d/m/Y') : '—' }}</dd></div>@endif
@if($setting?->is_using_cashier ?? true)<div><dt>Kasir</dt><dd>{{ $data->user?->nm_user ?? '—' }}</dd></div>@endif
</dl>@if($setting?->is_using_customer ?? true)<p><strong>Pelanggan</strong><br>{{ $data->customer?->name ?? 'Pelanggan Umum' }}</p>@endif
<table><thead><tr><th>Item</th><th>Total</th></tr></thead><tbody>@forelse($detail as $item)<tr><td>{{ $item->product?->name ?? 'Produk tidak tersedia' }}<small>{{ toNumber($item->quantity) }} {{ $item->product?->unit?->abbreviation ?? '' }} × {{ toNumber($item->price) }}</small>@if($item->discount)<small>Diskon {{ toNumber($item->discount) }}</small>@endif</td><td>{{ toNumber($item->subtotal) }}</td></tr>@empty<tr><td colspan="2">Tidak ada rincian produk.</td></tr>@endforelse</tbody></table>
<dl><div><dt><strong>Total</strong></dt><dd><strong>{{ toNumber($data->total) }}</strong></dd></div><div><dt>Voucher</dt><dd>{{ toNumber($data->voucher ?? 0) }}</dd></div><div><dt>Dibayar</dt><dd>{{ toNumber($data->total_payment) }}</dd></div><div><dt>Sisa tagihan</dt><dd>{{ toNumber($data->total_due) }}</dd></div></dl><footer>@if($setting?->header)<p>{{ strip_tags($setting->header) }}</p>@endif @if($setting?->footer)<p>{{ strip_tags($setting->footer) }}</p>@endif <p>{{ $data->invoice_number }}</p></footer></main><nav class="receipt-screen-actions" aria-label="Aksi struk"><button type="button" class="erp-button erp-button--primary" onclick="window.print()">Cetak struk</button><a href="{{ route('pos.index') }}" class="erp-button">Daftar POS</a></nav></body></html>
