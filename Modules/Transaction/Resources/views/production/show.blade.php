@extends('template.root')
@section('title', 'Detail Produksi')

@section('content')
<style>
    .production-detail { display:flex; flex-direction:column; gap:16px; max-width:896px; margin:0 auto; }
    .production-detail__card { overflow:clip; border:1px solid #e5e7eb; border-radius:16px; background:#fff; }
    .production-detail__head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding:16px 20px; }
    .production-detail__title { display:flex; align-items:center; gap:12px; min-width:0; }
    .production-detail__avatar { display:flex; width:40px; height:40px; flex:none; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:19px; }
    .production-detail__title h1 { margin:0; color:#111827; font-size:16px; font-weight:800; overflow-wrap:anywhere; }
    .production-detail__title p { margin:2px 0 0; color:#6b7280; font-size:12px; }
    .production-detail__status { display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .production-detail__badge { border-radius:999px; padding:3px 10px; font-size:11px; font-weight:700; }
    .production-detail__badge.is-done { background:#f0fdf4; color:#166534; }
    .production-detail__badge.is-ready { background:#fffbeb; color:#b45309; }
    .production-detail__badge.is-temp { background:#f3f4f6; color:#6b7280; }
    .production-detail__badge.is-pos { background:#ecfdf5; color:#0b595b; }
    .production-detail__body { padding:16px 20px; }
    .production-detail__meta { display:grid; gap:10px; margin:0; }
    .production-detail__meta > div { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; font-size:13px; }
    .production-detail__meta dt { flex:none; color:#6b7280; font-weight:500; }
    .production-detail__meta dd { margin:0; color:#111827; font-weight:700; text-align:right; overflow-wrap:anywhere; }
    .production-detail__meta dd a { color:#0b595b; text-decoration:none; }
    .production-detail__meta .is-note { border-top:1px solid #f3f4f6; padding-top:10px; }
    .production-detail__section-title { margin:0; color:#111827; font-size:14px; font-weight:800; }
    .production-detail__section-title small { margin-left:6px; color:#6b7280; font-size:12px; font-weight:700; }
    .production-detail__ingredient { display:flex; align-items:center; justify-content:space-between; gap:12px; border-top:1px solid #f3f4f6; padding:12px 0; }
    .production-detail__ingredient:first-child { border-top:0; }
    .production-detail__ingredient-name { min-width:0; }
    .production-detail__ingredient-name strong { display:block; color:#111827; font-size:13px; font-weight:700; overflow-wrap:anywhere; }
    .production-detail__ingredient-name span { color:#6b7280; font-size:11px; }
    .production-detail__ingredient-cost { flex:none; text-align:right; font-variant-numeric:tabular-nums; }
    .production-detail__ingredient-cost strong { display:block; color:#111827; font-size:13px; font-weight:700; }
    .production-detail__ingredient-cost span { color:#047857; font-size:11px; font-weight:600; }
    .production-detail__empty { padding:24px 0; color:#6b7280; text-align:center; font-size:13px; }
    .production-detail__total { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; border:1px solid #d1fae5; border-radius:16px; background:#ecfdf5; padding:16px 20px; }
    .production-detail__total span { display:block; color:#065f46; font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
    .production-detail__total strong { color:#0b595b; font-size:18px; font-weight:800; font-variant-numeric:tabular-nums; }
    .production-detail__total div:last-child { text-align:right; }
    .production-detail__total div:last-child strong { font-size:15px; }
    .production-detail__note { margin:0; color:#6b7280; font-size:11px; line-height:1.6; }
    .production-detail__actions { display:flex; flex-wrap:wrap; gap:8px; }
    .production-detail__actions .btn { min-height:40px; border-radius:12px; font-size:12px; font-weight:700; }
    @media (max-width:640px) {
        .production-detail__head, .production-detail__body { padding-right:16px; padding-left:16px; }
        .production-detail__actions { width:100%; }
        .production-detail__actions .btn { flex:1; }
    }
</style>

<div class="production-detail">
    <section class="production-detail__card" aria-labelledby="production-detail-title">
        <div class="production-detail__head">
            <div class="production-detail__title">
                <span class="production-detail__avatar" aria-hidden="true"><i class="ph ph-orange"></i></span>
                <div>
                    <h1 id="production-detail-title">Detail Produksi #{{ $data->production_number }}</h1>
                    <p>{{ dateindo($data->created_at) }} {{ ($data->created_at?->format('H:i') ?? '—') }} WIB</p>
                </div>
            </div>
            <div class="production-detail__status">
                @if(in_array($data->status, ['posting', 'complete']))
                    <span class="production-detail__badge is-done">Selesai</span>
                @elseif($data->status == 'draft')
                    <span class="production-detail__badge is-ready">Siap Produksi</span>
                @elseif($data->status == 'temp')
                    <span class="production-detail__badge is-temp">Draft Sementara</span>
                @else
                    <span class="production-detail__badge is-temp">{{ ucfirst($data->status) }}</span>
                @endif
                @if($data->pos_id)
                    <span class="production-detail__badge is-pos">POS</span>
                @endif
            </div>
        </div>

        <div class="production-detail__body">
            <dl class="production-detail__meta">
                <div><dt>Nomor Produksi</dt><dd>#{{ $data->production_number }}</dd></div>
                <div><dt>Produk yang Diproduksi</dt><dd>{{ $data->products->name ?? 'N/A' }}</dd></div>
                <div><dt>Jumlah Produksi</dt><dd>{{ tonumber($data->quantity) }} {{ $data->products->unit->abbreviation ?? 'pcs' }}</dd></div>
                <div><dt>Tanggal Produksi</dt><dd>{{ dateindo($data->created_at) }} {{ ($data->created_at?->format('H:i') ?? '—') }} WIB</dd></div>
                <div><dt>PIC / Penanggung Jawab</dt><dd>{{ $data->staff->name ?? $data->creator->nm_user ?? 'Tidak ada' }}</dd></div>
                @if($data->pos_id)
                    <div>
                        <dt>Pesanan POS</dt>
                        <dd>
                            <a href="{{ route('pos.show', $data->pos_id) }}">
                                {{ $data->pos && $data->pos->customer ? $data->pos->customer->name : 'Pelanggan Umum' }}
                            </a>
                        </dd>
                    </div>
                @endif
                @if($data->description)
                    <div class="is-note"><dt>Catatan</dt><dd>{{ $data->description }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="production-detail__body pt-0">
            <div class="production-detail__actions">
                @if(in_array($data->status, ['temp', 'draft']))
                    <a href="{{ route('production.edit', $data->id) }}" class="btn btn-light-primary"><i class="ph ph-pencil-simple me-1" aria-hidden="true"></i>Edit</a>
                @endif
                @if($data->status == 'draft')
                    <a href="{{ route('production.payment', $data->id) }}" class="btn btn-success"><i class="ph ph-check me-1" aria-hidden="true"></i>Selesaikan Produksi</a>
                @endif
                <a href="{{ route('production.print', $data->id) }}" class="btn btn-light-info"><i class="ph ph-printer me-1" aria-hidden="true"></i>Print</a>
                <a href="{{ route('production.index') }}" class="btn btn-light"><i class="ph ph-arrow-left me-1" aria-hidden="true"></i>Kembali</a>
            </div>
        </div>
    </section>

    <section class="production-detail__card" aria-labelledby="production-ingredients-title">
        <div class="production-detail__head">
            <h2 id="production-ingredients-title" class="production-detail__section-title">Bahan Baku yang Digunakan <small>({{ $production_detail->count() }} item)</small></h2>
        </div>
        <div class="production-detail__body">
            @forelse($production_detail as $item)
                <div class="production-detail__ingredient">
                    <div class="production-detail__ingredient-name">
                        <strong>{{ $item->products->name }}</strong>
                        <span>{{ $item->products->category->name ?? 'Umum' }} · HPP Rp {{ tonumberround($item->products->hpp) }}</span>
                    </div>
                    <div class="production-detail__ingredient-cost">
                        <strong>{{ tonumber($item->quantity) }} {{ $item->products->unit->abbreviation ?? 'pcs' }}</strong>
                        <span>Rp {{ tonumberround($item->products->hpp * $item->quantity) }}</span>
                    </div>
                </div>
            @empty
                <div class="production-detail__empty">Tidak ada bahan baku</div>
            @endforelse
        </div>
    </section>

    <section class="production-detail__total" aria-label="Ringkasan biaya">
        <div>
            <span>Total Biaya Produksi</span>
            <strong>Rp {{ tonumberround($total_hpp) }}</strong>
        </div>
        <div>
            <span>HPP per Unit</span>
            <strong>Rp {{ tonumberround($hpp_per_unit) }} / {{ $data->products->unit->abbreviation ?? 'pcs' }}</strong>
        </div>
    </section>
    @if($data->status == 'posting')
        <p class="production-detail__note">Total biaya dan HPP per unit memakai nilai yang dibekukan saat produksi diselesaikan. HPP bahan di daftar menunjukkan harga bahan saat ini, sehingga bisa berbeda.</p>
    @endif

    <section class="production-detail__card" aria-labelledby="production-extra-title">
        <div class="production-detail__head">
            <h2 id="production-extra-title" class="production-detail__section-title">Informasi Tambahan</h2>
        </div>
        <div class="production-detail__body">
            <dl class="production-detail__meta">
                <div><dt>Dibuat oleh</dt><dd>{{ $data->creator->nm_user ?? 'System' }}</dd></div>
                <div><dt>Tanggal dibuat</dt><dd>{{ ($data->created_at?->format('d/m/Y H:i') ?? '—') }}</dd></div>
                @if($data->updated_at != $data->created_at)
                    <div><dt>Terakhir diupdate</dt><dd>{{ ($data->updated_at?->format('d/m/Y H:i') ?? '—') }}</dd></div>
                @endif
                <div><dt>Total Bahan</dt><dd>{{ $production_detail->count() }} item</dd></div>
            </dl>
        </div>
    </section>
</div>
@endsection
