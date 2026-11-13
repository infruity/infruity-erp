{{-- The production detail popup of the list, as in the HTML reference: product, number + kitchen, grand total, who and when, then Edit / Hapus. --}}
@php
    $unit = $data->products->unit->abbreviation ?? 'porsi';
    $staffName = $data->staff->name ?? $data->creator->nm_user ?? 'Petugas';
    $madeAt = $data->created_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '—';
@endphp
<div class="production-detail production-detail--ref">
    <h1 id="production-detail-title" class="production-detail-ref__title">Detail Produksi</h1>
    <div class="production-detail-ref__rows">
        <div class="production-detail-ref__row">
            <span class="production-detail-ref__icon production-detail-ref__icon--product {{ $icon_class }}" aria-hidden="true">{{ $emoji }}</span>
            <div class="production-detail-ref__text">
                <div class="production-detail-ref__main is-strong">{{ $data->products->name ?? 'Produksi Barang' }}</div>
                <div class="production-detail-ref__sub is-target">Target: {{ tonumber($data->quantity) }} {{ $unit }}</div>
            </div>
        </div>
        <div class="production-detail-ref__row">
            <span class="production-detail-ref__icon" aria-hidden="true"><i class="ph-duotone ph-receipt"></i></span>
            <div class="production-detail-ref__text">
                <div class="production-detail-ref__main">{{ $data->production_number }}</div>
                <div class="production-detail-ref__sub">{{ $data->branch->name ?? '—' }}</div>
            </div>
        </div>
        <div class="production-detail-ref__row">
            <span class="production-detail-ref__icon" aria-hidden="true"><i class="ph-duotone ph-coins"></i></span>
            <div class="production-detail-ref__text">
                <div class="production-detail-ref__main">Rp {{ tonumberround($total_hpp) }}</div>
                <div class="production-detail-ref__sub">Grand Total ({{ $production_detail->count() }} Bahan Baku)</div>
            </div>
        </div>
        <div class="production-detail-ref__row">
            <span class="production-detail-ref__icon" aria-hidden="true"><i class="ph-duotone ph-clock"></i></span>
            <div class="production-detail-ref__text">
                <div class="production-detail-ref__main is-small">{{ $staffName }}</div>
                <div class="production-detail-ref__sub">Dibuat pada {{ $madeAt }}</div>
            </div>
        </div>
    </div>
    <div class="production-detail-ref__actions">
            <a href="{{ route('production.edit', $data->id) }}" class="production-detail-ref__action is-edit"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i>Edit</a>
            <span class="production-detail-ref__divider" aria-hidden="true"></span>
            <a href="{{ route('production.detail', $data->id) }}" class="production-detail-ref__action is-delete production-detail-ref__delete"><i class="ph-bold ph-trash" aria-hidden="true"></i>Hapus</a>
    </div>
</div>
