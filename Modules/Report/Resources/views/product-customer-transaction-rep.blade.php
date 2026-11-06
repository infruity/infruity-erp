@extends('layouts.erp')
@section('title', 'Produk per Pelanggan')
@section('page-title', 'Produk per Pelanggan')

@section('content')
<x-erp.workspace title="Produk per Pelanggan" subtitle="Rincian produk dan kuantitas transaksi pelanggan." icon="ph-chart-bar">
    <x-slot:toolbar>
        <label class="erp-search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input id="search" type="search" placeholder="Cari pelanggan atau produk…" aria-label="Cari pelanggan atau produk" autocomplete="off">
        </label>
        <x-erp.button icon="arrow-clockwise" onclick="reloadDataTable()">Muat ulang</x-erp.button>
    </x-slot:toolbar>
    <div class="erp-report-collection">
                <table class="erp-table erp-report-table" data-erp-collection="transaction-table" id="transaction-table" width="100%">
                    <thead>
                        <tr>
                            <th class="text-start min-w-[150px]">Tanggal dan Waktu</th>
                            <th class="text-start min-w-[150px]">Nama Pelanggan</th>
                            <th class="text-start min-w-[50px]">Jenis Kelamin</th>
                            <th class="text-start min-w-[50px]">Usia</th>
                            <th class="text-start min-w-[150px]">Kota</th>
                            <th class="text-start min-w-[150px]">Kecamatan</th>
                            <th class="text-start min-w-[150px]">Kelurahan</th>
                            <th class="text-start min-w-[150px]">Cabang</th>
                            <th class="text-start min-w-[150px]">Produk</th>
                            <th class="text-start min-w-[50px]">Kuantitas</th>
                            <th class="text-start min-w-[50px]">Unit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
    </div>
    <x-slot:footer>
        <p class="text-xs text-gray-400">Pilih urutan data atau buka baris untuk melihat rincian.</p>
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
                    url: "{{ route('report-customer-product.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'gender',
                        name: 'gender'
                    },
                    {
                        data: 'age',
                        name: 'age'
                    },
                    {
                        data: 'city_name',
                        name: 'city_name'
                    },
                    {
                        data: 'district_name',
                        name: 'district_name'
                    },
                    {
                        data: 'village_name',
                        name: 'village_name'
                    },
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
                        data: 'abbreviation',
                        name: 'abbreviation'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [0, 'desc']
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
