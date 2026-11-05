@extends('layouts.erp')

@section('title', 'Stok Barang')
@section('page-title', 'Stok Barang')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-300 transition hover:text-teal">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Stok Barang</span>
@endsection

@section('content')
    <style>
        .stock-page { display:flex; flex:1; flex-direction:column; min-height:0; font-family:'Plus Jakarta Sans',sans-serif; }
        .stock-card { position:relative; display:flex; flex:1; flex-direction:column; min-height:0; overflow:visible; border:1px solid #f1f3f5; border-radius:16px; background:#fff; }
        .stock-toolbar { position:relative; z-index:20; display:flex; align-items:center; padding:12px 16px; border-bottom:1px solid #f1f3f5; }
        .stock-search-shell { width:100%; max-width:720px; padding:0!important; }
        .stock-search-shell .erp-mobile-search { height:48px; box-shadow:none; }
        .stock-filters { position:fixed; top:var(--stock-filter-top,120px); right:var(--stock-filter-right,16px); z-index:1100; display:flex; flex-direction:column; width:256px; max-height:min(70dvh,var(--stock-filter-height,520px)); overflow:hidden; border:1px solid #f3f4f6; border-radius:16px; background:#fff; color:#1f2937; box-shadow:0 14px 32px rgba(15,23,42,.14); font-family:'Plus Jakarta Sans',sans-serif; outline:none; }
        .stock-filters *, .stock-filters *::before, .stock-filters *::after { box-sizing:border-box; }
        .stock-filters[hidden], .stock-filters [hidden], .stock-backdrop[hidden], .stock-empty[hidden], .stock-loading[hidden], .stock-more[hidden], #stock-search-clear[hidden], #stock-filter-dot[hidden] { display:none!important; }
        .stock-filter-body { min-height:0; overflow-y:auto; overscroll-behavior:contain; padding:8px 0; scrollbar-width:thin; scrollbar-color:#d1d5db transparent; }
        .stock-filter-section { padding:4px 0 8px; border-bottom:1px solid #f3f4f6; }
        .stock-filter-section:last-child { border-bottom:0; }
        .stock-filter-heading { display:flex; align-items:center; justify-content:space-between; gap:8px; margin:0; padding:8px 16px; color:#6b7280; font-size:11px; font-weight:700; letter-spacing:.025em; text-transform:uppercase; }
        .stock-filter-heading button { min-height:24px; border:0; padding:0; background:transparent; color:#0b595b; font-size:11px; font-weight:600; text-transform:none; cursor:pointer; }
        .stock-category-options { max-height:176px; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:thin; scrollbar-color:#d1d5db transparent; }
        .stock-filter-option { display:flex; width:100%; min-height:40px; align-items:center; justify-content:space-between; gap:10px; padding:10px 16px; border:0; background:#fff; color:#374151; font:inherit; font-size:12px; line-height:1.5; text-align:left; cursor:pointer; }
        .stock-filter-option:hover { background:#f9fafb; }
        .stock-filter-option.is-selected { background:#ecfdf5; color:#047857; font-weight:600; }
        .stock-filter-option > i { color:#10b981; font-size:8px; flex:none; }
        .stock-filter-sort > i:first-child { color:#6b7280; font-size:16px; }
        .stock-filter-sort.is-selected > i:first-child { color:#0b595b; }
        .stock-sort-label { flex:1; }
        .stock-filter-sort > i:last-child { color:#9ca3af; font-size:14px; }
        .stock-filter-sort.is-selected > i:last-child { color:#0b595b; }
        .stock-filter-sort-state { display:flex; align-items:center; gap:4px; color:#0b595b; font-size:11px; font-weight:700; }
        .stock-filter-selects { display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:12px 14px 8px; }
        .stock-filter-selects label { display:flex; min-width:0; flex-direction:column; gap:5px; color:#6b7280; font-size:10px; font-weight:700; text-transform:uppercase; }
        .stock-filter-selects select, .stock-mobile-status select { width:100%; min-width:0; height:40px; padding:0 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; color:#374151; font-family:inherit; font-size:12px; font-weight:500; text-transform:none; }
        .stock-filters button:focus-visible, .stock-filters select:focus-visible, .stock-branch-radio input:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
        .stock-filter-mobile { display:none; }
        .stock-hidden-filter { display:none!important; }
        .stock-backdrop { position:fixed; inset:0; z-index:1090; background:transparent; }
        .stock-results { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; }
        .stock-row { display:flex; align-items:center; gap:16px; min-height:78px; padding:14px 20px; border-bottom:1px solid #f3f4f6; color:inherit; text-decoration:none; transition:background .15s; }
        .stock-row:hover, .stock-row:focus-visible { background:#f8faf9; outline:0; }
        .stock-icon { display:grid; width:48px; height:48px; flex:none; place-items:center; border:1px solid #f3f4f6; border-radius:12px; font-size:22px; }
        .stock-icon.bg-rose-50, .stock-icon.bg-pink-100 { background:#fff1f2; }
        .stock-icon.bg-emerald-50, .stock-icon.bg-green-50 { background:#ecfdf5; }
        .stock-icon.bg-amber-50 { background:#fff7ed; }
        .stock-icon.bg-orange-50 { background:#fff7ed; }
        .stock-icon.bg-yellow-50 { background:#fefce8; }
        .stock-icon.bg-red-50 { background:#fef2f2; }
        .stock-icon.bg-purple-50 { background:#faf5ff; }
        .stock-icon.bg-lime-50 { background:#f7fee7; }
        .stock-icon.bg-stone-50, .stock-icon.bg-gray-100 { background:#f3f4f6; }
        .stock-main { flex:1; min-width:0; }
        .stock-name { display:block; overflow:hidden; color:#1f2937; font-size:14px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .stock-row:hover .stock-name { color:#0b595b; }
        .stock-meta { display:flex; align-items:center; gap:7px; margin-top:4px; color:#9ca3af; font-size:12px; }
        .stock-hpp { color:#047857; font-size:12px; font-weight:700; }
        .stock-unit { color:#9ca3af; font-size:12px; font-weight:500; }
        .stock-sku { overflow:hidden; font-family:monospace; text-overflow:ellipsis; white-space:nowrap; }
        .stock-balance { flex-shrink:0; min-width:155px; color:#1f2937; font-size:17px; font-weight:700; text-align:right; white-space:nowrap; }
        .stock-balance small { color:#6b7280; font-size:12px; font-weight:500; }
        .stock-balance.is-low { color:#b45309; }
        .stock-balance.is-empty { color:#9ca3af; }
        .stock-empty, .stock-loading { display:flex; min-height:220px; flex-direction:column; align-items:center; justify-content:center; padding:32px; color:#6b7280; text-align:center; }
        .stock-empty i { margin-bottom:12px; color:#9ca3af; font-size:30px; }
        .stock-empty strong { color:#374151; font-size:14px; }
        .stock-empty span, .stock-loading { font-size:12px; }
        .stock-more { display:flex; justify-content:center; padding:16px 20px 24px; }
        .stock-more button { width:100%; min-height:44px; padding:0 24px; border:2px dashed #e5e7eb; border-radius:12px; background:#fff; color:#6b7280; font-size:14px; font-weight:700; transition:all .15s; }
        .stock-more button:hover { border-color:#34d399; color:#047857; background:#ecfdf5; }
        @media (max-width:767.98px) {
            #kt_content { overflow:hidden!important; padding:0!important; }
            #kt_content_container { display:flex; flex:1; flex-direction:column; min-height:0; width:100%; max-width:none; padding:0; }
            .stock-card { border:0; border-radius:0; }
            .stock-toolbar { padding:12px 16px; }
            .stock-filters { top:auto; right:0; bottom:0; left:0; width:100%; max-height:85dvh; padding:0; border:0; border-radius:24px 24px 0 0; box-shadow:0 -16px 40px rgba(0,0,0,.2); }
            .stock-filter-mobile { display:block; }
            .stock-filter-desktop { display:none; }
            .stock-filter-handle { display:flex; align-items:center; justify-content:center; width:100%; height:22px; flex:none; border:0; padding:0; background:#fff; cursor:pointer; }
            .stock-filter-handle span { width:64px; height:6px; border-radius:999px; background:#d1d5db; }
            .stock-filter-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex:none; padding:14px 20px 16px; border-bottom:1px solid #f3f4f6; }
            .stock-filter-header h2 { margin:0; color:#1f2937; font-family:inherit; font-size:18px; font-weight:700; }
            .stock-filter-close { display:grid; width:32px; height:32px; flex:none; place-items:center; border:0; border-radius:50%; background:#f3f4f6; color:#6b7280; font-size:16px; cursor:pointer; }
            .stock-filter-body { padding:16px 24px 20px; }
            .stock-filter-section { padding:0; margin-bottom:20px; border:0; }
            .stock-filter-heading { padding:0; margin-bottom:8px; font-size:12px; }
            .stock-branch-picker { overflow:hidden; border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
            .stock-branch-search { display:flex; align-items:center; gap:10px; padding-left:12px; border-bottom:1px solid #f3f4f6; }
            .stock-branch-search i { color:#6b7280; font-size:14px; flex:none; }
            #stock-branch-search, #stock-branch-search:focus, #stock-branch-search:focus-visible { width:100%; min-width:0; height:44px; padding:10px 12px 10px 0; border:0!important; outline:0!important; box-shadow:none!important; background:transparent; color:#1f2937; font-family:inherit; font-size:13px!important; }
            #stock-branch-search::placeholder { color:#6b7280; }
            .stock-branch-options { max-height:220px; overflow-y:auto; overscroll-behavior:contain; background:#fafbfc; scrollbar-width:thin; scrollbar-color:#d1d5db transparent; }
            .stock-branch-radio { display:flex; align-items:center; gap:12px; min-height:44px; margin:0; padding:12px; border-bottom:1px solid #f3f4f6; color:#374151; font-family:inherit; font-size:12px; font-weight:500; line-height:1.5; cursor:pointer; }
            .stock-branch-radio:last-of-type { border-bottom:0; }
            .stock-branch-radio input { appearance:none; width:16px; height:16px; min-width:16px; margin:0; padding:0; border:2px solid #d1d5db; border-radius:50%; background:#fff; cursor:pointer; }
            .stock-branch-radio input:checked { border:4px solid #10b981; }
            .stock-branch-radio:has(input:checked) { color:#111827; font-weight:700; }
            .stock-branch-empty { padding:16px; color:#6b7280; font-size:12px; text-align:center; }
            .stock-category-options { display:flex; flex-wrap:wrap; gap:8px; max-height:none; overflow:visible; }
            .stock-category-options .stock-filter-option { width:auto; min-height:32px; padding:6px 12px; border:1px solid #e5e7eb; border-radius:999px; color:#4b5563; font-size:12px; font-weight:500; }
            .stock-category-options .stock-filter-option.is-selected { border-color:#a7f3d0; background:#ecfdf5; color:#047857; font-weight:600; }
            .stock-category-options .stock-filter-option i { display:none!important; }
            .stock-filter-sort { min-height:44px; padding:10px 0; border-bottom:1px solid #f3f4f6; background:#fff!important; }
            .stock-filter-sort:last-child { border-bottom:0; }
            .stock-filter-sort.is-selected .stock-sort-label { color:#111827; font-weight:700; }
            .stock-mobile-status { margin:0; padding:0; }
            .stock-filter-footer { display:flex; flex:none; gap:8px; padding:16px 16px calc(16px + env(safe-area-inset-bottom)); border-top:1px solid #f3f4f6; background:#fff; }
            .stock-filter-footer button { flex:1; min-height:44px; border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; background:#fff; color:#4b5563; font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; }
            .stock-filter-footer button:last-child { border-color:#0b595b; background:#0b595b; color:#fff; }
            .stock-backdrop { background:rgba(0,0,0,.7); }
            .stock-row { gap:12px; min-height:67px; padding:13px 20px; }
            .stock-icon { width:40px; height:40px; font-size:19px; }
            .stock-name { font-size:15px; }
            .stock-hpp, .stock-unit { font-size:12px; }
            .stock-balance { min-width:0; font-size:14px; }
            .stock-sku, .stock-dot { display:none; }
            .stock-results { padding-bottom:calc(6rem + env(safe-area-inset-bottom)); }
        }

    </style>

    <div class="stock-page">
        <div class="stock-card">
            <div class="stock-toolbar">
                <x-erp.mobile-search class="stock-search-shell erp-mobile-search-shell--always-visible erp-mobile-search-shell--fill">
                    <x-slot:input>
                        <input id="stock-search" type="search" placeholder="Cari nama produk atau SKU..." aria-label="Cari nama produk atau SKU" autocomplete="off" class="erp-mobile-search__input">
                    </x-slot:input>
                    <x-slot:clear>
                        <button id="stock-search-clear" type="button" aria-label="Hapus pencarian" hidden class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
                    </x-slot:clear>
                    <x-slot:filter>
                        <button id="stock-filter-toggle" type="button" aria-label="Filter dan urutkan stok" aria-expanded="false" aria-controls="stock-filters" aria-haspopup="dialog" class="erp-mobile-search__filter"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="stock-filter-dot" hidden class="erp-mobile-search__filter-dot"></span></button>
                    </x-slot:filter>
                </x-erp.mobile-search>
            </div>
            <div id="stock-backdrop" class="stock-backdrop" hidden></div>
            <div id="stock-filters" class="stock-filters" role="dialog" aria-labelledby="stock-filter-title" tabindex="-1" hidden>
                <button type="button" class="stock-filter-handle stock-filter-mobile" data-stock-filter-close aria-label="Tutup filter"><span aria-hidden="true"></span></button>
                <div class="stock-filter-header stock-filter-mobile"><h2 id="stock-filter-title">Filter</h2><button type="button" class="stock-filter-close" data-stock-filter-close aria-label="Tutup panel filter"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
                <div class="stock-filter-body">
                <section class="stock-filter-section stock-filter-mobile">
                    <label class="stock-filter-heading" for="stock-branch-search">Cabang</label>
                    <div class="stock-branch-picker">
                        <div class="stock-branch-search"><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input id="stock-branch-search" type="search" placeholder="Cari cabang..." autocomplete="off"></div>
                        <div class="stock-branch-options" role="group" aria-label="Pilih cabang">
                            @foreach ($branch as $item)
                                <label class="stock-branch-radio"><input type="radio" name="stock-branch-choice" value="{{ $item->id }}" @checked($loop->first)><span>{{ ucwords($item->name) }}</span></label>
                            @endforeach
                            <label class="stock-branch-radio"><input type="radio" name="stock-branch-choice" value="all" @checked($branch->isEmpty())><span>Semua Cabang</span></label>
                            <p id="stock-branch-empty" class="stock-branch-empty" hidden>Cabang tidak ditemukan</p>
                        </div>
                    </div>
                </section>
                <section class="stock-filter-section">
                    <div class="stock-filter-heading"><span>Kategori</span><button id="stock-category-reset" type="button" hidden>Reset</button></div>
                    <div class="stock-filter-options stock-category-options" role="group" aria-label="Kategori produk">
                        <button type="button" class="stock-filter-option is-selected" data-category="all">Semua<i class="ph-fill ph-circle" aria-hidden="true"></i></button>
                        @foreach ($category as $item)
                            <button type="button" class="stock-filter-option" data-category="{{ $item->id }}">{{ ucwords($item->name) }}<i class="ph-fill ph-circle" aria-hidden="true" hidden></i></button>
                        @endforeach
                    </div>
                </section>
                <section class="stock-filter-section">
                    <div class="stock-filter-heading"><span>Urutkan</span><button id="stock-sort-reset" type="button" hidden>Reset</button></div>
                    <div class="stock-filter-options">
                        <button type="button" class="stock-filter-option stock-filter-sort" data-sort="stock_available"><i class="ph-bold ph-package" aria-hidden="true"></i><span class="stock-sort-label">Stok</span><span class="stock-filter-sort-state" hidden></span><i class="ph ph-caret-up-down" aria-hidden="true"></i></button>
                        <button type="button" class="stock-filter-option stock-filter-sort" data-sort="name"><i class="ph-bold ph-text-aa" aria-hidden="true"></i><span class="stock-sort-label">Nama Produk</span><span class="stock-filter-sort-state" hidden></span><i class="ph ph-caret-up-down" aria-hidden="true"></i></button>
                        <button type="button" class="stock-filter-option stock-filter-sort" data-sort="hpp"><i class="ph-bold ph-currency-circle-dollar" aria-hidden="true"></i><span class="stock-sort-label">HPP Produk</span><span class="stock-filter-sort-state" hidden></span><i class="ph ph-caret-up-down" aria-hidden="true"></i></button>
                    </div>
                </section>
                <section class="stock-filter-selects stock-filter-desktop">
                    <label>Cabang
                        <select id="stock-branch" data-default="{{ $branch->first()->id ?? 'all' }}">
                            @foreach ($branch as $item)
                                <option value="{{ $item->id }}" @selected($loop->first)>{{ ucwords($item->name) }}</option>
                            @endforeach
                            <option value="all" @selected($branch->isEmpty())>Semua Cabang</option>
                        </select>
                    </label>
                    <label>Status Stok
                        <select id="stock-status">
                            <option value="all">Semua stok</option>
                            <option value="ada">Ada</option>
                            <option value="kosong">Kosong atau minus</option>
                        </select>
                    </label>
                </section>
                <section class="stock-mobile-status stock-filter-mobile"><label class="stock-filter-heading" for="stock-pending-status">Status Stok</label><select id="stock-pending-status"><option value="all">Semua stok</option><option value="ada">Ada</option><option value="kosong">Kosong atau minus</option></select></section>
                </div>
                <div class="stock-filter-footer stock-filter-mobile"><button type="button" data-stock-filter-close>Batal</button><button type="button" id="stock-filter-apply">Terapkan</button></div>
                <select id="stock-category" class="stock-hidden-filter" aria-hidden="true" tabindex="-1">
                    <option value="all">Semua kategori</option>
                    @foreach ($category as $item)
                        <option value="{{ $item->id }}">{{ ucwords($item->name) }}</option>
                    @endforeach
                </select>
                <select id="stock-sort" class="stock-hidden-filter" aria-hidden="true" tabindex="-1">
                    <option value="none">Default</option>
                    <option value="stock_available:desc">Stok terbanyak</option>
                    <option value="stock_available:asc">Stok tersedikit</option>
                    <option value="name:asc">Nama A–Z</option>
                    <option value="name:desc">Nama Z–A</option>
                    <option value="hpp:desc">HPP tertinggi</option>
                    <option value="hpp:asc">HPP terendah</option>
                </select>
                <button id="stock-filter-reset" type="button" class="stock-hidden-filter">Reset filter</button>
            </div>
            <div id="stock-results" class="stock-results" aria-live="polite">
                <div id="stock-loading" class="stock-loading">Memuat stok barang...</div>
                <div id="stock-empty" class="stock-empty" hidden>
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <strong>Produk tidak ditemukan</strong>
                    <span>Coba ubah kata kunci pencarian atau filter.</span>
                </div>
                <div id="stock-rows"></div>
                <div id="stock-more" class="stock-more" hidden><button type="button"><i class="ph ph-arrow-circle-down" aria-hidden="true"></i> Muat <span id="stock-more-count">50</span> Produk Lagi</button></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const endpoint = @json(route('product-stock-data'));
            const el = Object.fromEntries(['search', 'search-clear', 'branch', 'category', 'status', 'sort', 'filter-toggle', 'filters', 'backdrop', 'filter-reset', 'results', 'rows', 'loading', 'empty', 'more'].map(key => [key, document.getElementById('stock-' + key)]));
            const pageSize = 50;
            const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
            const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            let shown = 0;
            let controller = null;
            let debounce = null;

            const mobileFilter = window.matchMedia('(max-width:767.98px)');
            const branchSearch = document.getElementById('stock-branch-search');
            const branchChoices = [...el.filters.querySelectorAll('input[name="stock-branch-choice"]')];
            const pendingStatus = document.getElementById('stock-pending-status');
            let pendingFilters = currentFilters();

            // Keep the overlay above the shared application chrome, outside scrolling containers.
            document.body.append(el.backdrop, el.filters);

            function currentFilters() {
                return { branch:el.branch.value, category:el.category.value, status:el.status.value, sort:el.sort.value };
            }

            function positionFilters() {
                if (mobileFilter.matches) return;
                const anchor = el['filter-toggle'].getBoundingClientRect();
                const top = anchor.bottom + 10;
                el.filters.style.setProperty('--stock-filter-top', top + 'px');
                el.filters.style.setProperty('--stock-filter-right', Math.max(16, window.innerWidth - anchor.right) + 'px');
                el.filters.style.setProperty('--stock-filter-height', Math.max(200, window.innerHeight - top - 16) + 'px');
            }

            function filterBranches() {
                const term = branchSearch.value.trim().toLocaleLowerCase('id');
                let visible = 0;
                branchChoices.forEach(input => {
                    const label = input.closest('label');
                    label.hidden = !label.textContent.toLocaleLowerCase('id').includes(term);
                    if (!label.hidden) visible++;
                });
                document.getElementById('stock-branch-empty').hidden = visible > 0;
            }

            function setFilterOpen(open) {
                if (!open && el.filters.hidden) return;
                if (open) {
                    pendingFilters = currentFilters();
                    branchSearch.value = '';
                    filterBranches();
                    positionFilters();
                    el.filters.setAttribute('aria-modal', mobileFilter.matches ? 'true' : 'false');
                }
                el.filters.hidden = !open;
                el.backdrop.hidden = !open;
                el['filter-toggle'].setAttribute('aria-expanded', open ? 'true' : 'false');
                refreshFilterIndicator();
                (open ? el.filters : el['filter-toggle']).focus({ preventScroll:true });
            }

            function updateFilter(key, value) {
                if (mobileFilter.matches && !el.filters.hidden) {
                    pendingFilters[key] = value;
                    refreshFilterIndicator();
                } else {
                    el[key].value = value;
                    el[key].dispatchEvent(new Event('change', { bubbles:true }));
                }
            }

            function refreshFilterIndicator() {
                const applied = currentFilters();
                const values = mobileFilter.matches && !el.filters.hidden ? pendingFilters : applied;
                const active = applied.branch !== el.branch.dataset.default || applied.category !== 'all' || applied.status !== 'all' || applied.sort !== 'none';
                el['filter-toggle'].classList.toggle('is-active', active || !el.filters.hidden);
                document.getElementById('stock-filter-dot').hidden = !active;
                document.getElementById('stock-category-reset').hidden = values.category === 'all';
                document.getElementById('stock-sort-reset').hidden = values.sort === 'none';
                branchChoices.forEach(input => { input.checked = input.value === values.branch; });
                pendingStatus.value = values.status;

                document.querySelectorAll('.stock-filter-option[data-category]').forEach(option => {
                    const selected = option.dataset.category === values.category;
                    option.classList.toggle('is-selected', selected);
                    option.setAttribute('aria-pressed', String(selected));
                    const marker = option.querySelector('i');
                    if (marker) marker.hidden = !selected;
                });
                document.querySelectorAll('.stock-filter-option[data-sort]').forEach(option => {
                    const [field, direction] = values.sort.split(':');
                    const selected = option.dataset.sort === field;
                    option.classList.toggle('is-selected', selected);
                    option.setAttribute('aria-pressed', String(selected));
                    const state = option.querySelector('.stock-filter-sort-state');
                    const icons = option.querySelectorAll('i');
                    state.hidden = !selected;
                    state.textContent = selected ? (direction === 'desc' ? 'Z - A' : 'A - Z') : '';
                    icons[icons.length - 1].className = selected ? 'ph-bold ' + (direction === 'desc' ? 'ph-arrow-down' : 'ph-arrow-up') : 'ph ph-caret-up-down';
                });
            }

            function addText(parent, tag, className, value) {
                const child = document.createElement(tag);
                child.className = className;
                child.textContent = value;
                parent.appendChild(child);
                return child;
            }

            function addRow(product) {
                const row = document.createElement('a');
                row.className = 'stock-row';
                row.href = product.detail_url;
                row.title = product.product_name || 'Produk';
                const icon = addText(row, 'span', 'stock-icon ' + (product.icon_class || ''), product.icon || '📦');
                icon.setAttribute('aria-hidden', 'true');
                const main = addText(row, 'span', 'stock-main', '');
                addText(main, 'span', 'stock-name', product.product_name || 'Produk');
                const meta = addText(main, 'span', 'stock-meta', '');
                addText(meta, 'span', 'stock-hpp', rupiah.format(Number(product.hpp_raw) || 0));
                addText(meta, 'span', 'stock-unit', '/' + (product.unit || 'unit').toLocaleLowerCase('id'));
                if (product.sku) {
                    addText(meta, 'span', 'stock-dot', '•');
                    addText(meta, 'span', 'stock-sku', product.sku);
                }
                const stock = Number(product.stock_raw) || 0;
                const balance = addText(row, 'span', 'stock-balance' + (stock <= 0 ? ' is-empty' : stock <= Number(product.limit || 0) ? ' is-low' : ''), number.format(stock) + ' ');
                addText(balance, 'small', '', (product.unit || 'unit').toLocaleLowerCase('id'));
                el.rows.appendChild(row);
            }

            async function load(reset = false) {
                if (controller) controller.abort();
                controller = new AbortController();
                const signal = controller.signal;
                if (reset) {
                    shown = 0;
                    el.rows.replaceChildren();
                    el.results.scrollTo(0, 0);
                }
                el.loading.hidden = false;
                el.empty.hidden = true;
                el.more.hidden = true;
                const [requestedSort, requestedDirection] = el.sort.value.split(':');
                const sort = requestedSort === 'none' ? 'stock_available' : requestedSort;
                const direction = requestedDirection || 'desc';
                const columns = { name: 0, hpp: 1, stock_available: 2 };
                const params = new URLSearchParams({
                    draw: '1', start: String(shown), length: String(pageSize),
                    'search[value]': el.search.value.trim(),
                    'order[0][column]': String(columns[sort] ?? 2),
                    'order[0][dir]': direction || 'desc',
                    branch: el.branch.value, stock_kategori: el.category.value,
                    stock_filter: el.status.value
                });
                try {
                    const response = await fetch(endpoint + '?' + params, { headers: { Accept: 'application/json' }, signal });
                    if (!response.ok) throw new Error('Data stok gagal dimuat.');
                    const payload = await response.json();
                    if (payload.error) throw new Error(payload.error);
                    (payload.data || []).forEach(addRow);
                    shown += (payload.data || []).length;
                    const total = Number(payload.recordsFiltered) || 0;
                    el.empty.hidden = total !== 0;
                    el.more.hidden = shown >= total;
                    document.getElementById('stock-more-count').textContent = number.format(Math.min(pageSize, total - shown));
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        el.empty.hidden = false;
                        el.empty.querySelector('strong').textContent = 'Gagal memuat stok';
                        el.empty.querySelector('span').textContent = 'Coba muat ulang halaman ini.';
                    }
                } finally {
                    if (!signal.aborted) el.loading.hidden = true;
                }
            }

            el.search.addEventListener('input', () => {
                el['search-clear'].hidden = !el.search.value;
                clearTimeout(debounce);
                debounce = setTimeout(() => load(true), 250);
            });
            el['search-clear'].addEventListener('click', () => {
                el.search.value = '';
                el['search-clear'].hidden = true;
                load(true);
                el.search.focus();
            });
            ['branch', 'category', 'status', 'sort'].forEach(key => el[key].addEventListener('change', () => {
                refreshFilterIndicator();
                load(true);
            }));
            document.querySelectorAll('.stock-filter-option[data-category]').forEach(option => option.addEventListener('click', () => updateFilter('category', option.dataset.category)));
            document.querySelectorAll('.stock-filter-option[data-sort]').forEach(option => option.addEventListener('click', () => {
                const values = mobileFilter.matches && !el.filters.hidden ? pendingFilters : currentFilters();
                const [field, direction] = values.sort.split(':');
                const nextDirection = field === option.dataset.sort && direction === 'desc' ? 'asc' : 'desc';
                updateFilter('sort', option.dataset.sort + ':' + nextDirection);
            }));
            document.getElementById('stock-category-reset').addEventListener('click', () => updateFilter('category', 'all'));
            document.getElementById('stock-sort-reset').addEventListener('click', () => updateFilter('sort', 'none'));
            branchSearch.addEventListener('input', filterBranches);
            branchChoices.forEach(input => input.addEventListener('change', () => { if (input.checked) updateFilter('branch', input.value); }));
            pendingStatus.addEventListener('change', () => updateFilter('status', pendingStatus.value));
            document.querySelectorAll('[data-stock-filter-close]').forEach(button => button.addEventListener('click', () => setFilterOpen(false)));
            document.getElementById('stock-filter-apply').addEventListener('click', () => {
                const changed = JSON.stringify(currentFilters()) !== JSON.stringify(pendingFilters);
                Object.entries(pendingFilters).forEach(([key, value]) => { el[key].value = value; });
                setFilterOpen(false);
                if (changed) load(true);
            });
            el['filter-toggle'].addEventListener('click', () => setFilterOpen(el.filters.hidden));
            el.backdrop.addEventListener('click', () => setFilterOpen(false));
            el['filter-reset'].addEventListener('click', () => {
                el.branch.value = el.branch.dataset.default;
                el.category.value = 'all';
                el.status.value = 'all';
                el.sort.value = 'none';
                setFilterOpen(false);
                refreshFilterIndicator();
                load(true);
            });
            el.more.querySelector('button').addEventListener('click', () => load());
            window.addEventListener('mobile-search-toggle', () => el.search.focus());
            document.addEventListener('keydown', event => {
                if (el.filters.hidden) return;
                if (event.key === 'Escape') { event.preventDefault(); setFilterOpen(false); return; }
                if (event.key !== 'Tab') return;
                const controls = [...el.filters.querySelectorAll('button:not(:disabled), input:not(:disabled), select:not(:disabled)')].filter(control => control.getClientRects().length);
                if (!controls.length) { event.preventDefault(); return; }
                const first = controls[0], last = controls[controls.length - 1];
                if (event.shiftKey && (document.activeElement === first || document.activeElement === el.filters)) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && (document.activeElement === last || document.activeElement === el.filters)) { event.preventDefault(); first.focus(); }
            });
            mobileFilter.addEventListener('change', () => setFilterOpen(false));
            window.addEventListener('resize', () => { if (!el.filters.hidden) positionFilters(); });
            refreshFilterIndicator();
            load(true);
        });
    </script>
@endsection
