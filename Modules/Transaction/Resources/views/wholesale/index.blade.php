@extends('layouts.erp-tailwind')

@section('title', 'Pengadaan - Inventaris')
@section('page-title', 'Pengadaan')
@section('page-breadcrumb')
    <span>Inventaris</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Pengadaan</span>
@endsection

@section('content')
    <div x-data="{
        filtersOpen: false,
        sheetOpen: false,
        filterApplied: false,
        syncApplied() { this.filterApplied = ['branch', 'status', 'start', 'end'].some((key) => { const value = getWholesaleFilter(key); return value && value !== 'all'; }); },
        openFilters() {
            if (window.matchMedia('(max-width: 767px)').matches) {
                this.sheetOpen = true;
                this.$dispatch('erp-filter-open', { id: 'wholesale', values: {
                    branch: getWholesaleFilter('branch') || 'all',
                    status: getWholesaleFilter('status') || 'all',
                    start: getWholesaleFilter('start'),
                    end: getWholesaleFilter('end')
                } });
                return;
            }
            this.filtersOpen = !this.filtersOpen;
        }
    }" @erp-filter-apply.window="if ($event.detail.id === 'wholesale') { applyWholesaleMobileFilters($event.detail.values.status, $event.detail.values.branch, $event.detail.values.start, $event.detail.values.end); $nextTick(() => syncApplied()); }" @erp-filter-close.window="if ($event.detail.id === 'wholesale') { sheetOpen = false; $nextTick(() => syncApplied()); }" class="flex min-h-0 flex-1 flex-col -mx-4 md:mx-0">
        <section class="erp-surface relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-none bg-white shadow-none lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm">
            <header class="shrink-0 border-b border-gray-100 px-0 py-0 sm:px-4 sm:py-4 md:px-5">
                <div class="mb-3 hidden items-center justify-between gap-3 md:mb-4 sm:flex">
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-bold text-gray-900 md:text-lg">Pengadaan</h1>
                        <p class="mt-0.5 text-xs text-gray-500">Pantau pembelian bahan dari seluruh cabang yang dapat Anda akses.</p>
                    </div>
                </div>

                <div class="relative flex items-center gap-3">
                    <div class="relative min-w-0 flex-1">
                    <x-erp.mobile-search class="wholesale-search-shell erp-mobile-search-shell--always-visible erp-mobile-search-shell--fill erp-mobile-search-shell--flush">
                        <x-slot:input><input id="wholesale-search" type="search" placeholder="Cari..." autocomplete="off" aria-label="Cari cabang, nomor PO, atau bahan" class="erp-mobile-search__input"></x-slot:input>
                        <x-slot:clear><button id="wholesale-search-clear" type="button" class="erp-mobile-search__clear hidden" aria-label="Hapus pencarian"><i class="ph-bold ph-x text-xs"></i></button></x-slot:clear>
                        <x-slot:filter><button type="button" @click="openFilters()" :aria-expanded="filtersOpen" aria-label="Buka filter pengadaan" class="erp-mobile-search__filter" :class="(filtersOpen || sheetOpen || filterApplied) ? 'is-active' : ''"><span class="icon-rounded text-xl leading-none" :class="(filtersOpen || sheetOpen || filterApplied) ? 'is-bold' : ''" aria-hidden="true">tune</span><span id="wholesale-filter-dot" class="hidden absolute right-1 top-1 h-2 w-2 rounded-full bg-[#0b595b] ring-2 ring-white"></span></button></x-slot:filter>
                    </x-erp.mobile-search>

                    <div x-show="filtersOpen && window.matchMedia('(min-width: 768px)').matches" x-cloak @click.outside="filtersOpen = false" @keydown.escape.window="filtersOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-1 opacity-0 scale-95" x-transition:enter-end="translate-y-0 opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="translate-y-0 opacity-100 scale-100" x-transition:leave-end="translate-y-1 opacity-0 scale-95" class="absolute right-0 top-12 z-50 hidden w-72 rounded-2xl border border-gray-100 bg-white p-4 shadow-xl md:block">
                            <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-2.5">
                                <h2 class="text-xs font-bold uppercase tracking-wide text-gray-600">Filter Pengadaan</h2>
                                <button type="button" class="text-xs font-semibold text-[#0b595b] hover:underline" data-wholesale-filter-reset>Reset</button>
                            </div>
                            <label class="mb-3 block text-xs font-semibold text-gray-600">Status
                                <select data-wholesale-filter="status" class="mt-1.5 h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-emerald-500">
                                    <option value="all">Semua status</option><option value="temp">Draft</option><option value="draft">Pending</option><option value="posting">Dalam Pengiriman</option><option value="complete">Selesai</option>
                                </select>
                            </label>
                            <label class="mb-3 block text-xs font-semibold text-gray-600">Cabang
                                <select data-wholesale-filter="branch" class="mt-1.5 h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-emerald-500">
                                    @if (count($branches) > 1)<option value="all">Semua cabang</option>@endif
                                    @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>@endforeach
                                </select>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block text-xs font-semibold text-gray-600">Dari<input type="date" data-wholesale-filter="start" class="mt-1.5 h-10 w-full rounded-xl border border-gray-200 px-2 text-xs font-normal text-gray-700 outline-none focus:border-emerald-500"></label>
                                <label class="block text-xs font-semibold text-gray-600">Sampai<input type="date" data-wholesale-filter="end" class="mt-1.5 h-10 w-full rounded-xl border border-gray-200 px-2 text-xs font-normal text-gray-700 outline-none focus:border-emerald-500"></label>
                            </div>
                        </div>
                    </div>
                    @if (check_access('wholesale.create'))
                        <a href="{{ route('wholesale.create') }}" class="hidden h-11 shrink-0 items-center gap-2 rounded-full bg-[#0b595b] px-5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#08484a] active:scale-[.98] md:inline-flex">
                            <i class="ph-bold ph-plus text-sm" aria-hidden="true"></i><span>Tambah Pengadaan</span>
                        </a>
                    @endif
                </div>

                <div id="wholesale-summary" class="mt-3 flex items-center justify-end text-[11px] text-gray-500">
                    <span id="wholesale-active-filter" class="hidden max-w-full truncate rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-800"></span>
                </div>
            </header>
            <x-erp.list-count noun="pengadaan" shown-id="wholesale-count-shown" total-id="wholesale-count-total" />

            <div class="mobile-list-edge-fade min-h-0 flex-1 overflow-auto pb-24 md:pb-0">
                <table id="wholesale-table" class="w-full text-sm" width="100%">
                    <thead>
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            <th class="px-4 py-3 md:px-5">Cabang Tujuan</th>
                            <th class="px-4 py-3 md:px-5">No. PO</th>
                            <th class="px-4 py-3 md:px-5">Petugas</th>
                            <th class="px-4 py-3 md:px-5">Item &amp; Volume</th>
                            <th class="px-4 py-3 text-right md:px-5">Status &amp; Nominal</th>
                            <th class="px-4 py-3 text-right md:px-5">Aksi</th>
                            <th>Status</th><th>Tanggal</th><th>ID</th><th>Pemasok</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <x-erp.filter-sheet
            id="wholesale"
            title="Filter Pengadaan"
            aria-label="Filter pengadaan"
            subtitle="Saring cabang tujuan, status, dan periode tanggal."
            branch-label="Cabang Tujuan"
            branch-all-label="Semua Cabang"
            reset-label="Reset Filter"
            apply-label="Terapkan Filter"
            status-label="Status Pengadaan"
            :branches="$branches"
            :show-branch-all="count($branches) > 1"
            :statuses="['all' => 'Semua Status', 'temp' => 'Draft', 'draft' => 'Pending', 'posting' => 'Dalam Pengiriman', 'complete' => 'Selesai']"
        />
        <template x-teleport="body">
            <div x-data="{
                    open: false,
                    visible: false,
                    item: null,
                    show(detail) { this.item = detail; this.visible = true; this.$nextTick(() => { this.open = true; }); document.body.classList.add('overflow-hidden'); this.$nextTick(() => this.$refs.close?.focus()); },
                    close() { this.open = false; document.body.classList.remove('overflow-hidden'); setTimeout(() => { if (!this.open) this.visible = false; }, 220); },
                    remove() { const id = this.item?.id; const label = this.item?.order_number; this.close(); if (id) window.deleteProduct(id, label); }
                }"
                @wholesale-detail-open.window="show($event.detail)"
                @keydown.escape.window="close()"
                x-show="visible" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center px-4" role="presentation">
                <button type="button" x-show="open" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/60 cursor-default" @click="close()" aria-label="Tutup detail pengadaan"></button>
                <section x-show="open" x-transition:enter="transition-all duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition-all duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2" class="relative bg-white w-full max-w-sm rounded-2xl shadow-xl overflow-hidden flex flex-col z-10 outline-none" role="dialog" aria-modal="true" aria-labelledby="wholesale-detail-title" tabindex="-1">
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <h3 id="wholesale-detail-title" class="text-lg font-bold text-gray-800">Detail Pengadaan</h3>
                            <button x-ref="close" type="button" @click="close()" aria-label="Tutup" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 transition-colors"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                        </div>
                        <div class="flex flex-col gap-5 text-sm" x-show="item">
                            <div class="flex gap-3 items-center">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0b595b] border border-emerald-200/80 flex items-center justify-center shrink-0 shadow-2xs"><i class="ph-duotone ph-receipt text-lg" aria-hidden="true"></i></div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-gray-800 leading-snug truncate" x-text="item?.order_number"></div>
                                    <div class="text-[11px] text-gray-400 font-medium mt-0.5 truncate" x-text="item?.branch_name"></div>
                                </div>
                            </div>
                            <div class="flex gap-3 items-center">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0b595b] border border-emerald-200/80 flex items-center justify-center shrink-0 shadow-2xs"><i class="ph-duotone ph-coins text-lg" aria-hidden="true"></i></div>
                                <div>
                                    <div class="font-bold text-gray-800" x-text="item?.total_label"></div>
                                    <div class="text-[11px] text-gray-400 font-medium mt-0.5" x-text="'Grand Total (' + item?.item_count_label + ')'"></div>
                                </div>
                            </div>
                            <div class="flex gap-3 items-center">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0b595b] border border-emerald-200/80 flex items-center justify-center shrink-0 shadow-2xs"><i class="ph-duotone ph-clock text-lg" aria-hidden="true"></i></div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-800 leading-snug truncate" x-text="item?.creator_name"></div>
                                    <div class="text-[11px] text-gray-400 font-medium mt-0.5" x-text="'Dibuat pada ' + item?.order_date_label"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex w-full border-t border-gray-100" x-show="item?.can_edit || item?.can_delete">
                        <a x-show="item?.can_edit" :href="item?.edit_url" class="flex items-center justify-center gap-2 flex-1 py-4 text-sm font-semibold text-[#0b595b] hover:bg-emerald-50 transition-colors"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i>Edit</a>
                        <div x-show="item?.can_edit && item?.can_delete" class="w-px bg-gray-100"></div>
                        <button x-show="item?.can_delete" type="button" class="flex items-center justify-center gap-2 flex-1 py-4 text-sm font-semibold text-red-500 hover:bg-red-50 transition-colors" @click="remove()"><i class="ph-bold ph-trash" aria-hidden="true"></i>Hapus</button>
                    </div>
                </section>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
    <style>
        .wholesale-order-cell { display:flex; align-items:center; gap:12px; min-width:0; }
        .wholesale-order-avatar { display:flex; width:40px; height:40px; flex:none; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:11px; font-weight:700; }
        .wholesale-order-copy { display:flex; min-width:0; flex-direction:column; align-items:flex-start; gap:2px; text-decoration:none; }
        .wholesale-order-number { overflow:hidden; max-width:100%; color:#1f2937; font-size:14px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .wholesale-order-number:hover { color:#0b595b; }
        .wholesale-order-subtitle { color:#6b7280; font-size:12px; }
        .wholesale-order-total { color:#0f5c45; font-size:13px; font-weight:700; font-variant-numeric:tabular-nums; }
        .wholesale-po-link { display:inline-flex; border-radius:6px; background:#ecfdf5; padding:4px 8px; color:#065f46; font-size:11px; font-weight:700; text-decoration:none; white-space:nowrap; }
        .wholesale-staff-cell { display:flex; align-items:center; gap:7px; color:#4b5563; font-size:12px; }
        .wholesale-items-cell { display:flex; flex-direction:column; gap:3px; }
        .wholesale-items-count { color:#374151; font-size:12px; font-weight:700; }
        .wholesale-items-volume { overflow:hidden; max-width:190px; color:#6b7280; font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
        .wholesale-status-cell { display:flex; flex-direction:column; align-items:flex-end; gap:5px; }
        .wholesale-status-total { color:#0f5c45; font-size:14px; font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .wholesale-status-badge { display:inline-flex; align-items:center; min-height:22px; border-radius:999px; padding:3px 9px; font-size:10px; font-weight:700; }
        .wholesale-status-badge.is-draft { background:#f3f4f6; color:#6b7280; }
        .wholesale-status-badge.is-posting { background:#f3f4f6; color:#6b7280; }
        .wholesale-status-badge.is-complete { background:#f0fdf4; color:#166534; }
        .wholesale-status-badge.is-muted { background:#f3f4f6; color:#4b5563; }
        .wholesale-row-actions { display:flex; justify-content:flex-end; gap:4px; }
        .wholesale-action-button { display:inline-flex; width:36px; height:36px; align-items:center; justify-content:center; border:0; border-radius:999px; background:transparent; color:#6b7280; font-size:18px; text-decoration:none; cursor:pointer; }
        .wholesale-action-button:hover { background:#ecfdf5; color:#0b595b; }
        .wholesale-action-button.is-danger:hover { background:#fff1f2; color:#be123c; }
        #wholesale-table { border-collapse:collapse; }
        #wholesale-table thead th { border-bottom:1px solid #f3f4f6; }
        #wholesale-table tbody td { border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        #wholesale-table_wrapper .dataTables_length, #wholesale-table_wrapper .dataTables_filter { display:none; }
        #wholesale-table_wrapper .dataTables_info { padding:14px 16px; color:#6b7280; font-size:11px; }
        #wholesale-table_wrapper .dataTables_paginate { padding:8px 16px 16px; }
        #wholesale-table_wrapper .paginate_button { border-radius:9px !important; padding:6px 10px !important; color:#4b5563 !important; font-size:12px; }
        #wholesale-table_wrapper .paginate_button.current { border:0 !important; background:#ecfdf5 !important; color:#0b595b !important; }
        #wholesale-table td.dataTables_empty { padding:36px 16px !important; color:#6b7280; text-align:center; }
        .wholesale-empty-state { display:flex; min-height:300px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
        .wholesale-empty-icon { display:flex; width:56px; height:56px; align-items:center; justify-content:center; margin-bottom:4px; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
        .wholesale-empty-title { color:#1f2937; font-size:14px; font-weight:700; }
        .wholesale-empty-copy { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
        .wholesale-empty-link { display:inline-flex; align-items:center; gap:7px; margin-top:8px; border-radius:999px; background:#0b595b; padding:9px 15px; color:white; font-size:12px; font-weight:700; text-decoration:none; }
        .wholesale-empty-link:hover { background:#08484a; color:white; }
        .wholesale-order-copy:focus-visible, .wholesale-action-button:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
        #wholesale-table tbody tr.is-openable { cursor:pointer; }
        @media (max-width:1279px) {
            #wholesale-table, #wholesale-table tbody { display:block; width:100% !important; }
            #wholesale-table thead { display:none; }
            #wholesale-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) minmax(126px,auto); grid-template-areas:'order status'; align-items:center; gap:4px 14px; border-bottom:1px solid #f1f3f5; padding:14px clamp(16px,3vw,24px); transition:background-color .16s ease; }
            #wholesale-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
            .mobile-list-edge-fade.has-wholesale-empty-state { position:relative; }
            #wholesale-table_wrapper.has-wholesale-empty-state { position:static; }
            #wholesale-table_wrapper.has-wholesale-empty-state #wholesale-table tbody tr { display:block; height:0; padding:0; border:0; }
            #wholesale-table_wrapper.has-wholesale-empty-state #wholesale-table td.dataTables_empty { height:0; padding:0 !important; border:0 !important; }
            #wholesale-table_wrapper.has-wholesale-empty-state .wholesale-empty-state { position:absolute; top:50%; left:50%; width:calc(100% - 32px); max-width:380px; min-height:0; transform:translate(-50%, -50%); }
            #wholesale-table tbody td:nth-child(1) { grid-area:order; min-width:0; }
            #wholesale-table tbody td:nth-child(2), #wholesale-table tbody td:nth-child(3), #wholesale-table tbody td:nth-child(4) { display:none; }
            #wholesale-table tbody td:nth-child(5) { grid-area:status; }
            #wholesale-table tbody td:nth-child(6) { display:none; }
            #wholesale-table tbody td:nth-child(7), #wholesale-table tbody td:nth-child(8), #wholesale-table tbody td:nth-child(9) { display:none; }
            .wholesale-order-avatar { width:40px; height:40px; border-radius:12px; }
            .wholesale-order-number { font-size:14px; }
            .wholesale-order-subtitle { font-size:12px; }
            .wholesale-status-cell { min-width:0; align-items:flex-end; gap:3px; text-align:right; }
            .wholesale-status-badge { min-height:0; max-width:100%; padding:0; background:transparent !important; font-size:12px; font-weight:500; white-space:nowrap; }
            .wholesale-status-badge.is-draft, .wholesale-status-badge.is-posting, .wholesale-status-badge.is-muted { color:#9ca3af; }
            .wholesale-status-badge.is-complete { color:#0b595b; font-weight:700; }
            .wholesale-status-total { color:#111827; font-size:14px; }
            #wholesale-table_wrapper .dataTables_info, #wholesale-table_wrapper .dataTables_paginate { display:none !important; }
        }
        @media (max-width:767px) {
            #wholesale-summary { display:none; }
            .wholesale-search-shell { padding:16px; }
            #wholesale-table { margin:0 !important; }
            .wholesale-search-shell .erp-mobile-search__filter.is-active { background:#ecfdf5; }
            body.erp-ui #wholesale-filter-sheet .erp-filter-sheet__footer { background:rgba(249,250,251,.7); }
            body.erp-ui #wholesale-filter-sheet#wholesale-filter-sheet [x-show="!calendarOpen"] :is(label, span).uppercase { font-weight:600 !important; line-height:15px !important; }
            .wholesale-search-shell .erp-mobile-search { height:44px; gap:0; padding:0 6px 0 16px; border-color:rgba(229,231,235,.8); box-shadow:none; }
            .wholesale-search-shell .erp-mobile-search__icon { margin-right:10px; }
            .wholesale-search-shell .erp-mobile-search__divider { height:16px; margin:0 4px; }
            #wholesale-table tbody tr { grid-template-columns:minmax(0,1fr) auto; gap:0 12px; padding:12px 16px; border-bottom:1px solid #f3f4f6; }
            .wholesale-order-avatar { width:36px; height:36px; border-radius:12px; font-size:12px; }
            .wholesale-order-cell { gap:12px; }
            .wholesale-order-copy { gap:2px; }
            .wholesale-order-number { font-size:12px; line-height:16px; color:#111827; }
            .wholesale-order-subtitle { color:#9ca3af; font-size:11px; line-height:16.5px; }
            .wholesale-status-cell { gap:2px; }
            .wholesale-status-badge { font-size:12px; font-weight:600; line-height:16px; }
            .wholesale-status-badge.is-complete { color:#059669; font-weight:700; }
            .wholesale-status-total { font-size:12px; font-weight:700; line-height:16px; }
        }
        @media (max-width:359px) {
            #wholesale-table tbody tr { grid-template-columns:minmax(0,1fr) minmax(104px,auto); gap:3px 8px; padding-right:12px; padding-left:12px; }
            .wholesale-order-avatar { width:34px; height:34px; }
            .wholesale-order-cell { gap:8px; }
            .wholesale-order-number { font-size:12px; }
            .wholesale-status-badge { font-size:11px; }
            .wholesale-status-total { font-size:12px; }
        }
        @media (min-width:1280px) {
            #wholesale-table { min-width:960px; }
        }
    </style>
    <script>
        const emptyStockData = @json($emptyStockData ?? []);
        const wholesaleCreateUrl = @json(check_access('wholesale.create') ? route('wholesale.create') : null);
        const wholesaleEmptyState = '<div class="wholesale-empty-state"><span class="wholesale-empty-icon"><i class="ph ph-receipt"></i></span><span class="wholesale-empty-title">Belum ada pengadaan</span><span class="wholesale-empty-copy">Pengadaan yang tersimpan akan muncul di sini.</span>'
            + (wholesaleCreateUrl ? '<a class="wholesale-empty-link" href="' + wholesaleCreateUrl + '"><i class="ph-bold ph-plus"></i>Tambah Pengadaan</a>' : '')
            + '</div>';
        // A search or filter with no match is not the same as having no orders yet: no "Tambah" prompt.
        const wholesaleNoMatchState = '<div class="wholesale-empty-state"><span class="wholesale-empty-icon"><i class="ph ph-magnifying-glass"></i></span><span class="wholesale-empty-title">Pengadaan tidak ditemukan</span><span class="wholesale-empty-copy">Coba kata pencarian lain atau sesuaikan filter.</span></div>';
        let wholesaleDataTable;
        let wholesaleLoadMorePending = false;

        function getWholesaleFilter(name) {
            const $visible = $('[data-wholesale-filter="' + name + '"]:visible');
            return ($visible.length ? $visible.last() : $('[data-wholesale-filter="' + name + '"]').first()).val() || '';
        }

        function applyWholesaleMobileFilters(status, branch, start, end) {
            $('[data-wholesale-filter="status"]').val(status);
            $('[data-wholesale-filter="branch"]').val(branch);
            $('[data-wholesale-filter="start"]').val(start || '');
            $('[data-wholesale-filter="end"]').val(end || '');
            updateWholesaleFilterSummary();
            wholesaleDataTable.draw();
            if (branch) showStockAlert(branch);
        }

        function hasWholesaleFilterCriteria(filters) {
            const status = filters.status || 'all';
            const branch = filters.branch || 'all';
            return status !== 'all'
                || (branch !== '' && branch !== 'all')
                || Boolean(filters.start || filters.end)
                || Boolean(String(filters.search || '').trim());
        }

        function updateWholesaleFilterSummary() {
            const status = getWholesaleFilter('status') || 'all';
            const branch = getWholesaleFilter('branch') || 'all';
            const start = getWholesaleFilter('start');
            const end = getWholesaleFilter('end');
            const hasFilter = hasWholesaleFilterCriteria({ status, branch, start, end });
            $('#wholesale-filter-dot').toggleClass('hidden', !hasFilter);
            const labels = [];
            if (status !== 'all') labels.push($('[data-wholesale-filter="status"] option[value="' + status + '"]').first().text());
            if (branch && branch !== 'all') labels.push($('[data-wholesale-filter="branch"] option[value="' + branch + '"]').first().text());
            if (start || end) labels.push((start || '…') + ' – ' + (end || '…'));
            $('#wholesale-active-filter').text(labels.join(' · ')).toggleClass('hidden', labels.length === 0);
        }

        function showStockAlert(selectedBranchId) {
            let filtered = emptyStockData;
            if (selectedBranchId && selectedBranchId !== 'all') filtered = emptyStockData.filter(item => item.branch_id == selectedBranchId);
            if (!filtered.length) return;
            const badges = filtered.slice(0, 8).map(item => `<span class="wholesale-stock-warning-item">${$('<div>').text(item.name + ' (' + item.branch_name + ')').html()}</span>`).join('');
            Swal.fire({
                title: 'Peringatan stok kosong',
                html: `<p>Terdapat <strong>${filtered.length}</strong> produk dengan stok minus pada cabang ini.</p><div class="wholesale-stock-warning-list">${badges}</div>${filtered.length > 8 ? `<small>dan ${filtered.length - 8} produk lainnya</small>` : ''}`,
                icon: 'warning', confirmButtonText: 'Mengerti', confirmButtonColor: '#0b595b'
            });
        }

        $(function () {
            wholesaleDataTable = $('#wholesale-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 20,
                lengthChange: false,
                autoWidth: false,
                order: [[7, 'desc']],
                ajax: {
                    url: @json(route('wholesale-data')),
                    data: function (data) {
                        data.url = 'wholesale';
                        data.status = getWholesaleFilter('status') || 'all';
                        data.cabang_filter = getWholesaleFilter('branch') || 'all';
                        data.start_date = getWholesaleFilter('start') || null;
                        data.end_date = getWholesaleFilter('end') || null;
                    }
                },
                columnDefs: [
                    { targets: 5, orderable: false, searchable: false },
                    { targets: [6, 7, 8], visible: false, searchable: false },
                    { targets: [9], visible: false, orderable: false }
                ],
                columns: [
                    { data: 'branch', name: 'branch.name' },
                    { data: 'po', name: 'wholesale.order_number' },
                    { data: 'staff', name: 'users.nm_user' },
                    { data: 'items', name: 'wholesale_totals.item_names' },
                    { data: 'status', name: 'wholesale.status', className: 'wholesale-status-column', render: function (value, type, row) { return type === 'sort' || type === 'filter' ? row.status_raw : value; } },
                    { data: 'action', name: 'action' },
                    { data: 'status_raw', name: 'wholesale.status' },
                    { data: 'order_date', name: 'wholesale.order_date' },
                    { data: 'wholesale_id', name: 'wholesale.id' },
                    { data: 'supplier_search', name: 'wholesale_totals.supplier_names' }
                ],
                language: {
                    processing: 'Memuat pengadaan…',
                    emptyTable: wholesaleEmptyState,
                    zeroRecords: wholesaleNoMatchState,
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ pengadaan',
                    infoEmpty: 'Belum ada pengadaan',
                    paginate: { previous: '‹', next: '›' }
                },
                drawCallback: function () {
                    const info = this.api().page.info();
                    document.querySelector('.mobile-list-edge-fade')?.classList.toggle('has-wholesale-empty-state', !info.recordsDisplay);
                    $('#wholesale-table_wrapper').toggleClass('has-wholesale-empty-state', !info.recordsDisplay);
                    const hasCriteria = hasWholesaleFilterCriteria({
                        status: getWholesaleFilter('status'),
                        branch: getWholesaleFilter('branch'),
                        start: getWholesaleFilter('start'),
                        end: getWholesaleFilter('end'),
                        search: this.api().search()
                    });
                    if (!info.recordsDisplay) {
                        $('#wholesale-table td.dataTables_empty').html(hasCriteria ? wholesaleNoMatchState : wholesaleEmptyState);
                    }
                    $('#wholesale-count-shown, #wholesale-count-shown-mobile').text((info.recordsDisplay ? info.end - info.start : 0).toLocaleString('id-ID'));
                    $('#wholesale-count-total, #wholesale-count-total-mobile').text(Number(info.recordsDisplay).toLocaleString('id-ID'));
                    this.api().rows({ page: 'current' }).every(function () {
                        $(this.node()).toggleClass('is-openable', Boolean(this.data()?.detail_payload));
                    });
                    wholesaleLoadMorePending = false;
                }
            });

            function openWholesaleDetail(rowElement) {
                const row = wholesaleDataTable.row(rowElement).data();
                if (!row?.detail_payload) return;
                window.dispatchEvent(new CustomEvent('wholesale-detail-open', { detail: row.detail_payload }));
            }

            $('#wholesale-table tbody').on('click', 'tr', function (event) {
                if ($(event.target).closest('a, button').length) return;
                openWholesaleDetail(this);
            });
            $('#wholesale-table tbody').on('click', '.wholesale-open-detail', function (event) {
                event.preventDefault();
                openWholesaleDetail($(this).closest('tr'));
            });

            const wholesaleListViewport = document.querySelector('.mobile-list-edge-fade');
            wholesaleListViewport?.addEventListener('scroll', function () {
                if (!window.matchMedia('(max-width: 1279px)').matches || wholesaleLoadMorePending) return;

                const info = wholesaleDataTable.page.info();
                if (info.end >= info.recordsDisplay) return;

                const nearBottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 180;
                if (!nearBottom) return;

                wholesaleLoadMorePending = true;
                wholesaleDataTable.page.len(info.end + 20).draw(false);
            }, { passive: true });

            let searchTimer;
            $('#wholesale-search').on('input', function () {
                const value = this.value;
                $('#wholesale-search-clear').toggleClass('hidden', !value);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => wholesaleDataTable.search(value).draw(), 250);
            });
            $('#wholesale-search-clear').on('click', function () {
                $('#wholesale-search').val('').trigger('input').trigger('focus');
            });
            $('[data-wholesale-filter]').on('change', function () {
                updateWholesaleFilterSummary();
                wholesaleDataTable.draw();
                if (this.dataset.wholesaleFilter === 'branch') showStockAlert(this.value);
            });
            $('[data-wholesale-filter-reset]').on('click', function () {
                $('[data-wholesale-filter="status"]').val('all');
                $('[data-wholesale-filter="branch"]').each(function () {
                    this.value = this.querySelector('option[value="all"]') ? 'all' : '';
                });
                $('[data-wholesale-filter="start"], [data-wholesale-filter="end"]').val('');
                updateWholesaleFilterSummary();
                wholesaleDataTable.draw();
            });
            updateWholesaleFilterSummary();
            const initialBranch = getWholesaleFilter('branch');
            if (initialBranch) showStockAlert(initialBranch);
        });

        function reloadDataTable() {
            if (wholesaleDataTable) wholesaleDataTable.ajax.reload(null, false);
        }

        function deleteProduct(id, label) {
            ErpDialog.confirmDelete({ what: 'Pengadaan', name: label || '', subject: 'Pengadaan', suffix: 'akan dihapus permanen beserta rinciannya.' }).then(confirmed => {
                if (!confirmed) return;
                $.ajax({
                    url: `/wholesale/${id}`, type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: response => {
                        Swal.fire({ icon: 'success', title: 'Pengadaan dihapus', text: response.message || 'Data berhasil dihapus.', timer: 1500, showConfirmButton: false });
                        reloadDataTable();
                    },
                    error: xhr => Swal.fire({ icon: 'error', title: 'Gagal menghapus', text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus pengadaan.' })
                });
            });
        }
    </script>
@endpush
