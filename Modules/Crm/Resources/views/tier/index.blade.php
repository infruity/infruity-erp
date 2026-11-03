@extends('template.root')
@section('title', 'Tier Pelanggan - CRM')
@section('page-title', 'Tier Pelanggan')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Tier Pelanggan" subtitle="Kelola level dan benefit pelanggan. Pilih tier untuk melihat benefit." icon="ph-medal">
    <x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-kt-ecommerce-product-filter="search" placeholder="Cari tier pelanggan…" aria-label="Cari tier pelanggan"></div>
            @if (check_access('tier.create'))
            <button type="button" class="erp-action erp-action--primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_customer" data-crm-create><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Tier</span></button>
            @endif</x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="table erp-crm-table" data-erp-row-details width="100%"><thead><tr><th scope="col">Nama tier</th><th scope="col">Level</th><th scope="col">Minimal EXP</th><th scope="col">Aksi</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
<div class="modal fade erp-crm-drawer" id="kt_modal_add_customer" tabindex="-1" aria-labelledby="crm-form-title" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog"><div class="modal-content">
        <form id="kt_modal_add_customer_form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}" data-kt-redirect="#">@csrf
            <div id="kt_modal_add_customer_header" class="erp-sheet-header">
                <div><h2 id="crm-form-title">Tambah Tier</h2><p>Isi identitas dan level tier pelanggan.</p></div>
                <button type="button" id="kt_modal_add_customer_close" class="erp-action erp-crm-close" aria-label="Tutup formulir"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="erp-sheet-body"><div class="erp-crm-fields">
                <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
<div class="erp-crm-field"><label>Ikon tier</label><div class="erp-crm-upload image-input image-input-empty image-input-outline" data-kt-image-input="true"><div class="image-input-wrapper" style="background-image:url('{{ asset('assets/media/svg/files/blank-image.svg') }}')"></div><label class="erp-action" data-kt-image-input-action="change"><i class="ph ph-upload-simple" aria-hidden="true"></i> Pilih gambar<input type="file" name="avatar" accept=".png,.jpg,.jpeg"><input type="hidden" name="avatar_remove"></label><button type="button" class="erp-action" data-kt-image-input-action="remove" aria-label="Hapus ikon tier"><i class="ph ph-trash" aria-hidden="true"></i></button><span class="erp-action" data-kt-image-input-action="cancel" role="button" tabindex="0">Batal</span></div><p class="erp-crm-help">PNG atau JPG untuk ikon tier.</p></div><div class="erp-crm-field"><label for="crm-name">Nama tier</label><input id="crm-name" type="text" name="name" class="form-control" required></div><div class="erp-crm-field"><label for="crm-level">Level</label><input id="crm-level" type="number" name="level" class="form-control" inputmode="numeric" required></div><div class="erp-crm-field"><label for="crm-exp">Minimal EXP</label><input id="crm-exp" type="text" name="exp" class="form-control format-number" inputmode="numeric" required></div><div class="erp-crm-field"><label for="style">Warna label</label><select id="style" name="style" class="form-select" ><option value="badge-light-primary" data-badge="badge-light-primary">Brand</option><option value="badge-light-secondary" data-badge="badge-light-secondary">Netral</option><option value="badge-light-success" data-badge="badge-light-success">Success</option><option value="badge-light-danger" data-badge="badge-light-danger">Error</option><option value="badge-light-warning" data-badge="badge-light-warning">Warning</option><option value="badge-light-info" data-badge="badge-light-info">Info</option><option value="badge-light" data-badge="badge-light">Terang</option><option value="badge-light-dark" data-badge="badge-light-dark">Gelap</option></select></div>
            </div></div>
            <div class="erp-sheet-footer">
                <button type="button" id="kt_modal_add_customer_cancel" class="erp-action">Batal</button>
                <button type="submit" id="kt_modal_add_customer_submit" class="erp-action erp-action--primary"><span class="indicator-label">Simpan</span><span class="indicator-progress">Menyimpan… <span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span></button>
            </div>
        </form>
    </div></div>
</div>
<div class="modal fade erp-crm-drawer" id="tier-benefit-modal" tabindex="-1" aria-labelledby="tier-benefit-title" aria-hidden="true"><div class="modal-dialog erp-sheet-dialog"><div class="modal-content"><div id="tier-benefit-content"></div></div></div></div>
@endsection
@include('crm::tier.js-tier')
