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

            document.getElementById('kt_modal_add_customer_cancel').addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah form reset langsung
                Swal.fire({
                    text: "Apakah Anda yakin ingin membatalkan?",
                    icon: "warning",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "Ya, Batalkan!",
                    cancelButtonText: "Tidak, Kembali",
                    customClass: {
                        confirmButton: "btn btn-primary",
                        cancelButton: "btn btn-active-light"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tutup modal manual
                        const modal = bootstrap.Modal.getInstance(document.getElementById(
                            'kt_modal_add_customer'));
                        modal.hide();
                        document.getElementById('kt_modal_add_customer_form').reset();
                    }
                });
            });

            $('#kt_modal_add_customer_form').on('submit', function(e) {
                e.preventDefault();

                var form = $(this)[0];
                var url = $(this).attr('action');
                var submitBtn = $('#kt_modal_add_customer_submit');

                var formData = new FormData(form); // Gunakan FormData agar file bisa ikut terkirim

                // Show loading
                submitBtn.prop('disabled', true);
                submitBtn.find('.indicator-label').hide();
                submitBtn.find('.indicator-progress').show();

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
                            $('#kt_modal_add_customer_form').trigger('reset');
                            $('#kt_modal_add_customer_form').find(
                                'input[name="_method"]').remove();
                            $('#kt_modal_add_customer_form').attr('action',
                                `/${segment1}`);
                            $('#kt_modal_add_customer_header h2').text(
                                'Tambah Deposito');
                            const modal = bootstrap.Modal.getInstance(document
                                .getElementById('kt_modal_add_customer'));
                            if (modal) modal.hide();
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
                        submitBtn.find('.indicator-label').show();
                        submitBtn.find('.indicator-progress').hide();
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
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
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
                    var form = $('#kt_modal_add_customer_form');
                    form.attr('action', `/deposito/${id}`); // URL untuk update produk
                    form.find('input[name="_method"]').remove(); // Hapus input _method jika ada
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    ); // Menambahkan input _method untuk PUT

                    // Tampilkan modal untuk edit produk
                    var modal = new bootstrap.Modal(document.getElementById('kt_modal_add_customer'));
                    modal.show();
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

        $('#kt_modal_add_customer').on('shown.bs.modal', function() {
            $('#customer_id').select2({
                placeholder: 'Select a customer',
                dropdownParent: $('#kt_modal_add_customer'),
                ajax: {
                    url: '/customer/get-customer',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name
                        }))
                    })
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
            });
        });

        $('#kt_modal_add_customer').on('shown.bs.modal', function() {
            $('#tier_id').select2({
                placeholder: 'Select a tier',
                dropdownParent: $('#kt_modal_add_customer'),
                ajax: {
                    url: '/tier/list-tier',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name,
                            deposito: item.deposito
                        }))
                    })
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                const depostio = data.deposito ?? 0;
                $('input[name="deposito"]').val(depostio);
                bindFormatNumber();
            });
        });
    </script>
@endsection
