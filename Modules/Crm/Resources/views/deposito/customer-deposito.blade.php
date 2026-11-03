@extends('template.root')
@section('title', 'Deposito Pelanggan - CRM')
@section('page-title', 'Deposito Pelanggan')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Deposito Pelanggan" subtitle="Pantau saldo deposito dan voucher setiap pelanggan." icon="ph-users">
    <x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-kt-ecommerce-product-filter="search" placeholder="Cari deposito pelanggan…" aria-label="Cari deposito pelanggan"></div>
            </x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="table erp-crm-table" width="100%"><thead><tr><th scope="col">Pelanggan</th><th scope="col">Deposito</th><th scope="col">Total voucher</th><th scope="col">Detail</th></tr></thead><tbody></tbody></table>
    </x-erp.collection>
</x-erp.workspace>
@endsection
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
                    url: "{{ route('customer-deposito.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'deposito',
                        name: 'deposito'
                    },
                    {
                        data: 'voucher_qty',
                        name: 'voucher_qty'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
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
