@extends('layouts.erp')
@section('title', 'Tier Pelanggan - CRM')
@section('page-title', 'Tier Pelanggan')
@section('page-breadcrumb')
    <span>CRM</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Tier Pelanggan</span>
@endsection
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Tier Pelanggan" subtitle="Kelola level dan benefit pelanggan. Pilih tier untuk melihat benefit." icon="ph-medal">
    <x-slot:toolbar><label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" placeholder="Cari tier pelanggan…" aria-label="Cari tier pelanggan"></label>
            @if (check_access('tier.create'))
            <button type="button" class="erp-action erp-action--primary" data-erp-open="#crm-dialog" data-crm-create><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Tier</span></button>
            @endif</x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="erp-table erp-crm-table" data-erp-row-details width="100%"><thead><tr><th scope="col">Nama tier</th><th scope="col">Level</th><th scope="col">Minimal EXP</th><th scope="col">Aksi</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
<x-erp.dialog id="crm-dialog" title="Tambah Tier" variant="sheet" data-erp-create data-crm-dialog>
    <form id="crm-form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}" enctype="multipart/form-data">@csrf
        <div class="erp-crm-fields">
            <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
            <div class="erp-crm-field">
                <span class="erp-label">Ikon tier</span>
                <x-erp.image-input name="avatar" remove-name="avatar_remove" label="Pilih gambar" remove-label="Hapus ikon tier" />
                <p class="erp-crm-help">PNG atau JPG untuk ikon tier.</p>
            </div>
            <div class="erp-crm-field"><label for="crm-name" class="erp-label">Nama tier</label><input id="crm-name" type="text" name="name" class="erp-input" required></div>
            <div class="erp-crm-field"><label for="crm-level" class="erp-label">Level</label><input id="crm-level" type="number" name="level" class="erp-input" inputmode="numeric" required></div>
            <div class="erp-crm-field"><label for="crm-exp" class="erp-label">Minimal EXP</label><input id="crm-exp" type="text" name="exp" class="erp-input format-number" inputmode="numeric" required></div>
            <div class="erp-crm-field">
                <label for="style" class="erp-label">Warna label</label>
                <div class="flex items-center gap-3">
                    <select id="style" name="style" class="erp-select">
                        <option value="badge-light-primary">Brand</option>
                        <option value="badge-light-secondary">Netral</option>
                        <option value="badge-light-success">Success</option>
                        <option value="badge-light-danger">Error</option>
                        <option value="badge-light-warning">Warning</option>
                        <option value="badge-light-info">Info</option>
                        <option value="badge-light">Terang</option>
                        <option value="badge-light-dark">Gelap</option>
                    </select>
                    <span id="crm-style-preview" class="erp-badge erp-badge--primary shrink-0" aria-hidden="true">Contoh</span>
                </div>
            </div>
        </div>
    </form>
    <x-slot:footer>
        <div class="ml-auto flex gap-3">
            <button type="button" id="crm-cancel" class="erp-action">Batal</button>
            <button type="submit" form="crm-form" id="crm-submit" class="erp-action erp-action--primary"><span data-label>Simpan</span><span data-progress hidden>Menyimpan…</span></button>
        </div>
    </x-slot:footer>
</x-erp.dialog>
<x-erp.dialog id="tier-benefit-dialog" title="Atur Benefit" variant="sheet" size="md">
    <div id="tier-benefit-content"></div>
</x-erp.dialog>
@endsection
@include('crm::tier.js-tier')
