@extends('layouts.erp')
@section('title', 'Proses Parcel')
@section('page-title', 'Proses Parcel')

@section('content')
    <form id="kt_ecommerce_edit_order_form" class="form flex flex-col lg:flex-row"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Aside column-->
        <div class="w-full flex-lg-row-auto lg:w-[300px] mb-6 me-6 lg:me-8">
            <!--begin::Order details-->
            <section class="erp-editor-section py-3">
                <!--begin::Card header-->
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Detail Parcel</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="erp-editor-body pt-0">
                    <div class="flex flex-col gap-8">
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label">Nomor Pesanan</label>
                            <!--end::Label-->
                            <!--begin::Auto-generated ID-->
                            <div class="font-semibold text-lg">
                                #{{ isset($data) ? $data->production_number : '14364' }}
                                <span class="badge badge-info">{{ $data->quantity ?? 0 }} Pcs</span>
                            </div>
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label erp-label--required">Tanggal Pesanan</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <div class="font-semibold text-lg">{{ isset($data) ? dateindo($data->production_date) : '14364' }}</div>
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="erp-label erp-label--required">Anggaran</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <div class="font-semibold text-lg">
                                <span id="sisaAnggaran"></span>
                            </div>
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                    </div>
                </div>
                <!--end::Card header-->
            </section>
            <!--end::Order details-->
        </div>
        <!--end::Aside column-->
        <!--begin::Main column-->
        <div class="flex flex-col lg:grow lg:shrink-0 lg:basis-auto gap-6 lg:gap-8">
            <!--begin::Order details-->
            <section class="erp-editor-section py-3">
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Tambah Produk</h2>
                    </div>
                </div>
                <!--begin::Card body-->
                <div class="erp-editor-body pt-0">
                    <div class="flex flex-col gap-8">
                        <!--begin::Input group-->
                        <div class="fv-row mb-1.5">
                            <!--begin::Label-->
                            <label class="text-sm font-medium mb-1.5">Jenis Perhitungan
                                <span class="ms-1" title="Pilih tipe perhitungan yang akan digunakan">
                                    <i class="ph ph-info text-gray-400 text-sm">
                                        
                                        
                                        
                                    </i>
                                </span></label>
                            <!--End::Label-->
                            <!--begin::Row-->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                <!--begin::Col-->
                                <div>
                                    <!--begin::Option-->
                                    <label class="erp-action flex !justify-start gap-3 !rounded-xl p-4 text-left has-[:checked]:border-teal has-[:checked]:bg-teal/5">
                                        <!--begin::Radio-->
                                        <input class="erp-radio mt-1" type="radio" name="calculation_type"
                                                value="weight_to_price" />
                                        <!--end::Radio-->
                                        <!--begin::Info-->
                                        <span class="ms-4">
                                            <span class="text-base font-semibold text-gray-800 block">Berat ke Harga</span>
                                        </span>
                                        <!--end::Info-->
                                    </label>
                                    <!--end::Option-->
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div>
                                    <!--begin::Option-->
                                    <label class="erp-action flex !justify-start gap-3 !rounded-xl p-4 text-left has-[:checked]:border-teal has-[:checked]:bg-teal/5">
                                        <!--begin::Radio-->
                                        <input class="erp-radio mt-1" type="radio" name="calculation_type"
                                                value="price_to_weight" checked="checked" />
                                        <!--end::Radio-->
                                        <!--begin::Info-->
                                        <span class="ms-4">
                                            <span class="text-base font-semibold text-gray-800 block">Harga ke Berat</span>
                                        </span>
                                        <!--end::Info-->
                                    </label>
                                    <!--end::Option-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Search products-->
                        <div class="flex items-center relative -mb-6">
                            <i class="ph ph-magnifying-glass text-lg absolute ms-3">
                                
                                
                            </i>
                            <input type="text" data-order-filter="search"
                                class="erp-input w-full lg:w-1/2 ps-10" id="search"
                                placeholder="Cari produk" />
                        </div>
                        <!--end::Search products-->
                        <!--begin::Table-->
                        <table class="erp-table"
                            id="kt_ecommerce_edit_order_product_table">
                            <thead>
                                <tr class="text-start text-gray-400 font-semibold text-xs uppercase gs-0">
                                    {{-- <th class="w-[25px] pe-1.5"></th> --}}
                                    <th class="min-w-[200px]">Product</th>
                                    <th class="min-w-[100px] text-end pe-4">Stock</th>
                                </tr>
                            </thead>
                            <tbody class="font-medium text-gray-500">

                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                </div>
                <!--end::Card header-->
            </section>
            <!--end::Order details-->
            <!--begin::Order details-->
            <section class="erp-editor-section py-3">
                <!--begin::Card header-->
                <div class="erp-detail-header">
                    <div class="card-title">
                        <h2>Preview Products</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="erp-editor-body pt-0">
                    <div class="flex flex-col gap-8">
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="erp-label">Add products to this order</label>
                            <!--end::Label-->
                            <!--begin::Selected products-->
                            <div class="overflow-x-auto">
                                <table class="erp-table mb-4"
                                    id="kt_ecommerce_edit_order_selected_products_table">
                                    <thead>
                                        <tr class="text-start text-gray-300 font-semibold text-xs uppercase gs-0">
                                            <th class="min-w-[200px]">Product</th>
                                            <th class="min-w-[100px]">Hpp</th>
                                            <th class="min-w-[100px]">Total</th>
                                            <th class="min-w-[100px] text-end"></th>
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
                            </div>

                            <!-- Tempat hidden input -->
                            <div id="selected-products-hidden"></div>
                            <!--begin::Selected products-->
                            <table class="erp-table">
                                <tr>
                                    <td>Anggaran</td>
                                    <td>:</td>
                                    <td><span id="">Rp {{ tonumber($data->budget) }}</span></td>
                                </tr>
                                <tr>
                                    <td>Actual Anggaran</td>
                                    <td>:</td>
                                    <td><span id="totalSemuaProduk">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Biaya jasa</td>
                                    <td>:</td>
                                    <td><span id="totalFee">Rp {{ toNumber($data->quantity * $data->fee) }}</span></td>
                                </tr>
                                <tr>
                                    <td>Hpp</td>
                                    <td>:</td>
                                    <td><span id="totalHpp">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Total Actual Anggaran</td>
                                    <td>:</td>
                                    <td><span id="totalActualAnggaran">0.00</span></td>
                                </tr>
                                <tr>
                                    <td>Profit</td>
                                    <td>:</td>
                                    <td><span id="totalProfit">0.00</span></td>
                                </tr>
                            </table>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Separator-->
                        <div class="border-t border-gray-100"></div>
                        <!--end::Separator-->
                    </div>
                </div>
                <!--end::Card header-->
            </section>
            <!--end::Order details-->
            <!-- Modal for Jumlah Input -->
            <x-erp.dialog id="modalInputQty" title="Input Product Details" size="sm">
                <input type="hidden" id="inputProductId">
                <input type="hidden" id="typeList">
                <x-erp.field label="Jumlah" for="inputJumlah" class="mb-2.5"><input type="number" step="0.01" class="erp-input" id="inputJumlah" placeholder="Enter quantity"></x-erp.field>
                <x-erp.field label="Harga Jual" for="inputSellPrice" class="mb-2.5"><input type="text" class="erp-input format-number" id="inputSellPrice" placeholder="Enter price" min="0"></x-erp.field>
                <x-slot:footer>
                    <div class="ml-auto flex gap-2">
                        <x-erp.button data-erp-close>Cancel</x-erp.button>
                        <x-erp.button variant="primary" id="submitQty">Add Product</x-erp.button>
                    </div>
                </x-slot:footer>
            </x-erp.dialog>

            <input type="hidden" name="submit_type" id="submit_type" value="draft">
            <div class="flex justify-end">
                <!--begin::Button-->
                <x-erp.button :href="url(Request::segment(1))" id="kt_ecommerce_edit_order_cancel" class="me-4">Cancel</x-erp.button>
                <!--end::Button-->

                <x-erp.button variant="primary" id="kt_ecommerce_edit_order_submit"
                    :onclick="'setSelesai(' . (int) $data->id . ')'">
                    <span data-label>Selesai</span>
                    <span data-progress hidden>Mohon tunggu...</span>
                </x-erp.button>
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
    <!-- Modal for Jumlah Input -->
    <x-erp.dialog id="kt_modal_add_customer" title="Edit Detail" size="sm">
        <form id="kt_modal_add_customer_form" action="{{ url(Request::segment(1)) }}">
            @csrf
            <input type="hidden" name="_method" id="methodField" value="">
            <x-erp.field label="Jumlah" for="inputJumlahEdit" class="mb-2.5"><input type="number" class="erp-input" id="inputJumlahEdit" name="qty" placeholder="Enter quantity" step="0.01"></x-erp.field>
            <x-erp.field label="Harga Jual" for="inputPriceEdit" class="mb-2.5"><input type="text" class="erp-input format-number" id="inputPriceEdit" name="sell_price" placeholder="Enter price" min="0"></x-erp.field>
        </form>
        <x-slot:footer>
            <div class="ml-auto flex gap-2">
                <x-erp.button data-erp-close>Close</x-erp.button>
                <x-erp.button variant="primary" type="submit" form="kt_modal_add_customer_form" id="kt_modal_add_customer_submit"><span data-label>Simpan</span><span data-progress hidden>Mohon tunggu...</span></x-erp.button>
            </div>
        </x-slot:footer>
    </x-erp.dialog>

    <x-erp.dialog id="modalInputPrc" title="Edit Detail" size="sm">
        <form id="modalInputPrcForm" action="{{ url(Request::segment(1)) }}">
            @csrf
            <input type="hidden" name="_method" id="methodFieldPrc" value="">
            <input type="hidden" id="inputProductIdPrc" name="id">
            <input type="hidden" name="production_id" value="{{ $data->id }}">
            <input type="hidden" id="typeList">
            <x-erp.field label="Masukkan Harga" for="inputPrice" class="mb-2.5"><input type="text" class="erp-input format-number" id="inputPrice" placeholder="Enter price" min="0"></x-erp.field>
            <x-erp.field label="Jumlah" for="inputJumlahPrc" class="mb-2.5"><input type="number" name="qty" step="0.01" class="erp-input" id="inputJumlahPrc" placeholder="Enter quantity" readonly></x-erp.field>
            <x-erp.field label="Harga Jual" for="inputSellPricePrc" class="mb-2.5"><input type="text" name="sell_price" class="erp-input format-number" id="inputSellPricePrc" placeholder="Enter price" min="0" readonly></x-erp.field>
        </form>
        <x-slot:footer>
            <div class="ml-auto flex gap-2">
                <x-erp.button data-erp-close>Close</x-erp.button>
                <x-erp.button variant="primary" type="submit" form="modalInputPrcForm" id="kt_modal_add_customer_submit_prc"><span data-label>Simpan</span><span data-progress hidden>Mohon tunggu...</span></x-erp.button>
            </div>
        </x-slot:footer>
    </x-erp.dialog>
    @include('transaction::production.js-process')
@endsection
