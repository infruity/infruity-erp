@extends('template.root')

@section('content')
    <div class="pos-index-page">
        <style>
            .pos-index-page .pos-index-search {
                width: 100%;
                max-width: 320px;
            }

            .pos-index-page .pos-index-search .form-control {
                height: 44px;
                border-radius: 12px;
            }

            .pos-index-page .pos-index-filter-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                min-height: 44px;
                border-radius: 12px;
                white-space: nowrap;
            }

            .pos-index-page #active-branch-button-label {
                display: inline-block;
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            @media (max-width: 767.98px) {
                .pos-index-page .pos-index-search {
                    max-width: 100%;
                }

                .pos-index-page .pos-index-filter-toolbar {
                    width: 100%;
                    justify-content: flex-start !important;
                }

                .pos-index-page .pos-index-filter-btn {
                    width: 100%;
                }
            }
        </style>
        <div class="card card-flush">
            <div class="card-header align-items-stretch py-3 gap-3 flex-column flex-md-row">
                <div class="card-title flex-grow-1 w-100 mb-0">
                    <div class="d-flex align-items-center position-relative my-1 pos-index-search">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-100 ps-12" placeholder="Cari Pengadaan" />
                    </div>
                </div>
                <div class="card-toolbar w-100 w-md-auto ms-md-auto">
                    <div class="d-flex align-items-center justify-content-md-end pos-index-filter-toolbar gap-3"
                        data-kt-user-table-toolbar="base">
                        <button type="button" class="btn btn-light-primary px-4 pos-index-filter-btn"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span id="active-branch-button-label">Cabang</span>
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-2" data-kt-user-table-filter="form">
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Status:</label>
                                    @php
                                        $category = [
                                            'draft' => 'Draft',
                                            'posting' => 'Posting',
                                        ];
                                    @endphp
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Status"
                                        data-kt-ecommerce-product-filter="status">
                                        <option value="all">All</option>
                                        @foreach ($category as $key => $value)
                                            <option value="{{ $key }}">{{ ucwords($value) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="px-7 py-2" data-kt-user-table-filter="form">
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Cabang:</label>
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Cabang"
                                        data-kt-ecommerce-product-filter="cabang">
                                        @if (count($branches) > 1)
                                            <option value="all">All</option>
                                        @endif
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="px-7 py-5" data-kt-user-table-filter="form">
                                <div class="input-group mw-350px">
                                    <input class="form-control form-control-solid rounded rounded-end-0"
                                        placeholder="Pick date range" id="kt_ecommerce_sales_flatpickr" />
                                    <button class="btn btn-icon btn-light" id="kt_ecommerce_sales_flatpickr_clear">
                                        <i class="ki-duotone ki-cross fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="wholesale-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            {{-- <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true"
                                        data-kt-check-target="#kt_ecommerce_wholesale_table .form-check-input"
                                        value="1" />
                                </div>
                            </th> --}}
                            <th class="text-start min-w-100px">Name</th>
                            <th class="text-center min-w-100px">Status</th>
                            <th class="text-center min-w-100px">Order Date</th>
                            <th class="text-end min-w-70px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
    <a href="{{ url('wholesale/create') }}" class="btn btn-primary rounded-circle shadow-lg position-fixed"
        style="bottom: 60px; right: 30px; width: 60px; height: 60px; z-index: 1050; display: flex; align-items: center; justify-content: center;">
        <i class="ki-duotone ki-purchase fs-3x text-white">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
    <button type="button" onclick="resetWholesale()" class="btn btn-danger rounded-circle shadow-lg position-fixed"
        title="Reset Transaksi Wholesale"
        style="bottom: 60px; right: 100px; width: 60px; height: 60px; z-index: 1050; display: flex; align-items: center; justify-content: center;">
        <i class="ki-duotone ki-trash-square fs-3x text-white">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </button>
@section('script')
    <script type="text/javascript">
        // Data stock kosong dari server
        const emptyStockData = @json($emptyStockData ?? []);

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
                    confirmButtonColor: '#f1416c',
                    allowOutsideClick: false
                });
            }
        }

        var dataTable;
        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            dataTable = $('#wholesale-table').DataTable({
                processing: true,
                serverSide: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                        orderable: false,
                        targets: -1 // Disable sorting for action column
                    },
                    {
                        targets: [4], // Kolom ke-5 (status_raw)
                        visible: false,
                        searchable: false
                    },
                    {
                        targets: [5], // Kolom ke-5 (status_raw)
                        visible: false,
                        searchable: false
                    }
                ],
                ajax: {
                    url: "{{ route('wholesale-data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = $('[data-kt-ecommerce-product-filter="status"]').val();
                        d.cabang_filter = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0]; // jika hanya pilih 1 tanggal
                        }
                    }
                },
                order: [
                    // [1, 'asc'], // Sort by status_raw ASC
                    // [2, 'desc'], // Then by order_date ASC (kolom ke-3)
                    [0, 'desc']
                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (type === 'filter' || type === 'sort') {
                                return row.status_raw;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'order_date',
                        name: 'order_date',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'status_raw', // hidden column used only for sorting
                        name: 'status_raw'
                    },
                    {
                        data: 'wholesale_id',
                        name: 'wholesale_id'
                    }
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
                showStockAlert($(this).val());
            });

            updateActiveFilterInfo();

            $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function(e, t, n) {
                    dataTable.draw();
                }
            });

            showStockAlert($('[data-kt-ecommerce-product-filter="cabang"]').val());
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
                        url: `/wholesale/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                showConfirmButton: false,
                                timer: 1500,
                                text: response.message || 'Data berhasil dihapus.'
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

        function resetWholesale() {
            Swal.fire({
                title: 'Reset transaksi?',
                text: 'Semua transaksi yang digunakan untuk ujicoba akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, reset',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('wholesale.reset') }}",
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message ||
                                    'Transaksi wholesale berhasil direset.',
                                showConfirmButton: false,
                                timer: 1500,
                            });
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat mereset transaksi wholesale.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
