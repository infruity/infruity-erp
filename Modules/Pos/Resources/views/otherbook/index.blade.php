@extends('layouts.erp')
@section('title', 'Pemasukan')

@section('page-title', 'Pemasukan')
@section('page-breadcrumb')
    <span>Keuangan</span><i class="ph ph-caret-right text-[0.55rem]" aria-hidden="true"></i><span>Pemasukan</span>
@endsection
@push('styles')
    @vite('resources/css/flatpickr.css')
@endpush
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
<x-erp.workspace title="Pemasukan" subtitle="Kelola pemasukan dan lihat rincian transaksi."><x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-erp-filter="search" aria-label="Cari pemasukan" placeholder="Cari pemasukan…"></div><details class="erp-operations-filter"><summary class="erp-action"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></summary><div class="erp-operations-filter__body"><label for="pos-status-filter">Status<select id="pos-status-filter" class="erp-select" data-erp-filter="status"><option value="all">Semua Status</option><option value="pending">Proses</option><option value="done">Selesai</option></select></label><label for="kt_ecommerce_sales_flatpickr">Periode<span class="erp-input-group"><input class="erp-input" placeholder="Pilih rentang tanggal" id="kt_ecommerce_sales_flatpickr"><x-erp.button icon="x" id="kt_ecommerce_sales_flatpickr_clear" aria-label="Hapus periode" /></span></label></div></details>@if(check_access('pos.create'))<x-erp.button variant="primary" icon="plus" bold :href="url('pos/create')">Tambah Pemasukan</x-erp.button>@endif</x-slot:toolbar><x-erp.collection table="pos-table"><table class="erp-table" id="pos-table" width="100%">
                    <thead>
                        <tr>
                            <th>Nama Pelanggan</th>
                            <th>Tanggal Proses</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table></x-erp.collection></x-erp.workspace>

@section('script')
    <script type="text/javascript">
        var dataTable;
        $(document).ready(function() {
            dataTable = $('#pos-table').DataTable({
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
                }, ],
                ajax: {
                    url: "{{ route('other-book.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = $('[data-erp-filter="status"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0]; // jika hanya pilih 1 tanggal
                        }
                    }
                },
                order: [
                    [1, 'desc'], // Then by order_date ASC (kolom ke-3)
                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-center'
                    },
                    {
                        data: 'process_status',
                        name: 'process_status'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-erp-filter="status"]').on('change', function() {
                dataTable.draw(); // trigger fetch ulang dari server
            });

            $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function(e, t, n) {
                    dataTable.draw();
                }
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

        function setSelesai(id) {
            Swal.fire({
                title: 'Selesaikan Pesanan?',
                text: 'Data yang dirubah tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, selesaikan!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'erp-action erp-action--danger',
                    cancelButton: 'erp-action ml-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/other-book/set-selesai/${id}`, // Ganti dengan URL yang sesuai
                        type: 'PUT',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil diselesaikan.',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            // Reload DataTable setelah berhasil selesaikan data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat selesaikan data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
