@extends('layouts.erp')
@section('title', 'Campaign - CRM')
@section('page-title', 'Campaign')
@section('page-breadcrumb')
    <span>CRM</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Campaign</span>
@endsection
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Campaign" subtitle="Kelola periode dan nilai promo pelanggan." icon="ph-megaphone">
    <x-slot:toolbar><label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" placeholder="Cari campaign…" aria-label="Cari campaign"></label>
            @if (check_access('campaign.create'))
            <button type="button" class="erp-action erp-action--primary" data-erp-open="#crm-dialog" data-crm-create><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Campaign</span></button>
            @endif</x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="erp-table erp-crm-table" width="100%"><thead><tr><th scope="col">Nama event</th><th scope="col">Mulai</th><th scope="col">Selesai</th><th scope="col">Jenis promo</th><th scope="col">Nilai</th><th scope="col">Aksi</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
<x-erp.dialog id="crm-dialog" title="Tambah Campaign" variant="sheet" data-erp-create data-crm-dialog>
    <form id="crm-form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}">@csrf
        <div class="erp-crm-fields">
            <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
<div class="erp-crm-field"><label for="crm-name" class="erp-label">Nama event</label><input id="crm-name" type="text" name="name" class="erp-input" autocomplete="off" required></div><div class="erp-crm-field"><label for="crm-start_date" class="erp-label">Tanggal mulai</label><input id="crm-start_date" type="date" name="start_date" class="erp-input" value="{{ date('Y-m-d') }}" required></div><div class="erp-crm-field"><label for="crm-end_date" class="erp-label">Tanggal selesai</label><input id="crm-end_date" type="date" name="end_date" class="erp-input" value="{{ date('Y-m-d') }}" required></div><div class="erp-crm-field"><label for="type_promo" class="erp-label">Jenis promo</label><select id="type_promo" name="type_promo" class="erp-select"><option value="discount">Diskon</option><option value="price">Harga</option></select></div><div class="erp-crm-field"><label for="crm-value_promo" class="erp-label">Nilai promo</label><input id="crm-value_promo" type="text" name="value_promo" class="erp-input format-number" inputmode="numeric" required></div>
        </div>
    </form>
    <x-slot:footer>
        <div class="ml-auto flex gap-3">
            <button type="button" id="crm-cancel" class="erp-action">Batal</button>
            <button type="submit" form="crm-form" id="crm-submit" class="erp-action erp-action--primary"><span data-label>Simpan</span><span data-progress hidden>Menyimpan…</span></button>
        </div>
    </x-slot:footer>
</x-erp.dialog>
@endsection
@include('crm::campaign.js-campaign')
