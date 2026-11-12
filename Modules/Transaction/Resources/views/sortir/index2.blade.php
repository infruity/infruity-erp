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
    <section class="sortir-list-page relative" x-data="sortirList({ endpoint: @js(route('sortir-data')), csrf: @js(csrf_token()) })" @erp-filter-apply.window="if ($event.detail.id === 'sortir') applyFilters($event.detail.values)" @erp-filter-close.window="if ($event.detail.id === 'sortir') closeFilter()" aria-label="Daftar Sortir Barang">
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
        <x-erp.list-count noun="transaksi sortir" shown="rows.length" total="filtered" list=".sortir-list-scroll" />
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
        </div>
        <x-erp.filter-sheet
            id="sortir"
            title="Filter Sortir Barang"
            aria-label="Filter sortir barang"
            branch-label="Cabang Tujuan"
            branch-all-label="Semua Cabang"
            status-label="Status Sortir"
            :branches="$sortirFilterBranches"
            :statuses="['all' => 'Semua Status', 'draft' => 'Draf', 'paid' => 'Selesai']"
            :timezone="config('app.timezone')"
            desktop="modal"
            reset-applies
            apply-label="Terapkan Filter"
        />
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
