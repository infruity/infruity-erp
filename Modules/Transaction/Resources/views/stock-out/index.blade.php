@extends('layouts.erp')
@section('title', 'Stok Keluar')
@section('page-title', 'Stok Keluar')
@section('page-breadcrumb')
    <span>Inventaris</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Stok Keluar</span>
@endsection
@push('styles')
    @vite('resources/css/flatpickr.css')
@endpush
@section('content')
    @include('crm::partials.assets')
    <link rel="stylesheet" href="{{ asset('assets/css/erp-operations.css') }}?v={{ filemtime(public_path('assets/css/erp-operations.css')) }}">
    <x-erp.workspace title="Stok Keluar" subtitle="Telusuri barang keluar dan jumlahnya." icon="package">
        <x-slot:toolbar>
            <label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" aria-label="Cari transaksi stok keluar" placeholder="Cari transaksi stok keluar..."></label>
            <div class="erp-operations-toolbar-actions"><details class="erp-operations-filter"><summary class="erp-action"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i>Filter</summary><div class="erp-operations-filter__body"><label for="stock-out-type-filter">Jenis stok<select id="stock-out-type-filter" class="erp-select" data-erp-filter="stock"><option value="all">Semua</option>@foreach ($type as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label><label for="kt_ecommerce_sales_flatpickr">Rentang tanggal<div class="flex gap-2"><input class="erp-input" placeholder="Pilih rentang tanggal" id="kt_ecommerce_sales_flatpickr"><button type="button" class="erp-action" id="kt_ecommerce_sales_flatpickr_clear" aria-label="Hapus rentang tanggal"><i class="ph ph-x" aria-hidden="true"></i></button></div></label></div></details><button type="button" class="erp-action erp-action--primary" data-erp-open="#crm-dialog" data-crm-create><i class="ph ph-plus" aria-hidden="true"></i>Tambah Transaksi</button></div>
        </x-slot:toolbar>
        <div class="erp-operations-collection" data-erp-collection="transaction-table" data-erp-empty="Belum ada stok keluar" data-erp-empty-description="Data yang tersimpan akan muncul di sini.">
            <table class="erp-table erp-operations-table" id="transaction-table" width="100%">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th class="text-right">Jumlah</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class=""></tbody>
                </table>
        </div>
    </x-erp.workspace>
    <x-erp.dialog id="crm-dialog" title="Tambah Transaksi" variant="sheet" data-erp-create data-crm-dialog>
        <form id="crm-form" class="erp-crm-form" action="{{ url(Request::segment(1)) }}">@csrf
            <div class="erp-crm-fields">
                <div class="erp-crm-form-error" data-crm-form-error role="alert" tabindex="-1" hidden></div>
                <div class="erp-crm-field"><label for="product_id" class="erp-label erp-label--required">Produk</label><select name="product_id" id="product_id" class="erp-select"></select></div>
                <div class="erp-crm-field"><label for="date" class="erp-label erp-label--required">Tanggal</label><input type="text" id="date" class="erp-input" name="date" value="{{ date('Y-m-d') }}"></div>
                <div class="erp-crm-field"><label for="type" class="erp-label erp-label--required">Jenis</label><select name="type" id="type" class="erp-select">@foreach ($type as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
                <div class="erp-crm-field"><label for="quantity" class="erp-label">Kuantitas</label><input type="number" step="0.01" id="quantity" class="erp-input" name="quantity"></div>
            </div>
        </form>
        <x-slot:footer>
            <div class="ml-auto flex gap-3">
                <button type="button" id="crm-cancel" class="erp-action">Batal</button>
                <button type="submit" form="crm-form" id="crm-submit" class="erp-action erp-action--primary"><span data-label>Simpan</span><span data-progress hidden>Menyimpan…</span></button>
            </div>
        </x-slot:footer>
    </x-erp.dialog>
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
                    leftColumns: 0, // Tidak ada kolom di sisi kiri yang dibekukan
                    rightColumns: 1 // Membekukan 1 kolom di sisi kanan (kolom action)
                },
                ajax: {
                    url: "{{ route('stock-out.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.stock_filter = $('[data-erp-filter="stock"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0]; // jika hanya pilih 1 tanggal
                        }
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'text-end'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        className: 'text-end',
                        orderable: false,
                        searchable: false
                    },

                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });
            $('[data-erp-filter="stock"]').on('change', function() {
                dataTable.draw(); // trigger fetch ulang dari server
            });

            document.getElementById('crm-cancel').addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah form reset langsung
                Swal.fire({
                    text: "Apakah Anda yakin ingin membatalkan?",
                    icon: "warning",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "Ya, Batalkan!",
                    cancelButtonText: "Tidak, Kembali",
                    customClass: {
                        confirmButton: "erp-action erp-action--primary",
                        cancelButton: "erp-action ml-3"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        erp.dialog.close('crm-dialog');
                    }
                });
            });

            $('#crm-form').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var submitBtn = $('#crm-submit');

                // Show loading
                submitBtn.prop('disabled', true);
                submitBtn.find('[data-label]').prop('hidden', true);
                submitBtn.find('[data-progress]').prop('hidden', false);

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: form.serialize(), // gunakan FormData(form)[... jika pakai file]
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // 1. Reset form
                            form.trigger('reset');

                            // 2. Hapus input _method
                            form.find('input[name="_method"]').remove();

                            // 3. Kembalikan action form ke default (untuk create)
                            form.attr('action',
                                `/${segment1}`); // Misal segment1 = 'stock-out'

                            // 4. Kembalikan judul modal (opsional)
                            // 5. Tutup modal
                            erp.dialog.close('crm-dialog');

                            // 6. Refresh DataTable
                            if (typeof dataTable !== 'undefined') {
                                dataTable.ajax.reload(null, false);
                            }
                        });
                    },
                    error: function(xhr) {
                        var msg = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    },
                    complete: function() {
                        // Reset loading state
                        submitBtn.prop('disabled', false);
                        submitBtn.find('[data-label]').prop('hidden', false);
                        submitBtn.find('[data-progress]').prop('hidden', true);
                    }
                });
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
                        url: `/stock-out/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.'
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

        function editProduct(id) {
            $.ajax({
                url: `/stock-out/${id}/edit`, // URL untuk mengambil data produk yang akan diedit
                type: 'GET',
                success: function(response) {
                    console.log(response);
                    // Isi form dengan data produk yang ada
                    // Set flatpickr date
                    var fp = $('#date')[0]._flatpickr;
                    if (fp) {
                        fp.setDate(response.date);
                    }
                    $('input[name="quantity"]').val(response.quantity);
                    $('select[name="product_id"]').append(
                        $('<option>', {
                            value: response.product_id,
                            text: response.name
                        })
                    ).val(response.product_id).trigger('change');
                    $('select[name="type"]').val(response.type_id).trigger('change');

                    // Ubah action form untuk update
                    var form = $('#crm-form');
                    form.attr('action', `/stock-out/${id}`); // URL untuk update produk
                    form.find('input[name="_method"]').remove(); // Hapus input _method jika ada
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    ); // Menambahkan input _method untuk PUT

                    // Tampilkan modal untuk edit produk
                    
                    erp.dialog.open('crm-dialog');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        $("#date").flatpickr({
            altInput: !0,
            altFormat: "d F, Y",
            dateFormat: "Y-m-d"
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

        // Clear rentang tanggal filter.
        $('#kt_ecommerce_sales_flatpickr_clear').on('click', function() {
            document.getElementById('kt_ecommerce_sales_flatpickr')._flatpickr?.clear();
            dataTable.draw();
        });

        // Produk (ErpPicker menggantikan select2); memilih produk mengisi kuantitas dengan stok tersedia.
        $(function() {
            ErpPicker.attach('#product_id', {
                title: 'Pilih produk',
                placeholder: 'Cari produk...',
                placeholderTrigger: 'Pilih produk',
                emptyText: 'Produk tidak ditemukan',
                url: '{{ route('ajax.stock-available') }}',
                map: item => ({ id: item.id, text: item.name, data: { stock_available: item.stock_available } })
            });
            $('#product_id').on('select2:select', function(e) {
                $('input[name="quantity"]').val(e.params.data.stock_available || 0);
            });
        });
    </script>
@endsection
@endsection
