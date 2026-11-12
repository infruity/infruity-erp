@extends('template.root')

@section('title', 'Stok Barang')
@section('page-title', 'Stok Barang')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-400 transition hover:text-[#0b595b]">Laporan</a>
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
        .stock-empty[hidden], .stock-loading[hidden], .stock-more[hidden], #stock-search-clear[hidden], #stock-filter-dot[hidden] { display:none!important; }
        .stock-hidden-filter { display:none!important; }
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
        @media (max-width:767.98px) {
            #kt_content { overflow:hidden!important; padding:0!important; }
            #kt_content_container { display:flex; flex:1; flex-direction:column; min-height:0; width:100%; max-width:none; padding:0; }
            .stock-card { border:0; border-radius:0; }
            .stock-toolbar { padding:16px; }
            .stock-search-shell .erp-mobile-search { height:44px; }
            .stock-row { gap:12px; min-height:64px; padding:14px 20px; }
            .stock-icon { width:36px; height:36px; font-size:18px; }
            .stock-name { font-size:14px; font-weight:600; }
            .stock-hpp { font-size:12px; font-weight:600; }
            .stock-unit { font-size:11px; font-weight:400; }
            .stock-balance { min-width:0; font-size:14px; }
            .stock-sku, .stock-dot { display:none; }
            .stock-results { padding-bottom:calc(6rem + env(safe-area-inset-bottom)); }
        }

    </style>

    <div class="stock-page">
        <div class="stock-card relative">
            <div class="stock-toolbar">
                <x-erp.mobile-search class="stock-search-shell erp-mobile-search-shell--always-visible erp-mobile-search-shell--fill">
                    <x-slot:input>
                        <input id="stock-search" type="search" placeholder="Cari nama produk atau SKU..." aria-label="Cari nama produk atau SKU" autocomplete="off" class="erp-mobile-search__input">
                    </x-slot:input>
                    <x-slot:clear>
                        <button id="stock-search-clear" type="button" aria-label="Hapus pencarian" hidden class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
                    </x-slot:clear>
                    <x-slot:filter>
                        <button id="stock-filter-toggle" type="button" aria-label="Filter dan urutkan stok" aria-expanded="false" aria-controls="stock-filter-sheet" aria-haspopup="dialog" class="erp-mobile-search__filter"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="stock-filter-dot" hidden class="erp-mobile-search__filter-dot"></span></button>
                    </x-slot:filter>
                </x-erp.mobile-search>
            </div>
            <div class="stock-hidden-filter" hidden aria-hidden="true">
                <select id="stock-branch" data-default="{{ $branch->first()->id ?? 'all' }}" tabindex="-1">
                    @foreach ($branch as $item)
                        <option value="{{ $item->id }}" @selected($loop->first)>{{ ucwords($item->name) }}</option>
                    @endforeach
                    <option value="all" @selected($branch->isEmpty())>Semua Cabang</option>
                </select>
                <select id="stock-status" tabindex="-1">
                    <option value="all">Semua stok</option>
                    <option value="ada">Ada</option>
                    <option value="kosong">Kosong atau minus</option>
                </select>
                <select id="stock-category" tabindex="-1">
                    <option value="all">Semua kategori</option>
                    @foreach ($category as $item)
                        <option value="{{ $item->id }}">{{ ucwords($item->name) }}</option>
                    @endforeach
                </select>
                <select id="stock-sort" tabindex="-1">
                    <option value="none">Default</option>
                    <option value="stock_available:desc">Stok terbanyak</option>
                    <option value="stock_available:asc">Stok tersedikit</option>
                    <option value="name:asc">Nama A–Z</option>
                    <option value="name:desc">Nama Z–A</option>
                    <option value="hpp:desc">HPP tertinggi</option>
                    <option value="hpp:asc">HPP terendah</option>
                </select>
                <button id="stock-filter-reset" type="button" tabindex="-1">Reset filter</button>
            </div>
            <x-erp.list-count noun="produk" shown-id="stock-count-shown" total-id="stock-count-total" list=".stock-results" />
            <div id="stock-results" class="stock-results" aria-live="polite">
                <div id="stock-loading" class="stock-loading">Memuat stok barang...</div>
                <div id="stock-empty" class="stock-empty" hidden>
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <strong>Produk tidak ditemukan</strong>
                    <span>Coba ubah kata kunci pencarian atau filter.</span>
                </div>
                <div id="stock-rows"></div>
                <div id="stock-more" class="stock-more" hidden><x-erp.load-more>Muat <span id="stock-more-count">50</span> Produk Lagi</x-erp.load-more></div>
            </div>
        </div>
    </div>

@php
    $stockCategoryOptions = collect(['all' => 'Semua'])->union($category->mapWithKeys(fn ($item) => [(string) $item->id => ucwords($item->name)]))->all();
@endphp
<x-erp.filter-sheet
    id="stock"
    title="Filter"
    aria-label="Filter dan urutkan stok"
    branch-label="Cabang"
    branch-all-label="Semua Cabang"
    status-label="Status Stok"
    :branches="$branch"
    :default-branch="(string) ($branch->first()->id ?? 'all')"
    :statuses="['all' => 'Semua stok', 'ada' => 'Ada', 'kosong' => 'Kosong atau minus']"
    :groups="[
        ['key' => 'category', 'label' => 'Kategori', 'options' => $stockCategoryOptions],
        ['key' => 'sort', 'label' => 'Urutkan', 'type' => 'radio', 'default' => 'none', 'options' => ['none' => 'Default', 'stock_available:desc' => 'Stok terbanyak', 'stock_available:asc' => 'Stok tersedikit', 'name:asc' => 'Nama A–Z', 'name:desc' => 'Nama Z–A', 'hpp:desc' => 'HPP tertinggi', 'hpp:asc' => 'HPP terendah']],
    ]"
    :with-dates="false"
    desktop="modal"
/>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const endpoint = @json(route('product-stock-data'));
            const el = Object.fromEntries(['search', 'search-clear', 'branch', 'category', 'status', 'sort', 'filter-toggle', 'filter-reset', 'results', 'rows', 'loading', 'empty', 'more'].map(key => [key, document.getElementById('stock-' + key)]));
            const pageSize = 50;
            const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
            const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            let shown = 0;
            let controller = null;
            let debounce = null;

            // The sheet itself is the shared filter-sheet component (id "stock"). The four hidden selects
            // above hold what the list is filtered by; the sheet edits a draft and hands the result back.
            function currentFilters() {
                return { branch:el.branch.value, category:el.category.value, status:el.status.value, sort:el.sort.value };
            }

            function refreshFilterIndicator() {
                const applied = currentFilters();
                const active = applied.branch !== el.branch.dataset.default || applied.category !== 'all' || applied.status !== 'all' || applied.sort !== 'none';
                el['filter-toggle'].classList.toggle('is-active', active);
                document.getElementById('stock-filter-dot').hidden = !active;
            }

            function openFilterSheet() {
                const values = currentFilters();
                el['filter-toggle'].setAttribute('aria-expanded', 'true');
                window.dispatchEvent(new CustomEvent('erp-filter-open', { detail: {
                    id: 'stock',
                    values: { branch: values.branch, status: values.status, extra: { category: values.category, sort: values.sort } }
                } }));
            }

            function applySheetFilters(values) {
                const next = { branch: values.branch, category: values.extra?.category || 'all', status: values.status || 'all', sort: values.extra?.sort || 'none' };
                const changed = JSON.stringify(currentFilters()) !== JSON.stringify(next);
                Object.entries(next).forEach(([key, value]) => { el[key].value = value; });
                refreshFilterIndicator();
                if (changed) load(true);
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
                    document.querySelectorAll('#stock-count-shown, #stock-count-shown-mobile').forEach(node => node.textContent = number.format(shown));
                    document.querySelectorAll('#stock-count-total, #stock-count-total-mobile').forEach(node => node.textContent = number.format(total));
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
            el['filter-toggle'].addEventListener('click', openFilterSheet);
            window.addEventListener('erp-filter-apply', event => {
                if (event.detail.id === 'stock') applySheetFilters(event.detail.values);
            });
            window.addEventListener('erp-filter-close', event => {
                if (event.detail.id === 'stock') el['filter-toggle'].setAttribute('aria-expanded', 'false');
            });
            el['filter-reset'].addEventListener('click', () => {
                el.branch.value = el.branch.dataset.default;
                el.category.value = 'all';
                el.status.value = 'all';
                el.sort.value = 'none';
                refreshFilterIndicator();
                load(true);
            });
            el.more.querySelector('button').addEventListener('click', () => load());
            window.addEventListener('mobile-search-toggle', () => el.search.focus());
            refreshFilterIndicator();
            load(true);
        });
    </script>
@endsection
