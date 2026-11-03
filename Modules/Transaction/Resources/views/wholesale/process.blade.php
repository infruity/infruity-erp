@extends('template.root')
@section('title', 'Proses Pengadaan')

@section('content')
    <form id="kt_ecommerce_edit_order_form" class="form erp-editor"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data" data-kt-redirect="">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Aside column-->
        <div class="erp-editor-summary">
            <!--begin::Order details-->
            <div class="erp-editor-section">
                <!--begin::Card header-->
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Informasi pengadaan</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="erp-editor-body">
                    <div class="d-flex flex-column gap-10">
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Nomor Pengadaan</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <div class="fw-bold fs-3">#{{ isset($data) ? $data->order_number : '14364' }}</div>
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Tanggal Pengadaan</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <div class="fw-bold fs-3">{{ isset($data) ? dateindo($data->order_date) : '14364' }}</div>
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Status</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <div class="fw-bold fs-3">
                                @if ($data->status == 'draft')
                                    <span class="badge badge-light-warning">Draft</span>
                                @elseif($data->status == 'processing')
                                    <span class="badge badge-light-primary">Processing</span>
                                @elseif($data->status == 'complete')
                                    <span class="badge badge-light-success">Complete</span>
                                @elseif($data->status == 'cancel')
                                    <span class="badge badge-light-danger">Cancel</span>
                                @endif
                            </div>
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                    </div>
                </div>
                <!--end::Card header-->
            </div>
            <!--end::Order details-->
        </div>
        <!--end::Aside column-->
        <!--begin::Main column-->
        <div class="erp-editor-main">
            <!--begin::Order details-->
            <div class="erp-editor-section">
                <!--begin::Card header-->
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Daftar Produk</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="erp-editor-body">
                    <div class="d-flex flex-column gap-10">
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Selected products-->
                            <div class="table table-responsive">
                                <label class="erp-process-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" data-kt-ecommerce-edit-order-filter="search" placeholder="Cari produk pengadaan…" aria-label="Cari produk pengadaan"></label><x-erp.collection table="kt_ecommerce_edit_order_selected_products_table"><table class="table align-middle table-row-dashed fs-6 gy-3 mb-5"
                                    id="kt_ecommerce_edit_order_selected_products_table">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-200px">Product</th>
                                            <th class="min-w-100px">Price</th>
                                            <th class="min-w-100px">Total</th>
                                            <th class="min-w-100px text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="kt_ecommerce_edit_order_selected_products_body">
                                        <tr class="text-muted text-center">
                                            <td colspan="6">Select one or more products from the list below by ticking
                                                the
                                                checkbox.</td>
                                        </tr>
                                    </tbody>
                                </table></x-erp.collection>
                            </div>

                            <!-- Tempat hidden input -->
                            <div id="selected-products-hidden"></div>
                            <!--begin::Selected products-->
                            {{-- <!--begin::Total price-->
                            <div class="fw-bold fs-4">Total Cost: $
                                <span id="kt_ecommerce_edit_order_total_price">0.00</span>
                            </div>
                            <!--end::Total price--> --}}
                        </div>
                        <!--end::Input group-->
                        <!--begin::Separator-->
                        <div class="separator"></div>
                        <!--end::Separator-->
                    </div>
                </div>
                <!--end::Card header-->
            </div>
            <!--end::Order details-->

            <div class="erp-editor-actions">
                <!--begin::Button-->
                <a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_edit_order_cancel"
                    class="erp-action">Cancel</a>
                <!--end::Button-->
                <!--begin::Button-->
                @if ($data->status != 'complete')
                    <button type="button" id="kt_ecommerce_edit_order_submit" class="erp-action erp-action--primary"
                        onclick="setSelesai({{ $data->id }})">
                        <span class="indicator-label">Selesaikan Pengadaan</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                @endif
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
@section('script')
    <script>
        wholsaleId = {{ $data->id }};
        let selectedProduct = {}; // Global, satu kali saja
        var tableSelectedProduct;

        $(document).ready(function() {
            const table = $('#kt_ecommerce_edit_order_product_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [],
                scrollY: "400px",
                scrollCollapse: true,
                paging: false,
                info: false,
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }]
            });

            var url = `{{ url('wholesale/get-product/${wholsaleId}') }}`;
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
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'total',
                        name: 'total'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        className: 'text-end',
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

            document.querySelector('[data-kt-ecommerce-edit-order-filter="search"]')?.addEventListener("keyup",
                function(e) {
                    tableSelectedProduct.search(e.target.value).draw();
                });

            // Checkbox change event

        });

        $("#kt_ecommerce_edit_order_form").submit(function() {
            $(this).find(":submit").attr('disabled', 'disabled');
            $(this).find(":submit").html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`
            );
        });

        function deleteProductOld(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Barang Sudah diterima!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Diterima!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/wholesale/update-receive-product/${id}`, // Ganti dengan URL yang sesuai
                        type: 'POST',
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

        function deleteProduct(id) {
            $.ajax({
                url: `/wholesale/update-receive-product/${id}`, // Ganti dengan URL yang sesuai
                type: 'POST',
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

        function setSelesai(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Menyelesaikan Kulak, Berarti barang sudah diterima semua!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/wholesale/set-selesai/${id}`, // Ganti dengan URL yang sesuai
                        type: 'POST',
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
                            window.location.href = "{{ url('wholesale') }}";
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
