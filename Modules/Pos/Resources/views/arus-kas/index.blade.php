@extends('template.root')

@section('title', $pageMode === 'edit' ? 'Edit Arus Kas' : ($pageMode === 'create' ? 'Tambah Arus Kas' : 'Arus Kas'))

@section('content')
@php($branchOptions = $branches->map(fn ($branch) => ['id' => (string) $branch->id, 'name' => $branch->name])->values()->all())

<style>
    [x-cloak] { display:none !important; }
    .arus-kas-page { min-height:100%; color:#1f2937; font-family:'Plus Jakarta Sans',sans-serif; }
    .arus-kas-page .erp-workspace-heading { display:none; }
    .arus-kas-mobile-search.erp-mobile-search-shell { padding:16px 16px 15px; background:#fff; }
    .arus-kas-mobile-search .erp-mobile-search { height:44px; }
    .arus-kas-row { display:flex; min-height:61px; align-items:center; gap:12px; border-bottom:1px solid #f3f4f6; padding:9px 16px; background:#fff; }
    .arus-kas-row:active { background:#f9fafb; }
    .arus-kas-row__icon { display:flex; width:36px; height:36px; flex:none; align-items:center; justify-content:center; border:1px solid; border-radius:50%; }
    .arus-kas-row__copy { min-width:0; flex:1; }
    .arus-kas-row__title { overflow:hidden; color:#111827; font-size:12px; font-weight:700; line-height:17px; text-overflow:ellipsis; white-space:nowrap; }
    .arus-kas-row__date { margin-top:2px; color:#9ca3af; font-size:10px; line-height:14px; }
    .arus-kas-row__meta { min-width:86px; flex:none; text-align:right; }
    .arus-kas-row__status { display:block; font-size:11px; font-weight:700; line-height:16px; }
    .arus-kas-row__total { display:block; margin-top:1px; color:#111827; font-size:11px; font-weight:800; line-height:15px; }
    .arus-kas-empty { display:flex; min-height:calc(100dvh - 218px); flex-direction:column; align-items:center; justify-content:center; padding:24px 20px 108px; text-align:center; }
    .arus-kas-empty__icon { display:flex; width:64px; height:64px; align-items:center; justify-content:center; border:1px solid rgba(16,185,129,.12); border-radius:24px; background:rgba(236,253,245,.72); color:#0b595b; }
    .arus-kas-empty__icon i { font-size:28px; }
    .arus-kas-sheet-backdrop { position:fixed; z-index:110; inset:0; background:rgba(0,0,0,.58); }
    .arus-kas-filter-sheet { position:fixed; z-index:111; right:0; bottom:0; left:0; display:flex; max-height:88dvh; flex-direction:column; overflow:hidden; border-radius:22px 22px 0 0; background:#fff; box-shadow:0 -12px 40px rgba(0,0,0,.18); }
    .arus-kas-sheet-handle { width:64px; height:5px; margin:11px auto 9px; border-radius:999px; background:#d1d5db; }
    .arus-kas-filter-scroll { min-height:0; overflow-y:auto; padding:16px 20px 20px; }
    .arus-kas-radio-list { overflow:hidden; border:1px solid #e5e7eb; border-radius:10px; }
    .arus-kas-radio-row { display:flex; min-height:37px; align-items:center; gap:12px; border-bottom:1px solid #f3f4f6; padding:7px 12px; color:#4b5563; font-size:12px; }
    .arus-kas-radio-row:last-child { border-bottom:0; }
    .arus-kas-radio-row input { width:16px; height:16px; accent-color:#10b981; }
    .arus-kas-form-page { display:flex; min-height:100%; flex-direction:column; background:#e5e9f3; }
    .arus-kas-form-scroll { min-height:0; flex:1; overflow-y:auto; padding:18px 16px 14px; }
    .arus-kas-form-branch { position:relative; z-index:30; min-height:99px; border-radius:16px; background:#fff; padding:16px; box-shadow:0 8px 20px -12px rgba(31,41,55,.18); }
    .arus-kas-branch-native { position:absolute; width:1px; height:1px; margin:-1px; padding:0; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    .arus-kas-branch-select { display:flex; width:100%; height:44px; align-items:center; justify-content:space-between; gap:12px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; padding:0 14px; color:#111827; font-size:12px; font-weight:500; text-align:left; cursor:pointer; transition:border-color .15s ease,background-color .15s ease,box-shadow .15s ease; }
    .arus-kas-branch-select[aria-expanded="true"] { border-color:#10b981; background:#fff; box-shadow:0 0 0 1px #10b981; }
    .arus-kas-branch-select:focus-visible { outline:2px solid #10b981; outline-offset:1px; }
    .arus-kas-branch-select:disabled { cursor:not-allowed; opacity:.6; }
    .arus-kas-branch-value--selected { color:#1f2937; font-weight:600; }
    .arus-kas-branch-value--placeholder { color:#9ca3af; font-weight:400; }
    .arus-kas-branch-chevron { flex:none; color:#9ca3af; font-size:14px; transition:transform .2s ease,color .2s ease; }
    .arus-kas-branch-chevron.rotate-180 { color:#0b595b; }
    .arus-kas-branch-options { position:absolute; top:calc(100% + 6px); left:0; z-index:50; width:100%; max-height:224px; overflow-y:auto; overscroll-behavior:contain; border:1px solid #f3f4f6; border-radius:12px; background:#fff; padding:4px 0; box-shadow:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -4px rgba(0,0,0,.1); }
    .arus-kas-branch-option { display:flex; width:100%; min-height:40px; align-items:center; justify-content:space-between; gap:12px; border:0; background:#fff; padding:10px 14px; color:#374151; font-size:12px; font-weight:500; line-height:18px; text-align:left; cursor:pointer; transition:background-color .15s ease,color .15s ease; }
    .arus-kas-branch-option:hover, .arus-kas-branch-option:focus-visible { background:#f9fafb; outline:0; }
    .arus-kas-branch-option[aria-selected="true"] { background:#f9fafb; color:#059669; font-weight:600; }
    .arus-kas-branch-option__mark { width:8px; height:8px; flex:none; border-radius:50%; background:#10b981; }
    .arus-kas-items-card { display:flex; min-height:calc(100dvh - 299px); flex-direction:column; margin-top:25px; overflow:hidden; border:1px solid rgba(229,231,235,.55); border-radius:17px; background:#fff; box-shadow:0 8px 20px -12px rgba(31,41,55,.16); }
    .arus-kas-items-head { display:flex; min-height:62px; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding:14px 20px; }
    .arus-kas-items-body { min-height:0; flex:1; overflow-y:auto; }
    .arus-kas-form-item { display:flex; min-height:67px; align-items:flex-start; justify-content:space-between; gap:10px; border-bottom:1px solid #f3f4f6; padding:13px 18px 11px; }
    .arus-kas-form-footer { display:flex; min-height:72px; flex:none; align-items:center; gap:12px; border-top:1px solid rgba(229,231,235,.65); background:#fff; padding:12px 16px calc(12px + env(safe-area-inset-bottom)); }
    .arus-kas-action-btn { display:flex; min-height:44px; flex:1; align-items:center; justify-content:center; border:1px solid #e5e7eb; border-radius:13px; background:#fff; color:#1f2937; font-size:12px; font-weight:700; }
    .arus-kas-action-btn--primary { border-color:#0b595b; background:#0b595b; color:#fff; box-shadow:0 4px 12px rgba(11,89,91,.2); }
    .arus-kas-modal-backdrop { position:fixed; z-index:130; inset:0; background:rgba(0,0,0,.62); }
    .arus-kas-invoice-modal { position:fixed; z-index:131; top:50%; left:50%; display:flex; width:calc(100% - 32px); max-width:430px; max-height:calc(100dvh - 88px); transform:translate(-50%,-50%); flex-direction:column; overflow:hidden; border-radius:24px; background:#fff; box-shadow:0 20px 60px rgba(0,0,0,.24); }
    .arus-kas-invoice-scroll { min-height:0; overflow-y:auto; padding:20px 24px 18px; }
    .arus-kas-item-modal { position:fixed; z-index:141; top:50%; left:50%; display:flex; width:calc(100% - 32px); max-width:512px; max-height:92vh; transform:translate(-50%,-50%); flex-direction:column; overflow:hidden; border:1px solid #e5e7eb; border-radius:24px; background:#fff; box-shadow:0 20px 60px rgba(0,0,0,.25); }
    .arus-kas-item-modal__body { overflow-y:auto; padding:24px; font-size:16px; line-height:24px; }
    @media (min-width:640px) { .arus-kas-item-modal__body { padding:28px; } }
    .arus-kas-item-modal__footer { border-top:1px solid #f3f4f6; padding:20px 24px; }
    .arus-kas-field { width:100%; height:44px; border:1px solid #e5e7eb !important; border-radius:12px !important; background:#f9fafb !important; padding:0 14px !important; color:#1f2937; font-size:14px !important; box-shadow:none !important; }
    .arus-kas-field:focus { border-color:#34a684 !important; outline:0 !important; }
    .arus-kas-category { display:flex; height:48px; align-items:center; gap:11px; border:1px solid #e5e7eb; border-radius:16px; padding:8px 12px; color:#374151; font-size:11px; font-weight:700; }
    .arus-kas-category.is-active-in { border-color:#34d399; background:rgba(236,253,245,.7); box-shadow:0 0 0 2px rgba(16,185,129,.08); }
    .arus-kas-category.is-active-out { border-color:#fb7185; background:rgba(255,241,242,.7); box-shadow:0 0 0 2px rgba(244,63,94,.07); }
    .arus-kas-category-icon { display:flex; width:28px; height:28px; flex:none; align-items:center; justify-content:center; border-radius:10px; background:#e5e7eb; color:#6b7280; }
    .arus-kas-category.is-active-in .arus-kas-category-icon { background:#10b981; color:#fff; }
    .arus-kas-category.is-active-out .arus-kas-category-icon { background:#f43f5e; color:#fff; }
    .arus-kas-item-modal > header.px-6.py-5 { padding:20px 24px; }
    .arus-kas-item-modal > header h2.text-base { font-size:16px; line-height:20px; }
    .arus-kas-item-modal > header p.mt-1.text-xs { margin-top:4px; font-size:12px; line-height:16px; }
    .arus-kas-item-modal > header > button.h-8.w-8 { width:32px; height:32px; }
    .arus-kas-item-modal > header > button i { font-size:14px; }
    .arus-kas-item-modal__body > :not([hidden]) ~ :not([hidden]) { margin-top:20px; }
    .arus-kas-item-modal__body fieldset > legend.mb-2.text-xs.font-semibold { margin-bottom:8px; font-size:12px; line-height:16px; }
    .arus-kas-item-modal__body fieldset > .grid { gap:12px; }
    .arus-kas-item-modal__body label.block.text-xs.font-semibold { font-size:12px; line-height:16px; }
    .arus-kas-item-modal__body label > .arus-kas-field.mt-2 { margin-top:8px; }
    .arus-kas-item-modal__body > .grid { gap:16px; }
    .arus-kas-category { gap:12px; border-radius:16px; padding:8px 14px; font-size:12px; line-height:16px; }
    .arus-kas-category-icon { border-radius:12px; }
    .arus-kas-category svg.h-4.w-4 { width:16px; height:16px; }
    .arus-kas-item-modal__footer button.h-12 { height:48px; border-radius:12px; font-size:12px; line-height:16px; }
    .arus-kas-item-modal__footer button.px-5 { padding:0 20px; }
    .arus-kas-item-modal__footer button.px-4 { padding:0 16px; }
    .arus-kas-item-modal__footer button i { margin-right:4px; font-size:14px; }
    @media (max-width:767px) {
        body:has(#arus-kas-index-page) #kt_content { padding:0 !important; }
        body:has(#arus-kas-form-page) #kt_content { display:flex; overflow:hidden; padding:0 !important; }
        body:has(#arus-kas-form-page) #kt_post,
        body:has(#arus-kas-form-page) #kt_content_container { display:flex; min-height:0; flex:1; flex-direction:column; }
        body:has(#arus-kas-form-page) .erp-mobile-nav { display:none; }
        .arus-kas-list-page { min-height:100%; background:#fff; }
        .arus-kas-row { padding:12px 16px; transition:background-color .15s ease; }
        .arus-kas-row:hover { background:rgba(249,250,251,.5); }
        .arus-kas-row:hover .arus-kas-row__title { color:#0b595b; }
        .arus-kas-row__icon { box-shadow:0 1px 2px 0 rgba(0,0,0,.05); }
        .arus-kas-row__title { line-height:16px; transition:color .15s ease; }
        .arus-kas-row__date { font-size:11px; line-height:16px; }
        .arus-kas-row__status { width:100%; text-align:right; font-size:12px; font-weight:600; line-height:16px; }
        .arus-kas-row__total { margin-top:2px; font-size:12px; font-weight:700; line-height:16px; }
        #arus-kas-detail-dialog { padding-right:16px; padding-left:16px; }
        #arus-kas-detail-dialog > section { width:384px; max-width:calc(100vw - 32px); max-height:calc(100dvh - 32px); border-radius:16px; }
        #arus-kas-detail-dialog > section > div.p-6 { padding:24px; font-size:16px; line-height:24px; }
        #arus-kas-detail-dialog > section > div.p-6 > div.mb-6 { margin-bottom:24px; }
        #arus-kas-detail-dialog #arus-kas-detail-title { font-size:18px; line-height:28px; }
        #arus-kas-detail-dialog button[aria-label="Tutup detail arus kas"] { width:32px; height:32px; padding:0; font-size:16px; }
        #arus-kas-detail-dialog .space-y-4 { display:flex; flex-direction:column; gap:20px; }
        #arus-kas-detail-dialog .space-y-4 > :not([hidden]) ~ :not([hidden]) { margin-top:0; }
        #arus-kas-detail-dialog .space-y-4 > .flex.items-center.gap-3 { gap:12px; }
        #arus-kas-detail-dialog .space-y-4 > .flex.items-center.gap-3 > span.h-10.w-10 { width:40px; height:40px; border-radius:12px; box-shadow:0 1px 2px 0 rgba(0,0,0,.05); }
        #arus-kas-detail-dialog .space-y-4 .text-sm { font-size:14px; line-height:20px; }
        #arus-kas-detail-dialog .space-y-4 [class~="text-[11px]"] { font-size:11px; line-height:16px; }
        #arus-kas-detail-dialog .space-y-4 .text-lg { font-size:18px; line-height:24px; }
        #arus-kas-detail-dialog > section > div.flex.w-full.border-t > button { gap:8px; font-size:14px; font-weight:600; line-height:20px; }
        .arus-kas-form-page { min-height:0; flex:1; }
        .arus-kas-invoice-modal { max-height:calc(100dvh - 88px); }
    }
    @media (min-width:768px) {
        .arus-kas-page { padding:0 8px 24px; }
        .arus-kas-page .erp-workspace-heading { display:flex; }
        .arus-kas-mobile-search { display:none; }
        .arus-kas-list-page { border-radius:18px; background:#fff; padding:20px; }
        .arus-kas-form-page { min-height:calc(100dvh - 160px); border-radius:18px; }
        .arus-kas-form-scroll { padding:24px; }
        .arus-kas-items-card { min-height:480px; }
        .arus-kas-form-footer { padding:14px 24px; }
        .arus-kas-row { border:0; }
    }
    @media print {
        body * { visibility:hidden !important; }
        #arus-kas-printable, #arus-kas-printable * { visibility:visible !important; }
        #arus-kas-printable { position:absolute; inset:0; width:100%; max-height:none; overflow:visible; box-shadow:none; }
        #arus-kas-printable button { display:none !important; }
    }
</style>

@if ($pageMode === 'list')
<section id="arus-kas-index-page" class="arus-kas-page arus-kas-list-page" aria-label="Arus Kas"
    x-data="arusKasApp({ orders: @js($orders), branches: @js($branchOptions), formOrder: @js($formOrder), selectedBranchId: @js((string) ($selectedBranchId ?? '')), pageMode: @js($pageMode), baseUrl: @js(url('/arus-kas')) })">
    <x-erp.mobile-search class="arus-kas-mobile-search">
        <x-slot:input>
            <input type="search" x-model.debounce.200ms="searchQuery" @input="visibleCount = pageSize" aria-label="Cari transaksi arus kas" placeholder="Cari..." class="erp-mobile-search__input">
        </x-slot:input>
        <x-slot:clear>
            <button type="button" x-show="searchQuery" x-cloak @click="searchQuery = ''; visibleCount = pageSize" aria-label="Hapus pencarian" class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
        </x-slot:clear>
        <x-slot:filter>
            <button type="button" @click="openFilterModal()" :class="(filterOpen || hasActiveFilter) ? 'is-active' : ''" :aria-pressed="hasActiveFilter" aria-label="Filter Arus Kas" aria-haspopup="dialog" class="erp-mobile-search__filter">
                <i class="ph ph-sliders-horizontal text-xl" :class="(filterOpen || hasActiveFilter) ? 'ph-bold' : ''" aria-hidden="true"></i>
                <span x-show="hasActiveFilter" x-cloak class="erp-mobile-search__filter-dot"></span>
            </button>
        </x-slot:filter>
    </x-erp.mobile-search>

    <div class="flex items-center justify-between gap-3 px-4 pb-3 sm:px-6 sm:pt-2">
        <div class="hidden min-w-0 flex-1 sm:block">
            <h1 class="text-base font-bold leading-none text-gray-900">Arus Kas</h1>
            <p class="mt-1 text-[11px] text-gray-400">Keuangan <span class="px-1">/</span> Arus Kas</p>
        </div>
        <p class="hidden text-xs text-gray-500 md:block" x-text="filteredOrders.length + ' transaksi'"></p>
        @if (check_access('expenditure.create'))
            <a href="{{ route('arus-kas.create') }}" class="hidden h-11 items-center gap-2 rounded-full bg-[#0b595b] px-5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#0a4e50] sm:inline-flex">
                <i class="ph-bold ph-plus text-sm" aria-hidden="true"></i><span>Tambah Arus Kas</span>
            </a>
        @endif
    </div>

    <div class="arus-kas-list" id="arus-kas-scroll-container">
        <div x-show="filteredOrders.length > 0" class="hidden border-b border-gray-100 bg-gray-50 px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-gray-500 lg:grid lg:grid-cols-[minmax(180px,1.3fr)_minmax(130px,1fr)_minmax(140px,1fr)_minmax(160px,1fr)_120px] lg:gap-3">
            <span>Aktivitas Transaksi</span><span>No. Referensi</span><span>Petugas Arus Kas</span><span>Item &amp; Volume</span><span>Status &amp; Nominal</span>
        </div>
        <template x-for="(order, index) in visibleOrders" :key="order.id">
            <article class="arus-kas-row cursor-pointer lg:grid lg:grid-cols-[minmax(180px,1.3fr)_minmax(130px,1fr)_minmax(140px,1fr)_minmax(160px,1fr)_120px] lg:gap-3 lg:px-4" @click="openDetailModal(order)">
                <div class="flex min-w-0 flex-1 items-center gap-3 lg:min-w-0">
                    <span class="arus-kas-row__icon" :class="directionTileClass(order)" aria-hidden="true">
                        <svg x-show="order.status === 'Draft'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <svg x-show="order.status !== 'Draft' && order.flowType === 'in'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="7" x2="7" y2="17"></line><polyline points="17 17 7 17 7 7"></polyline></svg>
                        <svg x-show="order.status !== 'Draft' && order.flowType !== 'in'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                    </span>
                    <div class="arus-kas-row__copy">
                        <p class="arus-kas-row__title" x-text="order.activity"></p>
                        <p class="arus-kas-row__date" x-text="formatDateTime(order.date, order.time)"></p>
                    </div>
                </div>
                <span class="hidden truncate text-xs font-semibold text-gray-700 lg:block" x-text="order.poNumber"></span>
                <span class="hidden truncate text-xs text-gray-600 lg:block" x-text="order.staffName"></span>
                <span class="hidden truncate text-xs text-gray-600 lg:block" x-text="itemSummary(order.items)"></span>
                <div class="arus-kas-row__meta">
                    <button type="button" class="arus-kas-row__status" :class="statusClass(order.status)" @click.stop="openInvoiceModal(order)" x-text="order.status" :aria-label="'Lihat alur status ' + order.status"></button>
                    <span class="arus-kas-row__total" x-text="formatRupiah(order.grandTotal)"></span>
                </div>
            </article>
        </template>

        <div x-show="filteredOrders.length === 0" x-cloak class="arus-kas-empty">
            <div class="arus-kas-empty__icon"><i class="ph-duotone ph-receipt" aria-hidden="true"></i></div>
            <h2 class="mt-4 text-sm font-bold text-gray-800 sm:text-base">Belum ada transaksi arus kas</h2>
            <p class="mt-2 max-w-xs text-xs leading-relaxed text-gray-500 sm:text-[13px]">
                Klik tombol <a href="{{ route('arus-kas.create') }}" class="mx-1 inline-flex h-6 w-6 items-center justify-center rounded-lg border border-emerald-200/80 bg-emerald-50 text-[#0b595b] align-middle" title="Tambah Arus Kas" aria-label="Tambah Arus Kas"><i class="ph-bold ph-plus text-xs" aria-hidden="true"></i></a> untuk menambahkan<br>transaksi arus kas baru
            </p>
            <button type="button" x-show="hasActiveFilter || searchQuery.trim() !== ''" x-cloak @click="resetFilters()" class="mt-4 inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-600 shadow-sm">
                <i class="ph-bold ph-arrow-counter-clockwise" aria-hidden="true"></i>Reset Pencarian &amp; Filter
            </button>
        </div>

        <div x-show="hasMore" x-cloak class="flex justify-center px-4 py-5 pb-28 lg:pb-5">
            <button type="button" @click="loadMore()" class="rounded-full border border-gray-200 bg-white px-5 py-2.5 text-xs font-semibold text-gray-600 shadow-sm">Muat lebih banyak <span class="ml-1 text-gray-400" x-text="'(' + (filteredOrders.length - visibleCount) + ' tersisa)'"></span></button>
        </div>
    </div>

    <div x-show="filterOpen" x-cloak @keydown.escape.window="filterOpen = false" class="sm:hidden">
        <div class="arus-kas-sheet-backdrop" @click="filterOpen = false"></div>
        <section class="arus-kas-filter-sheet" role="dialog" aria-modal="true" aria-labelledby="arus-kas-filter-title" @click.stop>
            <div class="arus-kas-sheet-handle" aria-hidden="true"></div>
            <x-erp.filter-sheet-frame title="Filter Arus Kas" title-id="arus-kas-filter-title" subtitle="Saring cabang dan status arus kas" body-class="arus-kas-filter-scroll">
                <x-slot:close><button type="button" @click="filterOpen = false" aria-label="Tutup filter"><i class="ph-bold ph-x" aria-hidden="true"></i></button></x-slot:close>
                <x-slot:body>

                <section>
                    <h3 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-500">Cabang Tujuan</h3>
                    <label class="flex h-11 items-center gap-2 rounded-t-lg border border-gray-200 px-3 text-gray-400"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" x-model="branchSearchQuery" placeholder="Cari cabang..." class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm outline-none focus:ring-0"></label>
                    <div class="arus-kas-radio-list rounded-t-none">
                        <label class="arus-kas-radio-row"><input type="radio" name="mobileArusKasCabangFilter" value="all" x-model="tempSelectedBranch"><span class="font-semibold text-gray-800">Semua Cabang</span></label>
                        <template x-for="branch in filteredBranches" :key="branch.id"><label class="arus-kas-radio-row"><input type="radio" name="mobileArusKasCabangFilter" :value="String(branch.id)" x-model="tempSelectedBranch"><span x-text="branch.name"></span></label></template>
                        <p x-show="filteredBranches.length === 0" x-cloak class="px-3 py-4 text-center text-xs text-gray-400">Cabang tidak ditemukan</p>
                    </div>
                </section>
                <section class="mt-5">
                    <h3 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-gray-500">Status Arus Kas</h3>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Status Arus Kas">
                        <template x-for="status in statusOptions" :key="status"><button type="button" @click="tempFilterStatus = status" :aria-pressed="tempFilterStatus === status" :class="tempFilterStatus === status ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-gray-200 bg-white text-gray-600'" class="rounded-full border px-3 py-2 text-xs font-semibold" x-text="status === 'Semua' ? 'Semua Status' : status"></button></template>
                    </div>
                </section>
                </x-slot:body>
                <x-slot:footer>
                    <button type="button" @click="resetMobileFilters()">Reset</button>
                    <button type="button" class="erp-filter-sheet__primary" @click="applyMobileFilters()">Terapkan</button>
                </x-slot:footer>
            </x-erp.filter-sheet-frame>
        </section>
    </div>

    <x-erp.detail-dialog open="detailModalOpen" close="closeDetailModal()" title="Detail Arus Kas" labelledby="arus-kas-detail-title" closeLabel="Tutup detail arus kas" id="arus-kas-detail-dialog">
        <template x-if="selectedOrder"><div class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl border" :class="directionTileClass(selectedOrder)">
                    <svg x-show="selectedOrder.status === 'Draft'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <svg x-show="selectedOrder.status !== 'Draft' && selectedOrder.flowType === 'in'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="7" x2="7" y2="17"></line><polyline points="17 17 7 17 7 7"></polyline></svg>
                    <svg x-show="selectedOrder.status !== 'Draft' && selectedOrder.flowType !== 'in'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                </span>
                <div class="min-w-0"><h3 class="truncate text-sm font-bold text-gray-900" x-text="selectedOrder.activity"></h3><button type="button" @click="openInvoiceModal(selectedOrder)" class="mt-1 text-left text-[11px] font-medium text-gray-400" x-text="selectedOrder.poNumber" aria-label="Lihat alur status transaksi"></button></div>
            </div>
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl border border-emerald-200/80 bg-emerald-50/80 text-[#0b595b]"><i class="ph-duotone ph-coins text-lg" aria-hidden="true"></i></span><div><p class="text-sm font-bold text-gray-900" x-text="formatRupiah(selectedOrder.grandTotal)"></p><p class="mt-0.5 text-[11px] text-gray-400">Total Nominal</p></div></div>
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl border border-emerald-200/80 bg-emerald-50/80 text-[#0b595b]"><i class="ph-duotone ph-storefront text-lg" aria-hidden="true"></i></span><div><p class="text-sm font-bold text-gray-900" x-text="selectedOrder.targetBranch"></p><p class="mt-0.5 text-[11px] text-gray-400">Cabang Toko</p></div></div>
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl border border-emerald-200/80 bg-emerald-50/80 text-[#0b595b]"><i class="ph-duotone ph-clock text-lg" aria-hidden="true"></i></span><div><p class="text-sm font-bold text-gray-900" x-text="selectedOrder.staffName"></p><p class="mt-0.5 text-[11px] text-gray-400" x-text="'Dibuat pada ' + formatDateTime(selectedOrder.date, selectedOrder.time)"></p></div></div>
        </div></template>
        <x-slot:actions>
            <button type="button" @click="editSelectedOrder()" :disabled="!selectedOrder?.canEdit" class="flex h-[52px] flex-1 items-center justify-center gap-2 border-r border-gray-100 text-sm font-bold text-[#0b595b] disabled:cursor-not-allowed disabled:text-gray-300"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i>Edit</button>
            <button type="button" @click="deleteSelectedOrder()" :disabled="!selectedOrder?.canDelete" class="flex h-[52px] flex-1 items-center justify-center gap-2 text-sm font-bold text-rose-500 disabled:cursor-not-allowed disabled:text-gray-300"><i class="ph-bold ph-trash" aria-hidden="true"></i>Hapus</button>
        </x-slot:actions>
    </x-erp.detail-dialog>

    <div x-show="invoiceModalOpen" x-cloak @keydown.escape.window="invoiceModalOpen = false" class="fixed inset-0 z-[130]" role="dialog" aria-modal="true" aria-labelledby="arus-kas-invoice-title">
        <div class="arus-kas-modal-backdrop" @click="invoiceModalOpen = false"></div>
        <section id="arus-kas-printable" class="arus-kas-invoice-modal" @click.stop>
            <header class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-emerald-50 text-[#0b595b]"><i class="ph-bold ph-receipt text-lg" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1"><h2 id="arus-kas-invoice-title" class="break-words text-sm font-extrabold text-gray-900" x-text="selectedOrder?.poNumber"></h2><p class="mt-0.5 text-[11px] text-gray-500" x-text="'Oleh: ' + (selectedOrder?.staffName || '—')"></p><p class="text-[10px] text-gray-400" x-text="formatDateTime(selectedOrder?.date, selectedOrder?.time)"></p></div>
                <span class="rounded-full border border-emerald-100 bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700" :class="selectedOrder?.status === 'Draft' ? 'border-gray-200 bg-gray-50 text-gray-500' : ''" x-text="selectedOrder?.status"></span>
                <button type="button" @click="printOrder()" class="rounded-xl border border-gray-200 px-2.5 py-2 text-[11px] font-bold text-gray-700"><i class="ph-bold ph-printer mr-1" aria-hidden="true"></i>Cetak PO</button>
                <button type="button" @click="invoiceModalOpen = false" aria-label="Tutup" class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </header>
            <div class="arus-kas-invoice-scroll" x-show="selectedOrder">
                <section class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                    <h3 class="mb-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">Alur Status Arus Kas</h3>
                    <div class="grid grid-cols-2">
                        <div class="flex flex-col items-center gap-1"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-700 text-xs font-bold text-white">1</span><span class="text-[11px] font-semibold text-gray-700">Draft</span></div>
                        <div class="flex flex-col items-center gap-1"><span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold text-white" :class="selectedOrder?.status === 'Selesai' ? 'bg-emerald-600' : 'bg-gray-300'">2</span><span class="text-[11px] font-semibold text-gray-700">Selesai</span></div>
                    </div>
                </section>
                <section class="mt-4 rounded-2xl border border-gray-100 p-3.5"><h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Petugas Arus Kas</h3><p class="text-sm font-bold text-gray-900" x-text="selectedOrder?.staffName"></p><p class="mt-0.5 text-xs text-gray-500" x-text="'Waktu: ' + (selectedOrder?.time || '—')"></p><p class="text-xs text-gray-400" x-text="'Tanggal: ' + formatDate(selectedOrder?.date)"></p></section>
                <section class="mt-3 rounded-2xl border border-gray-100 p-3.5"><h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Informasi Pemasok</h3><p class="text-sm font-bold text-gray-900" x-text="selectedOrder?.supplierName || 'Belum tersedia'"></p><p class="mt-0.5 text-xs text-gray-500" x-text="'Telp: ' + (selectedOrder?.supplierPhone || '—')"></p><p class="text-xs text-gray-400" x-text="'Lokasi: ' + (selectedOrder?.supplierCity || '—')"></p></section>
                <section class="mt-3 rounded-2xl border border-gray-100 p-3.5"><h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Tujuan &amp; Pembayaran</h3><p class="text-sm font-bold text-gray-900" x-text="selectedOrder?.targetBranch"></p><p class="mt-0.5 text-xs text-gray-500" x-text="'Estimasi: ' + formatDate(selectedOrder?.etaDate)"></p><p class="text-xs font-semibold text-emerald-700" x-text="'Status Bayar: ' + (selectedOrder?.paymentStatus || 'Belum Bayar')"></p></section>
                <section class="mt-5">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-900">Rincian Item Kas</h3>
                    <div class="overflow-hidden rounded-2xl border border-gray-100">
                        <table class="w-full text-left text-[10px]">
                            <thead class="bg-gray-50 text-[9px] font-bold uppercase text-gray-500"><tr><th class="px-2 py-3">No</th><th class="px-2 py-3">Deskripsi Item / Kebutuhan</th><th class="px-2 py-3">Satuan</th><th class="px-2 py-3">Kuantitas</th><th class="px-2 py-3">Harga Satuan</th><th class="px-2 py-3">Subtotal</th></tr></thead>
                            <tbody><template x-for="(item, idx) in (selectedOrder?.items || [])" :key="idx"><tr class="border-t border-gray-100"><td class="px-2 py-3" x-text="idx + 1"></td><td class="px-2 py-3 font-semibold text-gray-800" x-text="item.name"></td><td class="px-2 py-3" x-text="item.unit || '—'"></td><td class="px-2 py-3" x-text="formatQuantity(item.quantity)"></td><td class="px-2 py-3" x-text="formatRupiah(item.price)"></td><td class="px-2 py-3 font-semibold" x-text="formatRupiah(item.subtotal)"></td></tr></template></tbody>
                            <tfoot class="border-t border-gray-100 bg-gray-50"><tr><th colspan="5" class="px-3 py-2 text-right text-gray-500">Subtotal Item</th><td class="px-3 py-2 font-bold" x-text="formatRupiah(selectedOrder?.grandTotal)"></td></tr><tr><th colspan="5" class="px-3 py-2 text-right text-gray-500">Biaya Tambahan</th><td class="px-3 py-2 font-bold">Rp 0</td></tr><tr><th colspan="5" class="px-3 py-2 text-right text-gray-800">Total Transaksi Kas</th><td class="px-3 py-2 font-extrabold text-gray-900" x-text="formatRupiah(selectedOrder?.grandTotal)"></td></tr></tfoot>
                        </table>
                    </div>
                </section>
                <p x-show="selectedOrder?.source === 'legacy'" class="mt-4 rounded-xl bg-gray-50 px-3 py-2 text-[11px] text-gray-500">Data dari Pemasukan/Pengeluaran lama ditampilkan tanpa aksi ubah status.</p>
            </div>
            <footer class="flex shrink-0 flex-col gap-2 border-t border-gray-100 bg-white px-5 py-3">
                <div class="flex gap-2"><button type="button" @click="deleteSelectedOrder()" :disabled="!selectedOrder?.canDelete" class="flex-1 rounded-xl border border-rose-100 py-2.5 text-[11px] font-bold text-rose-600 disabled:text-gray-300" x-text="selectedOrder?.status === 'Draft' ? 'Hapus Draft' : 'Hapus Transaksi'"></button><button type="button" @click="editSelectedOrder()" :disabled="!selectedOrder?.canEdit" class="flex-1 rounded-xl border border-gray-200 py-2.5 text-[11px] font-bold text-gray-700 disabled:text-gray-300" x-text="selectedOrder?.status === 'Draft' ? 'Lanjut Edit Draft' : 'Edit Transaksi'"></button></div>
                <button type="button" @click="updateOrderStatus(selectedOrder?.status === 'Draft' ? 'completed' : 'draft')" :disabled="!selectedOrder?.canEdit || (selectedOrder?.status === 'Draft' && selectedOrder?.items?.length === 0)" class="rounded-xl bg-[#0b595b] py-2.5 text-[11px] font-bold text-white disabled:bg-gray-300" x-text="selectedOrder?.status === 'Draft' ? 'Posting ke Selesai' : 'Kembalikan ke Draft'"></button>
            </footer>
        </section>
    </div>
</section>
@else
<section id="arus-kas-form-page" class="arus-kas-page arus-kas-form-page" aria-label="{{ $pageMode === 'edit' ? 'Edit Arus Kas' : 'Tambah Arus Kas' }}"
    x-data="arusKasApp({ orders: [], branches: @js($branchOptions), formOrder: @js($formOrder), selectedBranchId: @js((string) ($selectedBranchId ?? '')), pageMode: @js($pageMode), baseUrl: @js(url('/arus-kas')) })">
    @if ($pageMode === 'edit')
        <form method="POST" action="{{ route('arus-kas.update', $formOrder['id']) }}" class="flex min-h-0 flex-1 flex-col" @submit="submitting = true">
            @method('PUT')
    @else
        <form method="POST" action="{{ route('arus-kas.store') }}" class="flex min-h-0 flex-1 flex-col" @submit="submitting = true">
    @endif
            @csrf
            <input type="hidden" name="items_json" :value="JSON.stringify(form.items)">
            <div class="arus-kas-form-scroll">
                @if ($errors->any())
                    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ $errors->first() }}</div>
                @endif
                <section class="arus-kas-form-branch">
                    <span id="arus-kas-branch-label" class="mb-1.5 block text-xs font-semibold text-gray-700">Cabang Toko <span class="text-rose-500">*</span></span>
                    <div class="relative" x-data="{ branchDropdownOpen: false, focusSelectedBranch() { this.$nextTick(() => { const selected = this.$refs.branchOptions.querySelector('[role=option][aria-selected=true]'); (selected || this.$refs.branchOptions.querySelector('[role=option]'))?.focus(); }); } }" @click.outside="branchDropdownOpen = false" @keydown.escape.window="if (branchDropdownOpen) { branchDropdownOpen = false; $refs.branchTrigger.focus(); }">
                        <select id="arus-kas-branch" name="branch_id" x-model="form.branchId" :disabled="branches.length === 0" tabindex="-1" aria-hidden="true" class="arus-kas-branch-native">
                            <option value="">Pilih Cabang</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch['id'] }}" {{ (string) (($formOrder['branchId'] ?? null) ?: ($selectedBranchId ?? '')) === $branch['id'] ? 'selected' : '' }}>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="arus-kas-branch-trigger" x-ref="branchTrigger" role="combobox" aria-haspopup="listbox" aria-controls="arus-kas-branch-options" aria-labelledby="arus-kas-branch-label arus-kas-branch-value" aria-required="true" :aria-expanded="branchDropdownOpen" :disabled="branches.length === 0" @click="branchDropdownOpen = !branchDropdownOpen" @keydown.arrow-down.prevent="branchDropdownOpen = true; focusSelectedBranch()" class="arus-kas-branch-select">
                            <span id="arus-kas-branch-value" class="truncate pr-2" :class="form.branchId ? 'arus-kas-branch-value--selected' : 'arus-kas-branch-value--placeholder'" x-text="(branches.find(branch => String(branch.id) === String(form.branchId)) || {}).name || 'Pilih Cabang'"></span>
                            <i class="ph-bold ph-caret-down arus-kas-branch-chevron" :class="branchDropdownOpen ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>
                        <div id="arus-kas-branch-options" x-ref="branchOptions" x-show="branchDropdownOpen" x-cloak role="listbox" aria-labelledby="arus-kas-branch-label" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="arus-kas-branch-options">
                            <template x-for="branch in branches" :key="branch.id">
                                <button type="button" role="option" :aria-selected="String(form.branchId) === String(branch.id)" @click="form.branchId = String(branch.id); branchDropdownOpen = false; $nextTick(() => $refs.branchTrigger.focus())" @keydown.arrow-down.prevent="if ($el.nextElementSibling) $el.nextElementSibling.focus()" @keydown.arrow-up.prevent="if ($el.previousElementSibling) $el.previousElementSibling.focus(); else $refs.branchTrigger.focus()" class="arus-kas-branch-option">
                                    <span x-text="branch.name"></span>
                                    <span x-show="String(form.branchId) === String(branch.id)" class="arus-kas-branch-option__mark" aria-hidden="true"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <p x-show="branches.length === 0" class="mt-2 text-[11px] text-rose-600">Tidak ada cabang yang tersedia untuk akun ini.</p>
                </section>

                <section class="arus-kas-items-card">
                    <header class="arus-kas-items-head"><h2 class="text-sm font-extrabold tracking-tight text-gray-900">Daftar Kebutuhan Kas</h2><button type="button" @click="openAddItemModal()" class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-3.5 py-2 text-[11px] font-bold text-[#0b595b]">+ Tambah</button></header>
                    <div class="arus-kas-items-body">
                        <div x-show="form.items.length === 0" class="flex h-full min-h-[280px] flex-col items-center justify-center px-5 text-center">
                            <p class="text-sm font-bold text-gray-800">Belum ada item kebutuhan kas</p>
                            <p class="mt-1 text-xs text-gray-500">Klik tombol <strong class="text-[#0b595b]">+ Tambah</strong> untuk melakukan input</p>
                        </div>
                        <template x-for="(item, index) in form.items" :key="index">
                            <button type="button" @click="openEditItemModal(index)" class="arus-kas-form-item w-full text-left">
                                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-gray-900" x-text="item.name"></span><span class="mt-1 block text-xs text-gray-500" x-text="'@' + formatQuantity(item.quantity) + ' x ' + formatNumber(item.price)"></span></span>
                                <span class="flex-none text-right text-xs font-extrabold" :class="item.type === 'in' ? 'text-emerald-600' : 'text-rose-600'" x-text="formatNumber(itemSubtotal(item))"></span>
                            </button>
                        </template>
                    </div>
                </section>
            </div>
            <footer class="arus-kas-form-footer">
                <button type="submit" name="status" value="draft" :disabled="submitting || !form.branchId" class="arus-kas-action-btn" x-text="pageMode === 'edit' ? 'Simpan Draft' : 'Draft'"></button>
                <button type="submit" name="status" value="completed" :disabled="submitting || !form.branchId || form.items.length === 0" class="arus-kas-action-btn arus-kas-action-btn--primary" x-text="pageMode === 'edit' ? 'Simpan Transaksi' : 'Tambah Transaksi'"></button>
            </footer>
        </form>

    <div x-show="itemModalOpen" x-cloak @keydown.escape.window="closeItemModal()" class="fixed inset-0 z-[140]" role="dialog" aria-modal="true" aria-labelledby="arus-kas-item-title">
        <div class="fixed inset-0 bg-black/45" @click="closeItemModal()"></div>
        <section class="arus-kas-item-modal" @click.stop>
            <header class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                <div><h2 id="arus-kas-item-title" class="text-base font-bold leading-tight text-gray-900" x-text="modalMode === 'add' ? 'Tambah Item Kas' : 'Edit Item Kas'"></h2><p class="mt-1 text-xs text-gray-500" x-text="modalMode === 'add' ? 'Input kas masuk & keluar' : 'Edit kas masuk & keluar'"></p></div>
                <button type="button" @click="closeItemModal()" aria-label="Tutup" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </header>
            <div class="arus-kas-item-modal__body space-y-5">
                <fieldset>
                    <legend class="mb-2 text-xs font-semibold text-gray-700">Kategori Kas <span class="text-rose-500">*</span></legend>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="modalItem.type = 'in'" :class="modalItem.type === 'in' ? 'is-active-in' : ''" class="arus-kas-category"><span class="arus-kas-category-icon"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="7" x2="7" y2="17"></line><polyline points="17 17 7 17 7 7"></polyline></svg></span>Kas Masuk</button>
                        <button type="button" @click="modalItem.type = 'out'" :class="modalItem.type === 'out' ? 'is-active-out' : ''" class="arus-kas-category"><span class="arus-kas-category-icon"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg></span>Kas Keluar</button>
                    </div>
                </fieldset>
                <label class="block text-xs font-semibold text-gray-700">Keterangan <span class="text-rose-500">*</span><input type="text" x-model="modalItem.name" maxlength="255" placeholder="Contoh: Bensin" class="arus-kas-field mt-2" autocomplete="off"></label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="block text-xs font-semibold text-gray-700">Kuantitas <span class="text-rose-500">*</span><input type="number" min="0.01" step="0.01" inputmode="decimal" x-model.number="modalItem.quantity" placeholder="0" class="arus-kas-field mt-2"></label>
                    <label class="block text-xs font-semibold text-gray-700">Biaya / Harga Satuan <span class="text-rose-500">*</span><input type="number" min="0" step="1" inputmode="numeric" x-model.number="modalItem.price" placeholder="0" class="arus-kas-field mt-2"></label>
                </div>
            </div>
            <footer class="arus-kas-item-modal__footer">
                <div x-show="modalMode === 'add'"><button type="button" @click="saveModalItem()" class="flex h-12 w-full items-center justify-between rounded-xl bg-[#0b595b] px-5 text-xs font-bold text-white shadow-md"><span>Tambah</span><span x-text="formatRupiah(itemSubtotal(modalItem))"></span></button></div>
                <div x-show="modalMode === 'edit'" class="flex gap-3"><button type="button" @click="deleteEditingItem()" class="h-12 rounded-xl border border-rose-100 px-4 text-xs font-bold text-rose-600"><i class="ph-bold ph-trash mr-1" aria-hidden="true"></i>Hapus</button><button type="button" @click="saveModalItem()" class="flex h-12 flex-1 items-center justify-between rounded-xl bg-[#0b595b] px-5 text-xs font-bold text-white shadow-md"><span>Simpan</span><span x-text="formatRupiah(itemSubtotal(modalItem))"></span></button></div>
            </footer>
        </section>
    </div>
</section>
@endif
@endsection

@section('script')
<script>
function arusKasApp(config) {
    const initialOrder = config.formOrder || {};
    const initialItems = Array.isArray(initialOrder.items) ? initialOrder.items.map(item => ({
        type: item.type || 'out', name: item.name || '', unit: item.unit || '',
        quantity: Number(item.quantity || 0), price: Number(item.price || 0),
        subtotal: Number(item.subtotal || (Number(item.quantity || 0) * Number(item.price || 0)))
    })) : [];

    return {
        orders: Array.isArray(config.orders) ? config.orders : [],
        branches: Array.isArray(config.branches) ? config.branches : [],
        pageMode: config.pageMode || 'list',
        baseUrl: config.baseUrl || '/arus-kas',
        pageSize: 50,
        visibleCount: 50,
        searchQuery: '',
        filterOpen: false,
        filterStatus: 'Semua',
        tempFilterStatus: 'Semua',
        selectedBranch: 'all',
        tempSelectedBranch: 'all',
        branchSearchQuery: '',
        detailModalOpen: false,
        invoiceModalOpen: false,
        itemModalOpen: false,
        selectedOrder: null,
        modalMode: 'add',
        editingIndex: null,
        submitting: false,
        form: {
            branchId: String(initialOrder.branchId || config.selectedBranchId || ''),
            items: initialItems
        },
        modalItem: { type: 'in', name: '', unit: '', quantity: '', price: '' },
        get statusOptions() { return ['Semua', 'Draft', 'Selesai']; },
        get filteredBranches() {
            const query = this.branchSearchQuery.trim().toLocaleLowerCase('id-ID');
            return this.branches.filter(branch => !query || branch.name.toLocaleLowerCase('id-ID').includes(query));
        },
        get hasActiveFilter() { return this.filterStatus !== 'Semua' || this.selectedBranch !== 'all'; },
        get filteredOrders() {
            const query = this.searchQuery.trim().toLocaleLowerCase('id-ID');
            return this.orders.filter(order => {
                const matchesStatus = this.filterStatus === 'Semua' || order.status === this.filterStatus;
                const matchesBranch = this.selectedBranch === 'all' || String(order.branchId) === this.selectedBranch;
                const searchable = [order.activity, order.staffName, order.poNumber, order.supplierName, order.targetBranch, ...(order.items || []).map(item => item.name)].join(' ').toLocaleLowerCase('id-ID');
                return matchesStatus && matchesBranch && (!query || searchable.includes(query));
            });
        },
        get visibleOrders() { return this.filteredOrders.slice(0, this.visibleCount); },
        get hasMore() { return this.visibleCount < this.filteredOrders.length; },
        get grandTotal() { return this.form.items.reduce((total, item) => total + this.itemSubtotal(item), 0); },
        openFilterModal() {
            this.tempFilterStatus = this.filterStatus;
            this.tempSelectedBranch = this.selectedBranch;
            this.branchSearchQuery = '';
            this.filterOpen = true;
        },
        applyMobileFilters() {
            this.filterStatus = this.tempFilterStatus;
            this.selectedBranch = this.tempSelectedBranch;
            this.filterOpen = false;
            this.visibleCount = this.pageSize;
        },
        resetMobileFilters() {
            this.tempFilterStatus = 'Semua';
            this.tempSelectedBranch = 'all';
            this.branchSearchQuery = '';
        },
        resetFilters() {
            this.searchQuery = '';
            this.filterStatus = 'Semua';
            this.tempFilterStatus = 'Semua';
            this.selectedBranch = 'all';
            this.tempSelectedBranch = 'all';
            this.branchSearchQuery = '';
            this.visibleCount = this.pageSize;
        },
        loadMore() { this.visibleCount += this.pageSize; },
        openDetailModal(order) { this.selectedOrder = order; this.detailModalOpen = true; },
        closeDetailModal() { this.detailModalOpen = false; this.selectedOrder = null; },
        openInvoiceModal(order) {
            this.selectedOrder = order;
            this.detailModalOpen = false;
            this.invoiceModalOpen = true;
        },
        directionTileClass(order) {
            if (order?.status === 'Draft') return 'border-gray-200 bg-gray-100 text-gray-400';
            return order?.flowType === 'in' ? 'border-emerald-200/80 bg-emerald-50 text-emerald-600' : 'border-rose-200/80 bg-rose-50 text-rose-600';
        },
        statusClass(status) {
            return status === 'Draft' ? 'text-gray-400' : (status === 'Dibatalkan' ? 'text-rose-500' : 'text-emerald-700');
        },
        itemSummary(items) { return (items || []).map(item => item.name).join(', ') || 'Belum ada item'; },
        itemSubtotal(item) { return Number(item?.quantity || 0) * Number(item?.price || 0); },
        formatNumber(value) { return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); },
        formatQuantity(value) { return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); },
        formatRupiah(value) { return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 }); },
        formatDate(value) {
            if (!value) return '—';
            const date = new Date(value + 'T00:00:00');
            return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
        },
        formatDateTime(date, time) { return this.formatDate(date) + (time ? ', ' + time : ''); },
        openAddItemModal() {
            this.modalMode = 'add';
            this.editingIndex = null;
            this.modalItem = { type: 'in', name: '', unit: '', quantity: '', price: '' };
            this.itemModalOpen = true;
        },
        openEditItemModal(index) {
            const item = this.form.items[index];
            if (!item) return;
            this.modalMode = 'edit';
            this.editingIndex = index;
            this.modalItem = { ...item };
            this.itemModalOpen = true;
        },
        closeItemModal() { this.itemModalOpen = false; this.editingIndex = null; },
        saveModalItem() {
            const item = {
                type: this.modalItem.type,
                name: String(this.modalItem.name || '').trim(),
                unit: String(this.modalItem.unit || '').trim(),
                quantity: Number(this.modalItem.quantity),
                price: Number(this.modalItem.price)
            };
            if (!item.name) { window.alert('Silakan masukkan keterangan terlebih dahulu'); return; }
            if (!Number.isFinite(item.quantity) || item.quantity <= 0) { window.alert('Silakan masukkan kuantitas'); return; }
            if (this.modalItem.price === '' || this.modalItem.price === null || this.modalItem.price === undefined || !Number.isFinite(item.price) || item.price < 0) { window.alert('Silakan masukkan biaya / harga satuan'); return; }
            item.subtotal = this.itemSubtotal(item);
            if (this.modalMode === 'edit' && this.editingIndex !== null) this.form.items.splice(this.editingIndex, 1, item);
            else this.form.items.push(item);
            this.closeItemModal();
        },
        deleteEditingItem() {
            if (this.editingIndex === null) return;
            this.form.items.splice(this.editingIndex, 1);
            this.closeItemModal();
        },
        editSelectedOrder() {
            if (!this.selectedOrder?.canEdit) return;
            window.location.href = this.baseUrl + '/' + encodeURIComponent(this.selectedOrder.id) + '/edit';
        },
        async deleteSelectedOrder() {
            const order = this.selectedOrder;
            if (!order?.canDelete || !window.confirm('Hapus transaksi arus kas ini?')) return;
            try {
                const response = await fetch(this.baseUrl + '/' + encodeURIComponent(order.id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' }
                });
                const result = await response.json().catch(() => null);
                if (!response.ok || result?.success !== true) throw new Error('Transaksi gagal dihapus.');
                this.orders = this.orders.filter(item => String(item.id) !== String(order.id));
                this.detailModalOpen = false;
                this.invoiceModalOpen = false;
                this.selectedOrder = null;
            } catch (error) { window.alert(error.message || 'Transaksi gagal dihapus.'); }
        },
        async updateOrderStatus(status) {
            const order = this.selectedOrder;
            if (!order?.canEdit) return;
            try {
                const response = await fetch(this.baseUrl + '/' + encodeURIComponent(order.id) + '/status', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                    body: JSON.stringify({ status })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Status transaksi gagal diperbarui.');
                order.status = status === 'draft' ? 'Draft' : 'Selesai';
                order.paymentStatus = status === 'draft' ? 'Belum Bayar' : 'Lunas';
                this.invoiceModalOpen = true;
            } catch (error) { window.alert(error.message || 'Status transaksi gagal diperbarui.'); }
        },
        printOrder() { window.print(); }
    };
}
</script>
@endsection
