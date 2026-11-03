@extends('template.root')
@section('title', 'Produksi Barang')

@section('content')
    <style>
        /* Animasi flying cart - produk terbang ke keranjang */
        @keyframes flyToCart {
            0% {
                transform: scale(1) translateX(0) translateY(0);
                opacity: 1;
            }

            50% {
                transform: scale(0.5) translateX(200px) translateY(-100px);
                opacity: 0.8;
            }

            100% {
                transform: scale(0.1) translateX(400px) translateY(-200px);
                opacity: 0;
            }
        }

        .fly-to-cart {
            animation: flyToCart 0.8s ease-in-out;
            pointer-events: none;
            z-index: 9999;
        }

        /* Animasi bounce untuk tombol */
        @keyframes bounceScale {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .bounce-add {
            animation: bounceScale 0.3s ease-in-out;
        }

        /* Animasi shake untuk badge */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .shake-badge {
            animation: shake 0.5s ease-in-out;
        }

        /* Animasi pulse untuk badge */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        .pulse-badge {
            animation: pulse 0.4s ease-in-out;
        }

        /* Ingredient item hover effects */
        .ingredient-item {
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .ingredient-item:hover {
            border-color: var(--erp-primary);
            box-shadow: 0 4px 8px rgba(11, 89, 91, 0.12);
            transform: translateY(-2px);
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .hover-bg-light-warning:hover {
            background-color: #fff8dd !important;
        }
    </style>

    <!--begin::Main column-->
    <div class="w-100 flex-lg-row-auto" x-data="productionApp()" x-init="init()">
        <!-- Header Information Card -->
        <div class="erp-editor erp-operations-editor"><div class="erp-editor-main"><section class="erp-editor-section"><header><i class="ph ph-factory" aria-hidden="true"></i><h2>Informasi produksi</h2></header><div class="erp-editor-body">
            <div class="d-flex flex-column gap-10 mb-3">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-6">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Pilih Cabang</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Pilih Branch</option>
                            </select>
                            <!--end::Editor-->
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Tanggal Produksi</label>
                            <!--end::Label-->
                            <!--begin::Input-->
                            <input type="date" class="form-control" name="production_date"
                                value="{{ old('production_date') ?? date('Y-m-d') }}">
                            <!--end::Input-->
                        </div>
                    </div>
                </div>
                <!--end::Input group-->
            </div>

            <div class="d-flex flex-column gap-10 mb-3">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-6">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Pilih Produk</label>
                            <!--end::Label-->
                            <!--begin::Select-->
                            <select name="product_id" id="product_id" class="form-select">
                                @if (isset($edit_product_id) && isset($edit_product_name))
                                    <option value="{{ $edit_product_id }}" selected>{{ $edit_product_name }}</option>
                                @elseif (isset($receipt) && $receipt != null)
                                    <option value="{{ $receipt->id }}" selected>{{ $receipt->products->name }}</option>
                                @else
                                    <option value="">Pilih Produk</option>
                                @endif
                            </select>
                            <!--end::Select-->
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Harga Jual</label>
                            <!--end::Label-->
                            <!--begin::Select-->
                            <input type="text" inputmode="numeric" name="sell_price" id="sell_price" class="form-control format-number"
                                placeholder="Masukkan Jumlah" x-model="sellPrice"
                                value="{{ isset($edit_sell_price) ? number_format($edit_sell_price, 0, ',', '.') : '' }}" />
                            <input type="hidden" name="production_number" id="production_number" class="form-control"
                                value="{{ $production_number }}">
                            <input type="hidden" name="id" id="production_id" class="form-control"
                                value="{{ isset($id) ? $id : '' }}">
                            <!--end::Select-->
                        </div>
                    </div>
                    {{-- <div class="col-3 mt-8">
                        <button type="button" @click="refreshProduct()"
                            class="btn btn-outline btn-outline-dashed btn-outline-primary btn-active-light-primary">
                            <i class="fa-solid fa-refresh"></i>
                        </button>
                    </div> --}}
                </div>
                <!--end::Input group-->
            </div>

            <div class="d-flex flex-column gap-10 mb-3">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-6">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Jumlah Produksi</label>
                            <!--end::Label-->
                            <!--begin::Input-->
                            <input type="number" inputmode="decimal" name="quantity" id="quantity" class="form-control"
                                placeholder="Masukkan Jumlah" step="0.01" x-model="productionQuantity"
                                value="{{ isset($data) ? $data->quantity : '' }}" />
                            <!--end::Input-->
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="form-label">Biaya Jasa Produksi per Unit</label>
                            <!--end::Label-->
                            <!--begin::Input-->
                            <input type="text" inputmode="numeric" name="service_cost" id="service_cost" class="form-control format-number"
                                placeholder="Masukkan Biaya Jasa" x-model="serviceCost"
                                value="{{ isset($edit_service_cost) ? number_format($edit_service_cost, 0, ',', '.') : '' }}" />
                            <!--end::Input-->
                        </div>
                    </div>
                </div>
                <!--end::Input group-->
            </div>
        </div></section><section class="erp-editor-section"><header><i class="ph ph-package" aria-hidden="true"></i><h2>Bahan produksi</h2></header><div class="erp-editor-body">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fs-5 fw-bold d-flex">Bahan Baku yang Digunakan</span>
                    <span class="text-danger">Diperbarui per {{ date('d/m/Y') }}</span>
                    <small class="text-muted d-block mt-1">
                        <i class="ki-duotone ki-information-5 fs-7 text-info me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        Klik pada bahan untuk edit jumlah
                    </small>
                </div>
                <div>
                    <button type="button" class="btn btn-outline btn-outline-dashed btn-outline-primary"
                        @click="openAddIngredientModal()" aria-label="Tambah bahan produksi">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                {{-- <div class="btn-group">
                    <button type="button" class="btn btn-outline btn-outline-dashed btn-outline-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-plus"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <button class="dropdown-item" type="button" @click="openAddIngredientModal()" aria-label="Tambah bahan produksi">
                                <i class="ki-duotone ki-purchase text-success me-2 fs-5"></i> Tambah Bahan
                            </button>
                        </li>
                    </ul>
                </div> --}}
            </div>

            <!-- Ingredients Cart -->
            @include('transaction::production.segment.ingredients-cart')
        </div></section></div><aside class="erp-editor-summary"><h2>Ringkasan produksi</h2>
            @include('transaction::production.segment.summary')
        </aside></div>

        <!-- Modals -->
        @include('transaction::production.segment.modal-ingredient')
        @include('transaction::production.segment.modal-recipe')
    </div>
    <!--end::Main column-->

    @include('transaction::production.js-create')
@endsection
