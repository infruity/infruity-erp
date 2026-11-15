@extends('layouts.erp-tailwind')
@php
    $receiving = request()->segment(1) === 'transfer-penerima';
    $filterBranches = $branches->map(fn ($branch) => ['id' => (string) $branch->id, 'name' => ucwords($branch->name)])->values();
    $side = $receiving ? 'transfer-penerima' : 'transfer-pengirim';
    $pageTitle = $receiving ? 'Pindah Stok - Menerima' : 'Pindah Stok - Mengirim';
    // Three states, as in the reference: being prepared, on its way, received (an unfinished receiving check still counts as on its way).
    $statuses = [['value' => 'all', 'label' => 'Semua Status']];
    if (!$receiving) $statuses[] = ['value' => 'draft', 'label' => 'Sedang Disiapkan'];
    $statuses = array_merge($statuses, [['value' => 'pending', 'label' => 'Dalam Perjalanan'], ['value' => 'selesai', 'label' => 'Sudah Diterima']]);
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
    <section class="transfer-list-page relative" x-data="transferList({ endpoint: @js(route('transfer-data')), csrf: @js(csrf_token()), side: @js($side), receiving: @js($receiving) })" @erp-filter-apply.window="if ($event.detail.id === 'transfer') applyFilters($event.detail.values)" @erp-filter-close.window="if ($event.detail.id === 'transfer') closeFilter()" aria-label="Daftar Pindah Stok">
        <div class="transfer-toolbar">
            <div class="transfer-search">
                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                <input id="transfer-search" type="search" x-model="search" @input.debounce.300ms="load()" placeholder="Cari..." aria-label="Cari transfer stok" autocomplete="off">
                <button type="button" x-show="search" x-cloak @click="clearSearch()" class="transfer-icon-button" aria-label="Hapus pencarian"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                <span class="transfer-search-divider" aria-hidden="true"></span>
                <button type="button" @click="openFilter($event)" class="transfer-icon-button" :class="activeFilters || filterOpen ? 'is-active' : ''" :aria-expanded="filterOpen" aria-label="Filter pindah stok"><span class="icon-rounded" aria-hidden="true">tune</span></button>
            </div>
            @if (check_access('transfer-pengirim.create'))
                <a class="transfer-list-create" href="{{ route('transfer-pengirim.create') }}"><i class="ph-bold ph-plus" aria-hidden="true"></i>Kirim Stok Baru</a>
            @endif
        </div>
        <x-erp.list-count noun="transfer stok" shown="rows.length" total="filtered" list=".transfer-list-scroll" />
        <div class="transfer-list-scroll" :aria-busy="loading">
            <div class="transfer-list-head" x-show="rows.length"><span>Cabang &amp; Waktu</span><span>Pengirim / Produk</span><span style="text-align:right">Status / Nomor Pengiriman</span></div>
            <template x-for="row in rows" :key="row.id">
                <button type="button" class="transfer-row" :class="hasDeficit(row) ? 'has-deficit' : ''" @click="openDetail(row, $event)" :aria-label="'Detail transfer ' + row.invoice_number">
                    <span class="transfer-row-branch">
                        <span class="transfer-branch-avatar" :style="branchTheme(branchName(row))" x-text="initials(branchName(row))"></span>
                        <span class="transfer-branch-copy"><strong class="transfer-branch-name" x-text="branchName(row)"></strong><span class="transfer-row-date" x-text="rowDate(row)"></span></span>
                    </span>
                    <span class="transfer-desktop-meta"><span x-text="row.staff_name || '—'"></span><br><small x-text="row.items.length + ' produk'"></small></span>
                    <span class="transfer-row-amount"><span class="transfer-row-status-line"><i class="ph-fill ph-warning-circle transfer-row-warning" x-show="hasDeficit(row)" x-cloak title="Terdapat Selisih Kurang" aria-hidden="true"></i><span class="transfer-row-status" :class="row.status === 'selesai' ? 'is-final' : (['pending','proses'].includes(row.status) ? 'is-pending' : '')" x-text="statusLabel(row.status)"></span></span><strong class="transfer-row-total" x-text="row.invoice_number"></strong></span>
                </button>
            </template>
            <div class="transfer-list-state" x-show="loading && !rows.length" role="status"><i class="ph ph-circle-notch" aria-hidden="true"></i><p>Memuat transfer stok...</p></div>
            <div class="transfer-list-state" x-show="error" x-cloak role="alert"><h2>Daftar transfer belum dapat dimuat</h2><p x-text="error"></p><button type="button" class="transfer-text-button" @click="load(rows.length > 0)">Coba lagi</button></div>
            <div class="transfer-list-state" x-show="!loading && !error && !rows.length" x-cloak>
                <span class="transfer-state-icon"><i class="ph-duotone ph-receipt" aria-hidden="true"></i></span>
                <h2 x-text="search || activeFilters ? 'Transaksi tidak ditemukan' : @js($receiving ? 'Belum ada penerimaan stok' : 'Belum ada pengiriman stok')"></h2>
                <p x-show="search || activeFilters">Coba kata pencarian lain atau sesuaikan filter.</p>
                @if (!$receiving && check_access('transfer-pengirim.create'))
                    <p x-show="!search && !activeFilters">Tekan tombol <a href="{{ route('transfer-pengirim.create') }}" class="transfer-add-tile" aria-label="Tambah pindah stok">+</a> di bawah untuk menambahkan transfer stok baru</p>
                @endif
                @if ($receiving)<p x-show="!search && !activeFilters">Pengiriman dari cabang lain akan tampil di sini untuk diterima</p>@endif
                <button type="button" class="transfer-text-button" x-show="search || activeFilters" @click="search = ''; resetFilters()">Reset pencarian &amp; filter</button>
            </div>
            <div class="transfer-load-more" x-show="hasMore && rows.length && !error" x-cloak>
                <x-erp.load-more @click="load(true)" loading="loading">
                    <span x-text="loading ? 'Memuat...' : 'Muat ' + Math.min(20, filtered - rows.length) + ' Transaksi Lagi'"></span>
                </x-erp.load-more>
            </div>
        </div>
        <x-erp.filter-sheet
            id="transfer"
            title="Filter Pindah Stok - {{ $receiving ? 'Menerima' : 'Mengirim' }}"
            aria-label="Filter pindah stok"
            subtitle="{{ $receiving ? 'Saring cabang, status penerimaan, dan periode tanggal' : 'Saring cabang tujuan, status, dan periode tanggal' }}"
            :branch-label="'Cabang Tujuan'"
            branch-all-label="Semua Cabang"
            :status-label="'Status ' . ($receiving ? 'Penerimaan' : 'Pengiriman')"
            :branches="$filterBranches"
            :statuses="collect($statuses)->pluck('label', 'value')->all()"
            :timezone="config('app.timezone')"
            desktop="modal"
            variant="transfer"
            reset-label="Reset Filter"
            apply-label="Terapkan Filter"
            reset-applies
        />
        @include('transaction::transfer.segment.detail-dialog')
    </section>
@endsection
