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
                    url: "{{ route('deposito.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
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
                        data: 'deposito',
                        name: 'deposito'
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

                var form = $(this)[0];
                var url = $(this).attr('action');
                var submitBtn = $('#crm-submit');

                var formData = new FormData(form); // Gunakan FormData agar file bisa ikut terkirim

                // Show loading
                submitBtn.prop('disabled', true);
                submitBtn.find('[data-label]').prop('hidden', true);
                submitBtn.find('[data-progress]').prop('hidden', false);

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Reset form, tutup modal, dan refresh DataTable
                            $('#crm-form').trigger('reset');
                            $('#crm-form').find(
                                'input[name="_method"]').remove();
                            $('#crm-form').attr('action',
                                `/${segment1}`);
                            erp.dialog.close('crm-dialog');
                            if (typeof dataTable !== 'undefined') dataTable.ajax.reload(
                                null, false);
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat menyimpan data.'
                        });
                    },
                    complete: function() {
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
                        url: `/deposito/${id}`, // Ganti dengan URL yang sesuai
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
                                timer: 1500
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
                url: `/deposito/${id}/edit`, // URL untuk mengambil data produk yang akan diedit
                type: 'GET',
                success: function(response) {
                    console.log(response);
                    // Isi form dengan data produk yang ada
                    $('input[name="date"]').val(response.date);
                    $('input[name="deposito"]').val(response.deposito);
                    $('select[name="customer_id"]').append(
                        $('<option>', {
                            value: response.customer_id,
                            text: response.customer.name
                        })
                    ).val(response.customer_id).trigger('change');

                    // Ubah action form untuk update
                    var form = $('#crm-form');
                    form.attr('action', `/deposito/${id}`); // URL untuk update produk
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


        // Pilihan pelanggan dan tier (ErpPicker menggantikan select2; sumber datanya sama).
        $(function() {
            ErpPicker.attach('#customer_id', {
                title: 'Pilih pelanggan',
                placeholder: 'Cari pelanggan...',
                placeholderTrigger: 'Pilih pelanggan',
                emptyText: 'Pelanggan tidak ditemukan',
                url: '/customer/get-customer',
                map: item => ({ id: item.id, text: item.name })
            });
            ErpPicker.attach('#tier_id', {
                title: 'Pilih tier',
                placeholder: 'Cari tier...',
                placeholderTrigger: 'Pilih tier',
                emptyText: 'Tier tidak ditemukan',
                url: '/tier/list-tier',
                map: item => ({ id: item.id, text: item.name, data: { deposito: item.deposito } })
            });
            // Nominal deposito mengikuti tier yang dipilih.
            $('#tier_id').on('select2:select', function(e) {
                $('input[name="deposito"]').val(e.params.data.deposito ?? 0);
                bindFormatNumber();
            });
        });
    </script>
@endsection
