@section('script')
    <script>
        parcelId = {{ $data->id }};
        let selectedProduct = {}; // Global, satu kali saja
        var tableSelectedProduct;

        $(document).ready(function() {

            var listDatatable = $('#kt_ecommerce_edit_order_product_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollY: "400px",
                scrollCollapse: true,
                paging: false,
                info: false,
                ajax: {
                    url: "{{ route('wholsale.product-table-data') }}",
                    data: function(d) {
                        d.searchValue = $('#search').val();
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [
                    // {
                    //     data: 'checkbox',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'name',
                        name: 'name',
                        render: function(data, type, row) {
                            return `
                            <div class="d-flex align-items-center"
                                data-kt-ecommerce-edit-order-id="${row.id}"
                                data-kt-ecommerce-edit-order-type="${row.type}"
                                data-kt-ecommerce-edit-order-price="${row.price}"
                                data-kt-ecommerce-edit-order-hpp="${row.hpp}">
                                <div class="ms-5">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-5 fw-bold">${data}</a>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        data: 'qty_remaining',
                        name: 'qty_remaining',
                        className: 'text-end',
                    }
                ],
                language: {
                    emptyTable: "Silahkan Search Product untuk menambahkan produk."
                }
            });

            $('#search').on('keyup', function() {
                listDatatable.search(this.value).draw();
            });

            // const table = $('#kt_ecommerce_edit_order_product_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            //     order: [],
            //     scrollY: "400px",
            //     scrollCollapse: true,
            //     paging: false,
            //     info: false,
            //     columnDefs: [{
            //         orderable: false,
            //         targets: 0
            //     }]
            // });

            var url = `{{ url('parcel/get-product/${parcelId}') }}`;
            console.log(url);
            tableSelectedProduct = $('#kt_ecommerce_edit_order_selected_products_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: false,
                info: false,
                paging: false,
                ajax: url,
                fixedColumns: {
                    leftColumns: 0, // Tidak ada kolom di sisi kiri yang dibekukan
                    rightColumns: 1 // Membekukan 1 kolom di sisi kanan (kolom action)
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Nonaktifkan sorting untuk kolom action
                }], // Ganti dengan route untuk ambil data
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'hpp',
                        name: 'hpp'
                    },
                    {
                        data: 'harga_jual',
                        name: 'harga_jual'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    emptyTable: "Tidak ada produk yang dipilih."
                }
            });

            $('#inputSupplier').select2({
                dropdownParent: $('#modalInputQty'),
                width: '100%',
                placeholder: 'Choose supplier',
                allowClear: true
            });

            // Checkbox change event
            $('#kt_ecommerce_edit_order_product_table').on('click', '.check-product', function() {
                let calculationType = document.querySelector('input[name="calculation_type"]:checked')
                    .value;
                const row = $(this).closest('tr');
                const checked = $(this).is(':checked');
                const productId = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-id');
                const type = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-type');
                const price = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-price');
                const hpp = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-hpp');

                const productName = row.find('a.text-gray-800').text().trim();
                // const productImage = row.find('.symbol-label').css('background-image').replace(
                //     /^url\(["']?/, '').replace(/["']?\)$/, '');
                // const price = row.find('[data-kt-ecommerce-edit-order-filter="price"]').text().trim();

                selectedProduct = {
                    id: productId,
                    name: productName,
                    // image: productImage,
                    price: price,
                    type: type
                };
                // console.log(selectedProduct);
                if (calculationType == 'weight_to_price') {
                    $('#inputProductId').val(productId);
                    $('#inputSellPrice').val(price);
                    $('#typeList').val(type);
                    $('#inputQuantity').val('');
                    $('#modalInputQty').modal('show');
                } else {
                    var url = `{{ route('parcel.save-product') }}`;
                    var form = $('#modalInputPrcForm');
                    form.attr('action', url);
                    $('#methodFieldPrc').val('POST');
                    $('#inputProductIdPrc').val(productId);
                    $('#inputSellPricePrc').val(price);
                    $('#typeList').val(type);
                    $('#inputPrice').val('');
                    $('#modalInputPrc').modal('show');
                }

                bindFormatNumber();
            });

            // Tombol Add Product dari modal
            $('#submitQty').on('click', function() {
                const qty = parseFloat($('#inputQuantity').val());
                const sellPrice = parseFloat(unformatNumber($('#inputSellPrice').val()));
                const id = $('#inputProductId').val();

                // if (!qty || qty <= 0) {
                //     Swal.fire("Error", "Quantity harus diisi dan lebih dari 0.", "error");
                //     return;
                // }

                // Kirim data ke server via AJAX
                $.ajax({
                    url: "{{ route('parcel.save-product') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id,
                        qty: qty,
                        sell_price: sellPrice,
                        production_id: parcelId
                    },
                    success: function(response) {
                        $('#modalInputQty').modal('hide');
                        // 6. Refresh DataTable
                        if (typeof tableSelectedProduct !== 'undefined') {
                            tableSelectedProduct.ajax.reload(null, false);
                        }
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
                    }
                });
            });

            $("#kt_ecommerce_edit_order_date").flatpickr({
                altInput: !0,
                altFormat: "d F, Y",
                dateFormat: "Y-m-d"
            });

            tableSelectedProduct.on('draw', function() {
                let budget = {{ $data->budget }};
                let fee = {{ $data->fee * $data->quantity }};
                let total = tableSelectedProduct.column(2, {
                    page: 'current'
                }).data().reduce(function(a, b) {
                    let cleanedB = 0;

                    if (typeof b === 'string') {
                        // Hilangkan "Rp.", spasi, dan titik
                        cleanedB = b.replace(/Rp\.?\s?/g, '').replace(/\./g, '').replace(',', '.');
                    } else {
                        cleanedB = b;
                    }

                    return parseFloat(a) + parseFloat(cleanedB || 0);
                }, 0);

                let totalHpp = tableSelectedProduct.column(1, {
                    page: 'current'
                }).data().reduce(function(a, b) {
                    let cleanedB = 0;

                    if (typeof b === 'string') {
                        // Hilangkan "Rp.", spasi, dan titik
                        cleanedB = b.replace(/Rp\.?\s?/g, '').replace(/\./g, '').replace(',', '.');
                    } else {
                        cleanedB = b;
                    }

                    return parseFloat(a) + parseFloat(cleanedB || 0);
                }, 0);

                let sisa = budget - total;
                let profit = total - totalHpp;
                let totalActual = total + fee;

                $('#sisaBudget').text(sisa.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }));

                $('#totalHpp').text(totalHpp.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }));

                $('#totalSemuaProduk').text(total.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }));

                $('#totalProfit').text(profit.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }));

                $('#totalActualBudget').text(totalActual.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                }));
            });
        });

        $("#kt_ecommerce_edit_order_form").submit(function() {
            $(this).find(":submit").attr('disabled', 'disabled');
            $(this).find(":submit").html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`
            );
        });

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
                        url: `/parcel/delete-product/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // Reload DataTable setelah berhasil menghapus data
                            if (typeof tableSelectedProduct !== 'undefined') {
                                tableSelectedProduct.ajax.reload(null, false);
                            }
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
            var url = `{{ url('parcel/edit-product/${id}') }}`;
            var urlUpdate = `{{ url('parcel/update-product/${id}') }}`;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    console.log(response);
                    let calculationType = document.querySelector('input[name="calculation_type"]:checked')
                        .value;

                    if (calculationType == 'weight_to_price') {
                        $('#inputPriceEdit').val(response.data.price);
                        $('#inputQuantityEdit').val(response.data.quantity);
                        $('#inputSupplierEdit').val(response.data.supplier_id).trigger('change');

                        // Set action dan method form
                        var form = $('#kt_modal_add_customer_form');
                        form.attr('action', urlUpdate);
                        $('#methodField').val('PUT');
                        $('#kt_modal_add_customer').modal('show');
                    } else {
                        var form = $('#modalInputPrcForm');
                        form.attr('action', urlUpdate);
                        $('#methodFieldPrc').val('PUT');
                        $('#inputProductIdPrc').val(id);
                        $('#inputSellPricePrc').val(response.data.price);
                        $('#inputQuantityPrc').val(response.data.quantity);
                        $('#inputPrice').val(response.data.nominal);
                        $('#modalInputPrc').modal('show');
                    }
                    // Set nilai input

                    bindFormatNumber();
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


        $('#kt_modal_add_customer_form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var submitBtn = $('#kt_modal_add_customer_submit');

            // Show loading
            submitBtn.prop('disabled', true);
            submitBtn.find('.indicator-label').hide();
            submitBtn.find('.indicator-progress').show();

            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(), // gunakan FormData(form)[... jika pakai file]
                success: function(response) {
                    form.trigger('reset');

                    // 5. Tutup modal
                    const modalEl = document.getElementById('kt_modal_add_customer');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    modalInstance.hide();

                    if (typeof tableSelectedProduct !== 'undefined') {
                        tableSelectedProduct.ajax.reload(null, false);
                    }
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
                    submitBtn.find('.indicator-label').show();
                    submitBtn.find('.indicator-progress').hide();
                }
            });
        });

        function setSubmitType(type) {
            document.getElementById('submit_type').value = type;
        }

        function setSelesai(id) {
            let rawText = $('#totalHpp').text();
            let totalHpp = parseInt(
                rawText.replace(/[^0-9,]/g, '').replace(/\./g, '').split(',')[0]
            ) || 0;

            console.log(totalHpp); // 19125

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Parcel tidak bisa diedit ketika sudah diselesaikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    const confirmBtn = Swal.getConfirmButton();
                    confirmBtn.disabled = false;
                },
                preConfirm: () => {
                    Swal.showLoading(); // Tampilkan loading di tombol konfirmasi

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: `/parcel/set-selesai/${id}`,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                hpp: totalHpp,
                            },
                            success: function(response) {
                                resolve(response);
                            },
                            error: function(xhr) {
                                reject(xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menyelesaikan.');
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.value.message || 'Parcel berhasil diselesaikan.',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Redirect setelah delay
                    setTimeout(() => {
                        window.location.href = "{{ url('parcel') }}";
                    }, 1500);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Opsional: aksi jika batal
                } else if (result.isDismissed && result.dismiss !== Swal.DismissReason.cancel) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: result.reason || 'Terjadi kesalahan saat menyelesaikan parcel.'
                    });
                }
            });

        }

        function calculateQuantity() {
            // Ambil value dari input price
            let price = parseFloat(
                document.getElementById('inputPrice').value.replace(/[.,]/g, '')
            ) || 0;
            let sellPrice = parseFloat(
                document.getElementById('inputSellPricePrc').value.replace(/[.,]/g, '')
            ) || 0;

            // Cek biar nggak bagi nol
            let result = 0;
            if (sellPrice !== 0) {
                result = price / sellPrice;
            }

            // Set hasil ke inputQuantityPrc, fix 2 angka desimal
            document.getElementById('inputQuantityPrc').value = result.toFixed(2);
        }
        document.getElementById('inputPrice').addEventListener('keyup', calculateQuantity);

        $('#modalInputPrcForm').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var submitBtn = $('#kt_modal_add_customer_submit_prc');
            console.log(form.serialize());

            // Show loading
            submitBtn.prop('disabled', true);
            submitBtn.find('.indicator-label').hide();
            submitBtn.find('.indicator-progress').show();

            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(), // gunakan FormData(form)[... jika pakai file]
                success: function(response) {
                    // 1. Reset form
                    form.trigger('reset');

                    // 5. Tutup modal
                    const modalEl = document.getElementById('modalInputPrc');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    modalInstance.hide();

                    if (typeof tableSelectedProduct !== 'undefined') {
                        tableSelectedProduct.ajax.reload(null, false);
                    }
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
                    submitBtn.find('.indicator-label').show();
                    submitBtn.find('.indicator-progress').hide();
                }
            });
        });
    </script>
@endsection
