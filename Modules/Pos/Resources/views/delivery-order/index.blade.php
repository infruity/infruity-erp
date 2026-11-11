@extends('template.root')
@section('title', 'Pengiriman')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/delivery-order-reference.css') }}?v={{ filemtime(public_path('assets/css/delivery-order-reference.css')) }}">
<style>
.delivery-order-filter-chips { display:flex; flex-wrap:wrap; gap:6px; }
.delivery-order-filter-chip { border:1px solid #e5e7eb; border-radius:999px; padding:6px 10px; background:#fff; color:#4b5563; font:500 12px/1.35 var(--erp-font); cursor:pointer; transition:background .15s,border-color .15s,color .15s; }
.delivery-order-filter-chip:hover { border-color:#99f6e4; color:#0f766e; }
.delivery-order-filter-chip.is-active { border-color:#a7f3d0; background:#ecfdf5; color:#047857; font-weight:600; }
.delivery-order-filter-chip:focus-visible { outline:2px solid #0f766e; outline-offset:2px; }
.delivery-order-filter-trigger.is-active { color:#0b595b; }
.delivery-order-detail-modal { position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center; padding:16px; }
.delivery-order-detail-modal[hidden] { display:none !important; }
.delivery-order-detail-modal__backdrop { position:absolute; inset:0; border:0; padding:0; background:rgba(0,0,0,.6); }
.delivery-order-detail-modal__panel { position:relative; z-index:1; display:flex; width:min(100%,384px); max-height:calc(100vh - 32px); max-height:calc(100dvh - 32px); flex-direction:column; overflow:hidden; border-radius:16px; background:#fff; color:#1f2937; box-shadow:0 24px 64px rgba(15,23,42,.25); }
.delivery-order-detail-modal__header { flex:none; padding:20px 20px 16px; }
.delivery-order-detail-modal__title { margin:0; color:#24364f; font:700 18px/1.4 var(--erp-font); }
.delivery-order-detail-modal__body { display:flex; min-height:0; flex:1 1 auto; flex-direction:column; gap:14px; overflow-y:auto; padding:4px 20px 16px; overscroll-behavior:contain; }
.delivery-order-detail-modal__row { display:flex; min-width:0; align-items:center; gap:12px; }
.delivery-order-detail-modal__icon { display:flex; width:40px; height:40px; flex:0 0 40px; align-items:center; justify-content:center; border:1px solid rgba(167,243,208,.8); border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:18px; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.delivery-order-detail-modal__copy { min-width:0; flex:1; }
.delivery-order-detail-modal__value { display:block; color:#24364f; font:700 14px/1.35 var(--erp-font); }
.delivery-order-detail-modal__label { display:block; margin-top:3px; color:#8da0ba; font:500 11px/1.35 var(--erp-font); }
.delivery-order-detail-modal__link { max-width:100%; overflow:hidden; color:#0b9a80; text-decoration:none; text-overflow:ellipsis; white-space:nowrap; }
.delivery-order-detail-modal__link--dashed { border-bottom:1px dashed currentColor; padding-bottom:2px; }
.delivery-order-detail-modal__link--wrap { overflow:visible; overflow-wrap:anywhere; text-overflow:clip; white-space:normal; }
.delivery-order-detail-modal__link--phone { color:#24364f; border-bottom-color:#d1d5db; font:600 12px/1.35 var(--erp-font); }
.delivery-order-detail-modal__link[aria-disabled="true"] { color:#8da0ba; border:0; pointer-events:none; }
.delivery-order-detail-modal__footer { flex:none; border-top:1px solid rgba(148,163,184,.2); padding:12px 16px 16px; }
.delivery-order-detail-modal__action { width:100%; min-height:44px; border:0; border-radius:15px; background:#0b595b; color:#fff; font:700 15px/1.2 var(--erp-font); cursor:pointer; box-shadow:0 5px 12px rgba(11,89,91,.18); transition:background .15s,transform .15s; }
.delivery-order-detail-modal__action:hover { background:#08484a; }
.delivery-order-detail-modal__action:active { transform:scale(.99); }
.delivery-order-detail-modal__action:focus-visible { outline:3px solid #2dd4bf; outline-offset:2px; }
.delivery-order-detail-modal [hidden] { display:none !important; }
.delivery-order-payment-step { display:flex; min-height:0; flex:1 1 auto; flex-direction:column; }
.delivery-order-payment-step__body { display:flex; min-height:0; flex:1 1 auto; flex-direction:column; gap:14px; overflow-y:auto; padding:20px 20px 16px; overscroll-behavior:contain; }
.delivery-order-payment-step__title { margin:0 0 20px; color:#1f2937; font:700 18px/1.4 var(--erp-font); }
.delivery-order-payment-step__row { display:flex; min-width:0; align-items:center; gap:12px; }
.delivery-order-payment-step__icon { display:flex; width:40px; height:40px; flex:0 0 40px; align-items:center; justify-content:center; border:1px solid rgba(167,243,208,.8); border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:18px; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.delivery-order-payment-step__copy { min-width:0; flex:1; }
.delivery-order-payment-step__value { display:block; color:#24364f; font:700 14px/1.35 var(--erp-font); }
.delivery-order-payment-step__value--total { font-size:16px; }
.delivery-order-payment-step__label { display:block; margin-top:3px; color:#8da0ba; font:500 11px/1.35 var(--erp-font); }
.delivery-order-payment-step__input-wrap { position:relative; min-width:0; flex:1; }
.delivery-order-payment-step__prefix { position:absolute; top:50%; left:12px; color:#8da0ba; font:700 12px/1 var(--erp-font); transform:translateY(-50%); pointer-events:none; }
.delivery-order-payment-step__input { width:100%; height:44px; border:1px solid #cbd5e1; border-radius:12px; background:#fff; padding:0 12px 0 40px; color:#24364f; font:700 16px/1 var(--erp-font); }
.delivery-order-payment-step__input:focus { border-color:#0b595b; outline:2px solid rgba(45,212,191,.3); }
.delivery-order-payment-step__balance { display:flex; min-height:42px; align-items:center; justify-content:space-between; gap:12px; border:1px solid; border-radius:12px; padding:10px 12px; font:500 12px/1.3 var(--erp-font); }
.delivery-order-payment-step__balance-label { display:flex; align-items:center; gap:7px; }
.delivery-order-payment-step__balance-value { font-weight:700; }
.delivery-order-payment-step__balance[data-state="paid"] { border-color:#a7f3d0; background:#ecfdf5; color:#065f46; }
.delivery-order-payment-step__balance[data-state="underpaid"] { border-color:#fecdd3; background:#fff1f2; color:#9f1239; }
.delivery-order-payment-step__balance[data-state="overpaid"] { border-color:#fde68a; background:#fffbeb; color:#92400e; }
.delivery-order-payment-step__error { margin:0; color:#be123c; font:500 12px/1.4 var(--erp-font); }
.delivery-order-payment-step__footer { display:flex; flex:none; gap:8px; border-top:1px solid rgba(148,163,184,.2); padding:12px 16px 16px; }
.delivery-order-payment-step__back { min-height:44px; flex:1; border:1px solid #e5e7eb; border-radius:15px; background:#fff; color:#4b5563; font:700 14px/1.2 var(--erp-font); cursor:pointer; }
.delivery-order-payment-step__submit { flex:1; }
.delivery-order-payment-step__back:focus-visible,.delivery-order-payment-step__submit:focus-visible { outline:3px solid #2dd4bf; outline-offset:2px; }
@media (max-width:767.98px) {
    body:has(#delivery-order-page) #kt_content { padding:0!important; scroll-padding-bottom:0; }
    body:has(#delivery-order-page) #kt_content_container { display:flex; min-height:0; flex:1; flex-direction:column; padding:0!important; }
    #delivery-order-page { min-height:0; flex:1; border-radius:0; }
    #delivery-order-page .erp-workspace-content { min-height:0; }
    #delivery-order-page .erp-workspace-heading { display:none; }
    #delivery-order-page .erp-workspace-toolbar { position:sticky; top:0; z-index:25; gap:0; padding:16px; background:#fff; }
    #delivery-order-page .erp-workspace-toolbar .erp-search { flex:1 0 100%; min-width:0; padding-right:68px; }
    #delivery-order-page .erp-workspace-toolbar > .erp-mobile-search-shell { min-width:0; flex:1 1 100%; padding:0; background:transparent; }
    #delivery-order-page .erp-workspace-toolbar .erp-search > input { font-size:16px; }
    #delivery-order-page .delivery-order-filter-trigger { position:absolute; top:16px; right:16px; width:52px; min-height:44px; gap:0; border:0; border-left:1px solid var(--erp-border); border-radius:0; padding:0; background:transparent; box-shadow:none; color:var(--erp-muted); }
    #delivery-order-page .delivery-order-filter-trigger:hover { background:transparent; color:var(--erp-primary); }
    #delivery-order-page .delivery-order-filter-trigger > i { font-size:20px; }
    #delivery-order-page .delivery-order-filter-trigger > span { display:none; }
    #delivery-order-page .delivery-order-filter-menu { display:none!important; }
    #delivery-order-page .erp-record-controls { display:none; }
    .erp-collection[data-erp-collection="pos-table"] .delivery-order-card__head { width:100%; min-height:61px; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border:0; border-radius:0; background:#fff; color:#1f2937; text-align:left; font:inherit; }
    .erp-collection[data-erp-collection="pos-table"] .delivery-order-card__head:hover { background:#f9fafb; }
    .delivery-order-card__customer { display:flex; min-width:0; flex:1; align-items:center; gap:12px; }
    .delivery-order-card__avatar { position:relative; display:flex; width:36px; height:36px; flex:0 0 36px; align-items:center; justify-content:center; border:1px solid; border-radius:12px; font:700 12px/1 var(--erp-font); }
    .delivery-order-card__avatar[data-avatar-theme="emerald"] { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
    .delivery-order-card__avatar[data-avatar-theme="blue"] { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .delivery-order-card__avatar[data-avatar-theme="purple"] { border-color:#e9d5ff; background:#faf5ff; color:#7e22ce; }
    .delivery-order-card__avatar[data-avatar-theme="amber"] { border-color:#fde68a; background:#fffbeb; color:#b45309; }
    .delivery-order-card__avatar[data-avatar-theme="teal"] { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
    .delivery-order-card__avatar[data-avatar-theme="rose"] { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
    .delivery-order-card__avatar[data-avatar-theme="indigo"] { border-color:#c7d2fe; background:#eef2ff; color:#4338ca; }
    .delivery-order-card__payment { position:absolute; right:-5px; bottom:-4px; display:flex; width:16px; height:16px; align-items:center; justify-content:center; border:2px solid #fff; border-radius:50%; color:#fff; font-size:9px; box-shadow:0 1px 2px rgb(15 23 42 / 16%); }
    .delivery-order-card__payment[data-payment-state="paid"] { background:#059669; }
    .delivery-order-card__payment[data-payment-state="unpaid"] { background:#f43f5e; }
    .delivery-order-card__payment i { font-size:8px; line-height:1; }
    .delivery-order-card__copy { display:flex; min-width:0; flex:1; flex-direction:column; }
    .delivery-order-card__title { display:block; overflow:hidden; color:#1f2937; font-size:12px; font-weight:600; line-height:16px; text-overflow:ellipsis; white-space:nowrap; }
    .delivery-order-card__phone { color:#9ca3af; font-size:11px; font-weight:400; }
    .delivery-order-card__amount { margin-top:2px; color:#6b7280; font-size:11px; font-weight:400; line-height:16px; }
    .delivery-order-card__status { display:flex; min-width:0; max-width:42%; flex:none; flex-direction:column; align-items:flex-end; text-align:right; }
    .delivery-order-card__delivery { overflow:hidden; font-size:12px; font-weight:600; line-height:16px; text-overflow:ellipsis; white-space:nowrap; }
    .delivery-order-card__delivery[data-delivery-state="received"] { color:#059669; }
    .delivery-order-card__delivery[data-delivery-state="unreceived"] { color:#e11d48; }
    .delivery-order-card__courier { max-width:120px; margin-top:2px; overflow:hidden; color:#6b7280; font-size:11px; font-weight:400; line-height:16px; text-overflow:ellipsis; white-space:nowrap; }
}
</style>
<x-erp.workspace id="delivery-order-page" title="Pengiriman" subtitle="Kelola pengiriman dan lihat rincian transaksi."><x-slot:toolbar><div class="erp-search d-none d-md-flex"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search-desktop" data-kt-ecommerce-product-filter="search" aria-label="Cari nama pelanggan, kurir, atau 4 digit HP" placeholder="Cari..."></div><x-erp.mobile-search class="d-md-none erp-mobile-search-shell--fill"><x-slot:input><input type="search" id="search" data-kt-ecommerce-product-filter="search" aria-label="Cari nama pelanggan, kurir, atau 4 digit HP" placeholder="Cari..." class="erp-mobile-search__input"></x-slot:input><x-slot:clear><button type="button" data-delivery-order-mobile-search-clear aria-label="Hapus pencarian" class="erp-mobile-search__clear" hidden><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button></x-slot:clear><x-slot:filter><button type="button" data-delivery-order-mobile-filter-trigger aria-label="Filter pengiriman" aria-haspopup="dialog" aria-controls="delivery-order-filter-sheet" aria-expanded="false" aria-pressed="false" class="erp-mobile-search__filter"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span class="erp-mobile-search__filter-dot" data-delivery-order-mobile-filter-dot hidden></span></button></x-slot:filter></x-erp.mobile-search><a href="{{ route('pos.index') }}" class="erp-action erp-action--primary d-none d-sm-inline-flex" aria-label="Tambah pengiriman lewat POS"><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah Pengiriman</span></a><button type="button" class="erp-action delivery-order-filter-trigger d-none d-md-inline-flex" data-delivery-order-filter-trigger aria-haspopup="dialog" aria-controls="delivery-order-filter-sheet" aria-expanded="false" aria-label="Filter pengiriman" aria-pressed="false"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></button><div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px delivery-order-filter-menu"
                            data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
                                <div class="fs-7 text-muted mt-1">Atur penerimaan, pembayaran, cabang, dan tanggal pengiriman.</div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Separator-->
                            <div class="separator border-gray-200"></div>
                            <!--end::Separator-->
                            <div class="px-7 py-2">
                                <div class="mb-4">
                                    <p class="form-label fs-6 fw-semibold mb-2">Status Penerimaan</p>
                                    <div class="delivery-order-filter-chips" role="group" aria-label="Status Penerimaan">
                                        <button type="button" class="delivery-order-filter-chip is-active" data-delivery-order-filter="delivery" data-filter-value="all" aria-pressed="true">Semua Status</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="delivery" data-filter-value="unreceived" aria-pressed="false">Belum Diterima</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="delivery" data-filter-value="received" aria-pressed="false">Sudah Diterima</button>
                                    </div>
                                </div>
                                <div>
                                    <p class="form-label fs-6 fw-semibold mb-2">Status Pembayaran</p>
                                    <div class="delivery-order-filter-chips" role="group" aria-label="Status Pembayaran">
                                        <button type="button" class="delivery-order-filter-chip is-active" data-delivery-order-filter="payment" data-filter-value="all" aria-pressed="true">Semua</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="payment" data-filter-value="unpaid" aria-pressed="false">Belum Lunas</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="payment" data-filter-value="paid" aria-pressed="false">Sudah Lunas</button>
                                    </div>
                                </div>
                            </div>
                            <!--end::Content-->
                            <!--begin::Content-->
                            <div class="px-7 py-2" data-kt-user-table-filter="form">
                                <!--begin::Input group-->
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Cabang:</label>
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Cabang"
                                        data-kt-ecommerce-product-filter="cabang">
                                        @if (count($branches) > 1)
                                            <option value="all">All</option>
                                        @endif
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Content-->
                            <!--begin::Content-->
                            <div class="px-7 py-5" data-kt-user-table-filter="form">
                                <!--begin::Input group-->
                                <div class="input-group mw-350px">
                                    <input class="form-control form-control-solid rounded rounded-end-0"
                                        placeholder="Pilih range tanggal" id="kt_ecommerce_sales_flatpickr" />
                                    <button class="btn btn-icon btn-light" id="kt_ecommerce_sales_flatpickr_clear">
                                        <i class="ki-duotone ki-cross fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Content-->
                        </div></x-slot:toolbar><x-erp.collection table="pos-table"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-start min-w-150px">Nama Pelanggan</th>
                            <th class="text-start min-w-150px">Tanggal Pengiriman</th>
                            <th class="text-start min-w-150px">Nama Kurir</th>
                            <th class="text-start min-w-150px">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table></x-erp.collection></x-erp.workspace>
<x-erp.filter-sheet
    id="delivery-order"
    title="Filter Pengiriman"
    aria-label="Filter pengiriman"
    subtitle="Saring cabang, status, dan periode pengiriman"
    branch-label="Cabang Tujuan"
    branch-all-label="Semua Cabang"
    status-label="Status Penerimaan"
    :branches="$branches"
    :show-branch-all="count($branches) > 1"
    :default-branch="count($branches) > 1 ? 'all' : (string) optional($branches->first())->id"
    :statuses="['all' => 'Semua Status', 'unreceived' => 'Belum Diterima', 'received' => 'Sudah Diterima']"
    :groups="[['key' => 'payment', 'label' => 'Status Pembayaran', 'options' => ['all' => 'Semua Status', 'unpaid' => 'Belum Lunas', 'paid' => 'Sudah Lunas']]]"
    :timezone="config('app.timezone')"
    reset-applies
/>
<div id="delivery-order-detail-modal" class="delivery-order-detail-modal" hidden aria-hidden="true">
    <button type="button" class="delivery-order-detail-modal__backdrop" data-delivery-order-detail-close tabindex="-1" aria-label="Tutup detail pengiriman"></button>
    <section class="delivery-order-detail-modal__panel" role="dialog" aria-modal="true" aria-labelledby="delivery-order-detail-title" tabindex="-1">
        <header class="delivery-order-detail-modal__header" data-delivery-order-detail-header>
            <h2 id="delivery-order-detail-title" class="delivery-order-detail-modal__title">Detail Pengiriman</h2>
        </header>
        <div class="delivery-order-detail-modal__body" data-delivery-order-detail-body>
            <p class="delivery-order-billing-note" data-delivery-order-billing-note hidden></p>
            <a class="delivery-order-payment-link" data-delivery-order-continue-payment hidden>Lanjutkan Pembayaran</a>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-user text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="customer"></span>
                    <a class="delivery-order-detail-modal__value delivery-order-detail-modal__link delivery-order-detail-modal__link--dashed delivery-order-detail-modal__link--wrap delivery-order-detail-modal__link--phone" data-delivery-order-detail-field="phone" target="_blank" rel="noopener noreferrer"></a>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-map-pin text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <a class="delivery-order-detail-modal__value delivery-order-detail-modal__link delivery-order-detail-modal__link--dashed" data-delivery-order-detail-field="address" target="_blank" rel="noopener noreferrer"></a>
                    <span class="delivery-order-detail-modal__label">Alamat Pengiriman (Klik untuk Buka Gmaps)</span>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-bag text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="subtotal"></span>
                    <span class="delivery-order-detail-modal__label">Total Belanja</span>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-moped text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="shipping-fee"></span>
                    <span class="delivery-order-detail-modal__label">Ongkos Kirim Kurir</span>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-receipt text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="grand-total"></span>
                    <span class="delivery-order-detail-modal__label" data-delivery-order-detail-field="grand-total-label">Total Belanja (Include Ongkir)</span>
                </div>
            </div>
        </div>
        <footer class="delivery-order-detail-modal__footer" data-delivery-order-detail-footer>
            <button type="button" class="delivery-order-detail-modal__action" data-delivery-order-detail-action>Tutup</button>
        </footer>
        <div class="delivery-order-payment-step" data-delivery-order-payment-step hidden>
            <div class="delivery-order-payment-step__body">
                <h3 id="delivery-order-payment-title" class="delivery-order-payment-step__title">Penerimaan Pembayaran</h3>
                <div class="delivery-order-payment-step__row">
                    <span class="delivery-order-payment-step__icon" aria-hidden="true"><i class="ph-duotone ph-bag"></i></span>
                    <div class="delivery-order-payment-step__copy">
                        <span class="delivery-order-payment-step__value" data-delivery-order-payment-field="subtotal"></span>
                        <span class="delivery-order-payment-step__label">Total Belanja</span>
                    </div>
                </div>
                <div class="delivery-order-payment-step__row">
                    <span class="delivery-order-payment-step__icon" aria-hidden="true"><i class="ph-duotone ph-moped"></i></span>
                    <div class="delivery-order-payment-step__copy">
                        <span class="delivery-order-payment-step__value" data-delivery-order-payment-field="shipping-fee"></span>
                        <span class="delivery-order-payment-step__label">Total Ongkir</span>
                    </div>
                </div>
                <div class="delivery-order-payment-step__row">
                    <span class="delivery-order-payment-step__icon" aria-hidden="true"><i class="ph-duotone ph-receipt"></i></span>
                    <div class="delivery-order-payment-step__copy">
                        <span class="delivery-order-payment-step__value delivery-order-payment-step__value--total" data-delivery-order-payment-field="grand-total"></span>
                        <span class="delivery-order-payment-step__label">Total Tagihan (Setelah Voucher)</span>
                    </div>
                </div>
                <dl class="delivery-order-billing-summary">
                    <div data-delivery-order-voucher-row hidden><dt>Potongan voucher</dt><dd data-delivery-order-billing-field="voucher"></dd></div>
                    <div><dt>Sudah dibayar sebelumnya</dt><dd data-delivery-order-billing-field="previous-paid"></dd></div>
                    <div><dt>Sisa sebelum penerimaan ini</dt><dd data-delivery-order-billing-field="remaining"></dd></div>
                </dl>
                <p class="delivery-order-billing-note" data-delivery-order-billing-note hidden></p>
                <a class="delivery-order-payment-link" data-delivery-order-continue-payment hidden>Periksa Pembayaran di POS</a>
                <label class="delivery-order-payment-amount-label" data-delivery-order-payment-amount-label for="delivery-order-received-amount">Uang diterima saat ini</label>
                <div class="delivery-order-payment-step__row">
                    <span class="delivery-order-payment-step__icon" aria-hidden="true"><i class="ph-duotone ph-money"></i></span>
                    <label class="delivery-order-payment-step__input-wrap">
                        <span class="delivery-order-payment-step__prefix" aria-hidden="true">Rp</span>
                        <input id="delivery-order-received-amount" type="text" inputmode="numeric" autocomplete="off" class="delivery-order-payment-step__input" data-delivery-order-payment-amount aria-label="Nominal pembayaran yang diterima" placeholder="Masukkan nominal">
                    </label>
                </div>
                <div class="delivery-order-payment-step__balance" data-delivery-order-payment-balance hidden data-state="paid" aria-live="polite">
                    <span class="delivery-order-payment-step__balance-label">
                        <i class="ph-bold ph-check-circle" data-delivery-order-payment-balance-icon aria-hidden="true"></i>
                        <span data-delivery-order-payment-balance-label>Lunas :</span>
                    </span>
                    <strong class="delivery-order-payment-step__balance-value" data-delivery-order-payment-balance-value>Rp 0</strong>
                </div>
                <label class="delivery-order-zero-confirm" data-delivery-order-zero-confirm-wrap hidden>
                    <input type="checkbox" data-delivery-order-zero-confirm>
                    <span>Belum ada pembayaran. Saya konfirmasi barang diterima dan sisa tagihan tetap belum lunas.</span>
                </label>
                <p class="delivery-order-payment-step__error" data-delivery-order-payment-error role="alert" hidden></p>
            </div>
            <footer class="delivery-order-payment-step__footer">
                <button type="button" class="delivery-order-payment-step__back" data-delivery-order-payment-back>Kembali</button>
                <button type="button" class="delivery-order-detail-modal__action delivery-order-payment-step__submit" data-delivery-order-payment-submit>Bayar</button>
            </footer>
        </div>
    </section>
</div>
<div class="modal fade" id="kt_modal_add_customer" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog erp-sheet-dialog modal-dialog-scrollable mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content rounded-4 shadow-sm">
                <!--begin::Form-->
                <form class="form" action="{{ url(Request::segment(1)) . '/update-courier' }}"
                    id="kt_modal_add_customer_form" data-kt-redirect="#">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="erp-sheet-header border-0 pb-0" id="kt_modal_add_customer_header">
                        <!--begin::Modal title-->
                        <div>
                            <h2 class="fw-bold mb-1">Pilih Kurir</h2>
                            <div class="fs-7 text-muted">Tentukan staff yang bertugas menerima order pengiriman.</div>
                        </div>
                        <!--end::Modal title-->
                        <!--begin::Close-->
                        <button type="button" aria-label="Tutup formulir" id="kt_modal_add_customer_close" class="btn btn-icon btn-sm btn-active-icon-primary">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                        <!--end::Close-->
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="erp-sheet-body py-7 px-5 px-lg-10">
                        <div class="rounded-3 px-4 py-3 mb-5 delivery-order-modal-note">
                            <div class="fs-7 text-gray-700">Centang staff yang ingin dijadikan kurir aktif.</div>
                        </div>
                        <!--begin::Scroll-->
                        <div class="scroll-y me-lg-n5 pe-lg-5" id="kt_modal_add_customer_scroll" data-kt-scroll="true"
                            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_customer_header"
                            data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div id="listKurir"></div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Scroll-->
                    </div>
                    <!--end::Modal body-->
                    <!--begin::Modal footer-->
                    <div class="erp-sheet-footer flex-center border-0 pt-0 delivery-order-modal-footer">
                        <!--begin::Button-->
                        <button type="reset" id="kt_modal_add_customer_cancel" class="btn btn-light me-3">Batal</button>
                        <!--end::Button-->
                        <!--begin::Button-->
                        <button type="submit" id="kt_modal_add_customer_submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <!--end::Button-->
                    </div>
                    <!--end::Modal footer-->
                </form>
                <!--end::Form-->
            </div>
        </div>
    </div>
@section('script')
    <script src="{{ asset('assets/js/delivery-order-mobile.js') }}?v={{ filemtime(public_path('assets/js/delivery-order-mobile.js')) }}"></script>
    <script src="{{ asset('assets/js/delivery-order-filter-state.js') }}"></script>
    <script type="text/javascript">
        var dataTable;
        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $dateFilter = $('#kt_ecommerce_sales_flatpickr');
            const $activeBranchButtonLabel = $('#active-branch-button-label');
            const $mobileFilterTrigger = $('[data-delivery-order-mobile-filter-trigger]');
            const $desktopFilterTrigger = $('[data-delivery-order-filter-trigger]');
            const $searchFields = $('[data-kt-ecommerce-product-filter="search"]');
            const $mobileSearchClear = $('[data-delivery-order-mobile-search-clear]');
            const defaultBranch = String($branchFilter.val() || 'all');
            const filterState = window.DeliveryOrderFilterState.create({ branch: defaultBranch });
            let salesFlatpickr;
            const searchDebounce = window.DeliveryOrderFilterState.createDebouncedSearch(query => {
                dataTable.search(query).draw();
            });

            function updateActiveFilterInfo() {
                const filters = filterState.applied;
                const branchOption = Array.from($branchFilter[0]?.options || []).find(option => option.value === filters.branch);
                const selectedBranch = filters.branch === 'all' ? 'Semua cabang' : (branchOption?.text.trim() || 'Semua cabang');
                const hasActiveFilters = filters.branch !== defaultBranch || filters.delivery !== 'all'
                    || filters.payment !== 'all' || Boolean(filters.dateRange);
                $activeBranchButtonLabel.text(selectedBranch);
                $('.delivery-order-filter-trigger').toggleClass('is-active', hasActiveFilters)
                    .attr('aria-pressed', String(hasActiveFilters));
                $mobileFilterTrigger.toggleClass('is-active', hasActiveFilters)
                    .attr('aria-pressed', String(hasActiveFilters));
                $('[data-delivery-order-mobile-filter-dot]').prop('hidden', !hasActiveFilters);
            }

            function syncDesktopFilterControls(filters) {
                $('[data-delivery-order-filter]').each(function() {
                    const group = this.getAttribute('data-delivery-order-filter');
                    const active = this.getAttribute('data-filter-value') === filters[group];
                    this.classList.toggle('is-active', active);
                    this.setAttribute('aria-pressed', String(active));
                });
                const branchExists = Array.from($branchFilter[0]?.options || []).some(option => option.value === filters.branch);
                if (branchExists) $branchFilter.val(filters.branch).trigger('change.select2');
                if (salesFlatpickr) {
                    if (filters.dateRange) salesFlatpickr.setDate(filters.dateRange, false);
                    else salesFlatpickr.clear(false);
                }
                updateActiveFilterInfo();
            }

            // The sheet itself is the shared filter-sheet component (id "delivery-order"): status penerimaan is
            // its status group, status pembayaran an extra group. It only exists on phones; desktop keeps the
            // inline controls above.
            function sheetValues(filters) {
                const [start = '', end = ''] = String(filters.dateRange || '').split(' to ');
                return { branch: filters.branch, status: filters.delivery, start, end: end || start, extra: { payment: filters.payment } };
            }

            function openFilterSheet() {
                $mobileFilterTrigger.add($desktopFilterTrigger).attr('aria-expanded', 'true');
                window.dispatchEvent(new CustomEvent('erp-filter-open', { detail: { id: 'delivery-order', values: sheetValues(filterState.applied) } }));
            }

            function applySheetFilters(values) {
                const start = values.start || '';
                filterState.setApplied('branch', values.branch || defaultBranch);
                filterState.setApplied('delivery', values.status || 'all');
                filterState.setApplied('payment', values.extra?.payment || 'all');
                filterState.setApplied('dateRange', start ? `${start} to ${values.end || start}` : '');
                syncDesktopFilterControls(filterState.applied);
                dataTable.draw();
            }

            dataTable = $('#pos-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                searchDelay: 300,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Disable sorting for action column
                }, ],
                ajax: {
                    url: "{{ route('delivery-order.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        const filters = filterState.applied;
                        d.delivery_status_filter = filters.delivery;
                        d.payment_status_filter = filters.payment;
                        d.cabang_filter = filters.branch;
                        if (filters.dateRange) {
                            const dates = filters.dateRange.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0]; // jika hanya pilih 1 tanggal
                        }
                    }
                },
                order: [
                    [4, 'desc'],

                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-center'
                    },
                    {
                        data: 'courier',
                        name: 'courier'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ]
            });
            if (window.DeliveryOrderMobileCards) {
                window.DeliveryOrderMobileCards.attach(dataTable);
            }

            $searchFields.on('input', function() {
                const query = this.value;
                $searchFields.not(this).val(query);
                $mobileSearchClear.prop('hidden', !query);
                searchDebounce.schedule(query);
            });

            $mobileSearchClear.on('click', function() {
                searchDebounce.cancel();
                $searchFields.val('');
                $mobileSearchClear.prop('hidden', true);
                dataTable.search('').draw();
                document.getElementById('search')?.focus();
            });

            $('[data-delivery-order-filter]').on('click', function() {
                const group = this.getAttribute('data-delivery-order-filter');
                const key = group === 'delivery' ? 'delivery' : 'payment';
                filterState.setApplied(key, this.getAttribute('data-filter-value'));
                syncDesktopFilterControls(filterState.applied);
                dataTable.draw();
            });

            $branchFilter.on('change', function() {
                filterState.setApplied('branch', this.value || 'all');
                updateActiveFilterInfo();
                dataTable.draw(); // trigger fetch ulang dari server
            });

            salesFlatpickr = $dateFilter.flatpickr({
                altInput: true,
                altFormat: 'd/m/Y',
                dateFormat: 'Y-m-d',
                mode: 'range',
                onChange: function(selectedDates, dateRange) {
                    filterState.setApplied('dateRange', dateRange || '');
                    updateActiveFilterInfo();
                    dataTable.draw();
                }
            });

            $('#kt_ecommerce_sales_flatpickr_clear').on('click', function() {
                salesFlatpickr.clear(false);
                filterState.setApplied('dateRange', '');
                updateActiveFilterInfo();
                dataTable.draw();
            });

            $mobileFilterTrigger.add($desktopFilterTrigger).on('click', openFilterSheet);
            window.addEventListener('erp-filter-apply', event => {
                if (event.detail.id === 'delivery-order') applySheetFilters(event.detail.values);
            });
            window.addEventListener('erp-filter-close', event => {
                if (event.detail.id === 'delivery-order') $mobileFilterTrigger.add($desktopFilterTrigger).attr('aria-expanded', 'false');
            });

            updateActiveFilterInfo();
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function setSelesai(id) {
            setBayar(id);
        }

        function setBayar(id) {
            if (window.DeliveryOrderMobileCards?.openPaymentReceiptStep?.(id)) return;
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Data pembayaran belum siap. Silakan muat ulang halaman atau periksa melalui POS.'
            });
        }

        $('#kt_modal_add_customer').on('show.bs.modal', function() {
            let targetDiv = $('#listKurir');
            targetDiv.html('<div class="text-center py-5">Loading...</div>');

            $.ajax({
                url: "{{ url('/delivery-order/get-courier') }}",
                type: "GET",
                success: function(data) {
                    targetDiv.html(data);

                    // hancurkan dulu kalau sebelumnya sudah ada
                    if ($.fn.DataTable.isDataTable('#kurir-table')) {
                        $('#kurir-table').DataTable().destroy();
                    }

                    // baru init ulang
                    $('#kurir-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        responsive: true,
                        searching: true,
                        paging: true,
                        lengthChange: false,

                        info: !isMobileView,
                        autoWidth: false
                    });
                },
                error: function() {
                    targetDiv.html('<div class="alert alert-danger">Gagal load data kurir.</div>');
                }
            });
        });

        document.getElementById('kt_modal_add_customer_cancel').addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah form reset langsung
            Swal.fire({
                text: "Apakah Anda yakin ingin membatalkan?",
                icon: "warning",
                showCancelButton: !0,
                buttonsStyling: !1,
                confirmButtonText: "Ya, Batalkan!",
                cancelButtonText: "Tidak, Kembali",
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: "btn btn-active-light"
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tutup modal manual
                    const modal = bootstrap.Modal.getInstance(document.getElementById(
                        'kt_modal_add_customer'));
                    modal.hide();
                    document.getElementById('kt_modal_add_customer_form').reset();
                }
            });
        });

        $('#kt_modal_add_customer_form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var submitBtn = $('#kt_modal_add_customer_submit');

            // Show loading
            submitBtn.prop('disabled', true);
            submitBtn.find('.indicator-label').hide();
            submitBtn.find('.indicator-progress').show();

            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(), // gunakan FormData(form)[... jika pakai file]
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'Data berhasil disimpan.',
                        showConfirmButton: false,
                        timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
                    }).then(() => {
                        // 1. Reset form

                        const modal = bootstrap.Modal.getInstance(document
                            .getElementById('kt_modal_add_customer'));
                        if (modal) modal.hide();

                        // 6. Refresh DataTable
                        if (typeof dataTable !== 'undefined') {
                            dataTable.ajax.reload(null, false);
                        }
                    });
                },
                error: function(xhr) {
                    let errorText = 'Terjadi kesalahan.';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Menggabungkan semua pesan error dalam <ul>
                        const errors = xhr.responseJSON.errors;
                        errorText = '<ul>';
                        for (const key in errors) {
                            if (errors.hasOwnProperty(key)) {
                                errors[key].forEach(function(msg) {
                                    errorText += `<li>${msg}</li>`;
                                });
                            }
                        }
                        errorText += '</ul>';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorText = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        html: errorText // gunakan html, bukan text
                    });
                },
                complete: function() {
                    // Reset loading state
                    submitBtn.prop('disabled', false);
                    submitBtn.find('.indicator-label').show();
                    submitBtn.find('.indicator-progress').hide();
                }
            });
        });
    </script>
@endsection
@endsection
