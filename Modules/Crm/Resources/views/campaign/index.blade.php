@extends('template.root')
@section('title', 'Campaign - CRM')
@section('page-title', 'Campaign')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace count-table="transaction-table" count-noun="campaign" title="Campaign" subtitle="Kelola periode dan nilai promo pelanggan." icon="ph-megaphone">
    <x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-kt-ecommerce-product-filter="search" placeholder="Cari campaign…" aria-label="Cari campaign"></div>
            @if (check_access('campaign.create'))
            <button type="button" class="erp-action erp-action--primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_customer" data-crm-create><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Campaign</span></button>
            @endif</x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="table erp-crm-table" width="100%"><thead><tr><th scope="col">Nama event</th><th scope="col">Mulai</th><th scope="col">Selesai</th><th scope="col">Jenis promo</th><th scope="col">Nilai</th><th scope="col">Aksi</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
<div class="modal fade erp-crm-drawer" id="kt_modal_add_customer" tabindex="-1" aria-labelledby="crm-form-title" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog"><div class="modal-content">
        <form id="kt_modal_add_customer_form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}" data-kt-redirect="#">@csrf
            <div id="kt_modal_add_customer_header" class="erp-sheet-header">
                <div><h2 id="crm-form-title">Tambah Campaign</h2><p>Isi detail event dan periode promo.</p></div>
                <button type="button" id="kt_modal_add_customer_close" class="erp-action erp-crm-close" aria-label="Tutup formulir"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="erp-sheet-body"><div class="erp-crm-fields">
                <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
<div class="erp-crm-field"><label for="crm-name">Nama event</label><input id="crm-name" type="text" name="name" class="form-control" autocomplete="off" required></div><div class="erp-crm-field"><label for="crm-start_date">Tanggal mulai</label><input id="crm-start_date" type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="erp-crm-field"><label for="crm-end_date">Tanggal selesai</label><input id="crm-end_date" type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="erp-crm-field"><label for="type_promo">Jenis promo</label><select id="type_promo" name="type_promo" class="form-select" data-control="select2" data-hide-search="true"><option value="discount">Diskon</option><option value="price">Harga</option></select></div><div class="erp-crm-field"><label for="crm-value_promo">Nilai promo</label><input id="crm-value_promo" type="text" name="value_promo" class="form-control format-number" inputmode="numeric" required></div>
            </div></div>
            <div class="erp-sheet-footer">
                <button type="button" id="kt_modal_add_customer_cancel" class="erp-action">Batal</button>
                <button type="submit" id="kt_modal_add_customer_submit" class="erp-action erp-action--primary"><span class="indicator-label">Simpan</span><span class="indicator-progress">Menyimpan… <span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span></button>
            </div>
        </form>
    </div></div>
</div>
@endsection
@include('crm::campaign.js-campaign')
