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


    <link rel="stylesheet" href="{{ asset('assets/css/pos-form.css') }}?v={{ filemtime(public_path('assets/css/pos-form.css')) }}">
    <!--begin::Aside column-->
    <div class="w-100 flex-lg-row-auto" x-data="posApp()" x-init="init()">
        <div class="pos-form pos-form--reference">
            <section class="pos-card pos-card--fields" aria-label="Cabang dan pelanggan">
                <div class="pos-field">
                    <label class="pos-field__label" for="branch_id"><i class="ph-bold ph-storefront" aria-hidden="true"></i>Cabang Transaksi <span class="pos-field__req" aria-hidden="true">*</span></label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value="">Pilih Branch</option>
                    </select>
                </div>
                <div class="pos-field">
                    <label class="pos-field__label" for="customer_id"><i class="ph-bold ph-user" aria-hidden="true"></i>Pelanggan <span class="pos-field__req" aria-hidden="true">*</span></label>
                    <div class="pos-customer">
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="">Pilih Pelanggan</option>
                        </select>
                        <button type="button" @click="addCustomer()" aria-label="Tambah pelanggan" class="pos-customer__add">
                            <i class="ph-bold ph-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                    <input type="hidden" name="tier_id" id="tier_id">
                </div>
            </section>

            <section class="pos-card pos-card--list" aria-labelledby="pos-items-title">
                <div class="pos-card__head">
                    <h2 id="pos-items-title">Daftar Belanja</h2>
                    <button type="button" class="pos-add" @click="openAddModal()" aria-label="Tambah item transaksi">+ Tambah</button>
                </div>
                <div class="pos-card__body pos-card__body--list">
                    @include('pos::pos.segment.cart')
                </div>
            </section>

            {{-- Footer total + simpan/bayar; lembar ringkasan memuat diskon, catatan, ongkir, dan pengaturan transaksi --}}
            @include('pos::pos.segment.ringkasan')
        </div>

            @include('pos::pos.segment.modal-product', ['reference' => true])
            @include('pos::pos.segment.modal-customer')
            @include('pos::pos.segment.modal-gift')
            @include('pos::pos.segment.modal-parcel', ['reference' => true])
            @include('pos::pos.segment.modal-jus', ['reference' => true])

    </div>
    <!--end::Aside column-->
    @include('pos::pos.js-create2')
@endsection
