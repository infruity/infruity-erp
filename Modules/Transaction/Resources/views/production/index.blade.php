@extends('template.root')
@section('title', 'Produksi')
@section('content')
    <div class="production-list-page">
    <link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
    <style>
        .production-row { display:flex; min-width:0; align-items:center; gap:12px; }
        .production-avatar { display:flex; width:40px; height:40px; flex:none; align-items:center; justify-content:center; border:1px solid #f3f4f6; border-radius:12px; background:#f3f4f6; font-size:18px; line-height:1; }
        /* Per-product tint from Modules\Master\Support\ProductIcon, matching the /products list. */
        .production-avatar.bg-emerald-50 { background:#ecfdf5; }
        .production-avatar.bg-rose-50 { background:#fff1f2; }
        .production-avatar.bg-amber-50 { background:#fffbeb; }
        .production-avatar.bg-pink-100 { background:#fce7f3; }
        .production-avatar.bg-yellow-50 { background:#fefce8; }
        .production-avatar.bg-orange-50 { background:#fff7ed; }
        .production-avatar.bg-red-50 { background:#fef2f2; }
        .production-avatar.bg-purple-50 { background:#faf5ff; }
        .production-avatar.bg-lime-50 { background:#f7fee7; }
        .production-avatar.bg-green-50 { background:#f0fdf4; }
        .production-avatar.bg-stone-50 { background:#fafaf9; }
        .production-avatar.bg-gray-100 { background:#f3f4f6; }
        /* Pagination and the page-size select are replaced by the load-more button everywhere. */
        #production-table_wrapper :is(.dataTables_paginate, .dataTables_length) { display:none !important; }
        .production-load-more { padding:16px 20px; }
        .production-load-more[hidden] { display:none; }
        .production-load-more__button { display:flex; width:100%; height:44px; align-items:center; justify-content:center; gap:8px; border:2px dashed #e5e7eb; border-radius:12px; background:transparent; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer; transition:color .15s,border-color .15s,background-color .15s; }
        .production-load-more__button:hover { border-color:#34d399; color:#047857; background:#ecfdf5; }
        .production-load-more__button:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
        .production-copy { display:flex; min-width:0; flex-direction:column; gap:2px; text-decoration:none; }
        .production-product { overflow:hidden; color:#1f2937; font-size:14px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .production-copy:hover .production-product { color:#0b595b; }
        .production-sub { overflow:hidden; color:#6b7280; font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
        .production-status-cell { display:flex; flex-direction:column; align-items:center; gap:3px; }
        .production-status { display:inline-flex; align-items:center; border-radius:999px; padding:3px 9px; font-size:11px; font-weight:700; }
        .production-status.is-draft { background:#fffbeb; color:#b45309; }
        .production-status.is-temp { background:#f3f4f6; color:#6b7280; }
        .production-status.is-posting, .production-status.is-complete { background:#f0fdf4; color:#166534; }
        .production-status.is-muted { background:#f3f4f6; color:#4b5563; }
        .production-qty { color:#111827; font-size:13px; font-weight:700; font-variant-numeric:tabular-nums; }
        .production-pos { color:#6b7280; font-size:11px; }
        #production-table tbody tr.is-openable { cursor:pointer; }
        .production-empty-state { display:flex; min-height:260px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
        .production-empty-state i { display:flex; width:56px; height:56px; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
        .production-empty-state strong { color:#1f2937; font-size:14px; }
        .production-empty-state span { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
        @media (max-width:1279px) {
            #production-table, #production-table tbody { display:block; width:100% !important; }
            #production-table thead { display:none; }
            #production-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) auto 36px; align-items:center; gap:4px 12px; padding:12px 16px; border-bottom:1px solid #f1f3f5; }
            #production-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
            #production-table tbody td:nth-child(3) { display:none; }
            #production-table tbody td:nth-child(2) { text-align:right; }
            #production-table .production-status-cell { align-items:flex-end; }
            #production-table .production-status { padding:0; background:transparent !important; font-size:12px; }
            #production-table .production-status.is-draft { color:#b45309; }
            #production-table .production-status.is-temp, #production-table .production-status.is-muted { color:#6b7280; }
            #production-table .production-status.is-posting, #production-table .production-status.is-complete { color:#0b595b; }
            #production-table_wrapper .dataTables_info, #production-table_wrapper .dataTables_paginate { display:none !important; }
            #production-table td.dataTables_empty { grid-column:1 / -1; }
        }
        @media (max-width:767px) {
            .production-avatar { width:36px; height:36px; border-radius:11px; font-size:16px; }
            .production-product { font-size:13px; }
            .production-sub { font-size:11px; }
            .production-qty { font-size:12px; }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/production-list-reference.css') }}?v={{ filemtime(public_path('assets/css/production-list-reference.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/production-detail-reference.css') }}?v={{ filemtime(public_path('assets/css/production-detail-reference.css')) }}">
    <x-erp.workspace title="Produksi" subtitle="Pantau produksi, bahan baku dan status pengerjaan." icon="cooking-pot">
        <x-slot:toolbar>
            <label class="erp-search production-desktop-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" data-production-search id="search-desktop" aria-label="Cari produksi" placeholder="Cari produksi..."></label>
            <x-erp.mobile-search class="production-mobile-search"><x-slot:input><input type="search" data-production-search id="search" class="erp-mobile-search__input" aria-label="Cari produksi" placeholder="Cari..."></x-slot:input><x-slot:filter><button type="button" class="erp-mobile-search__filter" data-production-filter-toggle aria-label="Filter produksi" aria-controls="production-list-filter" aria-expanded="false"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></x-slot:filter></x-erp.mobile-search>
            @if(count($branches) > 1)<div class="production-branch-pill" x-data="{ open: false, value: 'all', label: 'Semua Cabang' }" x-init="$('#production-branch-filter').on('change', e => { value = e.target.value; label = $(e.target).find('option:selected').text().trim(); })" @click.outside="open = false" @keydown.escape="open = false"><button type="button" class="production-branch-pill__button" aria-haspopup="listbox" :aria-expanded="open" @click="open = !open"><i class="ph ph-storefront" aria-hidden="true"></i><span x-text="label"></span><i class="ph ph-caret-down" aria-hidden="true"></i></button><div class="production-branch-pill__menu" role="listbox" aria-label="Pilih cabang" x-show="open" x-cloak><button type="button" role="option" :aria-selected="value === 'all'" @click="$('#production-branch-filter').val('all').trigger('change'); open = false">Semua Cabang</button>@foreach ($branches as $branch)<button type="button" role="option" :aria-selected="value === '{{ $branch->id }}'" @click="$('#production-branch-filter').val('{{ $branch->id }}').trigger('change'); open = false">{{ ucwords($branch->name) }}</button>@endforeach</div></div>@endif
            <div class="erp-operations-toolbar-actions production-list-tools"><details class="erp-operations-filter" id="production-list-filter"><summary class="erp-action"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></summary><div class="erp-operations-filter__body"><fieldset class="production-status-segments" role="radiogroup" aria-label="Status produksi"><legend>Status</legend><input type="hidden" id="production-status-filter" value="all" data-kt-ecommerce-product-filter="status">@foreach (['all' => 'Semua', 'temp' => 'Draf', 'draft' => 'Siap', 'posting' => 'Selesai'] as $statusValue => $statusLabel)<button type="button" role="radio" aria-checked="{{ $statusValue === 'all' ? 'true' : 'false' }}" class="production-status-segments__option" data-status-value="{{ $statusValue }}">{{ $statusLabel }}</button>@endforeach</fieldset><label for="production-branch-filter">Cabang<select id="production-branch-filter" class="form-select" data-control="select2" data-hide-search="true" data-placeholder="Cabang" data-kt-ecommerce-product-filter="cabang">@if(count($branches) > 1)<option value="all">Semua Cabang</option>@endif @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>@endforeach</select></label><label for="kt_ecommerce_sales_flatpickr">Rentang tanggal<div class="input-group"><input class="form-control" placeholder="Pilih rentang tanggal" id="kt_ecommerce_sales_flatpickr"><button type="button" class="erp-action" id="kt_ecommerce_sales_flatpickr_clear" aria-label="Hapus rentang tanggal"><i class="ph ph-x" aria-hidden="true"></i></button></div></label></div></details><a href="{{ url('production/create') }}" class="erp-action erp-action--primary"><i class="ph ph-plus" aria-hidden="true"></i>Tambah Produksi</a></div>
        </x-slot:toolbar>
        <div class="erp-operations-collection production-list">
            <table class="table erp-operations-table align-middle" id="production-table" width="100%">
                    <thead>
                        <tr class="text-start">

                            <th class="text-start min-w-100px">Nama</th>
                            <th class="text-center min-w-100px">Status</th>
                            <th class="text-center min-w-100px">Tanggal Produksi</th>
                            <th class="text-end min-w-70px" style="position: relative; z-index: 1;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class=""></tbody>
                </table>
            <div id="production-load-more" class="production-load-more" hidden>
                <button type="button" class="production-load-more__button">
                    <i class="ph ph-arrow-circle-down" aria-hidden="true"></i> Muat Produk Lagi
                </button>
            </div>
        </div>
    </x-erp.workspace>
    </div>
    <div data-production-detail-modal x-data="ProductionListUi.createDetailModal('{{ route('production.index') }}')">
        <template x-teleport="body">
            <x-erp.detail-dialog open="productionDetailOpen" close="closeProductionDetail()" title="Detail Produksi" title-binding="productionDetailTitle" labelledby="production-detail-modal-title" closeLabel="Tutup detail produksi" id="production-detail-modal" class="production-detail-dialog" tabindex="-1" x-on:keydown.tab="trapProductionDetailFocus($event)">
                <div x-show="productionDetailLoading" class="production-detail-dialog__loading" role="status" aria-live="polite">Memuat detail produksi…</div>
                <div x-show="!productionDetailLoading" class="production-detail-dialog__body" x-html="productionDetailHtml"></div>
            </x-erp.detail-dialog>
        </template>
    </div>
@section('script')
    <script src="{{ asset('assets/js/production-list-reference.js') }}?v={{ filemtime(public_path('assets/js/production-list-reference.js')) }}"></script>
    <script type="text/javascript">
        var dataTable;
        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            const productionEmptyState = '<div class="production-empty-state"><i class="ph ph-cooking-pot" aria-hidden="true"></i><strong>Belum ada produksi</strong><span>Data produksi yang tersimpan akan muncul di sini.</span></div>';

            dataTable = $('#production-table').DataTable({
                language: { emptyTable: productionEmptyState, zeroRecords: productionEmptyState },
                createdRow: function(row, data) {
                    ProductionListUi.enhanceRow(row, data);
                    const link = row.querySelector('a.production-copy');
                    if (link) {
                        row.classList.add('is-openable');
                        row.dataset.href = link.getAttribute('href');
                    }
                },
                drawCallback: function() {
                    const info = this.api().page.info();
                    $(this.api().table().container()).toggleClass('production-single-page', info.pages <= 1);
                    // Hide the button once every matching row is already on screen; if it owned
                    // focus, hand focus to the table so it does not fall back to <body>.
                    const $more = $('#production-load-more');
                    const hadFocus = $more.length && $more[0].contains(document.activeElement);
                    const done = info.end >= info.recordsDisplay;
                    $more.prop('hidden', done);
                    if (done && hadFocus) {
                        const table = document.getElementById('production-table');
                        if (table) {
                            table.setAttribute('tabindex', '-1');
                            table.focus({preventScroll: true});
                        }
                    }
                },
                pageLength: 50,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Disable sorting for action column
                }],
                ajax: {
                    url: "{{ route('production-data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = $('[data-kt-ecommerce-product-filter="status"]').val();
                        d.cabang_filter = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0];
                        }
                    },
                    error: function(xhr, error, code) {
                        console.error('DataTable AJAX Error:', error, code);
                        console.error('Response:', xhr.responseText);
                    }
                },
                order: [
                    [2, 'desc'], // Sort by production_date DESC
                    [0, 'desc'] // Then by name DESC
                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'production_date',
                        name: 'production_date',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        className: 'text-end',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // page.len() only sets the length in DataTables 1.13 (its own page-size select calls
            // the setter and then draws), so the draw is required. Clamp the base so an
            // unexpected length can never shrink the list instead of growing it.
            $('#production-load-more button').on('click', function() {
                dataTable.page.len(Math.max(dataTable.page.len(), 50) + 50).draw();
            });

            // A tap on the row opens the detail popup; the route remains the no-JS fallback.
            $('#production-table tbody').on('click', 'tr.is-openable', function(event) {
                if ($(event.target).closest('a, button, .dropdown, .dropdown-menu').length) return;
                if (typeof window.openProductionDetail === 'function') window.openProductionDetail(this.dataset.href);
                else window.location.href = this.dataset.href;
            });

            // Search manual lewat input
            $('[data-production-search]').on('input', function() {
                $('[data-production-search]').not(this).val(this.value);
                dataTable.search(this.value).draw();
            });
            const filter = document.getElementById('production-list-filter');
            const mobileFilter = document.querySelector('[data-production-filter-toggle]');
            mobileFilter.addEventListener('click', () => { filter.open = !filter.open; });
            filter.addEventListener('toggle', () => mobileFilter.setAttribute('aria-expanded', String(filter.open)));
            document.addEventListener('click', event => {
                if (event.target.closest('.select2-container, .flatpickr-calendar')) return;
                if (filter.open && !filter.contains(event.target) && !mobileFilter.contains(event.target)) filter.open = false;
            });
            filter.addEventListener('keydown', event => {
                if (event.key !== 'Escape') return;
                filter.open = false;
                (mobileFilter.getClientRects().length ? mobileFilter : filter.querySelector('summary')).focus();
            });

            $('.production-status-segments__option').on('click', function() {
                const value = this.dataset.statusValue;
                $('.production-status-segments__option').attr('aria-checked', 'false');
                this.setAttribute('aria-checked', 'true');
                $('[data-kt-ecommerce-product-filter="status"]').val(value).trigger('change');
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                dataTable.draw();
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
            });

            const salesFlatpickr = $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function() {
                    updateActiveFilterInfo();
                    dataTable.draw();
                }
            });

            $('#kt_ecommerce_sales_flatpickr_clear').on('click', function() {
                salesFlatpickr.clear(false);
                updateActiveFilterInfo();
                dataTable.draw();
            });

            updateActiveFilterInfo();
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
                        url: `/production/${id}`, // Ganti dengan URL yang sesuai
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
                                timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
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

        function receiveProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang diproses tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Proses!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/wholesale/receive/${id}`, // Ganti dengan URL yang sesuai
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            id: id
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil diterima.'
                            });

                            // Reload DataTable setelah berhasil menghapus data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menerima data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
