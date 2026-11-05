@extends('layouts.erp')
@section('title', 'Detail Sortir Barang')
@section('page-title', 'Sortir Barang')
@section('page-breadcrumb', 'Inventaris / Detail Sortir Barang')
@section('dashboard-fullscreen', '1')

@section('content')
    @include('transaction::sortir.segment.styles')
    @php
        $sortirFinal = in_array($data->status, ['paid', 'debt']);
        $sortirEditable = check_access('sortir.edit') && (!$sortirFinal || ($data->updated_at && \Carbon\Carbon::parse($data->updated_at)->gte(now()->subMinutes(30))));
    @endphp
    <style>
        .sortir-detail-meta { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:18px 16px; }
        .sortir-detail-meta dt { margin:0 0 6px; font-size:11px; font-weight:600; color:#6b7280; }
        .sortir-detail-meta dd { margin:0; color:#1f2937; font-weight:600; font-size:13px; overflow-wrap:anywhere; }
        .sortir-detail-meta .sortir-invoice { grid-column:1/-1; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .sortir-invoice strong { font-size:15px; color:#111827; overflow-wrap:anywhere; }
        .sortir-status { display:inline-flex; padding:5px 10px; border-radius:20px; background:#f3f4f6; color:#4b5563; font-size:11px; font-weight:700; }
        .sortir-status.is-final { color:#047857; background:#ecfdf5; }
        .sortir-detail-item { padding:16px 4px; border-bottom:1px solid #f3f4f6; }
        .sortir-detail-item:last-child { border-bottom:0; }
        .sortir-detail-item .sortir-cart-line + .sortir-cart-line { margin-top:8px; }
        .sortir-detail-footer { margin:0; font-size:11px; line-height:1.6; color:#6b7280; padding-top:12px; }
        .sortir-detail-tools { display:flex; align-items:center; gap:10px; }
        .sortir-print-button { display:flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; color:#0b595b; font-size:18px!important; }
        @media print {
            @page { margin:16mm; }
            html, body, body > div, body > div > div, main, main > div { height:auto!important; min-height:0!important; overflow:visible!important; background:#fff!important; }
            body:has(.sortir-detail) header, body:has(.sortir-detail) aside, body:has(.sortir-detail) nav, .sortir-summary-actions, .sortir-print-button { display:none!important; }
            .sortir-detail, .sortir-detail .sortir-canvas { display:block!important; max-width:none; height:auto!important; overflow:visible!important; padding:0; }
            .sortir-detail .sortir-branch-card { break-inside:avoid; margin-bottom:20px; }
            .sortir-detail .sortir-items-card, .sortir-detail .sortir-items-body { display:block; overflow:visible!important; height:auto; }
            .sortir-detail .sortir-detail-item { break-inside:avoid; }
            .sortir-detail .sortir-summary { padding:20px 0 0; box-shadow:none; }
            .sortir-detail .sortir-summary-inner { max-width:none; }
            .sortir-detail .sortir-summary-total { break-inside:avoid; }
        }
    </style>
    <div class="sortir-form sortir-detail">
        <div class="sortir-canvas">
            <section class="sortir-branch-card" aria-label="Detail transaksi sortir">
                <dl class="sortir-detail-meta">
                    <div class="sortir-invoice"><strong>{{ $data->invoice_number }}</strong><span class="sortir-status {{ $sortirFinal ? 'is-final' : '' }}">{{ $sortirFinal ? 'Selesai' : 'Draf' }}</span></div>
                    <div><dt>Cabang</dt><dd>{{ $data->branch->name ?? '-' }}</dd></div>
                    <div><dt>Tanggal Transaksi</dt><dd>{{ \Carbon\Carbon::parse($data->date)->format('d M Y') }}</dd></div>
                    <div><dt>Dibuat Oleh</dt><dd>{{ $data->createdBy->nm_user ?? 'System' }}</dd></div>
                    <div><dt>Waktu Dibuat</dt><dd>{{ \Carbon\Carbon::parse($data->created_at)->format('d M Y, H:i') }}</dd></div>
                </dl>
            </section>
            <section class="sortir-items-card" aria-label="Item sortir">
                <div class="sortir-items-header"><h1>Daftar Item Sortir</h1><div class="sortir-detail-tools"><span class="sortir-count">{{ $detail->count() }} item</span><button type="button" class="sortir-print-button" onclick="window.print()" aria-label="Cetak transaksi" title="Cetak transaksi"><i class="ph ph-printer" aria-hidden="true"></i></button></div></div>
                <div class="sortir-items-body">
                    @forelse($detail as $item)
                        <article class="sortir-detail-item">
                            <div class="sortir-cart-line"><strong>{{ $item->product->name ?? 'Produk Dihapus' }}</strong><b>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</b></div>
                            <div class="sortir-cart-line"><small>Rp {{ number_format($item->price, 0, ',', '.') }} / {{ $item->product->unit->abbreviation ?? '-' }}</small><small>{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }} {{ $item->product->unit->abbreviation ?? '' }}</small></div>
                        </article>
                    @empty
                        <div class="sortir-empty"><h2>Tidak ada item sortir</h2></div>
                    @endforelse
                </div>
            </section>
        </div>
        <footer class="sortir-summary"><div class="sortir-summary-inner">
            <div class="sortir-summary-total"><div><h2>Total HPP / Modal</h2><span class="sortir-count">{{ $detail->count() }}</span></div><strong>Rp {{ number_format($data->total, 0, ',', '.') }}</strong></div>
            <div class="sortir-summary-actions"><a href="{{ route('sortir.index') }}" class="sortir-action">Kembali</a>@if($sortirEditable)<a href="{{ route('sortir.edit', $data->id) }}" class="sortir-action sortir-action-primary">{{ $sortirFinal ? 'Edit Transaksi' : 'Lanjutkan Draf' }}</a>@endif</div>
            <p class="sortir-detail-footer">{{ $sortirFinal ? 'Transaksi selesai. Stok cabang telah dikurangi sesuai kuantitas sortir.' : 'Draf belum mengurangi stok cabang. Selesaikan transaksi untuk mencatat barang yang disortir.' }}</p>
        </div></footer>
    </div>
@endsection
