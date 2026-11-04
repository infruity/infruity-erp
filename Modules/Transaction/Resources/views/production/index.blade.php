@extends('template.root')
@section('title', 'Produksi')
@section('content')
    <link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
    <x-erp.workspace title="Produksi" subtitle="Pantau produksi, bahan baku dan status pengerjaan." icon="cooking-pot">
        <x-slot:toolbar>
            <label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" data-kt-ecommerce-product-filter="search" id="search" aria-label="Cari produksi" placeholder="Cari produksi..."></label>
            <div class="erp-operations-toolbar-actions"><details class="erp-operations-filter"><summary class="erp-action"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></summary><div class="erp-operations-filter__body"><label for="production-status-filter">Status<select id="production-status-filter" class="form-select" data-control="select2" data-hide-search="true" data-placeholder="Status" data-kt-ecommerce-product-filter="status"><option value="all">Semua Status</option>@foreach (['temp', 'draft', 'posting', 'complete'] as $statusOption)<option value="{{ $statusOption }}">{{ ucwords($statusOption) }}</option>@endforeach</select></label><label for="production-branch-filter">Cabang<select id="production-branch-filter" class="form-select" data-control="select2" data-hide-search="true" data-placeholder="Cabang" data-kt-ecommerce-product-filter="cabang">@if(count($branches) > 1)<option value="all">Semua Cabang</option>@endif @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>@endforeach</select></label><label for="kt_ecommerce_sales_flatpickr">Rentang tanggal<div class="input-group"><input class="form-control" placeholder="Pilih rentang tanggal" id="kt_ecommerce_sales_flatpickr"><button type="button" class="erp-action" id="kt_ecommerce_sales_flatpickr_clear" aria-label="Hapus rentang tanggal"><i class="ph ph-x" aria-hidden="true"></i></button></div></label></div></details><a href="{{ url('production/create') }}" class="erp-action erp-action--primary"><i class="ph ph-plus" aria-hidden="true"></i>Tambah Produksi</a></div>
        </x-slot:toolbar>
        <div class="erp-operations-collection" data-erp-collection="production-table" data-erp-empty="Belum ada produksi barang" data-erp-empty-description="Data yang tersimpan akan muncul di sini.">
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

            dataTable = $('#production-table').DataTable({
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

            // Search manual lewat input
            $('#search').on('keyup', function() {
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
