@extends('template.root')

@section('content')
    <form id="kt_ecommerce_edit_order_form" class="form d-flex flex-column flex-lg-row"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data" data-kt-redirect="">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Main column-->
        <div class="d-flex flex-column flex-lg-row-fluid gap-7 gap-lg-10">
            <!--begin::Order details-->
            <div class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <div class="card-title">
                        <h2>Production Details</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-10">
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Production ID</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <div class="fw-bold fs-3">#{{ isset($data) ? $data->production_number : '14364' }}</div>
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Quantity</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <input type="number" name="quantity" id="quantity" class="form-control mb-2"
                                placeholder="Enter Quantity"
                                value="{{ isset($data) ? $data->quantity : old('quantity') }}" />
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Budget</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <input type="text" name="budget" id="budget" class="form-control format-number mb-2"
                                placeholder="Enter Budget" value="{{ isset($data) ? $data->budget : old('budget') }}" />
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Production Date</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input id="kt_ecommerce_edit_order_date" name="production_date" placeholder="Select a date"
                                class="form-control mb-2" value="{{ old('production_date') ?? date('Y-m-d') }}" />
                            <!--end::Editor-->
                            <!--begin::Description-->
                            <div class="text-muted fs-7">Set the date of the production to process.</div>
                            <!--end::Description-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Pic</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <select name="staff_id" id="staff_id" class="form-select mb-2">
                                @if (isset($data) && $data->staff)
                                    <option value="{{ $data->staff->id }}" selected>{{ $data->staff->name }}</option>
                                @endif
                            </select>
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Fee</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <input type="text" name="fee" id="fee" class="form-control format-number mb-2"
                                placeholder="Enter Fee Product" value="{{ isset($data) ? $data->fee : old('fee') }}" />
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                    </div>
                </div>
                <!--end::Card header-->
            </div>
            <!--end::Order details-->
            <!--begin::Order details-->
            <div class="card card-flush py-4">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Detail Bahan</h2>
                    </div>
                </div>
                <!--begin::Card body-->
                @if (isset($data))
                    <div class="card-body pt-0">
                        <div class="d-flex flex-column gap-10">
                            <!--begin::Table-->
                            <div class="table table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-3 mb-5"
                                    id="kt_ecommerce_edit_order_selected_products_table">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-200px">Product</th>
                                            <th class="min-w-100px">Hpp</th>
                                            <th class="min-w-100px">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="kt_ecommerce_edit_order_selected_products_body">
                                        <tr class="text-muted text-center">
                                            <td colspan="6">Select one or more products from the list below by ticking
                                                the
                                                checkbox.</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <hr>
                                <!--begin::Total price-->
                                <div class="fw-bold fs-4">Plan Budget:
                                    <span id="">{{ tonumber($data->budget) }}</span>
                                </div>
                                <!--end::Total price-->
                                <!--begin::Total price-->
                                <div class="fw-bold fs-4">Actual Budget:
                                    <span id="totalSemuaProduk">0.00</span>
                                </div>
                                <!--end::Total price-->
                                <!--begin::Total price-->
                                <div class="fw-bold fs-4">Hpp:
                                    <span id="totalHpp">0.00</span>
                                </div>
                                <!--end::Total price-->
                                <!--begin::Total price-->
                                <div class="fw-bold fs-4">Profit:
                                    <span id="totalProfit">0.00</span>
                                </div>
                                <!--end::Total price-->
                            </div>
                            <!--end::Input group-->
                            {{-- <button class="variant btn btn-light-primary btn-sm mb-10" type="button"
                                onclick="addVariant()">
                                <i class="ki-outline ki-plus fs-2"></i>Tambah Product
                            </button> --}}
                            <!--end::Table-->
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <span class="text-muted">Posting Parcel Terlebih dahulu</span>
                    </div>
                @endif
                <!--end::Card header-->
            </div>
            <!--end::Order details-->

            <div class="d-flex justify-content-end">
                <!--begin::Button-->
                <a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_edit_order_cancel"
                    class="btn btn-light me-5">Cancel</a>
                <!--end::Button-->

                @if (Request::segment(2) != 'show')
                    <input type="hidden" name="submit_type" id="submit_type" value="draft">
                    <button type="submit" class="btn btn-primary me-2" onclick="setSubmitType('draft')">
                        <span class="indicator-label">Draft</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>

                    <button type="submit" class="btn btn-primary" onclick="setSubmitType('posting')">
                        <span class="indicator-label">Posting</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                @endif
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
    {{-- @include('transaction::wholesale.js-create') --}}
@section('script')
    <script>
        let selectedProduct = {}; // Global, satu kali saja
        var tableSelectedProduct;

        $(document).ready(function() {

            // Checkbox change event

            $("#kt_ecommerce_edit_order_date").flatpickr({
                altInput: !0,
                altFormat: "d F, Y",
                dateFormat: "Y-m-d"
            });

        });

        $("#kt_ecommerce_edit_order_form").submit(function() {
            $(this).find(":submit").attr('disabled', 'disabled');
            $(this).find(":submit").html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`
            );
        });

        $('#product_id').select2({
            placeholder: 'Select a product',
            ajax: {
                url: '{{ route('products.get-product-receipt') }}',
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
        });

        $('#staff_id').select2({
            placeholder: 'Select a staff',
            ajax: {
                url: '{{ route('staff.get-staff') }}',
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
        });

        function setSubmitType(type) {
            document.getElementById('submit_type').value = type;
        }

        @if (isset($data))
            const parcelId = "{{ $data->id }}";
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
                    }
                ],
                language: {
                    emptyTable: "Tidak ada produk yang dipilih."
                }
            });

            tableSelectedProduct.on('draw', function() {
                let budget = {{ $data->budget }};
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
            });
        @endif
    </script>

    @if (Route::currentRouteName() == 'parcel.show')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const formElements = document.querySelectorAll('form input, form select, form textarea, form button');

                formElements.forEach(element => {
                    element.disabled = true;
                });
            });
        </script>
    @endif
@endsection

@endsection
