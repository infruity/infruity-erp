@extends('template.root')

@section('content')
    {{-- @livewire('product-table') --}}
    <div>
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header align-items-stretch py-3 gap-3 flex-column flex-md-row">
                <!--begin::Card title-->
                <div class="card-title align-items-start flex-column">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-200px ps-12" placeholder="Search" />
                    </div>
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar flex-wrap gap-3 justify-content-end">
                    <div class="w-100 w-md-auto">
                        <select class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true" data-placeholder="Cabang"
                            data-kt-ecommerce-product-filter="branch">
                            @foreach ($branch as $item)
                                <option value="{{ $item->id }}">{{ ucwords($item->name) }}</option>
                            @endforeach
                            <option value="all">Semua</option>
                        </select>
                    </div>
                    <div class="w-100 w-md-auto">
                        <select class="form-select form-select-solid" data-control="select2"
                            data-hide-search="true" data-placeholder="Reference"
                            data-kt-ecommerce-product-filter="reference">
                            <option value="all">Semua Reference</option>
                            <option value="stock-opname">Stock Opname</option>
                            <option value="sortir">Sortir</option>
                            <option value="pos">POS</option>
                            <option value="production">Produksi</option>
                            <option value="wholesale">Pengadaan</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="w-100 w-md-auto">
                        <div class="input-group">
                            <input class="form-control form-control-solid" placeholder="Pilih tanggal"
                                id="kt_ecommerce_sales_flatpickr" />
                            <button class="btn btn-icon btn-light" id="kt_ecommerce_sales_flatpickr_clear">
                                <i class="ki-duotone ki-cross fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </button>
                        </div>
                    </div>
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="products-stock-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-100px">Product</th>
                            <th class="text-end min-w-70px">Quantity</th>
                            <th class="text-end min-w-70px">Stock Awal</th>
                            <th class="text-end min-w-70px">Stock Akhir</th>
                            <th class="text-end min-w-100px">Tanggal</th>
                            <th class="text-end min-w-100px">Reference</th>
                            <th class="text-end min-w-100px">Created By</th>
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
            dataTable = $('#products-stock-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('product-stock-data-show') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.stock_filter = $('[data-kt-ecommerce-product-filter="stock"]').val();
                        d.product_id = {{ $data->id }};
                        d.branch = $('[data-kt-ecommerce-product-filter="branch"]').val();
                        d.reference = $('[data-kt-ecommerce-product-filter="reference"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0];
                        }
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'text-end'
                    },
                    {
                        data: 'stock_awal',
                        name: 'stock_awal',
                        className: 'text-end'
                    },
                    {
                        data: 'stock_akhir',
                        name: 'stock_akhir',
                        className: 'text-end'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-end'
                    },
                    {
                        data: 'reff',
                        name: 'reff',
                        className: 'text-end'
                    },
                    {
                        data: 'created_by',
                        name: 'created_by',
                        className: 'text-end'
                    },

                ],
                order: [
                    [4, 'desc']
                ]
            });

            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                let val = $(this).val();
                if (val === 'all') val = '';
                dataTable.column(5).search(val).draw();
            });

            $('[data-kt-ecommerce-product-filter="stock"]').on('change', function() {
                dataTable.draw();
            });

            $('[data-kt-ecommerce-product-filter="branch"]').on('change', function() {
                dataTable.draw();
            });

            $('[data-kt-ecommerce-product-filter="reference"]').on('change', function() {
                dataTable.draw();
            });

            $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function(e, t, n) {
                    dataTable.draw();
                }
            });

            $('#kt_ecommerce_sales_flatpickr_clear').on('click', function() {
                $('#kt_ecommerce_sales_flatpickr').val('');
                dataTable.draw();
            });
        });

        function reloadDataTable() {
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false);
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }
    </script>
@endsection
