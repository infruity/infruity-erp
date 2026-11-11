@extends('layouts.erp-tailwind')
@section('title', 'Sortir Barang - Inventaris')
@section('page-title', 'Sortir Barang')
@section('dashboard-fullscreen', '1')
@section('page-breadcrumb')
    <span>Inventaris</span><i class="ph ph-caret-right text-[0.55rem]" aria-hidden="true"></i><span>Sortir Barang</span>
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/sortir-mobile.css') }}?v={{ filemtime(public_path('assets/css/sortir-mobile.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('assets/js/sortir-list.js') }}?v={{ filemtime(public_path('assets/js/sortir-list.js')) }}"></script>
@endpush
@section('content')
    @php
        $sortirFilterBranches = $branches->map(function ($branch) {
            return ['id' => (string) $branch->id, 'name' => ucwords((string) $branch->name)];
        })->values();
    @endphp
    <section class="sortir-list-page" x-data="sortirList({ endpoint: @js(route('sortir-data')), csrf: @js(csrf_token()), timezone: @js(config('app.timezone')), branches: @js($sortirFilterBranches) })" aria-label="Daftar Sortir Barang">
        <div class="sortir-toolbar">
            <div class="sortir-search">
                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                <input id="sortir-search" type="search" x-model="search" @input.debounce.300ms="load()" placeholder="Cari cabang, nomor sortir, atau barang..." aria-label="Cari transaksi sortir" autocomplete="off">
                <button type="button" x-show="search" x-cloak @click="clearSearch()" class="sortir-icon-button" aria-label="Hapus pencarian"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                <span class="sortir-search-divider" aria-hidden="true"></span>
                <button type="button" @click="openFilter($event)" class="sortir-icon-button" :class="activeFilters || filterOpen ? 'is-active' : ''" :aria-expanded="filterOpen" aria-label="Filter sortir barang"><span class="icon-rounded" aria-hidden="true">tune</span></button>
            </div>
            @if (check_access('sortir.create'))
                <a class="sortir-list-create" href="{{ route('sortir.create') }}"><i class="ph-bold ph-plus" aria-hidden="true"></i>Tambah Sortir</a>
            @endif
        </div>
        <div class="sortir-list-scroll" :aria-busy="loading">
            <div class="sortir-list-head" x-show="rows.length"><span>Cabang &amp; Waktu</span><span>Nomor Sortir / Petugas</span><span style="text-align:right">Status / Nominal</span></div>
            <template x-for="row in rows" :key="row.id">
                <button type="button" class="sortir-row" @click="openDetail(row, $event)" :aria-label="'Detail sortir ' + row.invoice_number">
                    <span class="sortir-row-branch">
                        <span class="sortir-branch-avatar" :style="branchTheme(row.branch_name)" x-text="initials(row.branch_name)"></span>
                        <span class="sortir-branch-copy"><strong class="sortir-branch-name" x-text="row.branch_name || 'Tanpa cabang'"></strong><span class="sortir-row-date" x-text="dateLabel(row.created_at || row.date)"></span></span>
                    </span>
                    <span class="sortir-desktop-meta"><span x-text="row.invoice_number"></span><br><small x-text="row.staff_name || '—'"></small></span>
                    <span class="sortir-row-amount"><span class="sortir-row-status" :class="['paid','debt'].includes(row.status) ? 'is-final' : ''" x-text="statusLabel(row.status)"></span><strong class="sortir-row-total" x-text="money(row.total)"></strong></span>
                </button>
            </template>
            <div class="sortir-list-state" x-show="loading && !rows.length" role="status"><i class="ph ph-circle-notch" aria-hidden="true"></i><p>Memuat transaksi sortir...</p></div>
            <div class="sortir-list-state" x-show="error" x-cloak role="alert"><h2>Daftar sortir belum dapat dimuat</h2><p x-text="error"></p><button type="button" class="sortir-text-button" @click="load(rows.length > 0)">Coba lagi</button></div>
            <div class="sortir-list-state" x-show="!loading && !error && !rows.length" x-cloak>
                <i class="ph-duotone ph-receipt" aria-hidden="true"></i>
                <h2 x-text="search || activeFilters ? 'Transaksi tidak ditemukan' : 'Belum ada sortir barang'"></h2>
                <p x-show="search || activeFilters">Coba kata pencarian lain atau sesuaikan filter.</p>
                @if (check_access('sortir.create'))
                    <p x-show="!search && !activeFilters">Klik tombol <a href="{{ route('sortir.create') }}" class="sortir-text-button" aria-label="Tambah sortir barang">+</a> untuk menambahkan<br>transaksi sortir baru.</p>
                @endif
                <button type="button" class="sortir-text-button" x-show="search || activeFilters" @click="search = ''; resetFilters()">Reset pencarian &amp; filter</button>
            </div>
            <div class="sortir-load-more" x-show="hasMore && rows.length && !error" x-cloak>
                <x-erp.load-more @click="load(true)" loading="loading">
                    <span x-text="loading ? 'Memuat...' : 'Muat ' + Math.min(20, filtered - rows.length) + ' Transaksi Lagi'"></span>
                </x-erp.load-more>
            </div>
            <p class="sortir-list-count" x-show="rows.length && !hasMore" x-cloak x-text="rows.length + ' dari ' + filtered + ' transaksi sortir'"></p>
        </div>
        <template x-teleport="body">
            <div x-show="filterOpen" x-cloak class="sortir-overlay is-filter" @click.self="closeFilter()" @keydown.escape.window="if (filterOpen) closeFilter()" @keydown.tab="trapFocus($event)">
                <section class="sortir-dialog is-filter-dialog" id="sortir-filter-dialog" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="sortir-filter-title">
                    <x-erp.filter-sheet-frame title="Filter Sortir Barang" title-id="sortir-filter-title" header-class="sortir-dialog-head" body-class="sortir-filter-body" footer-class="sortir-filter-foot">
                        <x-slot:close><button type="button" class="sortir-icon-button" @click="closeFilter()" aria-label="Tutup filter"><i class="ph-bold ph-x" aria-hidden="true"></i></button></x-slot:close>
                        <x-slot:body>

                        <div class="sortir-filter-section" x-show="!calendarOpen">
                            <div class="sortir-filter-section-head">
                                <span id="sortir-filter-branch-label" class="sortir-filter-label">Cabang Tujuan</span>
                                <button type="button" class="sortir-filter-reset" x-show="pending.branch !== 'all' || branchSearch.trim()" x-cloak @click="resetPendingFilter('branch')">Reset</button>
                            </div>
                            <div class="sortir-branch-picker">
                                <label class="sortir-branch-search" for="sortir-filter-branch-search">
                                    <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                                    <input id="sortir-filter-branch-search" type="search" x-model.debounce.150ms="branchSearch" placeholder="Cari cabang..." autocomplete="off" aria-label="Cari cabang tujuan">
                                </label>
                                <div class="sortir-branch-options" role="radiogroup" aria-labelledby="sortir-filter-branch-label">
                                    <label class="sortir-branch-option" x-show="showAllBranchOption">
                                        <input type="radio" name="sortirBranchFilter" value="all" x-model="pending.branch">
                                        <span class="sortir-branch-radio" aria-hidden="true"></span>
                                        <span>Semua Cabang</span>
                                    </label>
                                    <template x-for="branch in visibleBranches" :key="branch.id">
                                        <label class="sortir-branch-option">
                                            <input type="radio" name="sortirBranchFilter" :value="String(branch.id)" x-model="pending.branch">
                                            <span class="sortir-branch-radio" aria-hidden="true"></span>
                                            <span x-text="branch.name"></span>
                                        </label>
                                    </template>
                                    <p class="sortir-branch-empty" x-show="visibleBranches.length === 0 && !showAllBranchOption" x-cloak>Cabang tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                        <div class="sortir-filter-section" x-show="!calendarOpen">
                            <div class="sortir-filter-section-head">
                                <span class="sortir-filter-label">Status Sortir</span>
                                <button type="button" class="sortir-filter-reset" x-show="pending.status !== 'all'" x-cloak @click="resetPendingFilter('status')">Reset</button>
                            </div>
                            <div class="sortir-filter-chips" role="group" aria-label="Status sortir">
                                <template x-for="status in [{value:'all',label:'Semua Status'},{value:'draft',label:'Draf'},{value:'paid',label:'Selesai'}]" :key="status.value"><button type="button" @click="pending.status = status.value" :class="pending.status === status.value ? 'is-active' : ''" :aria-pressed="pending.status === status.value" x-text="status.label"></button></template>
                            </div>
                        </div>
                        <div class="sortir-filter-section" x-show="!calendarOpen">
                            <div class="sortir-filter-section-head">
                                <span class="sortir-filter-label">Periode Tanggal</span>
                                <button type="button" class="sortir-filter-reset" x-show="pending.start || pending.end" x-cloak @click="resetPendingFilter('date')">Reset</button>
                            </div>
                            <div class="sortir-filter-dates flex w-full flex-col gap-2 sm:flex-row">
                                <button type="button" id="sortir-date-start" class="sortir-filter-date-card flex min-w-0 flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-left text-xs text-gray-700 shadow-xs transition-colors hover:border-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" @click="openFilterCalendar($event.currentTarget)" :aria-expanded="calendarOpen" aria-controls="sortir-filter-calendar" :aria-label="'Dari tanggal: ' + formatFilterDate(pending.start)">
                                    <span class="w-8 shrink-0 text-[10px] font-bold text-gray-400">DARI</span>
                                    <span class="min-w-0 truncate text-xs font-medium" :class="pending.start ? 'text-gray-800' : 'text-gray-400'" x-text="formatFilterDate(pending.start)"></span>
                                </button>
                                <button type="button" id="sortir-date-end" class="sortir-filter-date-card flex min-w-0 flex-1 cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-left text-xs text-gray-700 shadow-xs transition-colors hover:border-gray-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" @click="openFilterCalendar($event.currentTarget)" :aria-expanded="calendarOpen" aria-controls="sortir-filter-calendar" :aria-label="'Sampai tanggal: ' + formatFilterDate(pending.end)">
                                    <span class="w-8 shrink-0 text-[10px] font-bold text-gray-400">KE</span>
                                    <span class="min-w-0 truncate text-xs font-medium" :class="pending.end ? 'text-gray-800' : 'text-gray-400'" x-text="formatFilterDate(pending.end)"></span>
                                </button>
                            </div>
                            <p class="sortir-filter-error" x-show="pendingDateError" x-text="pendingDateError" role="alert"></p>
                            <div class="sortir-filter-quick flex flex-col gap-2 pt-2">
                                <span class="block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Pilihan Cepat</span>
                                <div class="sortir-filter-quick-actions flex flex-row gap-2">
                                    <button type="button" @click="setPendingDateRange('week')" class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl px-2 py-2.5 text-xs font-semibold shadow-xs transition-colors active:scale-95" style="background-color:#c5f037;color:#1f2937" title="Tampilkan Minggu Ini">Minggu Ini</button>
                                    <button type="button" @click="setPendingDateRange('today')" class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-2 py-2.5 text-xs font-semibold text-gray-700 shadow-xs transition-colors hover:border-gray-300 hover:bg-gray-50 active:scale-95" title="Tampilkan Hari Ini">Hari Ini</button>
                                </div>
                            </div>
                        </div>
                        <section id="sortir-filter-calendar" x-show="calendarOpen" x-cloak class="sortir-filter-calendar space-y-4" aria-label="Pilih periode tanggal">
                            <button id="sortir-filter-calendar-back" x-ref="filterCalendarBack" type="button" data-sortir-calendar-back @click="closeFilterCalendar()" class="inline-flex min-h-9 items-center gap-2 rounded-lg px-1 text-sm font-semibold text-gray-700 transition-colors hover:text-gray-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                <i class="ph-bold ph-arrow-left" aria-hidden="true"></i><span>Kembali</span>
                            </button>
                            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                                <div class="mb-4 flex items-center justify-between">
                                    <button type="button" @click="moveFilterCalendar(-1)" aria-label="Sebelumnya" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"><i class="ph-bold ph-caret-left" aria-hidden="true"></i></button>
                                    <button id="sortir-filter-calendar-heading" type="button" @click="calendarView = calendarView === 'date' ? 'month' : (calendarView === 'month' ? 'year' : 'date')" class="rounded-lg px-3 py-1.5 text-base font-bold text-gray-800 transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" x-text="filterCalendarHeading"></button>
                                    <button type="button" @click="moveFilterCalendar(1)" aria-label="Berikutnya" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></button>
                                </div>
                                <div x-show="calendarView === 'date'" class="grid grid-cols-7 gap-y-1 text-center">
                                    <template x-for="(weekday, index) in ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']" :key="weekday">
                                        <span class="flex h-9 items-center justify-center text-[11px] font-semibold text-gray-400" :aria-label="weekday" x-text="weekday[0]"></span>
                                    </template>
                                    <template x-for="blank in filterCalendarBlankDays" :key="'blank-' + blank"><span class="h-9"></span></template>
                                    <template x-for="day in filterCalendarDays" :key="getFilterCalendarDate(day)">
                                        <div class="relative flex h-9 items-center justify-center">
                                            <span x-show="isFilterCalendarDateInRange(day)" class="absolute inset-0 bg-emerald-50"></span>
                                            <button type="button" data-sortir-calendar-day @click="selectFilterCalendarDate(day)" :aria-label="formatFilterDate(getFilterCalendarDate(day))" :aria-pressed="isFilterCalendarDateSelected(day)" :class="isFilterCalendarDateSelected(day) ? 'relative z-10 rounded-full bg-[#c5f037] font-bold text-gray-900 shadow-sm' : (isFilterCalendarDateInRange(day) ? 'relative z-10 font-semibold text-gray-800' : 'relative z-10 rounded-full text-gray-700 hover:bg-gray-100')" class="flex h-9 w-9 cursor-pointer items-center justify-center text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" x-text="day"></button>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="calendarView === 'month'" x-cloak class="grid grid-cols-3 gap-2 py-2">
                                    <template x-for="(month, index) in filterCalendarMonths" :key="month">
                                        <button type="button" @click="chooseFilterCalendarMonth(index)" :class="calendarMonth === index ? 'bg-[#c5f037] font-bold text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="rounded-xl px-2 py-3 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" x-text="month"></button>
                                    </template>
                                </div>
                                <div x-show="calendarView === 'year'" x-cloak class="grid grid-cols-3 gap-2 py-2">
                                    <template x-for="year in filterCalendarYears" :key="year">
                                        <button type="button" @click="chooseFilterCalendarYear(year)" :class="calendarYear === year ? 'bg-[#c5f037] font-bold text-gray-900' : 'text-gray-600 hover:bg-gray-100'" class="rounded-xl px-2 py-3 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" x-text="year"></button>
                                    </template>
                                </div>
                            </div>
                        </section>
                        </x-slot:body>
                        <x-slot:footer>
                            <button type="button" @click="resetFilters()">Reset</button>
                            <button type="button" class="erp-filter-sheet__primary" @click="applyFilter()" :disabled="!!pendingDateError">Terapkan Filter</button>
                        </x-slot:footer>
                    </x-erp.filter-sheet-frame>
                </section>
            </div>
        </template>
        <template x-teleport="body">
            <div x-show="selected" x-cloak class="sortir-overlay" @click.self="closeDetail()" @keydown.escape.window="if (selected) closeDetail()" @keydown.tab="trapFocus($event)">
                <section class="sortir-dialog" id="sortir-detail-dialog" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="sortir-detail-title">
                    <div class="sortir-dialog-head"><h2 id="sortir-detail-title">Detail Sortir Barang</h2><button type="button" class="sortir-icon-button" @click="closeDetail()" :disabled="deleting" aria-label="Tutup detail sortir"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
                    <template x-if="selected">
                        <div>
                            <div class="sortir-detail-body">
                                <div class="sortir-detail-fact"><i class="ph-duotone ph-receipt" aria-hidden="true"></i><div><strong x-text="selected.invoice_number"></strong><small x-text="selected.branch_name"></small></div></div>
                                <div class="sortir-detail-fact"><i class="ph-duotone ph-coins" aria-hidden="true"></i><div><strong x-text="money(selected.total)"></strong><small x-text="'Total Sortir (' + (selected.items?.length || 0) + ' Barang)'"></small></div></div>
                                <div class="sortir-detail-fact"><i class="ph-duotone ph-clock" aria-hidden="true"></i><div><strong x-text="selected.staff_name || 'Petugas'"></strong><small x-text="dateLabel(selected.created_at || selected.date)"></small><small x-text="statusLabel(selected.status)"></small></div></div>
                                <a x-show="selected.can_show" :href="selected.show_url" class="sortir-detail-link">Lihat rincian barang &amp; cetak</a>
                            </div>
                            <div class="sortir-detail-actions" x-show="selected.can_edit || selected.can_delete">
                                <a x-show="selected.can_edit" :href="selected.edit_url"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i>Edit</a>
                                <button type="button" x-show="selected.can_delete" class="sortir-danger" @click="deleteSelected()" :disabled="deleting"><i class="ph-bold ph-trash" aria-hidden="true"></i><span x-text="deleting ? 'Menghapus...' : 'Hapus'"></span></button>
                            </div>
                        </div>
                    </template>
                </section>
            </div>
        </template>
    </section>
@endsection
