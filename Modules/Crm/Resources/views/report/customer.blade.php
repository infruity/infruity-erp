@extends('template.root')

@section('content')
    <style>
        .badge-select {
            border: none;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            width: auto;
            display: inline-block;
            padding: 0.25rem 0.5rem;
            appearance: none;
            background-image: none;
            box-shadow: none;
            color: white;
        }

        .badge-select.bg-warning {
            color: #212529;
            /* agar teks terlihat pada bg-warning */
        }

        .badge-select option {
            color: black;
        }
    </style>


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
                            class="form-control form-control-solid w-250px ps-12" placeholder="Search" />
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
                            <th class="text-start min-w-100px">Status</th>
                            <th class="text-start min-w-100px">Recency</th>
                            <th class="text-start min-w-100px">Frequency</th>
                            <th class="text-start min-w-100px">Monetary</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
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
                    url: "{{ route('customer.report.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [{
                        data: 'nama_customer',
                        name: 'nama_customer'
                    },
                    {
                        data: 'type_customer',
                        name: 'type_customer'
                    },
                    {
                        data: 'recency',
                        name: 'recency'
                    },
                    {
                        data: 'jumlah_transaksi',
                        name: 'jumlah_transaksi'
                    },
                    {
                        data: 'total_transaksi',
                        name: 'total_transaksi'
                    },
                ],
                order: [
                    [0, 'asc']
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function changeTypeCustomer(id, newType) {
            // Kirim AJAX ke server untuk update
            fetch('/update-type-customer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: id,
                        type: newType
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        $('#transaction-table').DataTable().ajax.reload(null, false); // Reload baris datanya
                    } else {
                        alert('Gagal update type customer.');
                    }
                });
        }
    </script>
@endsection
@endsection
