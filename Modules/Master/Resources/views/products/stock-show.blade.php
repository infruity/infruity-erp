@extends('layouts.erp')

@section('title', $data->name . ' - Stok Barang')
@section('page-title', $data->name)
@section('page-breadcrumb')
    <span>Inventaris</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <a href="{{ route('product-stock') }}">Stok Barang</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Riwayat Pergerakan</span>
@endsection

@push('styles')
    @vite('resources/css/flatpickr.css')
@endpush

@section('content')
    {{-- @livewire('product-table') --}}
    <style>
        .product-stock-detail-page { display:flex; min-height:0; flex:1; flex-direction:column; }
        .product-stock-detail-card { background:#fff; border-radius:12px; }
        .stock-detail-card-header { display:flex; flex-direction:column; align-items:stretch; }
        .stock-detail-view-header { display:flex; flex:none; align-items:center; justify-content:space-between; gap:16px; padding:16px 20px 12px; border-bottom:1px solid #f3f4f6; background:#fff; }
        .stock-detail-view-title { min-width:0; }
        .stock-detail-view-title p { margin:0 0 3px; color:#6b7280; font-size:12px; font-weight:500; line-height:1.2; }
        .stock-detail-view-title h2 { overflow:hidden; margin:0; color:#111827; font-size:14px; font-weight:700; line-height:1.3; text-overflow:ellipsis; white-space:nowrap; }
        .stock-detail-view-actions { display:flex; flex:none; align-items:center; gap:7px; }
        .stock-detail-view-tab { display:inline-flex; min-width:58px; min-height:32px; align-items:center; justify-content:center; padding:0 12px; border:1px solid #e5e7eb; border-radius:12px; background:#f3f4f6; color:#0b595b; font-size:12px; font-weight:700; text-decoration:none; }
        .stock-detail-view-tab:not(.is-active) { background:#fff; color:#6b7280; }
        .stock-detail-view-filter { position:relative; display:grid; width:32px; height:32px; place-items:center; border:1px solid #e5e7eb; border-radius:12px; background:#f3f4f6; color:#6b7280; }
        .stock-detail-view-filter.is-active { border-color:rgba(11,89,91,.3); background:rgba(11,89,91,.1); color:#0b595b; }
        .stock-detail-view-filter .erp-mobile-search__filter-dot { position:absolute; top:2px; right:2px; }
        .stock-detail-search-filter { min-width:220px; }
        .stock-detail-search-filter .erp-input { height:40px; border:1px solid #e5e7eb; border-radius:9px; background:#fff; font-size:13px; }
        .stock-detail-table-heading { display:flex; flex:none; align-items:center; justify-content:space-between; gap:10px; margin:0 20px 8px; padding:9px 10px; border:1px solid #f1f3f5; border-radius:9px; background:rgba(249,250,251,.8); color:#9ca3af; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .stock-detail-table-heading__numbers { display:flex; flex:none; align-items:center; gap:8px; text-align:right; }
        .stock-detail-table-heading__numbers span { width:72px; text-align:right; }
        .stock-detail-table-heading__numbers span:nth-child(2) { width:76px; }
        .stock-detail-table-heading__numbers span:last-child { width:64px; }
        .stock-detail-history-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:11px 10px; border-bottom:1px solid #f3f4f6; border-radius:9px; font-family:'DM Sans','Plus Jakarta Sans',sans-serif; }
        .stock-detail-history-main { display:flex; min-width:0; flex:1; flex-direction:column; gap:5px; }
        .stock-detail-history-activity { display:flex; min-width:0; align-items:center; gap:6px; color:#111827; font-size:13px; font-weight:700; line-height:1.2; text-decoration:none; }
        .stock-detail-history-activity:hover { color:#0b595b; text-decoration:underline; }
        .stock-detail-history-activity > span:first-child { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .stock-detail-history-direction { display:grid; width:16px; height:16px; flex:none; place-items:center; border-radius:4px; font-size:10px; }
        .stock-detail-history-direction.is-in { background:#dcfce7; color:#16a34a; }
        .stock-detail-history-direction.is-out { background:#fee2e2; color:#ef4444; }
        .stock-detail-history-user { overflow:hidden; color:#374151; font-size:11px; font-weight:500; text-overflow:ellipsis; white-space:nowrap; }
        .stock-detail-history-date { color:#9ca3af; font-size:10.5px; line-height:1.2; }
        .stock-detail-history-values { display:flex; flex:none; align-items:center; gap:8px; padding-top:2px; text-align:right; font-variant-numeric:tabular-nums; }
        .stock-detail-history-value { width:72px; overflow:hidden; color:#4b5563; font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
        .stock-detail-history-value:nth-child(2) { width:76px; }
        .stock-detail-history-value:last-child { width:64px; color:#111827; font-weight:700; }
        .stock-detail-history-value.is-in { color:#047857; font-weight:700; }
        .stock-detail-history-value.is-out { color:#e11d48; font-weight:700; }
        .stock-detail-history-unit { color:#9ca3af; font-size:10px; font-weight:400; }
        .stock-detail-load-more { flex:none; padding:12px 20px 18px; }
        .stock-detail-load-more[hidden] { display:none!important; }
        .stock-detail-load-more button { width:100%; min-height:44px; border:2px dashed #e5e7eb; border-radius:12px; background:#fff; color:#6b7280; font-size:14px; font-weight:600; }
        .stock-detail-load-more button:hover { border-color:#34d399; color:#047857; background:#ecfdf5; }
        .stock-mobile-list { display:block; min-height:0; flex:1; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:none; background:#fff; }
        .stock-mobile-list::-webkit-scrollbar { display:none; }
        .product-stock-detail-card .stock-detail-card-body { display:flex; flex:1; min-height:0; flex-direction:column; overflow:hidden; padding:0!important; }
        #products-stock-table_wrapper { display:none!important; }
        .stock-mobile-empty { display:flex; min-height:220px; flex-direction:column; align-items:center; justify-content:center; padding:32px 24px; color:#6b7280; text-align:center; }
        .stock-mobile-empty i { margin-bottom:10px; color:#9ca3af; font-size:28px; }
        .stock-mobile-empty strong { margin-bottom:4px; color:#1f2937; font-size:14px; }
        .stock-mobile-empty span { font-size:12px; }
        @media (min-width:768px) { .stock-detail-load-more { display:flex; justify-content:center; } .stock-detail-load-more button { width:auto; padding:0 24px; border-width:1px; border-style:solid; border-radius:999px; } }
        @media (min-width:768px) {
            .product-stock-detail-card { position:relative; display:flex; min-height:0; flex:1; flex-direction:column; overflow:hidden; }
            .product-stock-detail-card .stock-detail-card-header { flex:none; padding:0; border:0; }
            .product-stock-detail-card .stock-detail-card-toolbar { position:absolute; top:58px; right:20px; z-index:20; display:none!important; width:min(420px,calc(100vw - 48px)); max-height:75vh; flex-direction:column; align-items:stretch; overflow:auto; padding:20px; border:1px solid #e5e7eb; border-radius:16px; background:#fff; box-shadow:0 18px 42px rgba(15,23,42,.16); }
            .product-stock-detail-card .stock-detail-card-toolbar.is-open { display:flex!important; }
            .product-stock-detail-card .stock-detail-filter-handle { display:none; }
            .product-stock-detail-card .stock-detail-filter-head { display:flex; align-items:center; justify-content:space-between; }
            .product-stock-detail-card .stock-detail-filter-close { display:grid; width:32px; height:32px; place-items:center; border:0; border-radius:50%; background:#f3f4f6; color:#6b7280; }
            .product-stock-detail-card .stock-detail-filter-fields { display:flex; flex-direction:column; gap:16px; }
            .product-stock-detail-card .stock-detail-filter-mobile-controls { display:block; }
            .product-stock-detail-card .stock-detail-filter-desktop-control { display:none!important; }
            .product-stock-detail-card .stock-detail-filter-field { margin:0; }
            .product-stock-detail-card .stock-detail-filter-label { display:block; margin-bottom:6px; color:#6b7280; font-size:11px; font-weight:700; }
            .product-stock-detail-card .stock-detail-filter-footer { display:flex; gap:8px; }
            .product-stock-detail-card .stock-detail-filter-footer button { flex:1; min-height:40px; border-radius:10px; font-size:13px; font-weight:600; }
            .product-stock-detail-card .stock-detail-filter-branch-options { max-height:180px; overflow:auto; }
            .stock-detail-table-heading { margin-top:14px; }
        }
        .stock-detail-date-range { display:grid; width:100%; min-width:0; grid-template-columns:repeat(2,minmax(0,1fr)); align-items:end; gap:8px; }
        .stock-detail-filter-heading { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:6px; }
        .stock-detail-filter-heading .stock-detail-filter-label { margin-bottom:0; }
        .stock-detail-date-item { display:block; width:100%; min-width:0; }
        .stock-detail-date-item > label { display:block; margin:0 0 5px; color:#7b8790; font-size:10px; font-weight:700; letter-spacing:.06em; line-height:1.2; text-transform:uppercase; }
        .stock-detail-date-field { position:relative; display:flex; width:100%; min-width:0; height:44px; align-items:center; overflow:hidden; border:1px solid #e1e5e9; border-radius:10px; background:#fff; transition:border-color .18s ease,box-shadow .18s ease; }
        .stock-detail-date-field:focus-within { border-color:#0b8f68; box-shadow:0 0 0 3px rgba(16,185,129,.1); }
        .stock-detail-date-field > i { position:absolute; z-index:1; left:12px; color:#82909a; font-size:17px; pointer-events:none; }
        .stock-detail-date-field input:not(.flatpickr-input) { width:100%; min-width:0; height:100%; border:0!important; outline:0!important; padding:0 12px 0 38px!important; background:transparent!important; color:#253238; font-family:inherit; font-size:14px!important; font-weight:500; box-shadow:none!important; }
        .stock-detail-date-field input:not(.flatpickr-input)::placeholder { color:#9aa4ad; font-weight:400; opacity:1; }
        .stock-detail-date-clear { display:grid; width:32px; height:32px; flex:none; place-items:center; border:1px solid #e1e5e9; border-radius:9px; background:#fff; color:#7b8790; cursor:pointer; }
        .stock-detail-date-clear:hover { background:#f3f6f5; color:#0b595b; }
        .stock-detail-date-clear[hidden] { display:none!important; }
        .stock-detail-direction-options { display:flex; gap:8px; }
        .stock-detail-direction-option { display:flex; min-height:38px; flex:1; align-items:center; justify-content:center; gap:6px; border:1px solid #e5e7eb; border-radius:11px; background:#f9fafb; color:#6b7280; font-size:12px; font-weight:600; }
        .stock-detail-direction-option.is-selected[data-direction="all"] { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
        .stock-detail-direction-option.is-selected[data-direction="in"] { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
        .stock-detail-direction-option.is-selected[data-direction="out"] { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
        .stock-detail-date-presets { display:flex; gap:7px; margin-top:8px; }
        .stock-detail-date-presets button { min-height:32px; flex:1; padding:4px 7px; border:1px solid #e5e7eb; border-radius:9px; background:#fff; color:#4b5563; font-size:11px; font-weight:600; }
        .flatpickr-calendar { z-index:1065!important; border:1px solid #e6ebe8!important; border-radius:14px!important; box-shadow:0 12px 34px rgba(15,23,42,.16)!important; font-family:'Plus Jakarta Sans',sans-serif!important; }
        .flatpickr-calendar .flatpickr-day.selected,
        .flatpickr-calendar .flatpickr-day.startRange,
        .flatpickr-calendar .flatpickr-day.endRange,
        .flatpickr-calendar .flatpickr-day.selected:hover,
        .flatpickr-calendar .flatpickr-day.startRange:hover,
        .flatpickr-calendar .flatpickr-day.endRange:hover { border-color:#0b595b!important; background:#0b595b!important; color:#fff!important; }
        .flatpickr-calendar .flatpickr-day.inRange { border-color:#e6f6ef!important; background:#e6f6ef!important; box-shadow:-5px 0 0 #e6f6ef,5px 0 0 #e6f6ef!important; color:#0b595b!important; }
        .flatpickr-calendar .flatpickr-day:hover { border-color:#eff8f4!important; background:#eff8f4!important; }
        .flatpickr-calendar .flatpickr-months .flatpickr-month,
        .flatpickr-calendar .flatpickr-current-month { color:#253238!important; fill:#253238!important; }
        .flatpickr-calendar .flatpickr-weekday { color:#87939b!important; font-weight:600!important; }
        @media (max-width: 767.98px) {
            .product-stock-detail-page { display:flex; height:100%; min-height:0; flex:1; flex-direction:column; padding-bottom:0; box-sizing:border-box; }
            .product-stock-detail-card { display:flex; height:100%; min-height:0; flex-direction:column; border:0; border-radius:0; box-shadow:none; }
            .product-stock-detail-card .stock-detail-card-header { display:flex; flex:none; flex-direction:column; align-items:stretch; gap:0!important; padding:0!important; border:0; }
            .product-stock-detail-card .stock-detail-search-filter { width:100%; min-width:0; margin:0 8px 20px; }
            .stock-detail-filter-backdrop { position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,.7); opacity:0; transition:opacity .3s ease; }
            .stock-detail-filter-backdrop[hidden] { display:none!important; }
            .stock-detail-filter-backdrop.is-open { opacity:1; }
            .product-stock-detail-card .stock-detail-card-toolbar { position:fixed; right:0; bottom:0; left:0; z-index:1051; display:flex; width:auto; max-height:85dvh; flex:none; flex-direction:column; flex-wrap:nowrap!important; align-items:stretch; gap:0!important; overflow:hidden; overscroll-behavior:contain; padding:0 0 env(safe-area-inset-bottom)!important; border-radius:24px 24px 0 0; background:#fff; box-shadow:0 -16px 40px rgba(0,0,0,.2); transform:translateY(105%); visibility:hidden; transition:transform .3s ease,visibility .3s; }
            .product-stock-detail-card .stock-detail-card-toolbar.is-open { transform:translateY(0); visibility:visible; }
            .stock-detail-filter-handle { width:48px; height:6px; flex:none; margin:12px auto 4px; border-radius:999px; background:#d1d5db; }
            .stock-detail-filter-head { display:flex; width:100%; flex:none; align-items:center; justify-content:space-between; gap:12px; margin:0; padding:12px 20px; border-bottom:1px solid #f3f4f6; }
            .stock-detail-filter-head h2 { margin:0; color:#1f2937; font-size:16px; font-weight:700; line-height:1.25; }
            .stock-detail-filter-head p { margin:2px 0 0; color:#9ca3af; font-size:12px; line-height:1.35; }
            .stock-detail-filter-close { width:32px; height:32px; font-size:16px; }
            .stock-detail-filter-fields { display:flex; width:100%; min-height:0; flex:1; box-sizing:border-box; flex-direction:column; gap:16px; overflow-x:hidden; overflow-y:auto; overscroll-behavior:contain; padding:20px; }
            .stock-detail-search-filter, .stock-detail-filter-mobile-controls { display:none!important; }
            .product-stock-detail-card .stock-detail-filter-field { display:block; width:auto; margin:0; }
            .product-stock-detail-card .stock-detail-filter-desktop-control { display:block!important; width:auto; margin:0; }
            .stock-detail-filter-label { display:block; margin:0 0 6px; color:#6b7280; font-size:10px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; }
            .stock-detail-direction-options { gap:8px; }
            .stock-detail-direction-option { min-height:38px; border-radius:12px; font-size:12px; }
            .stock-detail-date-range { grid-template-columns:1fr; gap:8px; }
            .stock-detail-date-item { position:relative; }
            .stock-detail-date-item > label { position:absolute; z-index:2; top:50%; left:12px; display:block; width:32px; margin:0; transform:translateY(-50%); color:#9ca3af; font-size:10px; font-weight:700; }
            .stock-detail-date-field { height:38px; border:1px solid #e5e7eb; border-radius:12px; }
            .stock-detail-date-field > i { display:none; }
            .stock-detail-date-field input:not(.flatpickr-input) { padding:0 12px 0 52px!important; font-size:12px!important; }
            .stock-detail-date-presets { gap:8px; margin-top:8px; }
            .stock-detail-date-presets button { min-height:38px; border:1px solid #e5e7eb; border-radius:12px; font-size:12px; }
            .stock-detail-date-presets button[data-stock-date-preset="week"] { order:0; border-color:#c5f037; background:#c5f037; color:#1f2937; }
            .stock-detail-date-presets button[data-stock-date-preset="today"] { order:1; }
            .stock-detail-date-presets button[data-stock-date-preset="month"] { display:none; }
            .stock-detail-filter-close { display:grid; width:32px; height:32px; flex:none; place-items:center; border:0; border-radius:50%; background:#f3f4f6; color:#6b7280; font-size:16px; }
            .stock-detail-filter-footer { display:flex; width:100%; box-sizing:border-box; flex:none; gap:12px; margin-top:auto; padding:16px; border-top:1px solid #f3f4f6; background:#f9fafb; }
            .stock-detail-filter-footer button { flex:1; min-width:0; height:44px; border-radius:12px; font-size:12px; font-weight:600; white-space:nowrap; }
            .stock-detail-filter-cancel { border:1px solid #e5e7eb; background:#fff; color:#4b5563; }
            .stock-detail-filter-apply { border:1px solid #0b595b; background:#0b595b; color:#fff; box-shadow:0 1px 2px rgba(0,0,0,.08); }
            body.stock-detail-filter-open { overflow:hidden; }
            .product-stock-detail-card .stock-detail-card-body { display:flex; flex:1; min-height:0; flex-direction:column; overflow:hidden; padding:0!important; }
            .stock-detail-view-header { padding:14px 16px 10px; }
            .stock-detail-view-title p { font-size:11px; }
            .stock-detail-view-title h2 { font-size:13px; }
            .stock-detail-view-actions { gap:5px; }
            .stock-detail-view-tab { min-width:48px; min-height:30px; padding:0 9px; font-size:11px; }
            .stock-detail-view-filter { width:30px; height:30px; }
            .stock-detail-table-heading { margin:0 16px 6px; padding:8px 10px; font-size:9px; }
            .stock-detail-table-heading__numbers { gap:4px; }
            .stock-detail-table-heading__numbers span { width:68px; }
            .stock-detail-table-heading__numbers span:nth-child(2) { width:72px; }
            .stock-detail-table-heading__numbers span:last-child { width:64px; }
            .stock-detail-history-row { gap:7px; padding:10px 10px; }
            .stock-detail-history-activity { font-size:12px; }
            .stock-detail-history-values { gap:4px; }
            .stock-detail-history-value { width:68px; font-size:11px; }
            .stock-detail-history-value:nth-child(2) { width:72px; }
            .stock-detail-history-value:last-child { width:64px; }
            .stock-detail-load-more { padding:12px 16px 18px; }
            .stock-mobile-list { padding:0 16px; }
            .stock-mobile-row { padding:15px 20px; border-bottom:1px solid #f1f3f5; background:#fff; }
            .stock-mobile-row:active, .stock-mobile-row:focus-within { background:#f0fdf4; }
            .stock-mobile-row__top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
            .stock-mobile-row__product { min-width:0; color:#1f2937; font-size:14px; font-weight:600; line-height:1.4; text-decoration:none; }
            .stock-mobile-row__quantity { flex:none; padding:5px 9px; border-radius:999px; background:#f3f4f6; color:#4b5563; font-size:11px; font-weight:700; white-space:nowrap; }
            .stock-mobile-row__quantity.is-in { background:#ecfdf5; color:#047857; }
            .stock-mobile-row__quantity.is-out { background:#fff1f2; color:#e11d48; }
            .stock-mobile-row__levels { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:12px; }
            .stock-mobile-row__level { display:flex; min-width:0; flex-direction:column; gap:3px; padding:9px 10px; border:1px solid #f1f3f5; border-radius:11px; background:#fafbfc; }
            .stock-mobile-row__level span { color:#9ca3af; font-size:10px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; }
            .stock-mobile-row__level strong { overflow:hidden; color:#374151; font-size:12px; font-weight:600; text-overflow:ellipsis; white-space:nowrap; }
            .stock-mobile-row__meta { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; }
            .stock-mobile-row__by { min-width:0; overflow:hidden; color:#9ca3af; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
            .stock-mobile-row__reference { flex:none; padding:4px 8px; border:1px solid transparent; border-radius:999px; background:#f3faf6; color:#0b595b; font-size:10px; font-weight:600; white-space:nowrap; }
            .stock-mobile-row__reference.is-active { border-color:#a7f3d0; background:#ecfdf5; color:#047857; font-weight:700; }
            .stock-mobile-empty { display:flex; min-height:220px; flex-direction:column; align-items:center; justify-content:center; padding:32px 24px; color:#6b7280; text-align:center; }
            .stock-mobile-empty i { margin-bottom:10px; color:#9ca3af; font-size:28px; }
            .stock-mobile-empty strong { margin-bottom:4px; color:#1f2937; font-size:14px; }
            .stock-mobile-empty span { font-size:12px; }
        }
        @media (min-width:640px) and (max-width:767.98px) {
            .stock-detail-date-range { grid-template-columns:repeat(2,minmax(0,1fr)); }
        }
        @media (max-width:359.98px) {
            .stock-detail-date-range { gap:6px; }
            .stock-detail-date-field input:not(.flatpickr-input) { padding-left:48px!important; font-size:11px!important; }
        }
        @media (min-width: 768px) { .stock-detail-filter-backdrop { display:none!important; } }
    </style>
    <div class="product-stock-detail-page -mx-4 md:mx-0">
        <div id="stock-detail-filter-backdrop" class="stock-detail-filter-backdrop" hidden></div>
        <div class="product-stock-detail-card">
            <!--begin::Card header-->
            <div class="stock-detail-card-header">
                <div class="stock-detail-view-header">
                    <div class="stock-detail-view-title">
                        <p>Riwayat Pergerakan</p>
                        <h2>{{ $data->name }}</h2>
                    </div>
                    <div class="stock-detail-view-actions">
                        <button id="stock-detail-view-toggle" type="button" class="stock-detail-view-tab is-active" data-history-toggle aria-label="Tampilkan riwayat HPP" aria-pressed="false" title="Ganti tampilan riwayat">Stok</button>
                        <button id="stock-detail-filter-toggle" type="button" aria-label="Filter riwayat stok" aria-expanded="false" class="stock-detail-view-filter"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="stock-detail-filter-dot" hidden class="erp-mobile-search__filter-dot"></span></button>
                    </div>
                </div>
                <!--begin::Card toolbar-->
                <div class="stock-detail-card-toolbar" id="stock-detail-filters" aria-labelledby="stock-detail-filter-title">
                    <div class="stock-detail-filter-handle" aria-hidden="true"></div>
                    <div class="stock-detail-filter-head">
                        <div>
                            <h2 id="stock-detail-filter-title">Filter Riwayat Stok</h2>
                            <p id="stock-detail-filter-subtitle">Sesuaikan parameter riwayat pergerakan barang</p>
                        </div>
                        <button id="stock-detail-filter-close" class="stock-detail-filter-close" type="button" aria-label="Tutup filter"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                    </div>
                    <div class="stock-detail-filter-fields">
                        <div class="stock-detail-filter-field stock-detail-search-filter">
                            <label class="stock-detail-filter-label" for="search">Cari aktivitas</label>
                            <input id="search" type="search" class="erp-input" placeholder="Cari aktivitas atau referensi..." autocomplete="off">
                        </div>
                        <div class="stock-detail-filter-field">
                            <label class="stock-detail-filter-label">Arah Mutasi</label>
                            <div class="stock-detail-direction-options" role="group" aria-label="Filter arah mutasi">
                                <button type="button" class="stock-detail-direction-option is-selected" data-direction="all" aria-pressed="true">Semua</button>
                                <button type="button" class="stock-detail-direction-option" data-direction="in" aria-pressed="false"><i class="ph-bold ph-arrow-down-left" aria-hidden="true"></i> Masuk</button>
                                <button type="button" class="stock-detail-direction-option" data-direction="out" aria-pressed="false"><i class="ph-bold ph-arrow-up-right" aria-hidden="true"></i> Keluar</button>
                            </div>
                            <select id="stock-detail-direction" class="hidden" aria-hidden="true" tabindex="-1">
                                <option value="all" selected>Semua</option><option value="in">Masuk</option><option value="out">Keluar</option>
                            </select>
                        </div>
                        <div class="stock-detail-filter-mobile-controls">
                            <div class="stock-detail-filter-field">
                                <label class="stock-detail-filter-label" for="stock-detail-branch-search">Cabang</label>
                                <div class="stock-detail-filter-branch-picker">
                                    <div class="stock-detail-filter-branch-search">
                                        <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                                        <input id="stock-detail-branch-search" type="search" placeholder="Cari cabang..." autocomplete="off" aria-label="Cari cabang">
                                    </div>
                                    <div class="stock-detail-filter-branch-options" id="stock-detail-branch-options" role="radiogroup" aria-label="Pilih cabang">
                                        <label class="stock-detail-filter-branch-option">
                                            <input type="radio" name="stock-detail-branch-mobile" value="all" checked>
                                            <span>Semua Cabang</span>
                                        </label>
                                        @foreach ($branch as $item)
                                            <label class="stock-detail-filter-branch-option">
                                                <input type="radio" name="stock-detail-branch-mobile" value="{{ $item->id }}">
                                                <span>{{ ucwords($item->name) }}</span>
                                            </label>
                                        @endforeach
                                        <div id="stock-detail-branch-empty" class="stock-detail-filter-branch-empty" hidden>Cabang tidak ditemukan</div>
                                    </div>
                                </div>
                            </div>
                            <div class="stock-detail-filter-field">
                                <span class="stock-detail-filter-label">Sumber Transaksi</span>
                                <div class="stock-detail-filter-reference-chips" role="group" aria-label="Filter reference">
                                    <button type="button" class="stock-detail-filter-reference-chip is-selected" data-stock-reference="all" aria-pressed="true">Semua</button>
                                    <button type="button" class="stock-detail-filter-reference-chip" data-stock-reference="stock-opname" aria-pressed="false">Stock Opname</button>
                                    <button type="button" class="stock-detail-filter-reference-chip" data-stock-reference="sortir" aria-pressed="false">Sortir</button>
                                    <button type="button" class="stock-detail-filter-reference-chip" data-stock-reference="pos" aria-pressed="false">POS</button>
                                    <button type="button" class="stock-detail-filter-reference-chip" data-stock-reference="production" aria-pressed="false">Produksi</button>
                                    <button type="button" class="stock-detail-filter-reference-chip" data-stock-reference="wholesale" aria-pressed="false">Pengadaan</button>
                                    <button type="button" class="stock-detail-filter-reference-chip" data-stock-reference="transfer" aria-pressed="false">Transfer</button>
                                </div>
                            </div>
                        </div>
                        <div class="stock-detail-filter-field stock-detail-filter-desktop-control">
                            <label class="stock-detail-filter-label" for="stock-detail-branch">Cabang</label>
                            <select class="erp-select" data-erp-filter="branch" id="stock-detail-branch">
                                <option value="all" selected>Semua Cabang</option>
                                @foreach ($branch as $item)
                                    <option value="{{ $item->id }}">{{ ucwords($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="stock-detail-filter-field stock-detail-filter-desktop-control">
                            <label class="stock-detail-filter-label" for="stock-detail-reference">Sumber Transaksi</label>
                            <select class="erp-select" data-erp-filter="reference" id="stock-detail-reference">
                                <option value="all">Semua Sumber</option>
                                <option value="stock-opname">Stock Opname</option>
                                <option value="sortir">Sortir</option>
                                <option value="pos">POS</option>
                                <option value="production">Produksi</option>
                                <option value="wholesale">Pengadaan</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                    <div class="stock-detail-filter-field stock-detail-filter-field--date">
                        <div class="stock-detail-filter-heading">
                            <span class="stock-detail-filter-label">Periode Tanggal</span>
                            <button class="stock-detail-date-clear" id="stock-detail-date-clear" type="button" aria-label="Hapus rentang tanggal" hidden>
                                <i class="ph ph-x" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="stock-detail-date-range">
                            <div class="stock-detail-date-item">
                                <label for="stock-detail-date-start-display">DARI</label>
                                <div class="stock-detail-date-field">
                                    <i class="ph ph-calendar-blank" aria-hidden="true"></i>
                                    <input type="text" class="erp-input stock-detail-date-input" placeholder="Pilih Tanggal"
                                        id="stock-detail-date-start" aria-label="Tanggal mulai" autocomplete="off" />
                                </div>
                            </div>
                            <div class="stock-detail-date-item">
                                <label for="stock-detail-date-end-display">KE</label>
                                <div class="stock-detail-date-field">
                                    <i class="ph ph-calendar-blank" aria-hidden="true"></i>
                                    <input type="text" class="erp-input stock-detail-date-input" placeholder="Pilih Tanggal"
                                        id="stock-detail-date-end" aria-label="Tanggal akhir" autocomplete="off" />
                                </div>
                            </div>
                        </div>
                        <div class="stock-detail-date-presets" role="group" aria-label="Periode cepat">
                            <button type="button" data-stock-date-preset="today">Hari Ini</button>
                            <button type="button" data-stock-date-preset="week">Minggu Ini</button>
                            <button type="button" data-stock-date-preset="month">Bulan Ini</button>
                        </div>
                    </div>
                    </div>
                    <div class="stock-detail-filter-footer">
                        <button id="stock-detail-filter-reset" type="button" class="stock-detail-filter-cancel">Reset Filter</button>
                        <button id="stock-detail-filter-apply" type="button" class="stock-detail-filter-apply">Terapkan Filter</button>
                    </div>
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <div class="stock-detail-table-heading" aria-hidden="true">
                <span>Aktivitas</span>
                <div class="stock-detail-table-heading__numbers"><span id="history-col-one">Awal</span><span id="history-col-two">Mutasi</span><span id="history-col-three" style="color:#0b595b">Saldo</span></div>
            </div>
            <!--begin::Card body-->
            <div class="stock-detail-card-body">
                <div id="stock-mobile-list" class="stock-mobile-list" aria-live="polite"></div>
                <div class="stock-detail-load-more" id="stock-detail-load-more" hidden>
                    <button type="button"><i class="ph ph-arrow-circle-down" aria-hidden="true"></i> <span id="stock-detail-load-more-label">Muat Lebih Banyak</span></button>
                </div>
                <!--begin::Table-->
                <table class="erp-table" id="products-stock-table" width="100%">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Stock Awal</th>
                            <th class="text-right">Stock Akhir</th>
                            <th class="text-right">Tanggal</th>
                            <th class="text-right">Reference</th>
                            <th class="text-right">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
    <script type="text/javascript">
        var dataTable;
        let activeHistoryTab = 'stock';
        let stockFilterDraftOpen = false;
        let stockFilterSnapshot = null;
        let stockFilterBackdropTimeout = null;
        let stockStartDatePicker = null;
        let stockEndDatePicker = null;

        function stockDetailElement(tagName, className, text) {
            const element = document.createElement(tagName);
            if (className) element.className = className;
            if (text !== undefined && text !== null) element.textContent = text;
            return element;
        }

        function stockDetailNumber(value) {
            const numeric = typeof value === 'number' ? value : parseFloat(String(value ?? '').replace(/<[^>]*>/g, '').replace(',', '.'));
            return Number.isFinite(numeric) ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(numeric) : '—';
        }

        function stockDetailNumericValue(value) {
            if (typeof value === 'number') return value;
            const text = String(value ?? '').replace(/<[^>]*>/g, ' ').trim();
            const match = text.match(/-?\d+(?:[.,]\d+)?/);
            if (!match) return NaN;
            const numeric = Number(match[0].replace(',', '.'));
            return Number.isFinite(numeric) ? numeric : NaN;
        }

        function stockDetailLocalizedNumber(value) {
            if (typeof value === 'number') return value;
            const text = String(value ?? '').replace(/<[^>]*>/g, '').trim().replace(/^Rp\s*/i, '').replace(/\s/g, '');
            if (!text) return NaN;
            const normalized = text.includes(',') ? text.replace(/\./g, '').replace(',', '.') : text.replace(/\./g, '');
            const numeric = Number(normalized);
            return Number.isFinite(numeric) ? numeric : NaN;
        }

        function stockDetailDateObject(value) {
            if (!value) return null;
            const text = String(value).trim();
            const localized = text.match(/^(\d{1,2})\s+(Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des)\s+(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?/i);
            if (localized) {
                const months = { jan:0, feb:1, mar:2, apr:3, mei:4, jun:5, jul:6, agu:7, sep:8, okt:9, nov:10, des:11 };
                return new Date(Number(localized[3]), months[localized[2].toLowerCase()], Number(localized[1]), Number(localized[4] || 0), Number(localized[5] || 0), Number(localized[6] || 0));
            }
            const date = new Date(text.replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? null : date;
        }

        function stockDetailDate(value) {
            if (!value) return 'Tanggal tidak tersedia';
            const date = stockDetailDateObject(value);
            return !date ? String(value) : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
        }

        function syncStockMobileBranchControls() {
            const selectedBranch = String($('[data-erp-filter="branch"]').val() || 'all');
            document.querySelectorAll('input[name="stock-detail-branch-mobile"]').forEach((radio) => {
                radio.checked = radio.value === selectedBranch;
            });
        }

        function syncStockMobileReferenceControls() {
            const selectedReference = String($('[data-erp-filter="reference"]').val() || 'all');
            document.querySelectorAll('[data-stock-reference]').forEach((chip) => {
                const selected = chip.dataset.stockReference === selectedReference;
                chip.classList.toggle('is-selected', selected);
                chip.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        }

        function syncStockDirectionControls() {
            const selectedDirection = String($('#stock-detail-direction').val() || 'all');
            document.querySelectorAll('[data-direction]').forEach((button) => {
                const selected = button.dataset.direction === selectedDirection;
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        }

        function filterStockMobileBranchOptions() {
            const query = String(document.getElementById('stock-detail-branch-search')?.value || '').trim().toLocaleLowerCase('id');
            const options = [...document.querySelectorAll('.stock-detail-filter-branch-option')];
            let visibleCount = 0;
            options.forEach((option) => {
                const visible = option.textContent.trim().toLocaleLowerCase('id').includes(query);
                option.hidden = !visible;
                if (visible) visibleCount += 1;
            });
            const empty = document.getElementById('stock-detail-branch-empty');
            if (empty) empty.hidden = visibleCount > 0;
        }

        function updateStockDetailFilterState() {
            const branch = $('[data-erp-filter="branch"]').val();
            const reference = $('[data-erp-filter="reference"]').val();
            const direction = $('#stock-detail-direction').val();
            const hasDate = Boolean(stockDateValue(stockStartDatePicker) || stockDateValue(stockEndDatePicker));
            const panelOpen = $('#stock-detail-filters').hasClass('is-open');
            const hasActiveFilter = (branch && branch !== 'all') || (reference && reference !== 'all') || (direction && direction !== 'all') || hasDate;
            $('#stock-detail-filter-toggle')
                .toggleClass('is-active', Boolean(hasActiveFilter || panelOpen))
                .attr('aria-expanded', panelOpen ? 'true' : 'false');
            $('#stock-detail-filter-dot').prop('hidden', !hasActiveFilter);
            $('#stock-detail-date-clear').prop('hidden', !hasDate);
            syncStockMobileBranchControls();
            syncStockMobileReferenceControls();
            syncStockDirectionControls();
        }

        function stockDateValue(picker) {
            const date = picker?.selectedDates?.[0];
            return date && picker ? picker.formatDate(date, 'Y-m-d') : '';
        }

        function syncStockDateRange(redraw = true) {
            const startDate = stockStartDatePicker?.selectedDates?.[0] || null;
            const endDate = stockEndDatePicker?.selectedDates?.[0] || null;

            if (startDate && endDate && startDate > endDate) {
                stockEndDatePicker.clear(false);
            }

            const validStartDate = stockStartDatePicker?.selectedDates?.[0] || null;
            const validEndDate = stockEndDatePicker?.selectedDates?.[0] || null;
            stockStartDatePicker?.set('maxDate', validEndDate);
            stockEndDatePicker?.set('minDate', validStartDate);

            updateStockDetailFilterState();
            if (redraw && !stockFilterDraftOpen && typeof dataTable !== 'undefined') dataTable.draw();
        }

        function openStockDetailFilters() {
            window.clearTimeout(stockFilterBackdropTimeout);
            stockFilterSnapshot = {
                branch: $('[data-erp-filter="branch"]').val(),
                reference: $('[data-erp-filter="reference"]').val(),
                direction: $('#stock-detail-direction').val(),
                startDate: stockDateValue(stockStartDatePicker),
                endDate: stockDateValue(stockEndDatePicker)
            };
            stockFilterDraftOpen = true;
            $('#stock-detail-filters').attr({ role: 'dialog', 'aria-modal': 'true' });
            $('#stock-detail-filter-backdrop').prop('hidden', false);
            requestAnimationFrame(() => {
                $('#stock-detail-filter-backdrop').addClass('is-open');
                $('#stock-detail-filters').addClass('is-open');
                updateStockDetailFilterState();
            });
            document.body.classList.add('stock-detail-filter-open');
            document.getElementById('stock-detail-filter-close')?.focus();
        }

        function closeStockDetailFilters(applyChanges) {
            if (!stockFilterDraftOpen) return;

            if (!applyChanges && stockFilterSnapshot) {
                $('[data-erp-filter="branch"]').val(stockFilterSnapshot.branch);
                $('[data-erp-filter="reference"]').val(stockFilterSnapshot.reference);
                $('#stock-detail-direction').val(stockFilterSnapshot.direction || 'all');
                stockStartDatePicker?.set('maxDate', null);
                stockEndDatePicker?.set('minDate', null);
                stockStartDatePicker?.setDate(stockFilterSnapshot.startDate || '', false);
                stockEndDatePicker?.setDate(stockFilterSnapshot.endDate || '', false);
                syncStockDateRange(false);
            }

            stockFilterDraftOpen = false;
            stockFilterSnapshot = null;
            $('#stock-detail-filters').removeClass('is-open');
            $('#stock-detail-filters').removeAttr('role aria-modal');
            $('#stock-detail-filter-backdrop').removeClass('is-open');
            document.body.classList.remove('stock-detail-filter-open');
            stockFilterBackdropTimeout = window.setTimeout(() => {
                $('#stock-detail-filter-backdrop').prop('hidden', true);
                stockFilterBackdropTimeout = null;
            }, 260);
            if (applyChanges && dataTable) dataTable.draw();
            updateStockDetailFilterState();
            document.getElementById('stock-detail-filter-toggle')?.focus();
        }

        function renderStockDetailRows(api) {
            const list = document.getElementById('stock-mobile-list');
            if (!list) return;
            const rows = api.rows({ page: 'current' }).data().toArray();
            list.replaceChildren();

            if (!rows.length) {
                const empty = stockDetailElement('div', 'stock-mobile-empty');
                empty.append(stockDetailElement('i', 'ph ph-package'));
                empty.append(stockDetailElement('strong', '', 'Belum ada riwayat pergerakan'));
                empty.append(stockDetailElement('span', '', 'Coba ubah filter atau kata pencarian.'));
                list.append(empty);
            } else {
                rows.forEach((row) => {
                    const item = stockDetailElement('article', 'stock-detail-history-row');
                    const temporary = document.createElement('div');
                    temporary.innerHTML = row.name || '';
                    const sourceLink = temporary.querySelector('a');
                    const references = {
                        'stock-opname': 'Stock Opname',
                        'stock-in': 'Stok Masuk',
                        'stock-out': 'Stok Keluar',
                        'stock-out-transaction': 'Stok Keluar',
                        'sortir': 'Sortir',
                        'pos': 'POS',
                        'production': 'Produksi',
                        'wholesale': 'Pengadaan',
                        'transfer': 'Transfer'
                    };
                    const activity = references[row.url] || row.reff || 'Pergerakan Stok';
                    const main = stockDetailElement('div', 'stock-detail-history-main');
                    const title = stockDetailElement(sourceLink ? 'a' : 'span', 'stock-detail-history-activity', '');
                    if (sourceLink) title.href = sourceLink.href;
                    title.append(stockDetailElement('span', '', activity));
                    const quantity = stockDetailNumericValue(row.quantity);
                    const direction = stockDetailElement('span', 'stock-detail-history-direction ' + (quantity >= 0 ? 'is-in' : 'is-out'));
                    direction.append(stockDetailElement('i', 'ph-bold ' + (quantity >= 0 ? 'ph-arrow-down-left' : 'ph-arrow-up-right')));
                    title.append(direction);
                    main.append(title);
                    main.append(stockDetailElement('span', 'stock-detail-history-user', row.created_by || 'Staff Gudang'));
                    main.append(stockDetailElement('span', 'stock-detail-history-date', stockDetailDate(row.date)));
                    item.append(main);

                    const unit = row.unit ? ` ${row.unit}` : '';
                    const values = stockDetailElement('div', 'stock-detail-history-values');
                    const quantityText = Number.isFinite(quantity) ? `${quantity > 0 ? '+' : ''}${stockDetailNumber(quantity)}` : '—';
                    [
                        { value: `${stockDetailNumber(stockDetailNumericValue(row.stock_awal))}${unit}` },
                        { value: `${quantityText}${unit}`, className: quantity > 0 ? ' is-in' : quantity < 0 ? ' is-out' : '' },
                        { value: `${stockDetailNumber(stockDetailNumericValue(row.stock_akhir))}${unit}` }
                    ].forEach((column) => {
                        const value = stockDetailElement('span', 'stock-detail-history-value' + (column.className || ''), '');
                        value.append(document.createTextNode(column.value.replace(unit, '')));
                        if (unit) value.append(stockDetailElement('small', 'stock-detail-history-unit', unit));
                        values.append(value);
                    });
                    item.append(values);
                    list.append(item);
                });
            }

            const page = api.page.info();
            updateHistoryLoadMore(page);
        }

        function renderHppHistoryRows(api) {
            const list = document.getElementById('stock-mobile-list');
            if (!list) return;
            const rows = api.rows({ page: 'current' }).data().toArray();
            list.replaceChildren();
            if (!rows.length) {
                const empty = stockDetailElement('div', 'stock-mobile-empty');
                empty.append(stockDetailElement('i', 'ph ph-package'));
                empty.append(stockDetailElement('strong', '', 'Belum ada riwayat HPP'));
                empty.append(stockDetailElement('span', '', 'Coba ubah filter atau kata pencarian.'));
                list.append(empty);
            } else {
                rows.forEach((row) => {
                    const item = stockDetailElement('article', 'stock-detail-history-row');
                    const main = stockDetailElement('div', 'stock-detail-history-main');
                    const activity = String(row.remarks || 'Pergerakan HPP').toLocaleLowerCase('id').replace(/\b\w/g, (letter) => letter.toLocaleUpperCase('id'));
                    const anchor = stockDetailElement(row.url ? 'a' : 'span', 'stock-detail-history-activity', '');
                    if (row.url) anchor.href = row.url;
                    anchor.append(stockDetailElement('span', '', activity));
                    const typeHolder = document.createElement('div');
                    typeHolder.innerHTML = row.type || '';
                    const type = typeHolder.textContent.trim();
                    const isIncoming = type === '+';
                    const direction = stockDetailElement('span', 'stock-detail-history-direction ' + (isIncoming ? 'is-in' : 'is-out'));
                    direction.append(stockDetailElement('i', 'ph-bold ' + (isIncoming ? 'ph-arrow-down-left' : 'ph-arrow-up-right')));
                    anchor.append(direction);
                    main.append(anchor);
                    const sourceLabels = { pengadaan:'Pengadaan', barang_buang:'Sortir', penjualan:'POS', bahan_produksi:'Produksi', produksi:'Produksi', opname:'Stock Opname' };
                    main.append(stockDetailElement('span', 'stock-detail-history-user', sourceLabels[row.trx_type] || 'Pergerakan HPP'));
                    main.append(stockDetailElement('span', 'stock-detail-history-date', stockDetailDate(row.created_at)));
                    item.append(main);

                    const quantity = stockDetailLocalizedNumber(row.qty);
                    const hpp = stockDetailLocalizedNumber(row.hpp_berjalan);
                    const amount = Number.isFinite(quantity) && Number.isFinite(hpp) ? Math.round(Math.abs(quantity) * hpp) : stockDetailLocalizedNumber(row.cogs);
                    const totalAsset = stockDetailLocalizedNumber(row.total_aset_berjalan);
                    const values = stockDetailElement('div', 'stock-detail-history-values stock-detail-history-values--money');
                    const numberFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits:0 });
                    [
                        { value: `${isIncoming ? '+' : '-'}${numberFormat.format(Number.isFinite(amount) ? amount : 0)}`, className:isIncoming?' is-in':' is-out' },
                        { value: numberFormat.format(Number.isFinite(totalAsset) ? totalAsset : 0) },
                        { value: numberFormat.format(Number.isFinite(hpp) ? hpp : 0) }
                    ].forEach((column) => values.append(stockDetailElement('span', 'stock-detail-history-value' + (column.className || ''), column.value)));
                    item.append(values);
                    list.append(item);
                });
            }
            updateHistoryLoadMore(api.page.info());
        }

        function updateHistoryLoadMore(page) {
            const more = document.getElementById('stock-detail-load-more');
            const moreLabel = document.getElementById('stock-detail-load-more-label');
            const remaining = Math.max(0, page.recordsDisplay - page.end);
            if (more) more.hidden = remaining === 0;
            if (moreLabel && remaining > 0) moreLabel.textContent = `Muat ${Math.min(50, remaining)} Riwayat Lagi`;
        }

        function initializeHistoryTable(tab = 'stock') {
            const table = $('#products-stock-table');
            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
                table.find('tbody').empty();
            }
            activeHistoryTab = tab;
            const isHpp = tab === 'hpp';
            dataTable = table.DataTable({
                pageLength:50,
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                processing:true,
                serverSide:true,
                searching:true,
                ordering:true,
                scrollX:true,
                ajax:{
                    url:isHpp ? @json(route('product-transaction-data')) : @json(route('product-stock-data-show')),
                    data:function(d) {
                        d.product_id = {{ $data->id }};
                        d.branch = $('[data-erp-filter="branch"]').val();
                        d.reference = $('[data-erp-filter="reference"]').val();
                        d.stock_direction = $('#stock-detail-direction').val() || 'all';
                        d.start_date = stockDateValue(stockStartDatePicker);
                        d.end_date = stockDateValue(stockEndDatePicker);
                    }
                },
                drawCallback:function() {
                    if (activeHistoryTab === 'hpp') renderHppHistoryRows(this.api());
                    else renderStockDetailRows(this.api());
                },
                columns:isHpp ? [
                    {data:'remarks',name:'remarks'}, {data:'type',name:'type'}, {data:'qty',name:'qty'},
                    {data:'cogs',name:'cogs'}, {data:'total_aset_berjalan',name:'total_aset_berjalan'},
                    {data:'hpp_berjalan',name:'hpp_berjalan'}, {data:'created_at',name:'created_at'}
                ] : [
                    {data:'name',name:'name'}, {data:'quantity',name:'quantity'}, {data:'stock_awal',name:'stock_awal'},
                    {data:'stock_akhir',name:'stock_akhir'}, {data:'date',name:'date'}, {data:'reff',name:'reff'}, {data:'created_by',name:'created_by'}
                ],
                order:[[isHpp ? 6 : 4,'desc']]
            });
            const labels = isHpp ? ['Modal','Total Aset','HPP'] : ['Awal','Mutasi','Saldo'];
            ['history-col-one','history-col-two','history-col-three'].forEach((id,index) => {
                const label = document.getElementById(id);
                if (label) label.textContent = labels[index];
            });
            const viewToggle = document.getElementById('stock-detail-view-toggle');
            if (viewToggle) {
                viewToggle.textContent = isHpp ? 'HPP' : 'Stok';
                viewToggle.classList.add('is-active');
                viewToggle.setAttribute('aria-label', isHpp ? 'Tampilkan riwayat stok' : 'Tampilkan riwayat HPP');
                viewToggle.setAttribute('aria-pressed', isHpp ? 'true' : 'false');
            }
            const filterTitle = document.getElementById('stock-detail-filter-title');
            const filterSubtitle = document.getElementById('stock-detail-filter-subtitle');
            const filterToggle = document.getElementById('stock-detail-filter-toggle');
            if (filterTitle) filterTitle.textContent = isHpp ? 'Filter Riwayat HPP' : 'Filter Riwayat Stok';
            if (filterSubtitle) filterSubtitle.textContent = isHpp ? 'Sesuaikan parameter riwayat pergerakan HPP' : 'Sesuaikan parameter riwayat pergerakan barang';
            if (filterToggle) filterToggle.setAttribute('aria-label', isHpp ? 'Filter riwayat HPP' : 'Filter riwayat stok');
        }

        $(document).ready(function() {
            initializeHistoryTable('stock');

            $(document).on('click', '[data-history-toggle]', function() {
                initializeHistoryTable(activeHistoryTab === 'stock' ? 'hpp' : 'stock');
            });

            $(document).on('click', '[data-direction]', function() {
                $('#stock-detail-direction').val(this.dataset.direction || 'all');
                syncStockDirectionControls();
            });

            $(document).on('click', '[data-stock-date-preset]', function() {
                const now = new Date();
                const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                let end = new Date(start);
                if (this.dataset.stockDatePreset === 'week') {
                    const offset = (start.getDay() + 6) % 7;
                    start.setDate(start.getDate() - offset);
                    end = new Date(start);
                    end.setDate(start.getDate() + 6);
                } else if (this.dataset.stockDatePreset === 'month') {
                    start.setDate(1);
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                }
                stockStartDatePicker?.setDate(start, false);
                stockEndDatePicker?.setDate(end, false);
                syncStockDateRange(false);
            });

            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('#stock-detail-load-more button').on('click', function() {
                const page = dataTable.page.info();
                dataTable.page.len(Math.min(page.recordsDisplay, page.length + 50)).draw('page');
            });

            $('#stock-detail-filter-toggle').on('click', function() {
                if ($('#stock-detail-filters').hasClass('is-open')) {
                    closeStockDetailFilters(false);
                } else {
                    openStockDetailFilters();
                }
            });

            $('#stock-detail-filter-close, #stock-detail-filter-cancel, #stock-detail-filter-backdrop').on('click', function() {
                closeStockDetailFilters(false);
            });

            $('#stock-detail-filter-apply').on('click', function() {
                closeStockDetailFilters(true);
            });

            $('#stock-detail-filter-reset').on('click', function() {
                $('[data-erp-filter="branch"]').val('all');
                $('[data-erp-filter="reference"]').val('all');
                $('#stock-detail-direction').val('all');
                $('#search').val('');
                dataTable?.search('');
                stockStartDatePicker?.clear(false);
                stockEndDatePicker?.clear(false);
                syncStockDateRange(false);
                closeStockDetailFilters(true);
            });

            $('#stock-detail-branch-search').on('input', filterStockMobileBranchOptions);

            $(document).on('change', 'input[name="stock-detail-branch-mobile"]', function() {
                $('[data-erp-filter="branch"]').val(this.value).trigger('change');
                updateStockDetailFilterState();
            });

            $(document).on('click', '[data-stock-reference]', function() {
                $('[data-erp-filter="reference"]').val(this.dataset.stockReference).trigger('change');
                updateStockDetailFilterState();
            });

            $(document).on('keydown.stockDetailFilters', function(event) {
                if (event.key === 'Escape' && $('#stock-detail-filters').hasClass('is-open')) closeStockDetailFilters(false);
            });



            $('[data-erp-filter="branch"]').on('change', function() {
                if (!stockFilterDraftOpen) dataTable.draw();
            });

            $('[data-erp-filter="reference"]').on('change', function() {
                if (!stockFilterDraftOpen) dataTable.draw();
            });

            $('[data-erp-filter="branch"], [data-erp-filter="reference"]').on('change.stockDetailFilterState', updateStockDetailFilterState);

            stockStartDatePicker = $("#stock-detail-date-start").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    instance.altInput.id = 'stock-detail-date-start-display';
                    instance.altInput.setAttribute('aria-label', 'Tanggal mulai');
                    instance.altInput.setAttribute('autocomplete', 'off');
                },
                onChange: function() { syncStockDateRange(); }
            });

            stockEndDatePicker = $("#stock-detail-date-end").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    instance.altInput.id = 'stock-detail-date-end-display';
                    instance.altInput.setAttribute('aria-label', 'Tanggal akhir');
                    instance.altInput.setAttribute('autocomplete', 'off');
                },
                onChange: function() { syncStockDateRange(); }
            });

            $('#stock-detail-date-clear').on('click', function() {
                stockStartDatePicker?.clear(false);
                stockEndDatePicker?.clear(false);
                syncStockDateRange();
            });

            updateStockDetailFilterState();
            filterStockMobileBranchOptions();
        });

        function reloadDataTable() {
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false);
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }
    </script>
@endsection
