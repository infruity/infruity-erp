@extends('template.root')
@section('title', 'Kasir / POS')

@section('content')
@php
    $posBranches = collect($branches)->map(fn ($branch) => ['id' => (string) $branch->id, 'name' => ucwords($branch->name)])->values();
    $posFilterConfig = [
        'branches' => $posBranches,
        'defaultBranch' => $posBranches->count() === 1 ? $posBranches->first()['id'] : 'all',
        'timezone' => config('app.timezone', 'Asia/Jakarta'),
        'step' => 50,
    ];
    $posStatuses = ['all' => 'Semua Status', 'draft' => 'Pending', 'unpaid' => 'Belum Bayar', 'partial' => 'Kurang Bayar', 'paid' => 'Lunas'];
@endphp
<style>
    .pos-list-row { display:flex; min-width:0; align-items:center; gap:12px; }
    .pos-list-avatar-wrap { position:relative; flex:none; }
    .pos-list-avatar { display:flex; width:36px; height:36px; align-items:center; justify-content:center; border:1px solid #a7f3d0; border-radius:12px; background:#ecfdf5; color:#047857; font-size:12px; font-weight:700; line-height:1; }
    .pos-list-avatar.is-theme-1 { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .pos-list-avatar.is-theme-2 { border-color:#e9d5ff; background:#faf5ff; color:#7e22ce; }
    .pos-list-avatar.is-theme-3 { border-color:#fde68a; background:#fffbeb; color:#b45309; }
    .pos-list-avatar.is-theme-4 { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
    .pos-list-avatar.is-theme-5 { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
    .pos-list-avatar.is-theme-6 { border-color:#c7d2fe; background:#eef2ff; color:#4338ca; }
    .pos-list-parcel-badge { position:absolute; right:-4px; bottom:-4px; display:flex; width:18px; height:18px; align-items:center; justify-content:center; border-radius:999px; background:#fff; box-shadow:0 0 0 1px #fff, 0 1px 2px rgba(0,0,0,.12); }
    .pos-list-parcel-badge i { display:flex; width:14px; height:14px; align-items:center; justify-content:center; border-radius:999px; background:#d97706; color:#fff; font-size:9px; }
    .pos-list-copy { display:flex; min-width:0; flex-direction:column; gap:2px; text-decoration:none; }
    .pos-list-customer { display:flex; min-width:0; align-items:baseline; gap:4px; color:#111827; font-size:14px; font-weight:600; }
    .pos-list-customer span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pos-list-customer .pos-list-phone { flex:none; color:#6b7280; font-size:12px; font-weight:400; }
    .pos-list-copy:hover .pos-list-customer { color:#0b595b; }
    .pos-list-sub { color:#6b7280; font-size:12px; font-variant-numeric:tabular-nums; }
    .pos-list-status-cell { display:flex; flex-direction:column; align-items:flex-end; gap:3px; font-variant-numeric:tabular-nums; }
    .pos-list-status { display:inline-flex; align-items:center; font-size:12px; font-weight:700; }
    .pos-list-status.is-paid { color:#047857; }
    .pos-list-status.is-pending { color:#6b7280; }
    .pos-list-status.is-unpaid { color:#be123c; }
    .pos-list-status.is-partial { color:#b45309; }
    .pos-list-status.is-canceled { color:#374151; }
    .pos-list-status.is-other { color:#b45309; }
    .pos-list-total { display:inline-flex; align-items:baseline; gap:2px; color:#111827; font-size:13px; white-space:nowrap; }
    .pos-list-total strong { font-weight:700; }
    .pos-list-paid { color:#1f2937; font-weight:500; }
    .pos-list-sep { margin:0 2px; color:#d1d5db; }
    .pos-list-meta { color:#6b7280; font-size:11px; }
    #pos-table thead th { color:#6b7280; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
    #pos-table tbody tr.is-openable { cursor:pointer; }
    #pos-table :is(thead th, tbody td):first-child { padding-left:20px; }
    #pos-table :is(thead th, tbody td):last-child { padding-right:20px; }
    #pos-table_wrapper :is(.dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter) { display:none !important; }
    .pos-list-more { padding:16px 20px 24px; }
    .pos-list-empty { display:flex; min-height:260px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
    .pos-list-empty i { display:flex; width:56px; height:56px; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
    .pos-list-empty strong { color:#1f2937; font-size:14px; }
    .pos-list-empty span { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
    .pos-list-empty button { margin-top:6px; border:1px solid #d1d5db; border-radius:999px; background:#fff; padding:8px 16px; color:#374151; font-size:12px; font-weight:600; }
    .pos-list-empty button:hover { border-color:#34a684; color:#0b595b; }
    .pos-toolbar-search { min-width:0; flex:1; padding:0; }
    .pos-toolbar-search .erp-mobile-search { height:44px; box-shadow:none; }
    .pos-toolbar-search .erp-mobile-search:not(:focus-within) { border-color:#868e98; }
    @media (max-width:1279px) {
        #pos-table, #pos-table tbody { display:block; width:100% !important; }
        #pos-table thead { display:none; }
        #pos-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:4px 12px; padding:12px 16px; border-bottom:1px solid #f1f3f5; }
        #pos-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
        #pos-table tbody td:nth-child(2) { text-align:right; }
        #pos-table .pos-list-meta { display:none; }
        #pos-table td.dataTables_empty { grid-column:1 / -1; }
    }
    @media (max-width:767px) {
        .pos-list-customer { font-size:12px; }
        .pos-list-customer .pos-list-phone { font-size:11px; }
        .pos-list-sub { font-size:11px; }
        .pos-list-total { font-size:11px; }
        #pos-table tbody tr { padding:14.5px 16px; }
        #kt_content:has(.pos-list-page) { padding-inline:0 !important; }
        .pos-list-page .erp-workspace { border:0; border-radius:0; }
        .pos-list-page .erp-workspace-heading { display:none; }
        .pos-list-page .erp-workspace-toolbar { position:sticky; top:0; z-index:25; padding:16px; background:#fff; }
    }
    @media (max-width:639px) {
        .erp-workspace-toolbar > .pos-add { display:none; }
    }
</style>
<script src="{{ asset('assets/js/pos-list-reference.js') }}?v={{ filemtime(public_path('assets/js/pos-list-reference.js')) }}"></script>
<div class="contents pos-list-page" data-pos-list x-data="PosListUi.createFilters(@js($posFilterConfig))" x-init="onApply = (applied) => window.posApplyFilters(applied); onLoadMore = () => window.posLoadMore()" @pos-list-drawn.window="onDrawn($event.detail)" @keydown.escape.window="close()">
<x-erp.workspace title="Kasir / POS" subtitle="Kelola kasir / pos dan lihat rincian transaksi.">
    <x-slot:toolbar>
        <x-erp.mobile-search class="pos-toolbar-search erp-mobile-search-shell--always-visible erp-mobile-search-shell--flush">
            <x-slot:input><input id="search" type="search" class="erp-mobile-search__input" placeholder="Cari..." autocomplete="off" aria-label="Cari transaksi kasir"></x-slot:input>
            <x-slot:clear><button id="search-clear" type="button" class="erp-mobile-search__clear hidden" aria-label="Hapus pencarian"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button></x-slot:clear>
            <x-slot:filter><button type="button" @click="open()" :aria-expanded="filtersOpen" aria-haspopup="dialog" aria-label="Buka filter transaksi" class="erp-mobile-search__filter" :class="(filtersOpen || hasActive) ? 'is-active' : ''"><i class="ph ph-sliders-horizontal text-xl leading-none" aria-hidden="true"></i><span x-show="hasActive" x-cloak class="erp-mobile-search__filter-dot"></span></button></x-slot:filter>
        </x-erp.mobile-search>
        @if(check_access('pos.create'))<a href="{{ url('pos/create') }}" class="erp-action erp-action--primary pos-add" aria-label="Tambah Transaksi"><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Transaksi</span></a>@endif
    </x-slot:toolbar>
    <div class="pos-list"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
        <thead>
            <tr class="text-start gs-0">
                <th class="text-start min-w-150px">Pelanggan</th>
                <th class="text-end">Total &amp; Status</th>
            </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600"></tbody>
    </table></div>
    <x-slot:footer>
        <div class="pos-list-more" x-show="hasMore" x-cloak>
            <x-erp.load-more @click="loadMore()" loading="loading"><span>Muat Lebih Banyak <span x-text="remaining"></span></span></x-erp.load-more>
        </div>
    </x-slot:footer>
</x-erp.workspace>

<template x-teleport="body">
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end p-0 sm:items-center sm:justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="pos-filter-title">
        <button type="button" class="absolute inset-0 bg-black/60" @click="close()" aria-label="Tutup filter"></button>
        <section x-show="filtersOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0" class="relative flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-t-3xl bg-white pb-[env(safe-area-inset-bottom)] shadow-2xl sm:rounded-3xl sm:pb-0">
            <div class="flex shrink-0 justify-center pb-1 pt-3 sm:hidden"><div class="h-1.5 w-14 rounded-full bg-gray-300"></div></div>
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4">
                <div><h3 id="pos-filter-title" class="text-base font-bold text-gray-800 sm:text-lg">Filter Transaksi</h3><p class="mt-0.5 text-[11px] text-gray-500">Saring cabang, status, dan periode tanggal transaksi.</p></div>
                <button type="button" @click="close()" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600" aria-label="Tutup"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-none p-5">
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="pos-branch-search" class="block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Cabang</label>
                        <button type="button" x-show="draft.branch !== (branches.length === 1 ? branches[0].id : 'all')" @click="draft.branch = branches.length === 1 ? branches[0].id : 'all'" class="cursor-pointer text-[10px] font-semibold text-red-600">Reset</button>
                    </div>
                    <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xs">
                        <div class="relative shrink-0 border-b border-gray-100">
                            <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" aria-hidden="true"></i>
                            <input id="pos-branch-search" type="search" x-model="branchSearch" placeholder="Cari cabang..." autocomplete="off" class="w-full bg-transparent py-2 pl-8 pr-3 text-xs outline-none focus:bg-gray-50">
                        </div>
                        <div class="max-h-32 divide-y divide-gray-100/60 overflow-y-auto overscroll-none bg-gray-50/30">
                            <label x-show="branches.length > 1 && showAllBranches" class="flex cursor-pointer items-center gap-3 px-3.5 py-2 transition-colors hover:bg-gray-50">
                                <input type="radio" name="pos-branch" value="all" x-model="draft.branch" class="h-4 w-4 accent-emerald-600">
                                <span class="text-xs text-gray-700" :class="draft.branch === 'all' ? 'font-bold text-gray-900' : 'font-medium'">Semua Cabang</span>
                            </label>
                            <template x-for="branch in visibleBranches" :key="branch.id">
                                <label class="flex cursor-pointer items-center gap-3 px-3.5 py-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" name="pos-branch" :value="branch.id" x-model="draft.branch" class="h-4 w-4 accent-emerald-600">
                                    <span class="text-xs text-gray-700" :class="draft.branch === branch.id ? 'font-bold text-gray-900' : 'font-medium'" x-text="branch.name"></span>
                                </label>
                            </template>
                            <p x-show="visibleBranches.length === 0 && !showAllBranches" class="py-3 text-center text-xs text-gray-500">Cabang tidak ditemukan</p>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Status Transaksi</span>
                        <button type="button" x-show="draft.status !== 'all'" @click="draft.status = 'all'" class="cursor-pointer text-[10px] font-semibold text-red-600">Reset</button>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach ($posStatuses as $value => $label)
                            <button type="button" data-pos-status="{{ $value }}" @click="draft.status = '{{ $value }}'" :aria-pressed="draft.status === '{{ $value }}'" :class="draft.status === '{{ $value }}' ? 'border-emerald-300 bg-emerald-50 font-semibold text-emerald-700 shadow-2xs' : 'border-gray-200 bg-white text-gray-600 hover:border-emerald-300'" class="cursor-pointer rounded-full border px-3 py-1.5 text-xs font-medium transition-colors">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Periode Tanggal</span>
                        <button type="button" x-show="draft.start || draft.end" @click="draft.start = ''; draft.end = ''" class="cursor-pointer text-[10px] font-semibold text-red-600">Reset</button>
                    </div>
                    <div class="flex w-full flex-col gap-2 sm:flex-row">
                        <label class="relative flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs text-gray-700 shadow-xs transition-colors focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                            <span class="w-8 text-[10px] font-bold text-gray-500">DARI</span>
                            <span class="min-w-0 truncate text-xs font-medium" :class="draft.start ? 'text-gray-800' : 'text-gray-500'" x-text="formatDate(draft.start)"></span>
                            <input id="pos-start-date" type="date" x-model="draft.start" aria-label="Tanggal mulai transaksi" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                        </label>
                        <label class="relative flex flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs text-gray-700 shadow-xs transition-colors focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500">
                            <span class="w-8 text-[10px] font-bold text-gray-500">KE</span>
                            <span class="min-w-0 truncate text-xs font-medium" :class="draft.end ? 'text-gray-800' : 'text-gray-500'" x-text="formatDate(draft.end)"></span>
                            <input id="pos-end-date" type="date" x-model="draft.end" aria-label="Tanggal akhir transaksi" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                        </label>
                    </div>
                    <p x-show="dateError" x-cloak role="alert" class="mt-2 text-xs font-medium text-red-600" x-text="dateError"></p>
                    <div class="flex flex-col gap-2 pt-2">
                        <span class="block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Pilihan Cepat</span>
                        <div class="flex flex-row gap-2">
                            <button type="button" @click="setDateRange('week')" class="flex flex-1 cursor-pointer items-center justify-center rounded-xl px-2 py-2.5 text-xs font-semibold shadow-xs transition-colors active:scale-95" style="background-color:#c5f037;color:#1f2937">Minggu Ini</button>
                            <button type="button" @click="setDateRange('today')" class="flex flex-1 cursor-pointer items-center justify-center rounded-xl border border-gray-200 bg-white px-2 py-2.5 text-xs font-semibold text-gray-700 shadow-xs transition-colors hover:border-gray-300 hover:bg-gray-50 active:scale-95">Hari Ini</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-gray-50/70 px-5 py-3.5">
                <button type="button" @click="reset()" class="h-11 flex-1 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50 active:scale-95">Reset Filter</button>
                <button type="button" @click="apply()" :disabled="!!dateError" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white transition-colors hover:bg-[#08484a] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50">Terapkan Filter</button>
            </div>
        </section>
    </div>
</template>
</div>


@section('script')
    <script type="text/javascript">
        // Data stock kosong dari server
        const emptyStockData = @json($emptyStockData ?? []);
        let hasShownInitialAlert = false;

        function showStockAlert(selectedBranchId) {
            let filteredProducts = emptyStockData;
            if (selectedBranchId && selectedBranchId !== 'all') {
                filteredProducts = emptyStockData.filter(p => p.branch_id == selectedBranchId);
            }

            if (filteredProducts.length > 0) {
                const count = filteredProducts.length;
                const badges = filteredProducts.slice(0, 10).map(p => {
                    return `<span class="badge badge-light-danger me-1 mb-1">${p.name} (${p.branch_name})</span>`;
                }).join('');

                const remainingHtml = count > 10 ?
                    `<br><small>...dan ${count - 10} produk lainnya</small>` :
                    '';

                const scopeText = selectedBranchId === 'all' ? 'pada seluruh cabang' : 'pada cabang ini';
                const alertHtml = `Terdapat <strong>${count}</strong> produk dengan stok kosong ${scopeText}.<br><br>` +
                    `<div style="text-align: left; max-height: 200px; overflow-y: auto;">` +
                    badges +
                    remainingHtml +
                    `</div>`;

                Swal.fire({
                    title: 'Peringatan Stok Minus!',
                    html: alertHtml,
                    icon: 'warning',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#b45309',
                    allowOutsideClick: false
                });
            }
        }

        @if (session('error'))
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Bisa Diedit',
                    text: "{{ session('error') }}"
                });
            });
        @endif

        var dataTable;
        const POS_PAGE_STEP = {{ $posFilterConfig['step'] }};
        const posRoot = () => document.querySelector('[data-pos-list]');
        const posUi = () => Alpine.$data(posRoot());

        // Reference avatar: initials on a tinted tile, with the parcel flag as a micro-badge.
        function decoratePosRow(row) {
            const label = row.querySelector('.pos-list-customer > span:last-child');
            const avatar = row.querySelector('.pos-list-avatar');
            if (!label || !avatar) return;
            const full = label.textContent.trim();
            const match = full.match(/^(.*?)\s*\((\d{4})\)$/);
            const name = match ? match[1] : full;
            avatar.className = 'pos-list-avatar is-theme-' + PosListUi.avatarTheme(name);
            avatar.textContent = PosListUi.customerInitial(name);
            avatar.setAttribute('aria-hidden', 'true');
            const wrap = document.createElement('span');
            wrap.className = 'pos-list-avatar-wrap';
            avatar.replaceWith(wrap);
            wrap.appendChild(avatar);
            const parcel = row.querySelector('.pos-list-parcel');
            if (parcel) {
                parcel.remove();
                const badge = document.createElement('span');
                badge.className = 'pos-list-parcel-badge';
                badge.title = 'Ada Parcel';
                badge.innerHTML = '<i class="ph-bold ph-gift" role="img" aria-label="Ada Parcel"></i>';
                wrap.appendChild(badge);
            }
            if (match) {
                label.textContent = name;
                const phone = document.createElement('span');
                phone.className = 'pos-list-phone';
                phone.textContent = '(' + match[2] + ')';
                label.after(phone);
            }
        }

        $(document).ready(function() {
            const posEmptyState = '<div class="pos-list-empty"><i class="ph ph-receipt" aria-hidden="true"></i><strong>Belum ada data transaksi</strong><span>Transaksi kasir yang tersimpan akan muncul di sini.</span></div>';
            const posEmptyFiltered = '<div class="pos-list-empty"><i class="ph ph-receipt" aria-hidden="true"></i><strong>Tidak ada transaksi yang cocok</strong><span>Coba ubah kata kunci atau filter yang sedang aktif.</span><button type="button" data-pos-clear>Hapus Filter</button></div>';
            let lastBranch = '{{ $posFilterConfig['defaultBranch'] }}';

            dataTable = $('#pos-table').DataTable({
                language: { emptyTable: posEmptyState, zeroRecords: posEmptyState },
                createdRow: function(row) {
                    const link = row.querySelector('a.pos-list-copy');
                    if (link) {
                        row.classList.add('is-openable');
                        row.dataset.href = link.getAttribute('href');
                    }
                    decoratePosRow(row);
                },
                drawCallback: function() {
                    const info = this.api().page.info();
                    if (info.recordsDisplay === 0) {
                        const filtered = posUi().hasActive || $('#search').val().trim() !== '';
                        $('#pos-table td.dataTables_empty').html(filtered ? posEmptyFiltered : posEmptyState);
                    }
                    window.dispatchEvent(new CustomEvent('pos-list-drawn', { detail: { end: info.end, recordsDisplay: info.recordsDisplay } }));
                },
                pageLength: POS_PAGE_STEP,
                processing: true,
                serverSide: true,
                ordering: false, // The server always returns newest transactions first.
                ajax: {
                    url: "{{ route('pos-data') }}",
                    data: function(d) {
                        const applied = posUi().applied;
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = applied.status;
                        d.cabang_filter = applied.branch;
                        // A single picked date means that day, like the old range picker.
                        const start = applied.start || applied.end;
                        const end = applied.end || applied.start;
                        if (start && end) {
                            d.start_date = start;
                            d.end_date = end;
                        }
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-end'
                    },
                ]
            });

            window.posApplyFilters = function(applied) {
                dataTable.page.len(POS_PAGE_STEP).draw();
                if (applied.branch !== lastBranch) {
                    lastBranch = applied.branch;
                    showStockAlert(applied.branch);
                }
            };
            window.posLoadMore = function() {
                const info = dataTable.page.info();
                dataTable.page.len(PosListUi.nextLength(info.end, POS_PAGE_STEP)).draw(false);
            };

            // A tap on the row opens the existing detail page.
            $('#pos-table tbody').on('click', 'tr.is-openable', function(event) {
                if ($(event.target).closest('a, button, .dropdown, .dropdown-menu').length) return;
                window.location.href = this.dataset.href;
            });

            // Search manual lewat input
            let searchTimer;
            $('#search').on('input', function() {
                const value = this.value;
                $('#search-clear').toggleClass('hidden', !value);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    dataTable.search(value).page.len(POS_PAGE_STEP).draw();
                }, 250);
            });
            $('#search-clear').on('click', function() {
                $('#search').val('').trigger('input').trigger('focus');
            });
            $('#pos-table').on('click', '[data-pos-clear]', function() {
                $('#search').val('').trigger('input');
                posUi().reset();
            });

            showStockAlert(lastBranch);
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/pos/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            // Reload DataTable setelah berhasil menghapus data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
