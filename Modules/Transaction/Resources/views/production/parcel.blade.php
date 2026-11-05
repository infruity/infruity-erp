@extends('layouts.erp')
@section('title', 'Produksi Parcel')
@section('page-title', 'Produksi Parcel')
@section('content')
    <link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
    <x-erp.workspace title="Produksi Parcel" subtitle="Pantau proses perakitan parcel." icon="gift">
        <x-slot:toolbar>
            <label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" data-erp-filter="search" id="search" aria-label="Cari parcel" placeholder="Cari parcel..."></label>
            <div class="erp-operations-toolbar-actions"><label class="erp-operations-inline-select"><span class="sr-only">Status parcel</span><select class="erp-select" data-erp-filter="status"><option></option><option value="all">Semua Status</option><option value="processing">Diproses</option><option value="complete">Selesai</option></select></label><a href="{{ url('parcel/create') }}" class="erp-action erp-action--primary"><i class="ph ph-plus" aria-hidden="true"></i>Tambah Parcel</a></div>
        </x-slot:toolbar>
        <div class="erp-operations-collection" data-erp-collection="parcel-table" data-erp-empty="Belum ada produksi parcel" data-erp-empty-description="Data yang tersimpan akan muncul di sini.">
            <table class="erp-table erp-operations-table" id="parcel-table" width="100%">
                    <thead>
                        <tr class="text-start">

                            <th class="text-start min-w-[100px]">Nama</th>
                            <th class="text-center min-w-[100px]">Status</th>
                            <th class="text-center min-w-[100px]">Tanggal Produksi</th>
                            <th class="text-end min-w-[50px]"></th>
                        </tr>
                    </thead>
                    <tbody class=""></tbody>
                </table>
        </div>
    </x-erp.workspace>
@section('script')
    <script type="text/javascript">
        var dataTable;
        $(document).ready(function() {
            dataTable = $('#parcel-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                        orderable: false,
                        targets: -1 // Disable sorting for action column
                    },
                    {
                        targets: [4], // Kolom ke-5 (status_raw)
                        visible: false,
                        searchable: false
                    },
                    {
                        targets: [5], // Kolom ke-5 (status_raw)
                        visible: false,
                        searchable: false
                    }
                ],
                ajax: {
                    url: "{{ route('parcel-data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                order: [
                    [1, 'asc'], // Sort by status_raw ASC
                    [2, 'desc'], // Then by order_date ASC (kolom ke-3)
                    [0, 'desc']
                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (type === 'filter' || type === 'sort') {
                                return row.status_raw;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'production_date',
                        name: 'production_date',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                    },
                    {
                        data: 'status_raw', // hidden column used only for sorting
                        name: 'status_raw'
                    },
                    {
                        data: 'production_id',
                        name: 'production_id'
                    }
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-erp-filter="status"]').on('change', function() {
                let val = $(this).val();

                if (val === 'all') val = ''; // kosongkan filter jika all
                dataTable.column(1).search(val).draw();
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

        function deleteProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'erp-action erp-action--danger',
                    cancelButton: 'erp-action ml-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/parcel/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.',
                                showConfirmButton: false,
                                timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
                            });

                            // Reload DataTable setelah berhasil menghapus data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        }

    </script>
@endsection
@endsection
