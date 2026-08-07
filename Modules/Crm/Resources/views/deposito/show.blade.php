@extends('template.root')

@section('content')
    {{-- @livewire('product-table') --}}
    <div>
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-250px ps-12" placeholder="Search Transaction" />
                    </div>
                    <!--end::Search-->
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="transaction-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            {{-- <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true"
                                        data-kt-check-target="#kt_ecommerce_sortir_table .form-check-input"
                                        value="1" />
                                </div>
                            </th> --}}
                            <th class="text-start min-w-100px">Name</th>
                            <th class="text-start min-w-100px">Date</th>
                            <th class="text-start min-w-100px">Nominal</th>
                            <th class="text-start min-w-100px">Voucher</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align:right">Total:</th>
                            <th><span id="total_remaining" class="badge"></span></th>
                            <th><span id="total_voucher" class="badge"></span></th>
                        </tr>
                    </tfoot>
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
            dataTable = $('#transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true, // Aktifkan scroll horizontal
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Disable sorting for action column
                }, ],
                ajax: {
                    url: "{{ route('customer-deposito.transaction.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.customer_id = "{{ $customer->id ?? '' }}";
                    },
                    dataSrc: function(json) {
                        const totalVoucher = Number(json.total_voucher || 0);
                        const totalRemaining = Number(json.total_remaining || 0);

                        const setBadge = ($el, val) => {
                            const isPositive = val > 0;

                            $el
                                .text(val.toLocaleString('id-ID')) // pakai text, bukan html
                                .removeClass(
                                    // buang semua kemungkinan class lama biar nggak nempel
                                    'badge badge-light-success badge-light-danger ' +
                                    'bg-success bg-danger text-bg-success text-bg-danger ' +
                                    'text-success text-danger'
                                )
                                // ==== pilih salah satu style sesuai framework CSS kamu ====
                                // Jika pakai Bootstrap 5 (default):
                                .addClass(isPositive ? 'badge badge-light-success' : 'badge badge-light-danger');

                            // Jika kamu memang pakai tema dengan *-light-*:
                            // .addClass(isPositive ? 'badge badge-light-success' : 'badge badge-light-danger');
                        };

                        const $voucher = $('#total_voucher');
                        const $remaining = $('#total_remaining');

                        if ($voucher.length) setBadge($voucher, totalVoucher);
                        if ($remaining.length) setBadge($remaining, totalRemaining);

                        return json.data;
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'nominal',
                        name: 'nominal'
                    },
                    {
                        data: 'voucher_qty',
                        name: 'voucher_qty'
                    },
                ],
                order: [
                    [1, 'asc']
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });
        });
    </script>
@endsection
@endsection
