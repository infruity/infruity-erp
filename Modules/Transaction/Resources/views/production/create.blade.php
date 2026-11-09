@extends('template.root')
@section('title', 'Produksi')

@section('content')
    @php($productionCreateReference = request()->routeIs('production.create'))
    @if($productionCreateReference)
        <link rel="stylesheet" href="{{ asset('assets/css/production-form-reference.css') }}?v={{ filemtime(public_path('assets/css/production-form-reference.css')) }}">
    @endif
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

    <style>
        .production-form { display:flex; flex-direction:column; gap:16px; max-width:896px; margin:0 auto; }
        .production-card { overflow:clip; border:1px solid #e5e7eb; border-radius:16px; background:#fff; }
        .production-card__head { display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding:16px 20px; }
        .production-card__title { display:flex; align-items:center; gap:10px; min-width:0; }
        .production-card__step { display:flex; width:28px; height:28px; flex:none; align-items:center; justify-content:center; border-radius:9px; background:#ecfdf5; color:#0b595b; font-size:13px; font-weight:700; }
        .production-card__title h2 { margin:0; color:#111827; font-size:14px; font-weight:800; }
        .production-card__title small { color:#9ca3af; font-size:12px; font-weight:700; }
        .production-card__hint { color:#9ca3af; font-size:11px; font-weight:500; }
        .production-card__body { padding:16px 20px 20px; }
        .production-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .production-fields .is-wide { grid-column:1 / -1; }
        .production-form .form-label { margin-bottom:6px; color:#374151; font-size:12px; font-weight:700; }
        .production-form .form-control, .production-form .form-select { min-height:44px; border-radius:12px; font-size:13px; }
        .production-add { display:inline-flex; align-items:center; gap:6px; border:0; border-radius:999px; padding:7px 14px; background:#ecfdf5; color:#0b595b; font-size:12px; font-weight:700; }
        .production-add:hover { background:#d1fae5; }
        .production-form-footer { position:sticky; bottom:0; z-index:15; margin-top:4px; border-top:1px solid #eef0f3; background:rgba(255,255,255,.97); padding:12px 20px calc(14px + env(safe-area-inset-bottom)); -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); }
        .production-form-footer__inner { max-width:896px; margin:0 auto; }
        .production-total { display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding-bottom:12px; }
        .production-total__label { display:flex; align-items:center; gap:8px; color:#111827; font-size:14px; font-weight:800; }
        .production-total__count { border-radius:999px; background:#d1fae5; padding:2px 9px; color:#065f46; font-size:12px; font-weight:800; }
        .production-total__value { text-align:right; }
        .production-total__value strong { display:block; color:#0b595b; font-size:17px; font-weight:800; }
        .production-total__value span { color:#9ca3af; font-size:11px; }
        .production-actions { display:flex; gap:12px; padding-top:12px; }
        .production-actions .btn { flex:1; min-height:44px; border-radius:12px; font-size:13px; font-weight:700; }
        @media (max-width:640px) {
            .production-fields { grid-template-columns:minmax(0,1fr); gap:14px; }
            .production-card__head, .production-card__body { padding-right:16px; padding-left:16px; }
        }
    </style>

    <!--begin::Main column-->
    <div class="w-100 flex-lg-row-auto {{ $productionCreateReference ? 'production-create-reference' : '' }}" @if($productionCreateReference) data-reference-product-url="{{ route('products.get-receipt') }}" @endif x-data="productionApp()" x-init="init()">
        <div class="production-form">
            @if($productionCreateReference && isset($data) && $data?->status === 'temp')
                <p class="production-resume-note" role="status">Melanjutkan draf sementara #{{ $data->production_number }}. Menyimpan akan memperbarui draf ini.</p>
            @endif
            <!-- Kartu 1: informasi hasil produksi -->
            <section class="production-card" aria-labelledby="production-info-title">
                <div class="production-card__head">
                    <div class="production-card__title">
                        <span class="production-card__step" aria-hidden="true">1</span>
                        <h2 id="production-info-title">Informasi Hasil Produksi</h2>
                    </div>
                    <span class="production-card__hint">* Wajib diisi</span>
                </div>
                <div class="production-card__body">
                    <div class="production-fields">
                        <div class="fv-row">
                            <label class="required form-label" for="branch_id">Cabang / Dapur</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Pilih Branch</option>
                            </select>
                        </div>
                        @unless($productionCreateReference)
                        <div class="fv-row">
                            <label class="required form-label" for="production_date">Tanggal Produksi</label>
                            <input type="date" class="form-control" id="production_date" name="production_date"
                                value="{{ old('production_date') ?? date('Y-m-d') }}">
                        </div>
                        @endunless
                        <div class="fv-row {{ $productionCreateReference ? 'production-product-picker-field' : '' }}">
                            <label class="required form-label" for="product_id" @if($productionCreateReference) id="production-product-label" @endif>Produk yang Diproduksi</label>
                            <select name="product_id" id="product_id" class="form-select" @if($productionCreateReference) data-placeholder="Pilih produk olahan..." @endif>
                                @if (isset($edit_product_id) && isset($edit_product_name))
                                    <option value="{{ $edit_product_id }}" selected>{{ $edit_product_name }}</option>
                                @elseif (isset($receipt) && $receipt != null)
                                    <option value="{{ $receipt->id }}" selected>{{ $receipt->products->name }}</option>
                                @else
                                    <option value="">{{ $productionCreateReference ? 'Pilih produk olahan...' : 'Pilih Produk' }}</option>
                                @endif
                            </select>
                            @if($productionCreateReference)
                                <button type="button" class="production-product-picker-trigger" @click="openReferenceProductPicker()"
                                    aria-haspopup="dialog" aria-controls="reference-product-picker"
                                    :aria-label="referenceSelectedProductName ? 'Ganti produk: ' + referenceSelectedProductName : 'Pilih produk olahan'">
                                    <span class="production-product-picker-icon" aria-hidden="true"><i class="ph ph-bag"></i></span>
                                    <span class="production-product-picker-label" :class="referenceSelectedProductName ? 'is-selected' : ''" x-text="referenceSelectedProductName || 'Pilih produk olahan...'"></span>
                                    <span class="production-product-picker-action"><span x-text="referenceSelectedProductName ? 'Ganti' : 'Pilih'"></span><i class="ph-bold ph-caret-down" aria-hidden="true"></i></span>
                                </button>
                            @endif
                        </div>
                        <div class="fv-row">
                            <label class="required form-label" for="quantity">{{ $productionCreateReference ? 'Target Hasil Produksi' : 'Jumlah Produksi' }}</label>
                            <input type="number" inputmode="decimal" name="quantity" id="quantity" class="form-control"
                                placeholder="Masukkan Jumlah" step="0.01" max="9999" x-model="productionQuantity"
                                value="{{ isset($data) ? $data->quantity : '' }}" />
                            @if($productionCreateReference)
                                <span class="production-quantity-unit" aria-hidden="true">PORSI</span>
                            @endif
                        </div>
                        @unless($productionCreateReference)
                        <div class="fv-row">
                            <label class="form-label" for="sell_price">Harga Jual</label>
                            <input type="text" inputmode="numeric" name="sell_price" id="sell_price" class="form-control format-number"
                                placeholder="Masukkan Harga Jual" x-model="sellPrice"
                                value="{{ isset($edit_sell_price) ? number_format($edit_sell_price, 0, ',', '.') : '' }}" />
                            <input type="hidden" name="production_number" id="production_number" class="form-control"
                                value="{{ $production_number }}">
                            <input type="hidden" name="id" id="production_id" class="form-control"
                                value="{{ isset($id) ? $id : '' }}">
                        </div>
                        @endunless
                        @unless($productionCreateReference)
                        <div class="fv-row">
                            <label class="form-label" for="service_cost">Biaya Jasa Produksi per Unit</label>
                            <input type="text" inputmode="numeric" name="service_cost" id="service_cost" class="form-control format-number"
                                placeholder="Masukkan Biaya Jasa" x-model="serviceCost"
                                value="{{ isset($edit_service_cost) ? number_format($edit_service_cost, 0, ',', '.') : '' }}" />
                        </div>
                        @endunless
                        <div class="fv-row is-wide">
                            <label class="form-label" for="production_notes">Catatan Produksi (Opsional)</label>
                            <input type="text" class="form-control" id="production_notes" name="notes"
                                placeholder="Contoh: Batch pagi, untuk display etalase" x-model="notes">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Kartu 2: bahan baku terpakai -->
            <section class="production-card" aria-labelledby="production-ingredients-title">
                <div class="production-card__head">
                    <div class="production-card__title">
                        <span class="production-card__step" aria-hidden="true">2</span>
                        <h2 id="production-ingredients-title">Bahan Baku Terpakai <small x-text="'(' + ingredients.length + ' item)'"></small></h2>
                    </div>
                    <button type="button" class="production-add" @click="openAddIngredientModal()" aria-label="Tambah bahan produksi">
                        <i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Bahan</span>
                    </button>
                </div>
                <div class="production-card__body">
                    @unless($productionCreateReference)
                    <small class="text-muted d-block mb-3">Diperbarui per {{ date('d/m/Y') }} · ketuk bahan untuk mengubah jumlah.</small>
                    @endunless
                    @include('transaction::production.segment.ingredients-cart')
                </div>
            </section>

            @if($productionCreateReference)
                @include('transaction::production.segment.reference-extra-fields')
            @endif

            <!-- Footer: total biaya dan aksi simpan -->
            @include('transaction::production.segment.summary')
        </div>

        <!-- Modals -->
        @include('transaction::production.segment.modal-ingredient')
        @include('transaction::production.segment.modal-recipe')
        @if($productionCreateReference)
            @include('transaction::production.segment.reference-product-picker')
        @endif
    </div>
    <!--end::Main column-->

    @include('transaction::production.js-create')
@endsection
