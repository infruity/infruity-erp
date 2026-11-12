@extends('template.root')
@section('title', 'Deposito - CRM')
@section('page-title', 'Deposito')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace count-table="transaction-table" count-noun="deposito" title="Deposito" subtitle="Kelola deposito pelanggan dan tier yang digunakan." icon="ph-wallet">
    <x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-kt-ecommerce-product-filter="search" placeholder="Cari deposito…" aria-label="Cari deposito"></div>
            @if (check_access('deposito.create'))
            <button type="button" class="erp-action erp-action--primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_customer" data-crm-create><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Deposito</span></button>
            @endif</x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="table erp-crm-table" width="100%"><thead><tr><th scope="col">Pelanggan</th><th scope="col">Tanggal</th><th scope="col">Deposito</th><th scope="col">Aksi</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
<div class="modal fade erp-crm-drawer" id="kt_modal_add_customer" tabindex="-1" aria-labelledby="crm-form-title" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog"><div class="modal-content">
        <form id="kt_modal_add_customer_form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}" data-kt-redirect="#">@csrf
            <div id="kt_modal_add_customer_header" class="erp-sheet-header">
                <div><h2 id="crm-form-title">Tambah Deposito</h2><p>Pilih pelanggan dan tier deposito.</p></div>
                <button type="button" id="kt_modal_add_customer_close" class="erp-action erp-crm-close" aria-label="Tutup formulir"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="erp-sheet-body"><div class="erp-crm-fields">
                <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
<div class="erp-crm-field"><label for="crm-date">Tanggal</label><input id="crm-date" type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required></div><div class="erp-crm-field"><label for="customer_id">Pelanggan</label><select id="customer_id" name="customer_id" class="form-select" required><option value="">Pilih pelanggan</option></select></div><div class="erp-crm-field"><label for="tier_id">Tier deposito</label><select id="tier_id" name="tier_id" class="form-select" required><option value="">Pilih tier</option></select></div><div class="erp-crm-field"><label for="crm-deposito">Nominal deposito</label><input id="crm-deposito" type="text" name="deposito" class="form-control format-number" inputmode="numeric" readonly><p class="erp-crm-help">Nominal mengikuti tier deposito yang dipilih.</p></div>
            </div></div>
            <div class="erp-sheet-footer">
                <button type="button" id="kt_modal_add_customer_cancel" class="erp-action">Batal</button>
                <button type="submit" id="kt_modal_add_customer_submit" class="erp-action erp-action--primary"><span class="indicator-label">Simpan</span><span class="indicator-progress">Menyimpan… <span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span></button>
            </div>
        </form>
    </div></div>
</div>
@endsection
@include('crm::deposito.js-deposito')
