@extends('layouts.erp')

@section('content')
    <form id="kt_ecommerce_edit_order_form" class="form flex flex-col lg:flex-row"
        action="{{ route('wholesale-save-receive') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <!--begin::Main column-->
        <div class="flex flex-col lg:grow lg:shrink-0 lg:basis-auto gap-6 lg:gap-8">
            <!--begin::Order details-->
            <div class="card card-flush py-3">
                <!--begin::Card header-->
                <div class="card-header">
                    <div class="card-title">
                        <h2>Select Products</h2>
                    </div>
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <div class="flex flex-col gap-8">

                        <!--begin::Separator-->
                        <div class="border-t border-gray-100"></div>
                        <!--end::Separator-->
                        <!--begin::Table-->
                        <table class="erp-table"
                            id="kt_ecommerce_edit_order_product_table">
                            <thead>
                                <tr class="text-start text-gray-400 font-semibold text-xs uppercase gs-0">
                                    <th class="min-w-[200px]">Product</th>
                                    <th class="min-w-[50px] text-end pe-4">Quantity</th>
                                    <th class="min-w-[100px] pe-4">Price</th>
                                </tr>
                            </thead>
                            <input type="hidden" name="wholesale_id" id="wholesale_id" value="{{ $data->id }}" />
                            <tbody class="font-medium text-gray-500">
                                @foreach ($selectedProducts as $product)
                                    {{-- {{ dd($product) }} --}}
                                    <tr>
                                        <td>
                                            <div class="flex items-center"
                                                data-order-filter="product"
                                                data-order-id="{{ $product['id'] }}">
                                                <!--begin::Thumbnail-->
                                                <a href="{{ url('products/' . $product['id']) }}"
                                                    class="symbol symbol-50px">
                                                    <span class="symbol-label"
                                                        style="background-image:url({{ asset('storage/' . $product['image']) }});"></span>
                                                </a>
                                                <!--end::Thumbnail-->
                                                <div class="ms-4">
                                                    <!--begin::Title-->
                                                    <a href="{{ url('products/' . $product['id']) }}"
                                                        class="text-gray-800 hover:text-teal text-[15px] font-semibold">{{ $product['name'] }}</a>
                                                    <!--end::Title-->
                                                    <!--begin::SKU-->
                                                    <div class="text-gray-400 text-xs">SKU: {{ $product['sku'] }}</div>
                                                    <!--end::SKU-->
                                                    <!--begin::Unit-->
                                                    <div class="text-gray-400 text-xs">Product Unit: <span
                                                            class="badge badge-light-success">{{ $product['unit'] }}</span>
                                                    </div>
                                                    <!--end::Unit-->
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4" data-order="42">
                                            <input type="number" class="erp-input text-end"
                                                name="products[{{ $product['id'] }}][quantity]" value="{{ $product['qty'] }}"
                                                placeholder="0" />
                                        </td>
                                        <td class="pe-4" data-order="42">
                                            <input type="number" class="erp-input"
                                                name="products[{{ $product['id'] }}][price]" value="" placeholder="0" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                </div>
                <!--end::Card header-->
            </div>
            <!--end::Order details-->

            <div class="flex justify-end">
                <!--begin::Button-->
                <a href="apps/ecommerce/catalog/products.html" id="kt_ecommerce_edit_order_cancel"
                    class="erp-action me-4">Cancel</a>
                <!--end::Button-->
                <!--begin::Button-->
                <button type="submit" id="kt_ecommerce_edit_order_submit" class="erp-action erp-action--primary">
                    <span data-label>Save Changes</span>
                    <span data-progress hidden>Mohon tunggu...</span>
                </button>
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
@endsection
