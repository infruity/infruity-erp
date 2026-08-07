@extends('template.root')

@section('content')
    <div>
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <!--begin::Card title-->
                <div class="card-title">
                    <h3 class="fw-bold">Riwayat Transaksi HPP - {{ $product->name ?? 'Product' }}</h3>
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                    <a href="{{ url('product-stock') }}" class="btn btn-light-primary btn-sm">
                        <i class="ki-outline ki-arrow-left fs-4"></i> Kembali
                    </a>
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table table-bordered align-middle fs-6 gy-5 nowrap" id="product-transaction-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-nowrap min-w-70px">Type</th>
                            <th class="text-nowrap min-w-150px">Remarks</th>
                            <th class="text-nowrap text-center min-w-70px">Qty</th>
                            <th class="text-nowrap text-center min-w-120px">Harga Beli</th>
                            <th class="text-nowrap text-center min-w-120px">Total Belanja</th>
                            <th class="text-nowrap text-center min-w-100px">Covered Qty</th>
                            <th class="text-nowrap text-center min-w-120px">COGS</th>
                            <th class="text-nowrap text-center min-w-120px">Recovered COGS</th>
                            <th class="text-nowrap text-center min-w-120px">Saldo Qty</th>
                            <th class="text-nowrap text-center min-w-120px">Pending Qty</th>
                            <th class="text-nowrap text-center min-w-120px">Total Aset</th>
                            <th class="text-nowrap text-center min-w-120px">HPP Berjalan</th>
                            <th class="text-nowrap text-center min-w-120px">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Check Balance Tables-->
                <div class="row" id="check-balance">
                    <!-- Left Table -->
                    <div class="col-md-6">
                        <table class="table table-bordered align-middle fs-6">
                            <thead>
                                <tr class="bg-success text-white">
                                    <th class="fw-bold">Description</th>
                                    <th class="fw-bold text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold">
                                <tr class="bg-light-success">
                                    <td class="fw-bold">Grand Total</td>
                                    <td class="text-end">Rp {{ toNumber($report->total_belanja) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Belanja</td>
                                    <td class="text-end">Rp {{ toNumber($report->total_belanja) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Right Table -->
                    <div class="col-md-6">
                        <table class="table table-bordered align-middle fs-6">
                            <thead>
                                <tr class="bg-success text-white">
                                    <th class="fw-bold">Description</th>
                                    <th class="fw-bold text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold">
                                <tr class="bg-light-success">
                                    <td class="fw-bold">Grand Total</td>
                                    <td class="text-end">Rp {{ tonumberround($report->total_cogs + $report->total_recovered_cogs + $report->last_asset, 0) }}</td>
                                </tr>
                                <tr>
                                    <td>COGS/Var (recognized)</td>
                                    <td class="text-end">Rp {{ tonumberround($report->total_cogs, 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Recovered COGS</td>
                                    <td class="text-end">Rp {{ tonumberround($report->total_recovered_cogs, 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Lost Asset</td>
                                    <td class="text-end">Rp {{ tonumberround($report->last_asset, 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Opname (+/-)</td>
                                    <td class="text-end">Rp -</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!--end::Check Balance Tables-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
    <script type="text/javascript">
        var dataTable;

        function formatNumber(data) {
            if (data === null || data === undefined || data === '') return '-';
            var num = parseFloat(data);
            if (isNaN(num)) return data;
            return num.toLocaleString('id-ID');
        }

        $(document).ready(function() {
            dataTable = $('#product-transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('product-transaction-data') }}",
                    data: function(d) {
                        d.product_id = {{ $product->id }}
                    }
                },
                columns: [
                    {
                        data: 'type',
                        name: 'type',
                        className: 'text-center text-nowrap'
                    },
                    {
                        data: 'remarks',
                        name: 'remarks',
                        className: 'text-nowrap',
                        render: function(data, type, row) {
                            if (row.url) {
                                return '<a href="' + row.url + '" class="text-primary fw-bold text-decoration-underline">' + data + '</a>';
                            }
                            return data;
                        }
                    },
                    {
                        data: 'qty',
                        name: 'qty',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'harga_satuan',
                        name: 'harga_satuan',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'total_belanja',
                        name: 'total_belanja',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'covered_qty',
                        name: 'covered_qty',
                        className: 'text-end text-nowrap',
                    },
                    {
                        data: 'cogs',
                        name: 'cogs',
                        className: 'text-end text-nowrap',
                    },
                    {
                        data: 'recovered_cogs',
                        name: 'recovered_cogs',
                        className: 'text-end text-nowrap',
                    },
                    {
                        data: 'qty_berjalan',
                        name: 'qty_berjalan',
                        className: 'text-end text-nowrap',
                    },

                    {
                        data: 'qty_berjalan',
                        name: 'qty_berjalan',
                        className: 'text-end text-nowrap',
                        render: function(data) {
                            if (data < 0) {
                                return Math.abs(data).toLocaleString('id-ID');
                            }
                            return '0';
                        }
                    },

                    {
                        data: 'total_aset_berjalan',
                        name: 'total_aset_berjalan',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'hpp_berjalan',
                        name: 'hpp_berjalan',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        className: 'text-end text-nowrap'
                    },
                ],
                order: [
                    [10, 'asc']
                ]
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
