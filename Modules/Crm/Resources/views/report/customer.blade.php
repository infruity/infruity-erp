@extends('layouts.erp')
@section('title', 'Analisis Pelanggan')

@section('content')<x-erp.workspace title="Analisis Pelanggan" subtitle="Recency, frekuensi dan nilai belanja pelanggan."><x-slot:toolbar><label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input id="search" type="search" placeholder="Cari pelanggan…" aria-label="Cari pelanggan"></label></x-slot:toolbar><x-erp.collection table="transaction-table"><table class="erp-table" id="transaction-table" width="100%">
                    <thead>
                        <tr>
                            <th class="min-w-[100px]">Name</th>
                            <th class="min-w-[100px]">Status</th>
                            <th class="min-w-[100px]">Recency</th>
                            <th class="min-w-[100px]">Frequency</th>
                            <th class="min-w-[100px]">Monetary</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table></x-erp.collection></x-erp.workspace>@section('script')
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
