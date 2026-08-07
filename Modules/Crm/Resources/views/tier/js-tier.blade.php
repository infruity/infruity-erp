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
                    url: "{{ route('tier.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'level',
                        name: 'level'
                    },
                    {
                        data: 'exp',
                        name: 'exp'
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

            $('#style').select2({
                templateResult: function(data) {
                    if (!data.id) return data.text;

                    const badgeClass = $(data.element).data('badge') || 'badge-light';
                    return $('<span class="badge ' + badgeClass + '">' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    if (!data.id) return data.text;

                    const badgeClass = $(data.element).data('badge') || 'badge-light';
                    return $('<span class="badge ' + badgeClass + '">' + data.text + '</span>');
                },
                escapeMarkup: function(markup) {
                    return markup;
                },
                width: '100%' // agar mengikuti lebar
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
                            $('#kt_modal_add_customer_header h2').text('Tambah Tier');
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
                        url: `/tier/${id}`, // Ganti dengan URL yang sesuai
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
                url: `/tier/${id}/edit`, // URL untuk mengambil data produk yang akan diedit
                type: 'GET',
                success: function(response) {
                    console.log(response);
                    // Isi form dengan data produk yang ada
                    $('input[name="name"]').val(response.name);
                    $('input[name="exp"]').val(response.exp);
                    $('input[name="level"]').val(response.level);
                    // Tampilkan gambar jika ada
                    if (response.icon) {
                        $('.image-input-wrapper').css('background-image', `url(/storage/${response.icon})`);
                    }

                    // Ubah action form untuk update
                    var form = $('#kt_modal_add_customer_form');
                    form.attr('action', `/tier/${id}`); // URL untuk update produk
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
            $('#product_id').select2({
                placeholder: 'Select a product',
                dropdownParent: $('#kt_modal_add_customer'),
                ajax: {
                    url: '{{ route('ajax.stock-available') }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name,
                            stock_available: item.stock_available
                        }))
                    })
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                $('input[name="quantity"]').val(data.stock_available || 0);
            });

        });

        function format(rowData) {
            return `
                <div class="accordion-content">
                    <form class="accordion-form" data-id="${rowData.id}">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Atur Benefit</h3>
                            </div>
                            <div class="card-body">
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Diskon Setiap Transaksi
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="number" class="form-control" name="discount_transaction" value="${rowData.discount_transaction || ''}" placeholder="Diskon (Rp jika > 100, % jika ≤ 100)" />
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Gratis Product
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <select class="form-select select-product" multiple name="free_product_id[]" style="width:100%;"></select>
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Minimal Pembelian
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control format-number" name="minimal_purchase" value="${rowData.minimal_purchase || ''}" placeholder="Minimal Pembelian" />
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Maksimal Claim
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="number" class="form-control" name="max_claim" value="${rowData.max_claim || ''}" placeholder="Maksimal Claim" />
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Voucher
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control format-number" name="voucher" value="${rowData.voucher || ''}" placeholder="Voucher" />
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Hadiah Ulang Tahun
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" name="birthday_gift" id="birthday_gift" ${rowData.birthday_gift == 1 ? 'checked' : ''} value="1">
                                            <label class="form-check-label" for="birthday_gift">
                                                Aktifkan Hadiah Ulang Tahun
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container align-items-center">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Promo Gabungan
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" name="combo_promo" id="combo_promo" ${rowData.combo_promo == 1 ? 'checked' : ''} value="1">
                                            <label class="form-check-label" for="combo_promo">
                                                Aktifkan Promo Gabungan
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row fv-row mb-7 fv-plugins-icon-container">
                                    <div class="col-md-3 text-md-text-start">
                                        <label class="fs-6 fw-semibold form-label mt-3">
                                            <span>
                                                Deposito
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control format-number" name="deposito" value="${rowData.deposito || ''}" placeholder="Deposito" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            `;

        }

        $('#transaction-table tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('button, a').length) return;

            let tr = $(this);
            let row = dataTable.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                console.log(row.data());
                row.child(format(row.data())).show();
                tr.addClass('shown');

                // Aktifkan select2 setelah konten ditambahkan ke DOM
                let select = tr.next().find('.select-product');

                select.select2({
                    ajax: {
                        url: '/ajax/listProduct',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(item => ({
                                    id: item.id,
                                    text: item.name
                                }))
                            };
                        },
                        cache: true
                    },
                    placeholder: 'Pilih Produk',
                    minimumInputLength: 1
                });

                // 👇 Tambahkan ini untuk mengisi default value (selected)
                let freeProducts = row.data().freeProduct || [];
                console.log(freeProducts);
                freeProducts.forEach(prod => {
                    let option = new Option(prod.name, prod.id, true, true);
                    select.append(option);
                });

                select.trigger('change');

                bindFormatNumber();
            }
        });

        // Submit form di dalam accordion
        $('#transaction-table tbody').on('submit', '.accordion-form', function(e) {
            e.preventDefault();
            let form = $(this);
            let id = form.data('id');
            let data = form.serialize();

            $.ajax({
                url: `/tier/${id}/save-detail`,
                type: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    alert('Data berhasil disimpan!');
                    if (typeof dataTable !== 'undefined') dataTable.ajax.reload(
                        null, false);
                },
                error: function() {
                    alert('Gagal menyimpan!');
                }
            });
        });
    </script>
@endsection
