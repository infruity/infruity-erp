@extends('template.root')
@section('title', 'Produksi')
@section('content')
    <link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
    <style>
        [x-cloak] { display:none !important; }
        .production-row { display:flex; min-width:0; align-items:center; gap:12px; }
        .production-avatar { display:flex; width:36px; height:36px; flex:none; align-items:center; justify-content:center; border:1px solid #f3f4f6; border-radius:12px; background:#fff3e0; font-size:17px; line-height:1; }
        .production-copy { display:flex; min-width:0; flex-direction:column; gap:2px; text-decoration:none; }
        .production-product { overflow:hidden; color:#111827; font-size:13px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .production-copy:hover .production-product { color:#0b595b; }
        .production-sub { overflow:hidden; color:#9ca3af; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
        .production-code { display:none; }
        .production-status-cell { display:flex; flex-direction:column; align-items:flex-end; gap:2px; }
        .production-status { font-size:12px; font-weight:700; }
        .production-status.is-draft { color:#b45309; }
        .production-status.is-temp, .production-status.is-muted { color:#9ca3af; }
        .production-status.is-posting { color:#059669; }
        .production-qty { color:#111827; font-size:12px; font-weight:700; font-variant-numeric:tabular-nums; }
        #production-table tbody tr.is-openable { cursor:pointer; }
        .production-empty-state { display:flex; min-height:260px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
        .production-empty-state i { display:flex; width:56px; height:56px; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
        .production-empty-state strong { color:#1f2937; font-size:14px; }
        .production-empty-state span { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
        @media (min-width:1280px) {
            .production-code { display:inline; }
            .production-status { display:inline-flex; border-radius:999px; padding:3px 9px; font-size:11px; }
            .production-status.is-posting { background:#f0fdf4; color:#166534; }
            .production-status.is-draft { background:#fffbeb; }
            .production-status.is-temp, .production-status.is-muted { background:#f3f4f6; color:#6b7280; }
            .production-status-cell { align-items:center; }
        }
        @media (max-width:1279px) {
            #production-table, #production-table tbody { display:block; width:100% !important; }
            #production-table thead { display:none; }
            #production-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) auto 32px; align-items:center; gap:4px 12px; padding:12px 16px; border-bottom:1px solid #f3f4f6; }
            #production-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
            #production-table tbody td:nth-child(3) { display:none; }
            #production-table tbody td:nth-child(2) { text-align:right; }
            #production-table tbody td:nth-child(4) .btn { display:flex; width:32px; height:32px; align-items:center; justify-content:center; padding:0; border:0; background:transparent; color:#9ca3af; }
            #production-table tbody td:nth-child(4) .btn::before, #production-table tbody td:nth-child(4) .btn::after { display:none; }
            #production-table_wrapper .dataTables_info, #production-table_wrapper .dataTables_paginate { display:none !important; }
            #production-table td.dataTables_empty { grid-column:1 / -1; }
        }
        @media (min-width:768px) {
            .production-workspace .production-mobile-search { display:none; }
        }
        @media (max-width:767px) {
            .production-workspace .erp-workspace-heading { display:none; }
            .production-workspace .production-desktop-search, .production-workspace .erp-operations-toolbar-actions { display:none; }
            .production-workspace .erp-workspace-toolbar { padding:0; border:0; }
        }
        .production-filter { position:fixed; inset:0; z-index:1100; display:flex; align-items:flex-end; justify-content:center; }
        .production-filter__backdrop { position:absolute; inset:0; border:0; background:rgba(17,24,39,.6); }
        .production-filter__panel { position:relative; display:flex; width:100%; max-width:448px; max-height:88vh; flex-direction:column; overflow:hidden; border-radius:24px 24px 0 0; background:#fff; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); }
        @media (min-width:768px) { .production-filter { align-items:center; padding:16px; } .production-filter__panel { border-radius:24px; } }
        .production-filter__head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 20px; border-bottom:1px solid #f3f4f6; }
        .production-filter__head h2 { margin:0; color:#111827; font-size:16px; font-weight:800; }
        .production-filter__head p { margin:2px 0 0; color:#9ca3af; font-size:12px; }
        .production-filter__close { display:grid; width:32px; height:32px; flex:none; place-items:center; border:0; border-radius:50%; background:#f3f4f6; color:#6b7280; }
        .production-filter__body { display:flex; flex-direction:column; gap:18px; overflow-y:auto; padding:18px 20px; }
        .production-filter__label { margin:0 0 8px; color:#6b7280; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
        .production-filter__chips { display:flex; flex-wrap:wrap; gap:8px; }
        .production-filter__chip { min-height:36px; padding:0 14px; border:1px solid #e5e7eb; border-radius:999px; background:#fff; color:#4b5563; font-size:13px; font-weight:600; }
        .production-filter__chip.is-selected { border-color:#34a684; background:#f0fdf4; color:#0b595b; }
        .production-filter__branches { max-height:176px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:12px; }
        .production-filter__branch { display:flex; align-items:center; gap:12px; padding:10px 14px; border-top:1px solid #f3f4f6; font-size:13px; cursor:pointer; }
        .production-filter__branch:first-child { border-top:0; }
        .production-filter__branch input { accent-color:#0b595b; }
        .production-filter__dates { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .production-filter__dates label { display:flex; flex-direction:column; gap:4px; color:#6b7280; font-size:11px; font-weight:600; }
        .production-filter__dates input { min-height:44px; padding:0 12px; border:1px solid #e5e7eb; border-radius:12px; color:#111827; font-size:14px; }
        .production-filter__foot { display:flex; gap:10px; padding:14px 20px; border-top:1px solid #f3f4f6; background:#f9fafb; }
        .production-filter__foot button { flex:1; min-height:44px; border-radius:12px; font-size:14px; font-weight:700; }
        .production-filter__reset { border:1px solid #e5e7eb; background:#fff; color:#4b5563; }
        .production-filter__apply { border:0; background:#0b595b; color:#fff; }
    </style>
    <x-erp.workspace class="production-workspace" title="Produksi" subtitle="Pantau produksi, bahan baku dan status pengerjaan." icon="cooking-pot"
        x-data="productionFilterSheet()" @keydown.escape.window="close()">
        <x-slot:toolbar>
            <label class="erp-search production-desktop-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" data-kt-ecommerce-product-filter="search" id="search-desktop" aria-label="Cari produksi" placeholder="Cari produksi..."></label>
            <x-erp.mobile-search class="production-mobile-search erp-mobile-search-shell--fill">
                <x-slot:input><input type="search" id="search" data-kt-ecommerce-product-filter="search" aria-label="Cari produksi" placeholder="Cari..." class="erp-mobile-search__input"></x-slot:input>
                <x-slot:filter><button type="button" class="erp-mobile-search__filter" aria-label="Filter produksi" aria-haspopup="dialog" :class="hasActive ? 'is-active' : ''" @click="openSheet()"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></x-slot:filter>
            </x-erp.mobile-search>
            <div class="erp-operations-toolbar-actions"><details class="erp-operations-filter"><summary class="erp-action"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></summary><div class="erp-operations-filter__body"><label for="production-status-filter">Status<select id="production-status-filter" class="form-select" data-control="select2" data-hide-search="true" data-placeholder="Status" data-kt-ecommerce-product-filter="status"><option value="all">Semua Status</option><option value="temp">Draf</option><option value="draft">Siap Produksi</option><option value="posting">Selesai</option></select></label><label for="production-branch-filter">Cabang<select id="production-branch-filter" class="form-select" data-control="select2" data-hide-search="true" data-placeholder="Cabang" data-kt-ecommerce-product-filter="cabang">@if(count($branches) > 1)<option value="all">Semua Cabang</option>@endif @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>@endforeach</select></label><label for="kt_ecommerce_sales_flatpickr">Rentang tanggal<div class="input-group"><input class="form-control" placeholder="Pilih rentang tanggal" id="kt_ecommerce_sales_flatpickr"><button type="button" class="erp-action" id="kt_ecommerce_sales_flatpickr_clear" aria-label="Hapus rentang tanggal"><i class="ph ph-x" aria-hidden="true"></i></button></div></label></div></details><a href="{{ url('production/create') }}" class="erp-action erp-action--primary"><i class="ph ph-plus" aria-hidden="true"></i>Tambah Produksi</a></div>
        </x-slot:toolbar>
        <div class="production-filter" x-show="open" x-cloak role="dialog" aria-modal="true" aria-labelledby="production-filter-title">
            <button type="button" class="production-filter__backdrop" aria-label="Tutup filter" @click="close()"></button>
            <section class="production-filter__panel">
                <header class="production-filter__head">
                    <div><h2 id="production-filter-title">Filter Produksi</h2><p>Saring status, cabang, dan periode produksi</p></div>
                    <button type="button" class="production-filter__close" aria-label="Tutup filter" @click="close()"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                </header>
                <div class="production-filter__body">
                    <section>
                        <h3 class="production-filter__label">Status Produksi</h3>
                        <div class="production-filter__chips">
                            <template x-for="item in statuses" :key="item.value">
                                <button type="button" class="production-filter__chip" :class="draft.status === item.value ? 'is-selected' : ''" @click="draft.status = item.value" x-text="item.label"></button>
                            </template>
                        </div>
                    </section>
                    <section>
                        <h3 class="production-filter__label">Cabang</h3>
                        <div class="production-filter__branches">
                            <template x-for="branch in branches" :key="branch.value">
                                <label class="production-filter__branch"><input type="radio" name="production-draft-branch" :value="branch.value" x-model="draft.branch"><span x-text="branch.label"></span></label>
                            </template>
                        </div>
                    </section>
                    <section>
                        <h3 class="production-filter__label">Periode Tanggal</h3>
                        <div class="production-filter__dates">
                            <label>Dari<input type="date" x-model="draft.from"></label>
                            <label>Sampai<input type="date" x-model="draft.to"></label>
                        </div>
                    </section>
                </div>
                <footer class="production-filter__foot">
                    <button type="button" class="production-filter__reset" @click="reset()">Reset</button>
                    <button type="button" class="production-filter__apply" @click="apply()">Terapkan</button>
                </footer>
            </section>
        </div>
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
        </div>
    </x-erp.workspace>
@section('script')
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
                createdRow: function(row) {
                    const link = row.querySelector('a.production-copy');
                    if (link) {
                        row.classList.add('is-openable');
                        row.dataset.href = link.getAttribute('href');
                    }
                },
                pageLength: 100,
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

            // A tap on the row opens the existing detail page; row actions keep their own behaviour.
            $('#production-table tbody').on('click', 'tr.is-openable', function(event) {
                if ($(event.target).closest('a, button, .dropdown, .dropdown-menu').length) return;
                window.location.href = this.dataset.href;
            });

            // Search manual lewat input
            $('#search, #search-desktop').on('keyup', function() {
                $('#search, #search-desktop').not(this).val(this.value);
                dataTable.search(this.value).draw();
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

        // Mobile filter sheet: it only drives the existing status/cabang selects and date range, so the DataTable wiring is untouched.
        function productionFilterSheet() {
            const statusSelect = () => document.getElementById('production-status-filter');
            const branchSelect = () => document.getElementById('production-branch-filter');
            const picker = () => document.getElementById('kt_ecommerce_sales_flatpickr')?._flatpickr;
            const defaults = () => ({ status: 'all', branch: branchSelect()?.options[0]?.value ?? 'all', from: '', to: '' });
            return {
                open: false,
                statuses: [
                    { value: 'all', label: 'Semua' },
                    { value: 'temp', label: 'Draf' },
                    { value: 'draft', label: 'Siap Produksi' },
                    { value: 'posting', label: 'Selesai' },
                ],
                branches: [],
                applied: { status: 'all', branch: 'all', from: '', to: '' },
                draft: { status: 'all', branch: 'all', from: '', to: '' },
                init() {
                    this.branches = Array.from(branchSelect()?.options ?? []).map(option => ({ value: option.value, label: option.text.trim() }));
                    this.applied = defaults();
                    this.draft = { ...this.applied };
                },
                get hasActive() {
                    const base = defaults();
                    return Object.keys(base).some(key => this.applied[key] !== base[key]);
                },
                openSheet() { this.draft = { ...this.applied }; this.open = true; },
                close() { this.open = false; },
                reset() { this.draft = defaults(); },
                apply() {
                    const status = statusSelect(), branch = branchSelect();
                    if (status) window.jQuery(status).val(this.draft.status).trigger('change');
                    if (branch) window.jQuery(branch).val(this.draft.branch).trigger('change');
                    const range = picker();
                    if (range) {
                        if (this.draft.from) range.setDate([this.draft.from, this.draft.to || this.draft.from], true);
                        else range.clear();
                    }
                    this.applied = { ...this.draft };
                    this.open = false;
                },
            };
        }

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
