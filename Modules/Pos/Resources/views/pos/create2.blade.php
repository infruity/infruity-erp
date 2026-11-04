@extends('template.root')
@section('title', 'Transaksi penjualan')

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
    </style>


    <style>
        .pos-form { display:flex; flex-direction:column; gap:16px; max-width:896px; margin:0 auto; }
        .pos-card { overflow:clip; border:1px solid #e5e7eb; border-radius:16px; background:#fff; }
        .pos-card--flush .accordion-item { border:0; }
        .pos-card__head { display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding:14px 20px; }
        .pos-card__head h2 { margin:0; color:#111827; font-size:14px; font-weight:800; }
        .pos-card__hint { color:#9ca3af; font-size:11px; }
        .pos-card__body { padding:16px 20px 20px; }
        .pos-card__body--list { padding:4px 20px 12px; }
        .pos-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .pos-fields .is-wide { grid-column:1 / -1; }
        .pos-form .form-label { margin-bottom:6px; color:#374151; font-size:12px; font-weight:700; }
        .pos-form .form-control, .pos-form .form-select { min-height:44px; border-radius:12px; font-size:13px; }
        .pos-customer { display:flex; align-items:stretch; gap:8px; }
        .pos-customer .select2-container { flex:1; min-width:0; }
        .pos-customer__add, .pos-add { display:inline-flex; align-items:center; justify-content:center; gap:6px; flex:none; border:0; border-radius:12px; padding:0 14px; background:#ecfdf5; color:#0b595b; font-size:12px; font-weight:700; }
        .pos-add { min-height:34px; border-radius:999px; }
        .pos-customer__add:hover, .pos-add:hover { background:#d1fae5; }
        .pos-cart-row { display:flex; width:100%; flex-direction:column; gap:4px; border:0; border-bottom:1px solid #f1f3f5; background:transparent; padding:12px 8px; text-align:left; }
        .pos-cart-row:last-child { border-bottom:0; }
        .pos-cart-row:hover { background:#f9fafb; }
        .pos-cart-top, .pos-cart-bottom { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .pos-cart-name { display:flex; min-width:0; flex-wrap:wrap; align-items:center; gap:6px; }
        .pos-cart-name strong { color:#111827; font-size:14px; font-weight:700; overflow-wrap:anywhere; }
        .pos-cart-row:hover .pos-cart-name strong { color:#0b595b; }
        .pos-cart-tag { border-radius:6px; padding:1px 6px; font-size:10px; font-weight:700; }
        .pos-cart-tag.is-parcel { background:#fef3c7; color:#92400e; }
        .pos-cart-tag.is-discount { background:#fee2e2; color:#b91c1c; }
        .pos-cart-total { flex:none; color:#0b595b; font-size:14px; font-weight:800; font-variant-numeric:tabular-nums; }
        .pos-cart-bottom { color:#6b7280; font-size:12px; font-variant-numeric:tabular-nums; }
        .pos-form-footer { position:sticky; bottom:0; z-index:15; margin-top:4px; border-top:1px solid #eef0f3; background:rgba(255,255,255,.98); padding:12px 20px calc(14px + env(safe-area-inset-bottom)); }
        .pos-form-footer__inner { max-width:896px; margin:0 auto; }
        .pos-form-footer .btn { min-height:44px; border-radius:12px; font-size:13px; font-weight:700; }
        @media (max-width:640px) {
            .pos-fields { grid-template-columns:minmax(0,1fr); gap:14px; }
            .pos-card__head, .pos-card__body { padding-right:16px; padding-left:16px; }
        }
    </style>
    <!--begin::Aside column-->
    <div class="w-100 flex-lg-row-auto" x-data="posApp()" x-init="init()">
        <div class="pos-form">
            <section class="pos-card" aria-labelledby="pos-info-title">
                <div class="pos-card__head">
                    <h2 id="pos-info-title">Informasi Transaksi</h2>
                    <span class="pos-card__hint">* Wajib diisi</span>
                </div>
                <div class="pos-card__body">
                    <div class="pos-fields">
                        <div class="fv-row">
                            <label class="required form-label" for="branch_id">Cabang Transaksi</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Pilih Branch</option>
                            </select>
                        </div>
                        <div class="fv-row">
                            <label class="required form-label" for="branch_process_id">Di Proses oleh:</label>
                            <select class="form-select" id="branch_process_id" name="branch_process_id">
                                <option value="">Pilih Branch</option>
                            </select>
                        </div>
                        <div class="fv-row is-wide">
                            <label class="required form-label" for="customer_id">Pelanggan</label>
                            <div class="pos-customer">
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">Pilih Pelanggan</option>
                                </select>
                                <button type="button" @click="addCustomer()" aria-label="Tambah pelanggan" class="pos-customer__add">
                                    <i class="ph-bold ph-plus" aria-hidden="true"></i><span>Baru</span>
                                </button>
                            </div>
                            <input type="hidden" name="tier_id" id="tier_id">
                        </div>
                        <div class="fv-row">
                            <label class="required form-label" for="pos_date">Tanggal Transaksi</label>
                            <input type="date" class="form-control" id="pos_date" name="date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="fv-row">
                            <label class="required form-label" for="invoice_number">Nomor Faktur</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ $invoice_number }}"
                                readonly>
                        </div>
                    </div>
                </div>
            </section>

            <section class="pos-card" aria-labelledby="pos-items-title">
                <div class="pos-card__head">
                    <h2 id="pos-items-title">Daftar Belanja</h2>
                    <div class="dropdown">
                        <button type="button" class="pos-add" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tambah item transaksi">
                            <i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button type="button" class="dropdown-item" @click="openAddModal()"><i class="ph ph-orange me-2" aria-hidden="true"></i>Buah</button></li>
                            <li><button type="button" class="dropdown-item" @click="openParcelModal()"><i class="ph ph-gift me-2" aria-hidden="true"></i>Parcel</button></li>
                            <li><button type="button" class="dropdown-item" @click="openJusModal()"><i class="ph ph-cup me-2" aria-hidden="true"></i>Jus</button></li>
                        </ul>
                    </div>
                </div>
                <div class="pos-card__body pos-card__body--list">
                    @include('pos::pos.segment.cart')
                </div>
            </section>

            {{-- Rincian total (diskon, catatan, ongkir) dan footer simpan/bayar --}}
            @include('pos::pos.segment.ringkasan')
        </div>

            @include('pos::pos.segment.modal-product')
            @include('pos::pos.segment.modal-customer')
            @include('pos::pos.segment.modal-gift')
            @include('pos::pos.segment.modal-parcel')
            @include('pos::pos.segment.modal-jus')

    </div>
    <!--end::Aside column-->
    @include('pos::pos.js-create2')
@endsection
