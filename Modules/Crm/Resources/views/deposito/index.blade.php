@extends('layouts.erp')
@section('title', 'Deposito - CRM')
@section('page-title', 'Deposito')
@section('page-breadcrumb')
    <span>CRM</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Deposito</span>
@endsection
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Deposito" subtitle="Kelola deposito pelanggan dan tier yang digunakan." icon="ph-wallet">
    <x-slot:toolbar><label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" placeholder="Cari deposito…" aria-label="Cari deposito"></label>
            @if (check_access('deposito.create'))
            <x-erp.button variant="primary" icon="plus" bold data-erp-open="#crm-dialog" data-crm-create><span>Tambah Deposito</span></x-erp.button>
            @endif</x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="erp-table erp-crm-table" width="100%"><thead><tr><th scope="col">Pelanggan</th><th scope="col">Tanggal</th><th scope="col">Deposito</th><th scope="col">Aksi</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
<x-erp.dialog id="crm-dialog" title="Tambah Deposito" variant="sheet" data-erp-create data-crm-dialog>
    <form id="crm-form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}">@csrf
        <div class="erp-crm-fields">
            <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
<div class="erp-crm-field"><label for="crm-date" class="erp-label">Tanggal</label><input id="crm-date" type="date" name="date" class="erp-input" value="{{ date('Y-m-d') }}" required></div><div class="erp-crm-field"><label for="customer_id" class="erp-label">Pelanggan</label><select id="customer_id" name="customer_id" class="erp-select"><option value="">Pilih pelanggan</option></select></div><div class="erp-crm-field"><label for="tier_id" class="erp-label">Tier deposito</label><select id="tier_id" name="tier_id" class="erp-select"><option value="">Pilih tier</option></select></div><div class="erp-crm-field"><label for="crm-deposito" class="erp-label">Nominal deposito</label><input id="crm-deposito" type="text" name="deposito" class="erp-input format-number" inputmode="numeric" readonly><p class="erp-crm-help">Nominal mengikuti tier deposito yang dipilih.</p></div>
        </div>
    </form>
    <x-slot:footer>
        <div class="ml-auto flex gap-3">
            <x-erp.button id="crm-cancel">Batal</x-erp.button>
            <x-erp.button variant="primary" type="submit" form="crm-form" id="crm-submit"><span data-label>Simpan</span><span data-progress hidden>Menyimpan…</span></x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>
@endsection
@include('crm::deposito.js-deposito')
