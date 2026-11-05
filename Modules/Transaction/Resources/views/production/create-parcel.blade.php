@extends('layouts.erp')
@section('title', 'Produksi Parcel')
@section('page-title', 'Produksi Parcel')
@push('styles')
    @vite('resources/css/flatpickr.css')
@endpush

@section('content')
    <form id="kt_ecommerce_edit_order_form" class="form flex flex-col lg:flex-row"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Main column-->
        <div class="flex flex-col lg:grow lg:shrink-0 lg:basis-auto gap-6 lg:gap-8">
            <!--begin::Order details-->
            <section class="erp-editor-section py-3">
                <!--begin::Card header-->
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Detail Produksi</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="erp-editor-body pt-0">
                    <div class="flex flex-col gap-8">
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label">Nomor Produksi</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <div class="font-semibold text-lg">#{{ isset($data) ? $data->production_number : '14364' }}</div>
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label">Jumlah</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <input type="number" name="quantity" id="quantity" class="erp-input mb-1.5"
                                placeholder="Enter Jumlah"
                                value="{{ isset($data) ? $data->quantity : old('quantity') }}" />
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label">Anggaran</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <input type="text" name="budget" id="budget" class="erp-input format-number mb-1.5"
                                placeholder="Enter Anggaran" value="{{ isset($data) ? $data->budget : old('budget') }}" />
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label erp-label--required">Tanggal Produksi</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input id="kt_ecommerce_edit_order_date" name="production_date" placeholder="Select a date"
                                class="erp-input mb-1.5" value="{{ old('production_date') ?? date('Y-m-d') }}" />
                            <!--end::Editor-->
                            <!--begin::Description-->
                            <div class="text-gray-400 text-xs">Set the date of the production to process.</div>
                            <!--end::Description-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label">Penanggung jawab</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <select name="staff_id" id="staff_id" class="erp-select mb-1.5">
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
                            <label class="erp-label">Biaya jasa</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <input type="text" name="fee" id="fee" class="erp-input format-number mb-1.5"
                                placeholder="Masukkan biaya jasa" value="{{ isset($data) ? $data->fee : old('fee') }}" />
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                    </div>
                </div>
                <!--end::Card header-->
            </section>
            <!--end::Order details-->
            <!--begin::Order details-->
            <section class="erp-editor-section py-3">
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Detail Bahan</h2>
                    </div>
                </div>
                <!--begin::Card body-->
                @if (isset($data))
                    <div class="erp-editor-body pt-0">
                        <div class="flex flex-col gap-8">
                            <!--begin::Table-->
                            <div class="overflow-x-auto">
                                <table class="erp-table mb-4"
                                    id="kt_ecommerce_edit_order_selected_products_table">
                                    <thead>
                                        <tr class="text-start text-gray-300 font-semibold text-xs uppercase gs-0">
                                            <th class="min-w-[200px]">Product</th>
                                            <th class="min-w-[100px]">Hpp</th>
                                            <th class="min-w-[100px]">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="kt_ecommerce_edit_order_selected_products_body">
                                        <tr class="text-gray-400 text-center">
                                            <td colspan="6">Select one or more products from the list below by ticking
                                                the
                                                checkbox.</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <hr>
                                <!--begin::Total price-->
                                <div class="font-semibold text-base">Anggaran:
                                    <span id="">{{ tonumber($data->budget) }}</span>
                                </div>
                                <!--end::Total price-->
                                <!--begin::Total price-->
                                <div class="font-semibold text-base">Actual Anggaran:
                                    <span id="totalSemuaProduk">0.00</span>
                                </div>
                                <!--end::Total price-->
                                <!--begin::Total price-->
                                <div class="font-semibold text-base">Hpp:
                                    <span id="totalHpp">0.00</span>
                                </div>
                                <!--end::Total price-->
                                <!--begin::Total price-->
                                <div class="font-semibold text-base">Profit:
                                    <span id="totalProfit">0.00</span>
                                </div>
                                <!--end::Total price-->
                            </div>
                            <!--end::Input group-->
                            {{-- <button class="variant erp-action mb-8" type="button"
                                onclick="addVariant()">
                                <i class="ph ph-plus text-xl"></i>Tambah Product
                            </button> --}}
                            <!--end::Table-->
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <span class="text-gray-400">Posting Parcel Terlebih dahulu</span>
                    </div>
                @endif
                <!--end::Card header-->
            </section>
            <!--end::Order details-->

            <div class="flex justify-end">
                <!--begin::Button-->
                <a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_edit_order_cancel"
                    class="erp-action me-4">Cancel</a>
                <!--end::Button-->

                @if (Request::segment(2) != 'show')
                    <input type="hidden" name="submit_type" id="submit_type" value="draft">
                    <button type="submit" class="erp-action erp-action--primary me-1.5" onclick="setSubmitType('draft')">
                        <span data-label>Draft</span>
                        <span data-progress hidden>Mohon tunggu...</span>
                    </button>

                    <button type="submit" class="erp-action erp-action--primary" onclick="setSubmitType('posting')">
                        <span data-label>Posting</span>
                        <span data-progress hidden>Mohon tunggu...</span>
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
                `<span class="erp-spinner" role="status" aria-hidden="true"></span> Loading...`
            );
        });

        // Pemilih produk dan penanggung jawab (ErpPicker menggantikan select2). Skrip ErpPicker `defer`, jadi pasang saat DOM siap.
        $(function() {
            ErpPicker.attach('#product_id', {
                title: 'Pilih Produk',
                placeholder: 'Cari produk...',
                placeholderTrigger: 'Pilih produk',
                emptyText: 'Produk tidak ditemukan',
                url: '{{ route('products.get-product-receipt') }}',
                map: item => ({ id: item.id, text: item.name })
            });
            ErpPicker.attach('#staff_id', {
                title: 'Pilih Penanggung Jawab',
                placeholder: 'Cari penanggung jawab...',
                placeholderTrigger: 'Pilih penanggung jawab',
                emptyText: 'Penanggung jawab tidak ditemukan',
                url: '{{ route('staff.get-staff') }}',
                map: item => ({ id: item.id, text: item.name })
            });
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

                $('#sisaAnggaran').text(sisa.toLocaleString('id-ID', {
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
