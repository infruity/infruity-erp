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
                            class="form-control form-control-solid w-100 ps-12" placeholder="Cari Produk" />
                    </div>
                </div>
                <div class="card-toolbar w-100 w-md-auto ms-md-auto">
                    <div class="d-flex align-items-center justify-content-md-end pos-index-filter-toolbar"
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
                                    <label class="form-label fs-6 fw-semibold">Cabang:</label>
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Cabang"
                                        data-kt-ecommerce-product-filter="branch">
                                        @foreach ($branch as $item)
                                            <option value="{{ $item->id }}">{{ ucwords($item->name) }}</option>
                                        @endforeach
                                        <option value="all">Semua</option>
                                    </select>
                                </div>
                            </div>
                            <div class="px-7 py-2" data-kt-user-table-filter="form">
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Kategori:</label>
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Kategori"
                                        data-kt-ecommerce-product-filter="kategori">
                                        <option value="all">Semua</option>
                                        @foreach ($category as $category)
                                            <option value="{{ $category->id }}">{{ ucwords($category->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="px-7 py-5" data-kt-user-table-filter="form">
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Stok:</label>
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Stok"
                                        data-kt-ecommerce-product-filter="stock">
                                        <option value="all">Semua</option>
                                        <option value="ada">Ada</option>
                                        <option value="kosong">Kosong</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--begin::Card body-->
            <div class="card-body pt-0">

                <div id="keterangan" class="text-center">
                    <p></p>
                </div>
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="products-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            {{-- <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true"
                                        data-kt-check-target="#kt_ecommerce_products_table .form-check-input"
                                        value="1" />
                                </div>
                            </th> --}}
                            <th class="min-w-200px" data-data="name">Product</th>
                            <th class="text-end min-w-70px" data-data="hpp">Hpp</th>
                            <th class="text-end min-w-70px" data-data="stock_available">Stock</th>
                            <th class="d-none" data-data="category">Category</th>
                            <th class="min-w-75px"></th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
    <script type="text/javascript">
        var dataTable;
        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="branch"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            dataTable = $('#products-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                ordering: true,
                orderCellsTop: true, // Header sorting di baris pertama
                scrollX: true, // Aktifkan scroll horizontal
                fixedColumns: {
                    leftColumns: 0, // Tidak ada kolom di sisi kiri yang dibekukan
                    rightColumns: 1 // Membekukan 1 kolom di sisi kanan (kolom action)
                },
                columnDefs: [
                    {
                        orderable: true,
                        targets: [0, 1, 2] // Aktifkan sorting untuk kolom Product, Hpp, Stock
                    },
                    {
                        orderable: false,
                        targets: -1 // Nonaktifkan sorting untuk kolom action
                    },
                    {
                        searchable: false,
                        targets: -1 // Nonaktifkan search untuk kolom action
                    }
                ],
                // responsive: true,
                ajax: {
                    url: "{{ route('product-stock-data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.branch = $('[data-kt-ecommerce-product-filter="branch"]').val();
                        d.stock_kategori = $('[data-kt-ecommerce-product-filter="kategori"]').val();
                        d.stock_filter = $('[data-kt-ecommerce-product-filter="stock"]').val();
                    }
                },
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     name: 'DT_RowIndex'
                    // },
                    {
                        data: 'name',
                        name: 'name',
                        orderable: true
                    },
                    {
                        data: 'hpp',
                        name: 'hpp',
                        className: 'text-end',
                        orderable: true
                    },
                    {
                        data: 'stock_available',
                        name: 'stock_available',
                        className: 'text-end',
                        orderable: true
                    },
                    {
                        data: 'category',
                        name: 'category',
                        visible: false,
                        orderable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    },
                ],
                order: [
                    [2, 'desc']
                ] // Urutkan berdasarkan kolom Stock (index 2) descending
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="branch"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
                updateKeterangan();
            });

            $('[data-kt-ecommerce-product-filter="stock"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
            });

            $('[data-kt-ecommerce-product-filter="kategori"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
            });

            updateActiveFilterInfo();
        });

        function updateKeterangan() {
            const branchText = $('[data-kt-ecommerce-product-filter="branch"] option:selected').text();
            $('#keterangan p').html('Cabang: <span class="fw-bold">' + branchText + '</span>');
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
                        url: `/products/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
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

        // Handle click untuk ubah span jadi input
        $('#products-table').on('click', '.editable-price', function() {
            var $span = $(this);
            var currentValue = $span.data('value');
            var id = $span.data('id');

            var input = $('<input type="number" class="form-control form-control-sm text-end">')
                .val(currentValue)
                .blur(function() {
                    var newValue = $(this).val();
                    if (newValue != currentValue) {
                        updatePrice(id, newValue);
                    } else {
                        $span.text(currentValue).show();
                        $(this).remove();
                    }
                })
                .keypress(function(e) {
                    if (e.which === 13) { // Enter key
                        $(this).blur();
                    }
                });

            $span.hide().after(input);
            input.focus().select();
        });

        function updatePrice(id, price) {
            $.ajax({
                url: `/products/${id}/update-price`, // sesuaikan route
                type: 'PUT',
                data: {
                    price: price,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    dataTable.ajax.reload(null, false); // reload tanpa reset halaman
                },
                error: function() {
                    Swal.fire('Gagal', 'Tidak bisa memperbarui harga', 'error');
                    dataTable.ajax.reload(null, false);
                }
            });
        }
    </script>
@endsection
