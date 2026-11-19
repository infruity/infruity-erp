@extends('template.root')

@section('title', 'Stok Barang')
@section('page-title', 'Stok Barang')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-400 transition hover:text-[#0b595b]">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Stok Barang</span>
@endsection

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
        .stock-page { display:flex; flex:1; flex-direction:column; min-height:0; font-family:'Plus Jakarta Sans',sans-serif; }
        .stock-card { position:relative; display:flex; flex:1; flex-direction:column; min-height:0; overflow:visible; border:1px solid #f1f3f5; border-radius:16px; background:#fff; }
        .stock-toolbar { position:relative; z-index:20; display:flex; align-items:center; padding:12px 16px; border-bottom:1px solid #f1f3f5; }
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
        #stock-rows > .stock-row:last-child { border-bottom:0; }
        .stock-more button:hover { border-color:#e5e7eb !important; background:#fff !important; color:#6b7280 !important; }

        /* Search pill + filter trigger, measured on the Stok Barang reference */
        .stock-search { position:relative; display:flex; flex:1; min-width:0; max-width:720px; align-items:center; height:44px; padding:0 6px 0 16px; border:1px solid rgba(229,231,235,.8); border-radius:999px; background:rgba(249,250,251,.5); transition:all .15s; }
        .stock-search__icon { flex:none; width:18px; height:18px; margin-right:10px; color:rgba(11,89,91,.8); }
        .stock-search__input { flex:1; min-width:0; height:100%; margin:0; border:0!important; outline:0!important; padding:0!important; background:transparent!important; box-shadow:none!important; color:#1f2937; font-family:inherit; font-size:16px!important; font-weight:500; }
        .stock-search__input::placeholder { color:#9ca3af; opacity:1; }
        .stock-search__input::-webkit-search-cancel-button { display:none; }
        .stock-search__clear { display:flex; flex:none; align-items:center; justify-content:center; width:24px; height:24px; margin-right:4px; border:0; border-radius:999px; padding:0; background:transparent; color:#9ca3af; cursor:pointer; }
        .stock-search__clear i { font-size:12px; }
        .stock-search__clear:hover { background:rgba(0,0,0,.05); color:#4b5563; }
        .stock-search__divider { flex:none; width:1px; height:16px; margin:0 4px; background:#e5e7eb; }
        .stock-search__filter { position:relative; display:flex; flex:none; align-items:center; justify-content:center; width:32px; height:32px; border:0; border-radius:999px; padding:0; background:transparent; color:#9ca3af; cursor:pointer; transition:color .15s, transform .1s; -webkit-tap-highlight-color:transparent; }
        .stock-search__filter:hover, .stock-search__filter.is-active, .stock-search__filter[aria-expanded="true"] { color:#0b595b; }
        .stock-search__filter:active { transform:scale(.95); }
        .stock-search__filter .stock-tune { font-family:'Material Symbols Rounded'; font-size:20px; font-weight:300; font-style:normal; line-height:1; letter-spacing:normal; text-transform:none; white-space:nowrap; font-feature-settings:'liga'; -webkit-font-feature-settings:'liga'; font-variation-settings:'wght' 300; }
        .stock-search__filter.is-active .stock-tune, .stock-search__filter[aria-expanded="true"] .stock-tune { font-variation-settings:'wght' 600; }
        .stock-search__filter-dot { position:absolute; top:4px; right:4px; width:8px; height:8px; border-radius:50%; background:#0b595b; box-shadow:0 0 0 2px #fff; }

        /* Filter bottom sheet, measured on the Stok Barang reference at 400px */
        .sfs { position:fixed; inset:0; z-index:1100; display:flex; flex-direction:column; justify-content:flex-end; overflow:hidden; font-family:'Plus Jakarta Sans',sans-serif; }
        .sfs[hidden], .sfs [hidden], .sfs [x-cloak] { display:none!important; }
        .sfs__backdrop { position:absolute; inset:0; border:0; padding:0; background:rgba(0,0,0,.7); cursor:default; }
        .sfs__panel { position:relative; z-index:1; display:flex; width:100%; max-height:85vh; flex-direction:column; overflow:hidden; border-radius:24px 24px 0 0; background:#fff; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); padding-bottom:env(safe-area-inset-bottom); color:#1f2937; font-size:12px; line-height:16px; }
        .sfs__handle { display:flex; flex:none; justify-content:center; padding:12px 0 4px; cursor:pointer; }
        .sfs__handle span { width:64px; height:6px; border-radius:999px; background:#d1d5db; }
        .sfs__head { display:flex; flex:none; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #f3f4f6; background:#fff; }
        .sfs .sfs__title { margin:0; color:#1f2937!important; font-size:18px; font-weight:700; line-height:28px; }
        .sfs__close { display:flex; width:32px; height:32px; align-items:center; justify-content:center; border:0; border-radius:999px; padding:0; background:#f3f4f6; color:#6b7280; font-size:16px; cursor:pointer; transition:background-color .15s, transform .1s; }
        .sfs__close:hover { background:#e5e7eb; }
        .sfs__close:active { transform:scale(.95); }
        .sfs__body { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:none; padding:16px; scrollbar-width:none; }
        .sfs__body::-webkit-scrollbar { display:none; }
        .sfs__section { padding:0 8px; }
        .sfs__section--branch { margin-bottom:24px; }
        .sfs__section--category { margin-bottom:20px; }
        .sfs__section--sort { margin-bottom:16px; }
        .sfs__label-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
        .sfs__label { display:block; margin:0; color:#6b7280; font-size:12px; font-weight:700; letter-spacing:.6px; line-height:16px; text-transform:uppercase; }
        .sfs__reset { border:0; padding:0; background:none; color:#0b595b; font-size:11px; font-weight:600; line-height:16px; cursor:pointer; }
        .sfs__reset:hover { text-decoration:underline; }
        .sfs__box { display:flex; flex-direction:column; overflow:hidden; border:1px solid #e5e7eb; border-radius:8px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.05); }
        .sfs__search { position:relative; flex:none; border-bottom:1px solid #f3f4f6; }
        .sfs__search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:12px; line-height:16px; }
        .sfs__search input { display:block; width:100%; height:44px; margin:0; border:0!important; outline:0!important; padding:0 12px 0 32px!important; background:transparent!important; box-shadow:none!important; color:#1f2937; font-family:inherit; font-size:16px!important; font-weight:400; transition:background-color .15s; }
        .sfs__search input:focus { background:#f9fafb!important; }
        .sfs__search input::placeholder { color:#9ca3af; opacity:1; }
        .sfs__search input::-webkit-search-cancel-button { display:none; }
        .sfs__list-wrap { position:relative; width:100%; max-height:123px; }
        .sfs__list { max-height:123px; overflow-y:auto; overscroll-behavior:none; background:rgba(249,250,251,.3); scrollbar-width:none; }
        .sfs__list::-webkit-scrollbar { display:none; }
        .sfs__branch { display:flex; align-items:center; gap:12px; margin:0; padding:12px; border-bottom:1px solid #f3f4f6; cursor:pointer; transition:background-color .15s; }
        .sfs__branch:last-child { border-bottom:0; }
        .sfs__branch:hover { background:#f9fafb; }
        .sfs__radio { position:relative; display:flex; width:16px; height:16px; flex:none; align-items:center; justify-content:center; }
        .sfs__radio input { width:16px; height:16px; margin:0; border:2px solid #d1d5db; border-radius:999px; appearance:none; -webkit-appearance:none; background:transparent; cursor:pointer; transition:border-color .15s; }
        .sfs__radio input:checked { border-color:#10b981; }
        .sfs__radio i { position:absolute; width:8px; height:8px; border-radius:999px; background:#10b981; transform:scale(0); transition:transform .2s; }
        .sfs__radio input:checked + i { transform:scale(1); }
        .sfs__branch-name { color:#374151; font-size:12px; font-weight:500; line-height:16px; }
        .sfs__branch-name.is-on { color:#111827; font-weight:700; }
        .sfs__none { padding:16px; color:#9ca3af; font-size:12px; text-align:center; }
        .sfs__thumb-track { position:absolute; top:0; right:2px; width:4px; height:100%; padding:4px 0; pointer-events:none; box-sizing:border-box; }
        .sfs__thumb { width:100%; border-radius:999px; background:#d1d5db; transition:opacity .3s ease-in-out; }
        .sfs__chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .sfs__chip { border:1px solid #e5e7eb; border-radius:999px; padding:6px 12px; background:#fff; color:#4b5563; font-family:inherit; font-size:12px; font-weight:500; line-height:16px; cursor:pointer; transition:all .15s; }
        .sfs__chip:hover { border-color:#6ee7b7; }
        .sfs__chip.is-on { border-color:#a7f3d0; background:#ecfdf5; color:#047857; font-weight:600; }
        button.sfs__check { width:100%; border:0; border-bottom:1px solid #f3f4f6; background:transparent; text-align:left; font-family:inherit; }
        button.sfs__check:last-of-type { border-bottom:0; }
        .sfs__box-mark { display:flex; width:16px; height:16px; flex:none; align-items:center; justify-content:center; border:2px solid #d1d5db; border-radius:4px; background:#fff; color:transparent; font-size:10px; transition:all .15s; }
        .sfs__check.is-on .sfs__box-mark { border-color:#10b981; background:#10b981; color:#fff; }
        .sfs__sort > button { display:flex; width:100%; align-items:center; justify-content:space-between; border:0; border-top:1px solid #f3f4f6; padding:10px 0; background:transparent; text-align:left; font-family:inherit; cursor:pointer; transition:opacity .15s; }
        .sfs__sort > button:first-child { border-top:0; }
        .sfs__sort > button:active { opacity:.75; }
        .sfs__sort-name { display:flex; align-items:center; gap:10px; }
        .sfs__sort-name i { color:#9ca3af; font-size:16px; line-height:24px; }
        .sfs__sort-name span { color:#374151; font-size:12px; font-weight:500; line-height:16px; }
        .sfs__sort > button.is-on .sfs__sort-name i { color:#0b595b; }
        .sfs__sort > button.is-on .sfs__sort-name span { color:#111827; font-weight:700; }
        .sfs__sort-state { display:flex; align-items:center; gap:4px; color:#0b595b; font-size:12px; font-weight:700; line-height:16px; }
        .sfs__sort-state i { font-size:12px; }
        .sfs__sort-idle { color:#d1d5db; font-size:14px; line-height:20px; }
        .sfs__foot { display:flex; flex:none; align-items:center; gap:8px; padding:16px 16px calc(16px + env(safe-area-inset-bottom)); border-top:1px solid #f3f4f6; background:#fff; }
        .sfs__foot button { flex:1; height:44px; border-radius:12px; font-family:inherit; font-size:14px; font-weight:600; line-height:20px; cursor:pointer; transition:background-color .15s, transform .1s; }
        .sfs__cancel { border:1px solid #e5e7eb; background:transparent; color:#4b5563; }
        .sfs__cancel:hover { background:#f9fafb; }
        .sfs__apply { border:0; background:#0b595b; color:#fff; box-shadow:0 1px 2px rgba(0,0,0,.05); }
        .sfs__apply:hover { background:#0a4e50; }
        @media (min-width:640px) {
            .sfs { align-items:center; justify-content:center; padding:16px; }
            .sfs__panel { max-width:512px; max-height:88vh; border-radius:24px; }
            .sfs__handle { display:none; }
        }
        @media (max-width:767.98px) {
            #kt_content { overflow:hidden!important; padding:0!important; }
            #kt_content_container { display:flex; flex:1; flex-direction:column; min-height:0; width:100%; max-width:none; padding:0; }
            .stock-card { border:0; border-radius:0; }
            .stock-toolbar { padding:16px; }
            .stock-row { gap:12px; min-height:64px; padding:14px 20px; }
            .stock-icon { width:36px; height:36px; font-size:18px; }
            .stock-name { font-size:14px; font-weight:600; }
            .stock-hpp { color:#9ca3af; font-size:12px; font-weight:400; }
            .stock-unit { font-size:11px; font-weight:400; }
            .stock-balance { min-width:0; font-size:14px; }
            .stock-sku, .stock-dot { display:none; }
            .stock-results { padding-bottom:calc(24px + env(safe-area-inset-bottom)); }
            .stock-more { padding:16px 20px; }
            .stock-toolbar { border-bottom-color:#f3f4f6; }
            .stock-row { min-height:0; padding:14px 20px; }
            .stock-name { color:#111827; line-height:20px; }
            .stock-meta { gap:4px; margin-top:2px; line-height:16px; }
            .stock-hpp, .stock-unit { line-height:16px; }
            .stock-balance { color:#111827; line-height:20px; }
            .stock-balance.is-empty { color:#9ca3af; }
            .stock-balance.is-low { color:#b45309; }
            .stock-balance small { font-weight:400; line-height:15px; }
        }

    </style>

    <div class="stock-page">
        <div class="stock-card relative">
            <div class="stock-toolbar">
                <div class="stock-search">
                    <svg class="stock-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input id="stock-search" type="search" placeholder="Cari..." aria-label="Cari nama produk atau SKU" autocomplete="off" class="stock-search__input">
                    <button id="stock-search-clear" type="button" aria-label="Hapus pencarian" hidden class="stock-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
                    <span class="stock-search__divider" aria-hidden="true"></span>
                    <button id="stock-filter-toggle" type="button" aria-label="Filter dan urutkan stok" aria-expanded="false" aria-controls="stock-filter-sheet" aria-haspopup="dialog" class="stock-search__filter"><span class="stock-tune" aria-hidden="true">tune</span><span id="stock-filter-dot" hidden class="stock-search__filter-dot"></span></button>
                </div>
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
                <input type="hidden" id="stock-category" value="all">
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
    $stockBranches = $branch->map(fn ($item) => ['id' => (string) $item->id, 'name' => ucwords((string) $item->name)])->values()->all();
    $stockCategories = collect([['id' => 'all', 'name' => 'Semua']])->concat($category->map(fn ($item) => ['id' => (string) $item->id, 'name' => ucwords($item->name)]))->all();
@endphp
<script>
    window.stockFilterSheet = function (config) {
        return {
            open: false, branch: '', picked: [], categorySearch: '', status: 'all',
            catThumbHeight: 20, catThumbTop: 0, catScrolling: false, catTimer: null, sortField: 'none', sortDir: 'desc', branchSearch: '',
            branches: config.branches, categories: config.categories,
            thumbHeight: 20, thumbTop: 0, scrolling: false, scrollTimer: null,
            fields: { stock: 'stock_available', name: 'name', hpp: 'hpp' },
            onOpen(event) {
                if (event.detail?.id !== 'stock') return;
                const values = event.detail.values || {};
                const [field, dir] = String(values.extra?.sort || 'none').split(':');
                const field_ = Object.keys(this.fields).find(key => this.fields[key] === field) || 'none';
                this.branch = String(values.branch ?? '');
                this.status = values.status || 'all';
                this.picked = String(values.extra?.category || 'all').split(',').filter(id => id !== '' && id !== 'all');
                this.sortField = field_;
                this.sortDir = dir || 'desc';
                this.branchSearch = '';
                this.categorySearch = '';
                this.open = true;
                this.$nextTick(() => this.updateScroll(false));
            },
            close() {
                if (!this.open) return;
                this.open = false;
                window.dispatchEvent(new CustomEvent('erp-filter-close', { detail: { id: 'stock' } }));
            },
            matches(name) { return name.toLowerCase().includes(this.branchSearch.toLowerCase()); },
            get visibleBranches() { return this.branches.filter(row => this.matches(row.name)); },
            get visibleCategories() { return this.categories.filter(row => row.name.toLowerCase().includes(this.categorySearch.toLowerCase())); },
            updateCategoryScroll(flash = true) {
                const el = this.$refs.categoryArea;
                if (!el || el.scrollHeight <= el.clientHeight) { this.catScrolling = false; return; }
                const ratio = el.clientHeight / el.scrollHeight;
                this.catThumbHeight = Math.max(ratio * el.clientHeight, 20);
                this.catThumbTop = (el.scrollTop / el.scrollHeight) * el.clientHeight;
                if (!flash) return;
                this.catScrolling = true;
                clearTimeout(this.catTimer);
                this.catTimer = setTimeout(() => { this.catScrolling = false; }, 800);
            },
            isPicked(id) { return id === 'all' ? this.picked.length === 0 : this.picked.includes(id); },
            togglePick(id) {
                if (id === 'all') { this.picked = []; return; }
                this.picked = this.picked.includes(id) ? this.picked.filter(item => item !== id) : [...this.picked, id];
            },
            toggleSort(field) {
                if (this.sortField !== field) { this.sortField = field; this.sortDir = 'desc'; }
                else this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
            },
            resetSort() { this.sortField = 'none'; this.sortDir = 'desc'; },
            updateScroll(flash = true) {
                const el = this.$refs.scrollArea;
                if (!el || el.scrollHeight <= el.clientHeight) { this.scrolling = false; return; }
                const ratio = el.clientHeight / el.scrollHeight;
                this.thumbHeight = Math.max(ratio * el.clientHeight, 20);
                this.thumbTop = (el.scrollTop / el.scrollHeight) * el.clientHeight;
                if (!flash) return;
                this.scrolling = true;
                clearTimeout(this.scrollTimer);
                this.scrollTimer = setTimeout(() => { this.scrolling = false; }, 800);
            },
            apply() {
                const sort = this.sortField === 'none' ? 'none' : this.fields[this.sortField] + ':' + this.sortDir;
                window.dispatchEvent(new CustomEvent('erp-filter-apply', { detail: { id: 'stock', values: { branch: this.branch, status: this.status, extra: { category: this.picked.length ? this.picked.join(',') : 'all', sort } } } }));
                this.close();
            },
        };
    };
</script>
<div x-data="stockFilterSheet(@js(['branches' => $stockBranches, 'categories' => $stockCategories]))" @erp-filter-open.window="onOpen($event)" @keydown.escape.window="close()">
    <template x-teleport="body">
        <div id="stock-filter-sheet" class="sfs" x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Filter dan urutkan stok" aria-labelledby="stock-filter-title">
            <button type="button" class="sfs__backdrop" x-show="open" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="close()" aria-label="Tutup filter"></button>
            <section class="sfs__panel" x-show="open" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
                <div class="sfs__handle" @click="close()"><span></span></div>
                <div class="sfs__head">
                    <h2 id="stock-filter-title" class="sfs__title">Filter</h2>
                    <button type="button" class="sfs__close" @click="close()" aria-label="Tutup"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                </div>
                <div class="sfs__body">
                    <div class="sfs__section sfs__section--branch">
                        <div class="sfs__label-row"><span class="sfs__label">Cabang</span></div>
                        <div class="sfs__box">
                            <div class="sfs__search">
                                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" x-model="branchSearch" placeholder="Cari cabang..." aria-label="Cari cabang" autocomplete="off" @input="$nextTick(() => updateScroll(true))">
                            </div>
                            <div class="sfs__list-wrap">
                                <div x-ref="scrollArea" class="sfs__list" @scroll="updateScroll()">
                                    <template x-for="row in visibleBranches" :key="row.id">
                                        <label class="sfs__branch">
                                            <span class="sfs__radio"><input type="radio" name="stockFilterBranch" :value="row.id" x-model="branch"><i></i></span>
                                            <span class="sfs__branch-name" :class="branch === row.id && 'is-on'" x-text="row.name"></span>
                                        </label>
                                    </template>
                                    <div class="sfs__none" x-show="visibleBranches.length === 0">Cabang tidak ditemukan</div>
                                </div>
                                <div class="sfs__thumb-track"><div class="sfs__thumb" :style="`height:${thumbHeight}px;transform:translateY(${thumbTop}px);opacity:${scrolling ? 1 : 0}`"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="sfs__section sfs__section--category">
                        <div class="sfs__label-row">
                            <span class="sfs__label">Kategori</span>
                            <button type="button" class="sfs__reset" x-show="picked.length > 0" @click="picked = []">Reset</button>
                        </div>
                        <div class="sfs__box">
                            <div class="sfs__search">
                                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" x-model="categorySearch" placeholder="Cari kategori..." aria-label="Cari kategori" autocomplete="off" @input="$nextTick(() => updateCategoryScroll(true))">
                            </div>
                            <div class="sfs__list-wrap">
                                <div x-ref="categoryArea" class="sfs__list" role="group" aria-label="Kategori" @scroll="updateCategoryScroll()">
                                    <template x-for="row in visibleCategories" :key="row.id">
                                        <button type="button" role="checkbox" class="sfs__branch sfs__check" :class="isPicked(row.id) && 'is-on'" :aria-checked="isPicked(row.id)" @click="togglePick(row.id)">
                                            <span class="sfs__box-mark"><i class="ph-bold ph-check" aria-hidden="true"></i></span>
                                            <span class="sfs__branch-name" :class="isPicked(row.id) && 'is-on'" x-text="row.name"></span>
                                        </button>
                                    </template>
                                    <div class="sfs__none" x-show="visibleCategories.length === 0">Kategori tidak ditemukan</div>
                                </div>
                                <div class="sfs__thumb-track"><div class="sfs__thumb" :style="`height:${catThumbHeight}px;transform:translateY(${catThumbTop}px);opacity:${catScrolling ? 1 : 0}`"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="sfs__section sfs__section--sort">
                        <div class="sfs__label-row">
                            <span class="sfs__label">Urutkan</span>
                            <button type="button" class="sfs__reset" x-show="sortField !== 'none'" @click="resetSort()">Reset</button>
                        </div>
                        <div class="sfs__sort">
                            @foreach ([['stock', 'ph-package', 'Stok'], ['name', 'ph-text-aa', 'Nama Produk'], ['hpp', 'ph-currency-circle-dollar', 'HPP Produk']] as [$field, $icon, $label])
                                <button type="button" :class="sortField === '{{ $field }}' && 'is-on'" @click="toggleSort('{{ $field }}')">
                                    <span class="sfs__sort-name"><i class="ph-bold {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></span>
                                    <span>
                                        <span class="sfs__sort-state" x-show="sortField === '{{ $field }}'"><span x-text="sortDir === 'desc' ? 'Z - A' : 'A - Z'"></span><i class="ph-bold" :class="sortDir === 'desc' ? 'ph-arrow-down' : 'ph-arrow-up'" aria-hidden="true"></i></span>
                                        <i class="ph ph-caret-up-down sfs__sort-idle" x-show="sortField !== '{{ $field }}'" aria-hidden="true"></i>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="sfs__foot">
                    <button type="button" class="sfs__cancel" @click="close()">Batal</button>
                    <button type="button" class="sfs__apply" @click="apply()">Terapkan</button>
                </div>
            </section>
        </div>
    </template>
</div>

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

            // The sheet is the stockFilterSheet component above (same open/apply events as the shared sheet). The four hidden selects
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
