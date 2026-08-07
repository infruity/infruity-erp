@section('script')
    <script>
        wholsaleId = {{ $data->id }};
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
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: function(data, type, row) {
                            return `
                            <div class="d-flex align-items-center"
                                data-kt-ecommerce-edit-order-id="${row.id}"
                                data-kt-ecommerce-edit-order-type="${row.type}"
                                data-kt-ecommerce-edit-order-price="${row.price}">
                                <div class="ms-5">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-5 fw-bold">${data}</a>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        data: 'qty_remaining',
                        name: 'qty_remaining',
                        className: 'text-end pe-5'
                    }
                ]
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

            var url = `{{ url('wholesale/get-product/${wholsaleId}') }}`;
            console.log(url);
            tableSelectedProduct = $('#kt_ecommerce_edit_order_selected_products_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: false,
                info: false,
                paging: false,
                ajax: {
                    url: url,
                    data: function(d) {
                        d.searchValue = $('#search').val();
                        d.url = "{{ request()->segment(1) }}/{{ request()->segment(2) }}";
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'total',
                        name: 'total'
                    },
                    {
                        data: 'supplier',
                        name: 'supplier'
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
            $('#kt_ecommerce_edit_order_product_table').on('change', '.form-check-input', function() {
                const row = $(this).closest('tr');
                const checked = $(this).is(':checked');
                const productId = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-id');
                const type = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-type');
                const price = row.find('[data-kt-ecommerce-edit-order-id]').data(
                    'kt-ecommerce-edit-order-price');

                if (checked) {
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
                    console.log(selectedProduct);
                    $('#inputProductId').val(productId);
                    $('#inputSellPrice').val(price);
                    $('#typeList').val(type);
                    $('#inputQuantity').val('');
                    $('#modalInputQty').modal('show');
                } else {
                    $(`#kt_ecommerce_edit_order_selected_products [data-product-id="${productId}"]`)
                        .remove();
                    $(`#selected-products-hidden input[name="products[${productId}][id]"]`).remove();
                    $(`#selected-products-hidden input[name="products[${productId}][qty]"]`).remove();

                    if ($('#kt_ecommerce_edit_order_selected_products .col').length === 0) {
                        $('#kt_ecommerce_edit_order_selected_products').html(
                            `<span class="w-100 text-muted">Select one or more products from the list below by ticking the checkbox.</span>`
                        );
                    }
                }
            });


            $("#kt_ecommerce_edit_order_date").flatpickr({
                altInput: !0,
                altFormat: "d F, Y",
                dateFormat: "Y-m-d"
            });

            tableSelectedProduct.on('draw', function() {
                let total = tableSelectedProduct.column(3, {
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

                $('#totalSemuaProduk').text(total.toLocaleString('id-ID', {
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
    </script>
@endsection
