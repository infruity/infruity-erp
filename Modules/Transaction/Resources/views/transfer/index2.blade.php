@extends('layouts.erp')
@php
    $receiving = request()->segment(1) === 'transfer-penerima';
    $filterBranches = $branches->map(fn ($branch) => ['id' => (string) $branch->id, 'name' => ucwords($branch->name)])->values();
    $side = $receiving ? 'transfer-penerima' : 'transfer-pengirim';
    $pageTitle = $receiving ? 'Pindah Stok - Menerima' : 'Pindah Stok - Mengirim';
    $statuses = [['value' => 'all', 'label' => 'Semua Status']];
    if (!$receiving) $statuses[] = ['value' => 'draft', 'label' => 'Draf'];
    $statuses = array_merge($statuses, [['value' => 'pending', 'label' => 'Dalam Perjalanan'], ['value' => 'proses', 'label' => 'Sedang Diperiksa'], ['value' => 'selesai', 'label' => 'Sudah Diterima']]);
@endphp
@section('title', $pageTitle . ' - Inventaris')
@section('page-title', $pageTitle)
@section('dashboard-fullscreen', '1')
@section('page-breadcrumb')
    <span>Inventaris</span><i class="ph ph-caret-right text-[0.55rem]" aria-hidden="true"></i><span>{{ $pageTitle }}</span>
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/transfer-mobile.css') }}?v={{ filemtime(public_path('assets/css/transfer-mobile.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('assets/js/transfer-list.js') }}?v={{ filemtime(public_path('assets/js/transfer-list.js')) }}"></script>
@endpush
@section('content')
    <section class="transfer-list-page" x-data="transferList({ endpoint: @js(route('transfer-data')), csrf: @js(csrf_token()), timezone: @js(config('app.timezone')), side: @js($side), receiving: @js($receiving), branches: @js($filterBranches) })" aria-label="Daftar Pindah Stok">
        <div class="transfer-toolbar">
            <div class="transfer-search">
                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                <input id="transfer-search" type="search" x-model="search" @input.debounce.300ms="load()" placeholder="Cari cabang, nomor pengiriman, atau barang..." aria-label="Cari transfer stok" autocomplete="off">
                <button type="button" x-show="search" x-cloak @click="clearSearch()" class="transfer-icon-button" aria-label="Hapus pencarian"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                <span class="transfer-search-divider" aria-hidden="true"></span>
                <button type="button" @click="openFilter($event)" class="transfer-icon-button" :class="activeFilters || filterOpen ? 'is-active' : ''" :aria-expanded="filterOpen" aria-label="Filter pindah stok"><span class="icon-rounded" aria-hidden="true">tune</span></button>
            </div>
            @if (check_access('transfer-pengirim.create'))
                <a class="transfer-list-create" href="{{ route('transfer-pengirim.create') }}"><i class="ph-bold ph-plus" aria-hidden="true"></i>Kirim Stok Baru</a>
            @endif
        </div>
        <div class="transfer-list-scroll" :aria-busy="loading">
            <div class="transfer-list-head" x-show="rows.length"><span>Cabang &amp; Waktu</span><span>Pengirim / Produk</span><span style="text-align:right">Status / Nomor Pengiriman</span></div>
            <template x-for="row in rows" :key="row.id">
                <button type="button" class="transfer-row" @click="openDetail(row, $event)" :aria-label="'Detail transfer ' + row.invoice_number">
                    <span class="transfer-row-branch">
                        <span class="transfer-branch-avatar" :style="branchTheme(branchName(row))" x-text="initials(branchName(row))"></span>
                        <span class="transfer-branch-copy"><strong class="transfer-branch-name" x-text="branchName(row)"></strong><span class="transfer-row-date" x-text="dateLabel(row.created_at || row.date)"></span></span>
                    </span>
                    <span class="transfer-desktop-meta"><span x-text="row.staff_name || '—'"></span><br><small x-text="row.items.length + ' produk'"></small></span>
                    <span class="transfer-row-amount"><span class="transfer-row-status" :class="row.status === 'selesai' ? 'is-final' : (row.status === 'pending' ? 'is-pending' : '')" x-text="statusLabel(row.status)"></span><strong class="transfer-row-total" x-text="row.invoice_number"></strong></span>
                </button>
            </template>
            <div class="transfer-list-state" x-show="loading && !rows.length" role="status"><i class="ph ph-circle-notch" aria-hidden="true"></i><p>Memuat transfer stok...</p></div>
            <div class="transfer-list-state" x-show="error" x-cloak role="alert"><h2>Daftar transfer belum dapat dimuat</h2><p x-text="error"></p><button type="button" class="transfer-text-button" @click="load(rows.length > 0)">Coba lagi</button></div>
            <div class="transfer-list-state" x-show="!loading && !error && !rows.length" x-cloak>
                <i class="ph-duotone ph-receipt" aria-hidden="true"></i>
                <h2 x-text="search || activeFilters ? 'Transaksi tidak ditemukan' : @js($receiving ? 'Belum ada barang masuk' : 'Belum ada pengiriman')"></h2>
                <p x-show="search || activeFilters">Coba kata pencarian lain atau sesuaikan filter.</p>
                @if (!$receiving && check_access('transfer-pengirim.create'))
                    <p x-show="!search && !activeFilters">Klik tombol <a href="{{ route('transfer-pengirim.create') }}" class="transfer-text-button" aria-label="Tambah pindah stok">+</a> untuk menambahkan<br>transfer stok baru.</p>
                @endif
                @if ($receiving)<p x-show="!search && !activeFilters">Pengiriman ke cabang Anda akan tampil di sini untuk diperiksa dan diterima.</p>@endif
                <button type="button" class="transfer-text-button" x-show="search || activeFilters" @click="search = ''; resetFilters()">Reset pencarian &amp; filter</button>
            </div>
            <div class="transfer-load-more" x-show="hasMore && rows.length && !error" x-cloak>
                <x-erp.load-more @click="load(true)" loading="loading">
                    <span x-text="loading ? 'Memuat...' : 'Muat ' + Math.min(20, filtered - rows.length) + ' Transaksi Lagi'"></span>
                </x-erp.load-more>
            </div>
            <p class="transfer-list-count" x-show="rows.length && !hasMore" x-cloak x-text="rows.length + ' dari ' + filtered + ' transfer stok'"></p>
        </div>
        <template x-teleport="body">
            <div x-show="filterOpen" x-cloak class="transfer-overlay is-filter" @click.self="closeFilter()" @keydown.escape.window="if (filterOpen) closeFilter()" @keydown.tab="trapFocus($event)">
                <section class="transfer-dialog transfer-filter-dialog" id="transfer-filter-dialog" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="transfer-filter-title" aria-describedby="transfer-filter-description">
                    <button type="button" class="transfer-filter-handle" @click="closeFilter()" aria-label="Tutup filter"></button>
                    <div class="transfer-dialog-head"><div><h2 id="transfer-filter-title">Filter Pindah Stok</h2><p id="transfer-filter-description">Saring cabang dan status {{ $receiving ? 'penerimaan' : 'pengiriman' }}</p></div><button type="button" class="transfer-icon-button" @click="closeFilter()" aria-label="Tutup filter"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
                    <div class="transfer-filter-body">
                        <div class="transfer-filter-section">
                            <div class="transfer-filter-section-head">
                                <span class="transfer-filter-label" id="transfer-filter-branch-label">{{ $receiving ? 'Cabang Penerima' : 'Cabang Pengirim' }}</span>
                                <button type="button" class="transfer-filter-reset" x-show="pending.branch !== 'all'" x-cloak @click="resetPendingFilter('branch')">Reset</button>
                            </div>
                            <div class="transfer-filter-branch-picker">
                                <label class="transfer-filter-branch-search"><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input type="search" x-model="branchSearchQuery" placeholder="Cari cabang..." aria-label="Cari cabang" autocomplete="off"></label>
                                <div class="transfer-filter-branch-options" role="radiogroup" aria-labelledby="transfer-filter-branch-label">
                                    <template x-for="branchOption in filteredBranches" :key="branchOption.id">
                                        <label class="transfer-filter-branch-option"><input type="radio" name="transfer-filter-branch" :value="branchOption.id" x-model="pending.branch"><span x-text="branchOption.name"></span></label>
                                    </template>
                                    <p x-show="!filteredBranches.length" x-cloak class="transfer-filter-branch-empty" role="status">Cabang tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                        <div class="transfer-filter-section">
                            <div class="transfer-filter-section-head">
                                <span class="transfer-filter-label">Status {{ $receiving ? 'Penerimaan' : 'Pengiriman' }}</span>
                                <button type="button" class="transfer-filter-reset" x-show="pending.status !== 'all'" x-cloak @click="resetPendingFilter('status')">Reset</button>
                            </div>
                            <div class="transfer-filter-chips" role="group" aria-label="Status transfer">
                                <template x-for="status in @js($statuses)" :key="status.value"><button type="button" @click="pending.status = status.value" :class="pending.status === status.value ? 'is-active' : ''" :aria-pressed="pending.status === status.value" x-text="status.label"></button></template>
                            </div>
                        </div>
                        <div class="transfer-filter-section">
                            <div class="transfer-filter-section-head">
                                <span class="transfer-filter-label">Periode Tanggal</span>
                                <button type="button" class="transfer-filter-reset" x-show="pending.start || pending.end" x-cloak @click="resetPendingFilter('date')">Reset</button>
                            </div>
                            <div class="transfer-filter-date-cards">
                                <label class="transfer-filter-date-card" for="transfer-date-start"><span class="transfer-filter-date-label">DARI</span><input id="transfer-date-start" type="date" x-model="pending.start" aria-label="Dari tanggal"></label>
                                <label class="transfer-filter-date-card" for="transfer-date-end"><span class="transfer-filter-date-label">KE</span><input id="transfer-date-end" type="date" x-model="pending.end" :min="pending.start" aria-label="Sampai tanggal"></label>
                            </div>
                            <div class="transfer-filter-quick">
                                <span class="transfer-filter-label">Pilihan Cepat</span>
                                <div class="transfer-filter-quick-actions" role="group" aria-label="Pilihan cepat tanggal">
                                    <button type="button" class="is-week" @click="setPendingDateRange('week')">Minggu Ini</button>
                                    <button type="button" @click="setPendingDateRange('today')">Hari Ini</button>
                                </div>
                            </div>
                        </div>
                        <p class="transfer-filter-error" x-show="pendingDateError" x-cloak x-text="pendingDateError" role="alert"></p>
                    </div>
                    <div class="transfer-filter-foot"><button type="button" @click="resetFilters()">Reset</button><button type="button" @click="applyFilter()" :disabled="!!pendingDateError">Terapkan</button></div>
                </section>
            </div>
        </template>
        @include('transaction::transfer.segment.detail-dialog')
    </section>
@endsection
