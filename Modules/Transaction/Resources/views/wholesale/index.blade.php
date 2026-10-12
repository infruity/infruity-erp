@extends('layouts.erp-tailwind')

@section('title', 'Pengadaan - Pembelian')
@section('page-title', 'Pengadaan')
@section('page-breadcrumb')
    <span>Pembelian</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Pengadaan</span>
@endsection

@section('content')
    <div x-data="{ filtersOpen: false, branchSearch: '', mobileBranch: 'all', mobileStatus: 'all' }" class="flex min-h-0 flex-1 flex-col -mx-4 md:mx-0">
        <section class="relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-none bg-white shadow-none lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm">
            <header class="shrink-0 border-b border-gray-100 px-4 py-4 md:px-5">
                <div class="mb-3 hidden items-center justify-between gap-3 md:mb-4 sm:flex">
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-bold text-gray-900 md:text-lg">Pengadaan</h1>
                        <p class="mt-0.5 text-xs text-gray-500">Pantau pembelian bahan dari seluruh cabang yang dapat Anda akses.</p>
                    </div>
                </div>

                <div class="relative flex items-center gap-3">
                    <div class="relative min-w-0 flex-1">
                    <div class="wholesale-search-pill relative flex h-11 min-w-0 flex-1 items-center rounded-full border border-gray-200/80 bg-gray-50/60 pl-4 pr-1.5 transition focus-within:border-emerald-500 focus-within:bg-white focus-within:ring-1 focus-within:ring-emerald-500">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2.5 h-[18px] w-[18px] shrink-0 text-[#0b595b]/80" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input id="wholesale-search" type="search" placeholder="Cari cabang, nomor PO, atau bahan..." class="h-full min-w-0 flex-1 bg-transparent text-[13.5px] font-medium text-gray-800 outline-none placeholder:text-gray-400" autocomplete="off">
                        <button id="wholesale-search-clear" type="button" class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-black/5 hover:text-gray-700" aria-label="Hapus pencarian"><i class="ph-bold ph-x text-xs"></i></button>
                        <span class="mx-1 h-4 w-px shrink-0 bg-gray-200" aria-hidden="true"></span>
                        <button type="button" @click="filtersOpen = !filtersOpen; if (filtersOpen) { mobileStatus = getWholesaleFilter('status') || 'all'; mobileBranch = getWholesaleFilter('branch') || 'all'; branchSearch = ''; filterWholesaleBranches(''); }" :aria-expanded="filtersOpen" aria-label="Buka filter pengadaan" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#526b6b] transition hover:bg-emerald-50 hover:text-[#0b595b] active:scale-95" :class="filtersOpen ? 'bg-emerald-50 text-[#0b595b]' : ''">
                            <span class="icon-rounded text-xl leading-none" aria-hidden="true">tune</span>
                            <span id="wholesale-filter-dot" class="hidden absolute right-1 top-1 h-2 w-2 rounded-full bg-[#0b595b] ring-2 ring-white"></span>
                        </button>
                    </div>

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

                <div id="wholesale-summary" class="mt-3 flex items-center justify-between text-[11px] text-gray-500">
                    <span id="wholesale-count" aria-live="polite">Memuat pengadaan…</span>
                    <span id="wholesale-active-filter" class="hidden max-w-[48%] truncate rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-800"></span>
                </div>
            </header>

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

        <template x-teleport="body">
            <div x-show="filtersOpen && window.matchMedia('(max-width: 767px)').matches" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end md:hidden" role="dialog" aria-modal="true" aria-label="Filter pengadaan">
                <button type="button" class="absolute inset-0 bg-black/70" @click="filtersOpen = false" aria-label="Tutup filter"></button>
                <section x-show="filtersOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full" class="relative max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl">
                    <div class="flex justify-center pt-3 pb-1"><div class="h-1.5 w-14 rounded-full bg-gray-300"></div></div>
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div><h2 class="text-lg font-bold text-gray-900">Filter Pengadaan</h2><p class="mt-0.5 text-[11px] text-gray-400">Saring cabang dan status pengadaan.</p></div>
                        <button type="button" @click="filtersOpen = false" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600" aria-label="Tutup"><i class="ph-bold ph-x"></i></button>
                    </div>
                    <div class="space-y-5 px-5 py-5">
                        <div>
                            <label for="wholesale-mobile-branch-search" class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500">Cabang Tujuan</label>
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <div class="relative border-b border-gray-100">
                                    <i class="ph-bold ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" aria-hidden="true"></i>
                                    <input id="wholesale-mobile-branch-search" type="search" x-model="branchSearch" @input="filterWholesaleBranches(branchSearch)" placeholder="Cari cabang..." class="h-10 w-full bg-transparent pl-9 pr-3 text-xs outline-none focus:bg-gray-50">
                                </div>
                                <div id="wholesale-mobile-branches" class="max-h-36 divide-y divide-gray-100 overflow-y-auto bg-gray-50/30">
                                    @if (count($branches) > 1)
                                        <button type="button" data-wholesale-mobile-branch="all" data-branch-search="semua cabang" @click="mobileBranch = 'all'" :class="mobileBranch === 'all' ? 'bg-emerald-50/70 font-bold text-[#0b595b]' : 'text-gray-700'" class="flex w-full items-center gap-3 px-3.5 py-2.5 text-left text-xs hover:bg-gray-50">
                                            <span class="flex h-4 w-4 items-center justify-center rounded-full border-2" :class="mobileBranch === 'all' ? 'border-emerald-500' : 'border-gray-300'"><span class="h-2 w-2 rounded-full bg-emerald-500" x-show="mobileBranch === 'all'"></span></span>Semua cabang
                                        </button>
                                    @endif
                                    @foreach ($branches as $branch)
                                        <button type="button" data-wholesale-mobile-branch="{{ $branch->id }}" data-branch-search="{{ strtolower($branch->name) }}" @click="mobileBranch = '{{ $branch->id }}'" :class="mobileBranch === '{{ $branch->id }}' ? 'bg-emerald-50/70 font-bold text-[#0b595b]' : 'text-gray-700'" class="flex w-full items-center gap-3 px-3.5 py-2.5 text-left text-xs hover:bg-gray-50">
                                            <span class="flex h-4 w-4 items-center justify-center rounded-full border-2" :class="mobileBranch === '{{ $branch->id }}' ? 'border-emerald-500' : 'border-gray-300'"><span class="h-2 w-2 rounded-full bg-emerald-500" x-show="mobileBranch === '{{ $branch->id }}'"></span></span>{{ ucwords($branch->name) }}
                                        </button>
                                    @endforeach
                                    <p id="wholesale-mobile-branch-empty" class="hidden py-4 text-center text-xs text-gray-400">Cabang tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-gray-500">Status Pengadaan</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['all' => 'Semua Status', 'temp' => 'Draft', 'draft' => 'Pending', 'posting' => 'Dalam Pengiriman', 'complete' => 'Selesai'] as $value => $label)
                                    <button type="button" @click="mobileStatus = '{{ $value }}'" :class="mobileStatus === '{{ $value }}' ? 'border-emerald-300 bg-emerald-50 text-[#0b595b] font-semibold' : 'border-gray-200 bg-white text-[#46565c] hover:border-emerald-300'" class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 border-t border-gray-100 px-5 pt-4">
                        <button type="button" data-wholesale-filter-reset @click="mobileBranch = 'all'; mobileStatus = 'all'; branchSearch = ''; filterWholesaleBranches('')" class="h-11 flex-1 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Reset</button>
                        <button type="button" @click="applyWholesaleMobileFilters(mobileStatus, mobileBranch); filtersOpen = false" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Terapkan</button>
                    </div>
                </section>
            </div>
        </template>
        <template x-teleport="body">
            <div x-data="{
                    open: false,
                    item: null,
                    show(detail) { this.item = detail; this.open = true; document.body.classList.add('overflow-hidden'); this.$nextTick(() => this.$refs.close?.focus()); },
                    close() { this.open = false; document.body.classList.remove('overflow-hidden'); },
                    remove() { const id = this.item?.id; this.close(); if (id) window.deleteProduct(id); }
                }"
                @wholesale-detail-open.window="show($event.detail)"
                @keydown.escape.window="close()"
                x-show="open" x-cloak class="wholesale-detail-overlay" role="presentation">
                <button type="button" class="wholesale-detail-backdrop" @click="close()" aria-label="Tutup detail pengadaan"></button>
                <section class="wholesale-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="wholesale-detail-title" tabindex="-1">
                    <header class="wholesale-detail-heading">
                        <h2 id="wholesale-detail-title">Detail Pengadaan</h2>
                        <button x-ref="close" type="button" class="wholesale-detail-close" @click="close()" aria-label="Tutup"><i class="ph ph-x" aria-hidden="true"></i></button>
                    </header>
                    <div class="wholesale-detail-body" x-show="item">
                        <div class="wholesale-detail-line">
                            <i class="ph ph-receipt" aria-hidden="true"></i>
                            <div><strong x-text="item?.order_number"></strong><span x-text="item?.branch_name"></span></div>
                        </div>
                        <div class="wholesale-detail-line">
                            <i class="ph ph-coins" aria-hidden="true"></i>
                            <div><strong x-text="item?.total_label"></strong><span x-text="'Grand Total (' + item?.item_count_label + ')' "></span></div>
                        </div>
                        <div class="wholesale-detail-line">
                            <i class="ph ph-clock" aria-hidden="true"></i>
                            <div><strong x-text="item?.creator_name"></strong><span x-text="'Dibuat pada ' + item?.created_at_label"></span></div>
                        </div>
                    </div>
                    <footer class="wholesale-detail-actions" x-show="item?.can_edit || item?.can_delete" :class="item?.can_edit && item?.can_delete ? '' : 'is-single-action'">
                        <a x-show="item?.can_edit" :href="item?.edit_url" class="wholesale-detail-action is-edit"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Edit</span></a>
                        <button x-show="item?.can_delete" type="button" class="wholesale-detail-action is-delete" @click="remove()"><i class="ph ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
                    </footer>
                </section>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
    <style>
        .wholesale-order-cell { display:flex; align-items:center; gap:12px; min-width:0; }
        .wholesale-order-avatar { display:flex; width:40px; height:40px; flex:none; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:11px; font-weight:700; }
        .wholesale-order-avatar.is-branch-gr { border-color:#99f6d5; background:#effcf6; color:#0b595b; }
        .wholesale-order-avatar.is-branch-sr { border-color:#b6d4ff; background:#eff6ff; color:#1d4ed8; }
        .wholesale-order-avatar.is-branch-sb { border-color:#e9c8ff; background:#faf5ff; color:#7e22ce; }
        .wholesale-order-avatar.is-branch-sd { border-color:#ffdd62; background:#fffbeb; color:#b45309; }
        .wholesale-order-avatar.is-branch-ml { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
        .wholesale-order-avatar.is-branch-default { border-color:#d1fae5; background:#ecfdf5; color:#0b595b; }
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
        .wholesale-search-pill input[type="search"]::-webkit-search-cancel-button { display:none; }
        .wholesale-empty-state { display:flex; min-height:300px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
        .wholesale-empty-icon { display:flex; width:56px; height:56px; align-items:center; justify-content:center; margin-bottom:4px; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
        .wholesale-empty-title { color:#1f2937; font-size:14px; font-weight:700; }
        .wholesale-empty-copy { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
        .wholesale-empty-link { display:inline-flex; align-items:center; gap:7px; margin-top:8px; border-radius:999px; background:#0b595b; padding:9px 15px; color:white; font-size:12px; font-weight:700; text-decoration:none; }
        .wholesale-empty-link:hover { background:#08484a; color:white; }
        .wholesale-order-copy:focus-visible, .wholesale-action-button:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
        #wholesale-table tbody tr.is-openable { cursor:pointer; }
        .wholesale-detail-overlay { position:fixed; inset:0; z-index:110; display:flex; align-items:center; justify-content:center; padding:24px; }
        .wholesale-detail-backdrop { position:absolute; inset:0; border:0; background:rgba(19,24,32,.56); cursor:default; }
        .wholesale-detail-dialog { position:relative; width:min(100%, 384px); overflow:hidden; border-radius:17px; background:#fff; box-shadow:0 24px 72px rgba(11,22,31,.25); outline:none; }
        .wholesale-detail-heading { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 23px 8px; }
        .wholesale-detail-heading h2 { margin:0; color:#202b3b; font-size:18px; font-weight:700; letter-spacing:-.025em; }
        .wholesale-detail-close { display:flex; width:34px; height:34px; flex:none; align-items:center; justify-content:center; border:0; border-radius:50%; background:#f2f3f5; color:#788292; font-size:17px; }
        .wholesale-detail-body { display:grid; gap:20px; padding:14px 24px 24px; }
        .wholesale-detail-line { display:grid; grid-template-columns:24px minmax(0,1fr); align-items:center; gap:12px; min-width:0; }
        .wholesale-detail-line > i { color:#08a978; font-size:21px; }
        .wholesale-detail-line > div { display:flex; min-width:0; flex-direction:column; gap:3px; }
        .wholesale-detail-line strong { overflow:hidden; color:#253144; font-size:14px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .wholesale-detail-line span { overflow:hidden; color:#929bad; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
        .wholesale-detail-actions { display:grid; grid-template-columns:1fr 1fr; border-top:1px solid #eef0f3; }
        .wholesale-detail-actions.is-single-action { grid-template-columns:1fr; }
        .wholesale-detail-action { display:flex; min-height:52px; align-items:center; justify-content:center; gap:8px; border:0; background:#fff; color:#0b595b; font-size:14px; font-weight:600; text-decoration:none; }
        .wholesale-detail-action + .wholesale-detail-action { border-left:1px solid #eef0f3; }
        .wholesale-detail-action.is-delete { color:#ff3b42; }
        .wholesale-detail-action i { font-size:15px; }
        .wholesale-detail-action:hover { background:#f8faf9; }
        @media (max-width:1279px) {
            #wholesale-table, #wholesale-table tbody { display:block; width:100% !important; }
            #wholesale-table thead { display:none; }
            #wholesale-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) minmax(126px,auto); grid-template-areas:'order status'; align-items:center; gap:4px 14px; border-bottom:1px solid #f1f3f5; padding:14px clamp(16px,3vw,24px); transition:background-color .16s ease; }
            #wholesale-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
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
            #wholesale-table tbody tr { grid-template-columns:minmax(0,1fr) minmax(112px,auto); gap:3px 10px; padding:12px 16px; }
            .wholesale-order-avatar { width:36px; height:36px; border-radius:11px; }
            .wholesale-order-cell { gap:10px; }
            .wholesale-order-number { font-size:13px; }
            .wholesale-order-subtitle { font-size:11px; }
            .wholesale-status-badge { font-size:12px; }
            .wholesale-status-total { font-size:13px; }
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
        let wholesaleDataTable;
        let wholesaleLoadMorePending = false;

        function getWholesaleFilter(name) {
            const $visible = $('[data-wholesale-filter="' + name + '"]:visible');
            return ($visible.length ? $visible.last() : $('[data-wholesale-filter="' + name + '"]').first()).val() || '';
        }

        function filterWholesaleBranches(query = '') {
            const term = String(query).trim().toLocaleLowerCase('id');
            let visibleCount = 0;
            $('[data-wholesale-mobile-branch]').each(function () {
                const visible = String(this.dataset.branchSearch || '').toLocaleLowerCase('id').includes(term);
                this.hidden = !visible;
                if (visible) visibleCount++;
            });
            $('#wholesale-mobile-branch-empty').toggleClass('hidden', visibleCount > 0);
        }

        function applyWholesaleMobileFilters(status, branch) {
            $('[data-wholesale-filter="status"]').val(status);
            $('[data-wholesale-filter="branch"]').val(branch);
            updateWholesaleFilterSummary();
            wholesaleDataTable.draw();
            if (branch) showStockAlert(branch);
        }

        function updateWholesaleFilterSummary() {
            const status = getWholesaleFilter('status') || 'all';
            const branch = getWholesaleFilter('branch') || 'all';
            const start = getWholesaleFilter('start');
            const end = getWholesaleFilter('end');
            const hasFilter = status !== 'all' || (branch && branch !== 'all') || start || end;
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
                    zeroRecords: wholesaleEmptyState,
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ pengadaan',
                    infoEmpty: 'Belum ada pengadaan',
                    paginate: { previous: '‹', next: '›' }
                },
                drawCallback: function () {
                    const info = this.api().page.info();
                    $('#wholesale-count').text(info.recordsDisplay ? `Menampilkan ${info.start + 1}–${info.end} dari ${info.recordsDisplay} pengadaan` : 'Belum ada pengadaan');
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

        function deleteProduct(id) {
            Swal.fire({
                title: 'Hapus pengadaan ini?',
                text: 'Transaksi dan rincian pengadaan akan dihapus.',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Hapus', cancelButtonText: 'Batal',
                confirmButtonColor: '#be123c', cancelButtonColor: '#6b7280'
            }).then(result => {
                if (!result.isConfirmed) return;
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
