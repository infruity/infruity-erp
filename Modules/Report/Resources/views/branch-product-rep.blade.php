@extends('template.root')
@section('title', 'Produk per Cabang')
@section('page-title', 'Produk per Cabang')

@section('content')
<x-erp.workspace title="Produk per Cabang" subtitle="Jumlah produk yang terjual pada setiap cabang." icon="ph-chart-bar">
    <x-slot:toolbar>
        <label class="erp-search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input id="search" type="search" placeholder="Cari cabang atau produk…" aria-label="Cari cabang atau produk" autocomplete="off">
        </label>
        <button type="button" class="erp-action" onclick="reloadDataTable()"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i>Muat ulang</button>
    </x-slot:toolbar>
    <div class="erp-report-collection">
                <table class="table align-middle erp-report-table" data-erp-collection="transaction-table" id="transaction-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7">
                            <th class="text-start min-w-150px">Cabang</th>
                            <th class="text-start min-w-50px">Produk</th>
                            <th class="text-start min-w-50px">Kuantitas</th>
                            <th class="text-start min-w-50px">Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
    </div>
    <x-slot:footer>
        <p class="text-xs text-gray-500">Pilih urutan data atau buka baris untuk melihat rincian.</p>
    </x-slot:footer>
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
                language: {
                    processing: 'Memuat laporan…', emptyTable: 'Belum ada data laporan',
                    zeroRecords: 'Tidak ada hasil yang sesuai', lengthMenu: 'Tampilkan _MENU_',
                    info: '_START_–_END_ dari _TOTAL_ data', infoEmpty: 'Belum ada data',
                    infoFiltered: '(dari _MAX_ data)', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
                },
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
                    url: "{{ route('report-branch-product.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [
                    {
                        data: 'branch',
                        name: 'branch'
                    },
                    {
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'satuan',
                        name: 'satuan'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [4, 'desc']
                ]
            });
            // Search manual lewat input
            $('#search').on('input', function() {
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
        $("#date").flatpickr({
            altInput: !0,
            altFormat: "d F, Y",
            dateFormat: "Y-m-d"
        });

    </script>
@endsection
