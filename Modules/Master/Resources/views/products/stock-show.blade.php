@extends('template.root')

@section('title', $data->name . ' - Stok Barang')
@section('page-title', $data->name)
@section('page-breadcrumb')
    <span>Inventaris</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <a href="{{ route('product-stock') }}">Stok Barang</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Riwayat Pergerakan</span>
@endsection

@section('content')
    {{-- @livewire('product-table') --}}
    <style>
        #kt_content_container { display:flex; flex:1; flex-direction:column; min-height:0; }
        .product-stock-detail-page { display:flex; min-height:0; flex:1; flex-direction:column; }
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
        .stock-detail-search-filter .form-control { height:40px; border:1px solid #e5e7eb; border-radius:9px; background:#fff; font-size:13px; }
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
        .stock-detail-scroll { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:none; background:#fff; }
        .stock-detail-scroll::-webkit-scrollbar { display:none; }
        .stock-mobile-list { display:block; }
        .product-stock-detail-card .card-body { display:flex; flex:1; min-height:0; flex-direction:column; overflow:hidden; padding:0!important; }
        #products-stock-table_wrapper { display:none!important; }
        .stock-mobile-empty { display:flex; min-height:220px; flex-direction:column; align-items:center; justify-content:center; padding:32px 24px; color:#6b7280; text-align:center; }
        .stock-mobile-empty i { margin-bottom:10px; color:#9ca3af; font-size:28px; }
        .stock-mobile-empty strong { margin-bottom:4px; color:#1f2937; font-size:14px; }
        .stock-mobile-empty span { font-size:12px; }
        @media (min-width:768px) { .stock-detail-load-more { display:flex; justify-content:center; } .stock-detail-load-more button { width:auto; padding:0 24px; border-width:1px; border-style:solid; border-radius:999px; } }
        @media (min-width:768px) {
            .product-stock-detail-card { position:relative; display:flex; min-height:0; flex:1; flex-direction:column; overflow:hidden; }
            .product-stock-detail-card .card-header { flex:none; padding:0; border:0; }
            .stock-detail-table-heading { margin-top:14px; }
        }
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
            #kt_content { padding:0!important; overflow:hidden; }
            #kt_post { margin:0; min-height:0; }
            #kt_content_container { display:flex; flex:1; flex-direction:column; min-height:0; width:100%; max-width:none; padding:0; }
            .product-stock-detail-page { display:flex; width:100%; height:100%; min-height:0; flex:1; flex-direction:column; padding-bottom:0; box-sizing:border-box; }
            .product-stock-detail-card { display:flex; height:100%; min-height:0; flex-direction:column; border:0; border-radius:0; box-shadow:none; }
            .product-stock-detail-card .card-header { display:flex; flex:none; flex-direction:column; align-items:stretch; gap:0!important; padding:0!important; border:0; }
            .product-stock-detail-card .card-body { display:flex; flex:1; min-height:0; flex-direction:column; overflow:hidden; padding:0!important; }
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
            .stock-detail-scroll:has(.stock-detail-history-row:nth-child(6)):has(#stock-detail-load-more[hidden]) { display:flex; flex-direction:column; }
            .stock-detail-scroll:has(.stock-detail-history-row:nth-child(6)):has(#stock-detail-load-more[hidden]) .stock-mobile-list { display:flex; flex:1 0 auto; flex-direction:column; }
            .stock-detail-scroll:has(.stock-detail-history-row:nth-child(6)):has(#stock-detail-load-more[hidden]) .stock-mobile-list > .stock-detail-history-row { flex:1 0 auto; }
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
    </style>
    <x-erp.filter-sheet
        id="stock-detail"
        title="Filter Riwayat Stok"
        aria-label="Filter riwayat stok"
        subtitle="Sesuaikan parameter riwayat pergerakan barang"
        search-label="Cari aktivitas"
        search-placeholder="Cari aktivitas atau referensi..."
        branch-label="Cabang"
        branch-all-label="Semua Cabang"
        :branches="$branch"
        :groups="[
            ['key' => 'direction', 'label' => 'Arah Mutasi', 'options' => ['all' => 'Semua', 'in' => 'Masuk', 'out' => 'Keluar']],
            ['key' => 'reference', 'label' => 'Sumber Transaksi', 'options' => ['all' => 'Semua', 'stock-opname' => 'Stock Opname', 'sortir' => 'Sortir', 'pos' => 'POS', 'production' => 'Produksi', 'wholesale' => 'Pengadaan', 'transfer' => 'Transfer']],
        ]"
        :quick-ranges="['week', 'today', 'month']"
        :timezone="config('app.timezone')"
        desktop="modal"
        reset-applies
        reset-label="Reset Filter"
        apply-label="Terapkan Filter"
    />
    <div class="product-stock-detail-page">
        <div class="card card-flush product-stock-detail-card">
            <!--begin::Card header-->
            <div class="card-header align-items-stretch p-0 gap-0 flex-column">
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
            </div>
            <!--end::Card header-->
            <div class="stock-detail-table-heading" aria-hidden="true">
                <span>Aktivitas</span>
                <div class="stock-detail-table-heading__numbers"><span id="history-col-one">Awal</span><span id="history-col-two">Mutasi</span><span id="history-col-three" style="color:#0b595b">Saldo</span></div>
            </div>
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <div class="stock-detail-scroll">
                    <div id="stock-mobile-list" class="stock-mobile-list" aria-live="polite"></div>
                    <div class="stock-detail-load-more" id="stock-detail-load-more" hidden>
                        <button type="button"><i class="ph ph-arrow-circle-down" aria-hidden="true"></i> <span id="stock-detail-load-more-label">Muat Lebih Banyak</span></button>
                    </div>
                </div>
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="products-stock-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-100px">Product</th>
                            <th class="text-end min-w-70px">Quantity</th>
                            <th class="text-end min-w-70px">Stock Awal</th>
                            <th class="text-end min-w-70px">Stock Akhir</th>
                            <th class="text-end min-w-100px">Tanggal</th>
                            <th class="text-end min-w-100px">Reference</th>
                            <th class="text-end min-w-100px">Created By</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
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

        // The sheet itself is the shared filter-sheet component (id "stock-detail"). It edits a draft; this page
        // keeps what the table is currently filtered by and hands it over each time the sheet opens.
        const stockFilters = { branch: 'all', reference: 'all', direction: 'all', start: '', end: '', search: '' };

        function updateStockDetailFilterState() {
            const hasActiveFilter = stockFilters.branch !== 'all' || stockFilters.reference !== 'all'
                || stockFilters.direction !== 'all' || Boolean(stockFilters.start || stockFilters.end || stockFilters.search);
            $('#stock-detail-filter-toggle').toggleClass('is-active', hasActiveFilter);
            $('#stock-detail-filter-dot').prop('hidden', !hasActiveFilter);
        }

        function openStockDetailFilters() {
            const isHpp = activeHistoryTab === 'hpp';
            $('#stock-detail-filter-toggle').attr('aria-expanded', 'true');
            window.dispatchEvent(new CustomEvent('erp-filter-open', { detail: {
                id: 'stock-detail',
                title: isHpp ? 'Filter Riwayat HPP' : 'Filter Riwayat Stok',
                subtitle: isHpp ? 'Sesuaikan parameter riwayat pergerakan HPP' : 'Sesuaikan parameter riwayat pergerakan barang',
                values: {
                    branch: stockFilters.branch,
                    start: stockFilters.start,
                    end: stockFilters.end,
                    search: stockFilters.search,
                    extra: { direction: stockFilters.direction, reference: stockFilters.reference }
                }
            } }));
        }

        function applyStockDetailFilters(values) {
            stockFilters.branch = values.branch || 'all';
            stockFilters.reference = values.extra?.reference || 'all';
            stockFilters.direction = values.extra?.direction || 'all';
            stockFilters.start = values.start || '';
            stockFilters.end = values.start ? (values.end || values.start) : '';
            stockFilters.search = values.search || '';
            updateStockDetailFilterState();
            if (typeof dataTable !== 'undefined') dataTable.search(stockFilters.search).draw();
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
                search:{search:stockFilters.search},
                ordering:true,
                scrollX:true,
                ajax:{
                    url:isHpp ? @json(route('product-transaction-data')) : @json(route('product-stock-data-show')),
                    data:function(d) {
                        d.product_id = {{ $data->id }};
                        d.branch = stockFilters.branch;
                        d.reference = stockFilters.reference;
                        d.stock_direction = stockFilters.direction;
                        d.start_date = stockFilters.start;
                        d.end_date = stockFilters.end;
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
            const filterToggle = document.getElementById('stock-detail-filter-toggle');
            if (filterToggle) filterToggle.setAttribute('aria-label', isHpp ? 'Filter riwayat HPP' : 'Filter riwayat stok');
        }

        $(document).ready(function() {
            initializeHistoryTable('stock');

            $(document).on('click', '[data-history-toggle]', function() {
                initializeHistoryTable(activeHistoryTab === 'stock' ? 'hpp' : 'stock');
            });

            $('#stock-detail-load-more button').on('click', function() {
                const page = dataTable.page.info();
                dataTable.page.len(Math.min(page.recordsDisplay, page.length + 50)).draw('page');
            });

            $('#stock-detail-filter-toggle').on('click', openStockDetailFilters);
            window.addEventListener('erp-filter-apply', function(event) {
                if (event.detail.id === 'stock-detail') applyStockDetailFilters(event.detail.values);
            });
            window.addEventListener('erp-filter-close', function(event) {
                if (event.detail.id === 'stock-detail') $('#stock-detail-filter-toggle').attr('aria-expanded', 'false');
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                let val = $(this).val();
                if (val === 'all') val = '';
                dataTable.column(5).search(val).draw();
            });

            $('[data-kt-ecommerce-product-filter="stock"]').on('change', function() {
                dataTable.draw();
            });

            updateStockDetailFilterState();
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
