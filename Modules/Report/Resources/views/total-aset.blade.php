@extends('template.root')

@section('content')
    {{-- @livewire('product-table') --}}
    <div>
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header align-items-center py-3 gap-2 flex-wrap flex-md-nowrap">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-200px w-md-250px ps-12" placeholder="Cari Buah" />
                    </div>
                    <!--end::Search-->
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar ms-auto">
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                            <!--begin::Filter-->
                            <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-filter fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i></button>
                            <!--begin::Menu 1-->
                            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                                <!--begin::Header-->
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
                                </div>
                                <!--end::Header-->
                                <!--begin::Separator-->
                                <div class="separator border-gray-200"></div>
                                <!--end::Separator-->
                                <!--begin::Content-->
                                <div class="px-7 py-5" data-kt-user-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-3">
                                        <label class="form-label fs-6 fw-semibold">Cabang:</label>
                                        <select class="form-select form-select-solid" id="branch-filter"
                                            data-control="select2" data-hide-search="true" data-placeholder="Pilih Cabang">
                                            <option value="all">Semua Cabang</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                                <!--end::Content-->
                                <!--begin::Separator-->
                                <div class="separator border-gray-200"></div>
                                <!--end::Separator-->
                                <!--begin::Content-->
                                <div class="px-7 py-5" data-kt-user-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="input-group mw-350px">
                                        <input class="form-control form-control-solid rounded rounded-end-0"
                                            placeholder="Pilih Range Tanggal" id="kt_ecommerce_sales_flatpickr" />
                                        <button class="btn btn-icon btn-light" id="kt_ecommerce_sales_flatpickr_clear">
                                            <i class="ki-duotone ki-cross fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </button>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::Menu 1-->
                            <!--end::Filter-->
                        </div>
                        <!--end::Toolbar-->
                    </div>
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <div class="col-12 text-center mb-8">
                    <span class="fs-6 fw-bold text-gray-700">Laporan Aset Berdasarkan Stok</span>
                    <br>
                    <span class="fs-6 fw-bold text-gray-700">Periode: <span id="date-range-label">Semua Waktu</span></span>
                </div>
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="transaction-table" width="100%">
                    <thead>
                        <tr>
                            <th colspan="4" class="text-end fs-5 fw-bold">
                                Grand Total Aset: <span id="grand-total-cell"></span>
                            </th>
                        </tr>
                        <tr class="text-start text-gray-500 fw-bold fs-7">
                            <th class="text-start min-w-150px">Produk</th>
                            <th class="text-start min-w-50px">Satuan</th>
                            <th class="text-start min-w-50px">Stock</th>
                            <th class="text-start min-w-50px">Hpp Satuan</th>
                            <th class="text-start min-w-50px">Total Aset</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>

                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
@section('script')
    <script type="text/javascript">
        var dataTable;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const segment1 = "{{ Request::segment(1) }}";

        $(document).ready(function() {
            let dataTable = $('#transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('report-total-aset.data') }}",
                    data: function(d) {
                        d.branch_id = $('#branch-filter').val();
                        let range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            let dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0];
                        }
                    },
                    dataSrc: function(json) {
                        // ✅ Update footer grand total setiap kali data diterima
                        if (json.grand_total) {
                            $('#grand-total-cell').html(json.grand_total);
                        } else {
                            $('#grand-total-cell').html('Rp. 0');
                        }

                        return json.data; // tetap kembalikan data ke datatables
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'abbreviation',
                        name: 'abbreviation'
                    },
                    {
                        data: 'total_stock',
                        name: 'total_stock'
                    },
                    {
                        data: 'hpp',
                        name: 'hpp'
                    },
                    {
                        data: 'total_hpp',
                        name: 'total_hpp'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [2, 'desc']
                ]
            });

            // ✅ reload datatable saat filter diubah
            $('#branch-filter, #kt_ecommerce_sales_flatpickr').on('change', function() {
                dataTable.ajax.reload();
            });

            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('#branch-filter').on('change', function() {
                updateDateRangeLabel(); // Update label
                dataTable.draw(); // Reload data
            });

            $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function(selectedDates, dateStr, instance) {
                    updateDateRangeLabel(); // Update label
                    dataTable.draw(); // Reload data
                }
            });

            $('#branch-filter, #kt_ecommerce_sales_flatpickr').on('change', function() {
                dataTable.ajax.reload(null, false);
            });

            function updateDateRangeLabel() {
                let branchText = "Semua Cabang";
                const branchVal = $('#branch-filter').val();
                if (branchVal) {
                    const selectedOption = $('#branch-filter option:selected').text().trim();
                    branchText = selectedOption;
                }

                let dateText = "Semua Waktu";
                const range = $('#kt_ecommerce_sales_flatpickr').val();
                if (range) {
                    const dates = range.split(' to ');
                    if (dates.length === 2) {
                        dateText = `${dates[0]} – ${dates[1]}`;
                    } else {
                        dateText = dates[0];
                    }
                }

                $('#date-range-label').text(`${branchText}, ${dateText}`);
            }

            // Inisialisasi awal label
            updateDateRangeLabel();

        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }
        $("#date").flatpickr({
            altInput: !0,
            altFormat: "d F, Y",
            dateFormat: "Y-m-d"
        });
    </script>
@endsection

@endsection
