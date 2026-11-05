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
                    url: "{{ route('campaign.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'start_date',
                        name: 'start_date'
                    },
                    {
                        data: 'end_date',
                        name: 'end_date'
                    },
                    {
                        data: 'type_promo',
                        name: 'type_promo'
                    },
                    {
                        data: 'value',
                        name: 'value'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
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
                        url: `/campaign/${id}`, // Ganti dengan URL yang sesuai
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
                url: `/campaign/${id}/edit`, // URL untuk mengambil data produk yang akan diedit
                type: 'GET',
                success: function(response) {
                    console.log(response);
                    // Isi form dengan data produk yang ada
                    $('input[name="name"]').val(response.name);
                    $('input[name="start_date"]').val(response.start_date);
                    $('input[name="end_date"]').val(response.end_date);
                    $('input[name="value_promo"]').val(response.value);
                    $('input[name="type_promo"]').val(response.type_promo).trigger('change');


                    // Ubah action form untuk update
                    var form = $('#crm-form');
                    form.attr('action', `/campaign/${id}`); // URL untuk update produk
                    form.find('input[name="_method"]').remove(); // Hapus input _method jika ada
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    ); // Menambahkan input _method untuk PUT

                    // Tampilkan modal untuk edit produk
                    

                    bindFormatNumber(); // Jalankan saat awal();
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

    </script>
@endsection
