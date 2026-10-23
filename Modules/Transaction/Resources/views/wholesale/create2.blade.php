@extends('layouts.erp-tailwind')

@php
    $isWholesaleReadOnly = request()->routeIs('wholesale.show', 'wholesale-show');
    $isWholesaleEdit = request()->routeIs('wholesale.edit');
    $isWholesaleCreate = request()->routeIs('wholesale.create');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
@endpush

@section('title', 'Pengadaan - Pembelian')
@section('page-title', request()->routeIs('wholesale.edit') ? 'Edit Pengadaan' : (request()->routeIs('wholesale.show', 'wholesale-show') ? 'Detail Pengadaan' : 'Tambah Pengadaan'))
@section('page-breadcrumb')
    <span>Pembelian</span><i class="ph ph-caret-right text-[0.55rem]"></i><a href="{{ route('wholesale.index') }}">Pengadaan</a>
@endsection

@section('content')
    <style>
        .wholesale-procurement {
            max-width: 960px;
            margin: 0 auto;
            padding: 20px 12px 28px;
        }
        .wholesale-form-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin:0 0 16px; }
        .wholesale-form-heading h1 { margin:0; color:#172033; font-size:20px; font-weight:700; letter-spacing:-.03em; }
        .wholesale-form-heading p { margin:5px 0 0; color:#8a94a6; font-size:12px; }
        .wholesale-form-back { display:inline-flex; align-items:center; gap:7px; border:1px solid #e5e9ef; border-radius:999px; padding:8px 13px; background:#fff; color:#596579; font-size:12px; font-weight:700; text-decoration:none; }
        .wholesale-procurement .card { overflow:hidden; border:1px solid #e9edf1; border-radius:18px; background:#fff; box-shadow:0 6px 18px rgba(17,24,39,.035); }
        .wholesale-procurement .card-body { padding:18px; }
        .wholesale-procurement .row { --bs-gutter-x: 14px; }
        .wholesale-procurement .form-label { margin-bottom:7px; color:#384253; font-size:12px; font-weight:700; }
        .wholesale-procurement .form-control,
        .wholesale-procurement .select2-container--default .select2-selection--single { min-height:44px; border:1px solid #e1e6eb; border-radius:12px; background:#fafbfc; box-shadow:none; color:#273244; font-size:13px; }
        .wholesale-procurement .form-control { padding:10px 13px; }
        .wholesale-procurement .form-control:focus { border-color:#59b79b; background:#fff; box-shadow:0 0 0 3px rgba(16,145,106,.1); }
        .wholesale-procurement .select2-container { width:100% !important; }
        .wholesale-procurement .select2-container--default .select2-selection--single { display:flex; align-items:center; }
        .wholesale-procurement .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left:13px; color:#273244; font-size:13px; }
        .wholesale-procurement .select2-container--default .select2-selection--single .select2-selection__arrow { top:0; right:8px; display:flex; height:44px; align-items:center; justify-content:center; }
        .wholesale-procurement .select2-container--default .select2-selection--single .select2-selection__arrow b { position:static; width:8px; height:8px; margin:-4px 0 0 !important; transform:rotate(45deg); border:0 !important; border-right:1.5px solid #8792a2 !important; border-bottom:1.5px solid #8792a2 !important; }
        .wholesale-products-card { margin-bottom:14px; }
        .wholesale-products-card .wholesale-products-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .wholesale-products-heading h2 { margin:0; color:#172033; font-size:15px; font-weight:800; letter-spacing:-.02em; }
        .wholesale-products-heading p { margin:4px 0 0; color:#929bab; font-size:11px; }
        .wholesale-add-item { display:inline-flex; min-height:36px; align-items:center; justify-content:center; gap:6px; border:1px solid #bcebd7 !important; border-radius:10px !important; padding:7px 12px !important; background:#ecfdf5 !important; color:#0b595b !important; font-size:11px !important; font-weight:700 !important; white-space:nowrap; }
        .wholesale-cart-list { max-height:42vh; overflow-y:auto; overscroll-behavior:contain; }
        .wholesale-cart-empty { display:flex; min-height:180px; flex-direction:column; align-items:center; justify-content:center; gap:7px; padding:20px 12px; color:#8a94a6; text-align:center; }
        .wholesale-cart-empty-icon { display:flex; width:42px; height:42px; align-items:center; justify-content:center; margin-bottom:4px; border:1px solid #d7f1e6; border-radius:15px; background:#f0fbf6; color:#0b595b; font-size:21px; }
        .wholesale-cart-empty strong { color:#384253; font-size:12px; }
        .wholesale-cart-empty > span:last-child { font-size:11px; }
        .wholesale-cart-item { display:flex; width:100%; align-items:center; justify-content:space-between; gap:12px; border:0; border-bottom:1px solid #eef1f2; padding:12px 3px; background:transparent; color:inherit; text-align:left; cursor:pointer; }
        .wholesale-cart-item:disabled { cursor:default; opacity:1; }
        .wholesale-cart-item:disabled:hover { background:transparent; }
        .wholesale-cart-item:last-child { border-bottom:0; }
        .wholesale-cart-item:hover { background:#fbfdfc; }
        .wholesale-cart-item.is-gift { border-radius:10px; background:#f0fdf4; }
        .wholesale-cart-item-copy { display:flex; min-width:0; flex-direction:column; gap:3px; }
        .wholesale-cart-item-name { overflow:hidden; color:#172033; font-size:13px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .wholesale-cart-item-meta { color:#697386; font-size:11px; }
        .wholesale-cart-item-quantity { display:flex; align-items:center; gap:5px; color:#8a94a6; font-size:10px; }
        .wholesale-cart-item-discount { margin-left:5px; color:#a16207; }
        .wholesale-cart-item-total { display:flex; flex:none; align-items:center; gap:8px; color:#0b595b; font-size:12px; font-variant-numeric:tabular-nums; }
        .wholesale-cart-item-total strong { font-weight:800; }
        .wholesale-cart-item-total i { color:#b0b8c2; font-size:13px; }
        .wholesale-procurement .wholesale-summary { position:sticky; bottom:0; z-index:15; margin:-1px -18px -18px; border-top:1px solid #eef0f3; background:rgba(255,255,255,.97); padding:14px 18px calc(12px + env(safe-area-inset-bottom)); box-shadow:0 -8px 22px rgba(18,32,43,.05); backdrop-filter:blur(14px); }
        .wholesale-procurement .wholesale-summary-total { display:flex; align-items:center; justify-content:space-between; gap:12px; color:#172033; font-size:14px; font-weight:800; }
        .wholesale-procurement .wholesale-summary-total strong { color:#0b595b; font-size:15px; font-variant-numeric:tabular-nums; }
        .wholesale-procurement .wholesale-summary .row { margin-top:13px !important; }
        .wholesale-procurement .wholesale-summary .btn { min-height:44px; border-radius:12px; font-size:12px; font-weight:700; }
        .wholesale-procurement .wholesale-summary .btn-primary { border:1px solid #dce2e8 !important; background:#fff !important; box-shadow:none !important; color:#273244 !important; }
        .wholesale-procurement .wholesale-summary .btn-success { border:1px solid #0b595b !important; background:#0b595b !important; box-shadow:0 5px 12px rgba(11,89,91,.16) !important; color:#fff !important; }
        .wholesale-procurement.is-edit-mode .wholesale-products-card { min-height:0; }
        .wholesale-procurement.is-edit-mode .wholesale-cart-list { min-height:0; }
        .wholesale-procurement.is-edit-mode .wholesale-cart-item-total { flex-direction:column; align-items:flex-end; gap:5px; }
        .wholesale-edit-item-quantity { color:#465264; font-size:12px; }
        .wholesale-procurement .modal-content { overflow:hidden; border:1px solid #e9edf1; border-radius:20px; }
        .wholesale-procurement .modal-header { border:0; background:#0b595b !important; padding:16px 20px; }
        .wholesale-procurement .modal-title { color:#fff !important; font-size:15px; font-weight:700; }
        .wholesale-procurement .modal-body { padding:20px; }
        .wholesale-procurement .modal-footer { gap:8px; border-top:1px solid #eef0f3; padding:14px 20px; }
        .wholesale-procurement .modal-footer .btn { min-height:40px; border-radius:11px; font-size:12px; font-weight:700; }
        .wholesale-detail-status { display:inline-flex; min-height:38px; align-items:center; border-radius:12px; padding:0 13px; font-size:13px; font-weight:700; }
        .wholesale-status-muted { background:#f3f4f6; color:#4b5563; }
        .wholesale-status-pending { background:#fffbeb; color:#92400e; }
        .wholesale-status-shipping { background:#eff6ff; color:#1d4ed8; }
        .wholesale-status-complete { background:#ecfdf5; color:#047857; }
        .wholesale-form-print { display:inline-flex; min-height:36px; align-items:center; justify-content:center; gap:7px; border:1px solid #d9e8e2; border-radius:999px; padding:7px 13px; background:#effaf4; color:#0b595b; font-size:12px; font-weight:700; }
        .wholesale-form-print:hover { background:#dff4e9; }

        @media print {
            html, body, body .overflow-hidden, body .overflow-y-auto { height:auto !important; min-height:0 !important; overflow:visible !important; background:#fff !important; }
            body * { visibility:hidden !important; }
            #printable-po, #printable-po * { visibility:visible !important; }
            #printable-po { position:static !important; width:100% !important; max-width:none !important; margin:0 !important; padding:0 !important; }
            #printable-po .wholesale-no-print { display:none !important; }
            #printable-po .card { border-color:#d1d5db !important; box-shadow:none !important; break-inside:avoid; }
            #printable-po .wholesale-cart-list { max-height:none !important; overflow:visible !important; }
        }

        @media (max-width: 767.98px) {
            .wholesale-procurement { max-width:none; margin:0 -15px; padding:14px 16px 24px; }
            .wholesale-form-heading { margin-bottom:13px; }
            .wholesale-form-heading h1 { font-size:18px; }
            .wholesale-form-back { width:36px; height:36px; justify-content:center; padding:0; flex:none; }
            .wholesale-form-back span { display:none; }
            .wholesale-procurement .card { border-radius:17px; }
            .wholesale-procurement .card-body { padding:15px; }
            .wholesale-procurement .row > [class*="col-"] { margin-bottom:12px !important; }
            .wholesale-procurement .row > [class*="col-"]:last-child { margin-bottom:0 !important; }
            .wholesale-products-card .wholesale-products-heading { align-items:center; margin-bottom:10px; }
            .wholesale-products-heading h2 { font-size:14px; }
            .wholesale-add-item { min-height:34px; padding:6px 10px !important; }
            .wholesale-cart-list { max-height:42dvh; }
            .wholesale-cart-item { gap:8px; padding:11px 2px; }
            .wholesale-cart-item-name { font-size:12px; }
            .wholesale-cart-item-meta { font-size:10px; }
            .wholesale-cart-item-quantity { flex-wrap:wrap; font-size:9px; }
            .wholesale-cart-item-total { gap:5px; font-size:11px; }
            .wholesale-procurement .wholesale-summary { margin:-1px -15px -15px; padding:13px 15px calc(12px + env(safe-area-inset-bottom)); }
            .wholesale-procurement .wholesale-summary-total { font-size:13px; }
            .wholesale-procurement .wholesale-summary-total strong { font-size:14px; }
            .wholesale-procurement .wholesale-summary .row { --bs-gutter-x: 9px; }
            .wholesale-procurement .modal-dialog { margin:0; }
            .wholesale-procurement .modal-content { min-height:100dvh; border-radius:0; }
            body:has(.wholesale-procurement.is-edit-mode) main { overflow:hidden !important; padding:0 !important; }
            body:has(.wholesale-procurement.is-edit-mode) main > .animate-fade-in-up { min-height:0; }
            body:has(.wholesale-procurement.is-edit-mode) .erp-topbar-mobile { height:66px !important; }
            .wholesale-procurement.is-edit-mode { display:flex; width:calc(100% - 8px) !important; min-height:0; flex:1; flex-direction:column; margin:0 auto; padding:17px 0 0; }
            .wholesale-procurement.is-edit-mode .wholesale-form-heading { display:none; }
            .wholesale-procurement.is-edit-mode > .card { flex:none; margin:0 0 27px !important; padding:15px 17px 13px 13px !important; }
            .wholesale-procurement.is-edit-mode > .card .form-label { line-height:16px; }
            .wholesale-procurement.is-edit-mode > .card .row > [class*="col-"] { margin-bottom:0 !important; }
            .wholesale-procurement.is-edit-mode .wholesale-edit-meta-field { display:none; }
            .wholesale-procurement.is-edit-mode .wholesale-edit-content { display:flex; min-height:0; flex:1; flex-direction:column; padding-bottom:124px; }
            .wholesale-procurement.is-edit-mode .wholesale-products-heading p { display:none; }
            .wholesale-procurement.is-edit-mode .wholesale-products-card { display:flex; min-height:0; flex:1; flex-direction:column; margin-bottom:24px !important; padding:0 !important; }
            .wholesale-procurement.is-edit-mode .wholesale-products-heading { flex:none; margin:0 !important; border-bottom:1px solid #eef1f3; padding:16px 23px 16px 17px; }
            .wholesale-procurement.is-edit-mode .wholesale-add-item { min-height:29px; padding:5px 11px !important; }
            .wholesale-procurement.is-edit-mode .wholesale-cart-list { max-height:none; flex:1; overflow-y:auto; padding:11px 18px 10px 13px; }
            .wholesale-procurement.is-edit-mode .wholesale-summary-card { position:fixed; right:0; bottom:0; left:0; z-index:40; margin:0 !important; border:0; border-top:1px solid #edf0f2; border-radius:0; padding:4px 0 0 !important; background:#fff; box-shadow:0 -8px 28px rgba(20,31,46,.08); }
            .wholesale-procurement.is-edit-mode .wholesale-summary { position:static; margin:0 !important; border:0; padding:14px 18px calc(12px + env(safe-area-inset-bottom)) 16px; background:#fff; box-shadow:none; backdrop-filter:none; }
            .wholesale-procurement.is-edit-mode .wholesale-summary-total { padding-bottom:14px; border-bottom:1px solid #eef1f3; font-size:16px; }
            .wholesale-procurement.is-edit-mode .wholesale-summary-total strong { font-size:16px; }
            .wholesale-procurement.is-edit-mode .wholesale-summary .row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px !important; margin:13px 0 0 -3px !important; }
            .wholesale-procurement.is-edit-mode .wholesale-summary .row > [class*="col-"] { width:auto; margin:0 !important; padding:0; }
            .wholesale-procurement.is-edit-mode .wholesale-summary .row > .col { width:auto; margin:0 !important; padding:0 !important; }
            .wholesale-procurement.is-edit-mode .wholesale-summary .btn { min-height:44px; padding:8px 10px; font-size:12px; }
            .wholesale-procurement.is-edit-mode .wholesale-summary .btn-primary { border:1px solid #dce2e8 !important; background:#fff !important; box-shadow:none !important; color:#273244 !important; }
            .wholesale-procurement.is-edit-mode .wholesale-summary .btn i { display:none; }
            .wholesale-procurement.is-edit-mode .wholesale-cart-item { align-items:flex-start; padding:13px 2px; }
            .wholesale-procurement.is-edit-mode .wholesale-cart-item-name { font-size:14px; }
            .wholesale-procurement.is-edit-mode .wholesale-cart-item-meta { font-size:12px; }
            .wholesale-procurement.is-edit-mode .wholesale-cart-item-total { min-width:96px; font-size:13px; }
            .wholesale-procurement.is-edit-mode .wholesale-edit-item-quantity { white-space:nowrap; }
            .wholesale-procurement.is-edit-mode .select2-container--default .select2-selection--single .select2-selection__rendered { font-size:14px; }
        }
        /* Select2 appends its menu to body; style it independently of the form wrapper. */
        .select2-dropdown.wholesale-branch-dropdown { overflow:hidden; margin-top:6px; border:1px solid #f3f4f6; border-radius:12px; background:#fff; color:#374151; box-shadow:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -4px rgba(0,0,0,.1); }
        .select2-dropdown.wholesale-branch-dropdown.select2-dropdown--above { margin-top:-6px; }
        .select2-dropdown.wholesale-branch-dropdown .select2-search--dropdown { display:none; }
        .select2-dropdown.wholesale-branch-dropdown .select2-results { display:block; background:#fff; }
        .select2-dropdown.wholesale-branch-dropdown .select2-results__options { max-height:224px; overflow-y:auto; margin:0; padding:4px 0; overscroll-behavior:contain; }
        .select2-dropdown.wholesale-branch-dropdown .select2-results__option { position:relative; display:flex; min-height:40px; align-items:center; border-radius:0; padding:10px 36px 10px 14px; background:#fff; color:#374151; font-size:13px; line-height:20px; font-weight:500; }
        .select2-dropdown.wholesale-branch-dropdown .select2-results__option--highlighted { background:#f9fafb; color:#374151; }
        .select2-dropdown.wholesale-branch-dropdown .select2-results__option[aria-selected="true"],
        .select2-dropdown.wholesale-branch-dropdown .select2-results__option--selected { background:#f9fafb; color:#059669; font-weight:600; }
        .select2-dropdown.wholesale-branch-dropdown .select2-results__option[aria-selected="true"]::after,
        .select2-dropdown.wholesale-branch-dropdown .select2-results__option--selected::after { content:""; position:absolute; right:14px; top:50%; width:6px; height:6px; border-radius:50%; background:#10b981; -webkit-mask:none; mask:none; transform:translateY(-50%); }
        .wholesale-procurement #branch_id + .select2-container--open .select2-selection--single { border-color:#10b981; box-shadow:0 0 0 1px #10b981; }
        .wholesale-procurement.is-create-mode #branch_id + .select2-container .select2-selection--single { position:relative; background-image:none; }
        .wholesale-procurement.is-create-mode #branch_id + .select2-container .select2-selection__arrow { display:none; }
        .wholesale-procurement.is-create-mode #branch_id + .select2-container.has-branch-choices .select2-selection__rendered { padding-right:36px; }
        .wholesale-procurement.is-create-mode #branch_id + .select2-container.has-branch-choices .select2-selection--single::after { content:""; position:absolute; top:50%; right:18px; width:8px; height:8px; margin-top:-6px; border-right:2px solid #9ca3af; border-bottom:2px solid #9ca3af; transform:rotate(45deg); pointer-events:none; }
        .wholesale-procurement.is-create-mode #branch_id + .select2-container.has-branch-choices.select2-container--open .select2-selection--single::after { margin-top:-2px; transform:rotate(225deg); }
        /* Match the HTML procurement form while retaining the existing transaction inputs. */
        .wholesale-procurement.is-create-mode .wholesale-form-heading { display:none; }
        .wholesale-procurement.is-create-mode .wholesale-edit-meta-field { display:none; }
        .wholesale-procurement.is-create-mode .wholesale-products-heading p { display:none; }
        .wholesale-procurement.is-create-mode .wholesale-products-card { padding:0; }
        .wholesale-procurement.is-create-mode .wholesale-products-heading { margin:0; border-bottom:1px solid #f3f4f6; padding:16px 20px; }
        .wholesale-procurement.is-create-mode .wholesale-products-heading h2 { color:#111827; font-size:14px; }
        .wholesale-procurement.is-create-mode .wholesale-add-item { min-height:30px; padding:6px 14px !important; font-size:12px !important; border-radius:8px !important; }
        .wholesale-procurement.is-create-mode .wholesale-cart-list { padding:16px; }
        .wholesale-procurement.is-create-mode .wholesale-cart-empty { min-height:260px; gap:8px; }
        .wholesale-procurement.is-create-mode .wholesale-cart-empty strong { color:#1f2937; font-size:14px; font-weight:700; }
        .wholesale-procurement.is-create-mode .wholesale-cart-empty > span:last-child { color:#6b7280; font-size:12px; }
        .wholesale-empty-add { border:0; padding:0; background:transparent; color:#0b595b; font:inherit; font-weight:600; cursor:pointer; }
        .wholesale-empty-add:hover { text-decoration:underline; }
        .wholesale-procurement.is-create-mode .wholesale-summary .btn i { display:none; }
        .wholesale-procurement.is-create-mode :is(button, a):focus-visible { outline:2px solid #0b595b; outline-offset:3px; }
        .wholesale-procurement.is-create-mode .btn:disabled { cursor:wait; opacity:.65; }
        .wholesale-procurement.is-create-mode .wholesale-cart-item { align-items:flex-start; padding:12px 0; }
        .wholesale-procurement.is-create-mode .wholesale-cart-item-total { flex-direction:column; align-items:flex-end; gap:5px; }
        .wholesale-procurement.is-create-mode .wholesale-cart-item-name { font-size:14px; }
        .wholesale-procurement.is-create-mode .wholesale-cart-item-meta { font-size:12px; }
        .wholesale-procurement.is-create-mode .wholesale-cart-item-total { font-size:13px; }
        @media (min-width:768px) {
            .wholesale-procurement.is-create-mode { display:flex; min-height:560px; flex:1; flex-direction:column; padding-top:20px; padding-bottom:0; }
            .wholesale-procurement.is-create-mode > .card { flex:none; }
            .wholesale-procurement.is-create-mode .wholesale-edit-content { display:flex; min-height:0; flex:1; flex-direction:column; }
            .wholesale-procurement.is-create-mode .wholesale-products-card { display:flex; min-height:240px; flex:1; flex-direction:column; }
            .wholesale-procurement.is-create-mode .wholesale-cart-list { max-height:none; min-height:0; flex:1; }
            .wholesale-procurement.is-create-mode .wholesale-cart-empty { height:100%; min-height:140px; }
            .wholesale-procurement.is-create-mode .wholesale-summary-card { flex:none; padding:0; border-radius:0 0 16px 16px; }
            .wholesale-procurement.is-create-mode .wholesale-summary { position:static; margin:0; border:0; padding:16px 18px; background:#fff; box-shadow:none; backdrop-filter:none; }
        }
        @media (max-width:767.98px) {
            body:has(.wholesale-procurement.is-create-mode) main { overflow:hidden; padding:0 !important; }
            body:has(.wholesale-procurement.is-create-mode) main > .animate-fade-in-up { min-height:0; padding:0 !important; animation:none; }
            .wholesale-procurement.is-create-mode { display:flex; box-sizing:border-box; width:100% !important; max-width:none; min-height:0; flex:1; flex-direction:column; margin:0; padding:17px 16px 0; }
            .wholesale-procurement.is-create-mode .wholesale-form-heading { display:none; }
            .wholesale-procurement.is-create-mode > .card { flex:none; margin:0 0 24px !important; padding:16px !important; border-radius:16px; }
            .wholesale-procurement.is-create-mode > .card .row > [class*="col-"] { margin-bottom:0 !important; }
            .wholesale-procurement.is-create-mode .form-label { font-size:12px; line-height:16px; }
            .wholesale-procurement.is-create-mode .form-control { font-size:14px; }
            .wholesale-procurement.is-create-mode .select2-selection__rendered { font-size:14px !important; font-weight:600; }
            .wholesale-procurement.is-create-mode .wholesale-edit-content { display:flex; min-height:0; flex:1; flex-direction:column; }
            .wholesale-procurement.is-create-mode .wholesale-products-card { display:flex; min-height:0; flex:1; flex-direction:column; margin:0 0 24px !important; padding:0 !important; border-radius:16px; }
            .wholesale-procurement.is-create-mode .wholesale-products-heading { flex:none; padding:16px 20px; }
            .wholesale-procurement.is-create-mode .wholesale-cart-list { min-height:0; max-height:none; flex:1; overflow-y:auto; }
            .wholesale-procurement.is-create-mode .wholesale-cart-empty { height:100%; min-height:180px; padding:16px 0; }
            .wholesale-procurement.is-create-mode .wholesale-summary-card { flex:none; overflow:visible; margin:0 -16px; padding:0 !important; border:0; border-top:1px solid #f3f4f6; border-radius:0; box-shadow:0 -4px 20px rgba(0,0,0,.03); }
            .wholesale-procurement.is-create-mode .wholesale-summary { position:static; margin:0 !important; padding:16px 16px calc(12px + env(safe-area-inset-bottom)) !important; border:0 !important; background:#fff; box-shadow:none; backdrop-filter:none; }
            .wholesale-procurement.is-create-mode .wholesale-summary-total { border-bottom:1px solid #f3f4f6; padding-bottom:16px; color:#111827; font-size:16px; }
            .wholesale-procurement.is-create-mode .wholesale-summary-total strong { font-size:16px; }
            .wholesale-procurement.is-create-mode .wholesale-summary .row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px !important; margin:14px 0 0 !important; }
            .wholesale-procurement.is-create-mode .wholesale-summary .row > .col { width:auto; margin:0 !important; padding:0 !important; }
            .wholesale-procurement.is-create-mode .wholesale-summary .btn { min-height:44px; padding:8px 10px; font-size:12px; }
        }
        @media (max-width:767.98px) and (max-height:540px) {
            body:has(.wholesale-procurement.is-create-mode) main { overflow-y:auto; }
            .wholesale-procurement.is-create-mode .wholesale-products-card { flex:none; min-height:260px; }
        }
        @if (request()->routeIs('wholesale.create', 'wholesale.edit', 'wholesale.show', 'wholesale-show'))
            @media (max-width: 767.98px) { .erp-mobile-nav { display:none !important; } }
        @endif
    </style>

    <!--begin::Aside column-->
    <div @if ($isWholesaleReadOnly) id="printable-po" @endif class="wholesale-procurement w-100 flex-lg-row-auto {{ $isWholesaleEdit ? 'is-edit-mode' : ($isWholesaleCreate ? 'is-create-mode' : '') }}" x-data="posApp()" x-init="init()">
        <div class="wholesale-form-heading">
            <div>
                <h1>{{ request()->routeIs('wholesale.edit') ? 'Edit Pengadaan' : (request()->routeIs('wholesale.show', 'wholesale-show') ? 'Detail Pengadaan' : 'Tambah Pengadaan') }}</h1>
                <p>{{ $isWholesaleReadOnly ? 'Detail cabang tujuan dan bahan pada transaksi ini.' : 'Atur cabang tujuan dan daftar bahan untuk transaksi ini.' }}</p>
            </div>
            <div class="d-flex align-items-center gap-2 wholesale-no-print">
                @if ($isWholesaleReadOnly)
                    <button type="button" class="wholesale-form-print" onclick="window.print()" aria-label="Cetak detail pengadaan">
                        <i class="ph ph-printer" aria-hidden="true"></i><span>Cetak</span>
                    </button>
                @endif
                <a href="{{ route('wholesale.index') }}" class="wholesale-form-back" aria-label="Kembali ke Pengadaan"><i class="ph ph-arrow-left"></i><span>Kembali</span></a>
            </div>
        </div>
        <div class="card card-body mb-3">
            <div class="d-flex flex-column gap-10">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label for="branch_id" class="required form-label">Cabang</label>
                            <!--end::Label-->
                            <!--begin::Select-->
                            <select class="form-control" name="branch_id" id="branch_id" {{ $isWholesaleReadOnly ? 'disabled' : '' }}>
                                <option value="">Pilih Cabang</option>
                                @if (!empty($data->branch_id) && $data->branch)
                                    <option value="{{ $data->branch_id }}" selected>{{ ucwords($data->branch->name) }}</option>
                                @endif
                            </select>
                            <!--end::Select-->
                        </div>
                    </div>
                    <div class="col-md-6 mb-3 wholesale-edit-meta-field">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Tanggal Transaksi</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="date" class="form-control" name="date" value="{{ old('date', $data->order_date ? \Illuminate\Support\Carbon::parse($data->order_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" {{ $isWholesaleReadOnly ? 'disabled' : '' }}>
                            <!--end::Editor-->
                        </div>
                    </div>
                    <div class="col-md-6 mb-3 wholesale-edit-meta-field">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Nomor Faktur</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control" name="invoice_number" value="{{ $invoice_number }}"
                                readonly>
                            <!--end::Editor-->
                        </div>
                    </div>
                    @if ($isWholesaleReadOnly)
                        <div class="col-md-12 mb-3">
                            <div class="fv-row">
                                <label class="form-label">Status</label>
                                @php
                                    [$statusLabel, $statusClass] = match ($data->status) {
                                        'temp' => ['Draft', 'wholesale-status-muted'],
                                        'draft' => ['Pending', 'wholesale-status-pending'],
                                        'posting' => ['Dalam Pengiriman', 'wholesale-status-shipping'],
                                        'complete' => ['Selesai', 'wholesale-status-complete'],
                                        default => [ucfirst($data->status ?: 'Tidak diketahui'), 'wholesale-status-muted'],
                                    };
                                @endphp
                                <span class="wholesale-detail-status {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                        </div>
                    @endif
                </div>
                <!--end::Input group-->
            </div>
        </div>
        <div class="{{ ($isWholesaleEdit || $isWholesaleCreate) ? 'wholesale-edit-content' : '' }}">
            <div class="card card-body wholesale-products-card mb-3">
                <div class="wholesale-products-heading">
                    <div>
                        <h2>Daftar Belanja</h2>
                        <p>{{ $isWholesaleReadOnly ? 'Rincian bahan pada pesanan ini.' : 'Tambahkan bahan dan atur kuantitas pembelian.' }}</p>
                    </div>
                    <div class="btn-group">
                        @if (!request()->routeIs('wholesale.show', 'wholesale-show'))
                            <button type="button" class="btn wholesale-add-item"
                                @click="openAddModal()">
                                <i class="ph-bold ph-plus"></i><span>Tambah Item</span>
                            </button>
                        @endif
                    </div>
                </div>
                {{-- <!-- Cart --> --}}
                @include('transaction::wholesale.segment.cart')
            </div>

            @if (!$isWholesaleReadOnly)
                <div class="card card-body wholesale-summary-card">
                    @include('transaction::wholesale.segment.ringkasan')
                </div>
            @else
                <div class="card card-body wholesale-summary-card">
                    <div class="wholesale-summary-total">
                        <span>Total Pengadaan</span>
                        <strong>Rp {{ number_format($detail->sum(fn ($item) => (float) ($item->subtotal ?? $item->total_price ?? 0)), 0, ',', '.') }}</strong>
                    </div>
                </div>
            @endif

            @if (!$isWholesaleReadOnly)
                @include('transaction::wholesale.segment.modal-product')
            @endif
        </div>
    </div>
    <!--end::Aside column-->
    @include('transaction::wholesale.js-create')
@endsection
