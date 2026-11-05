@extends('layouts.erp')
@section('title', 'Riwayat Deposito - CRM')
@section('page-title', 'Riwayat Deposito')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Riwayat Deposito" subtitle="Riwayat transaksi deposito dan penggunaan voucher pelanggan." icon="ph-clock-counter-clockwise">
    <x-slot:toolbar><label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" placeholder="Cari riwayat deposito…" aria-label="Cari riwayat deposito"></label>
            </x-slot:toolbar>
    <x-erp.collection table="transaction-table">
        <table id="transaction-table" class="erp-table erp-crm-table" width="100%"><thead><tr><th scope="col">Pelanggan</th><th scope="col">Tanggal</th><th scope="col">Nominal</th><th scope="col">Voucher</th></tr></thead><tbody></tbody><tfoot><tr><th colspan="2">Total</th><th><span id="total_remaining" class="badge"></span></th><th><span id="total_voucher" class="badge"></span></th></tr></tfoot></table>
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
                                    'erp-badge erp-badge--success erp-badge--danger ' +
                                    'bg-success bg-danger text-bg-success text-bg-danger ' +
                                    'text-success text-danger'
                                )
                                // ==== pilih salah satu style sesuai framework CSS kamu ====
                                // Jika pakai Bootstrap 5 (default):
                                .addClass(isPositive ? 'erp-badge erp-badge--success' : 'erp-badge erp-badge--danger');
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
