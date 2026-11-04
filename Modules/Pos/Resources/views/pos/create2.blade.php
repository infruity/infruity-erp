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
