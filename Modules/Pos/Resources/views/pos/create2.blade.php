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

    <!--begin::Aside column-->
    <div class="w-100 flex-lg-row-auto" x-data="posApp()" x-init="init()">
        <div class="erp-editor"><div class="erp-editor-main"><section class="erp-editor-section"><header><i class="ph ph-identification-card" aria-hidden="true"></i><h2>Informasi transaksi</h2></header><div class="erp-editor-body">
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
                            <label class="required form-label">Di Proses oleh:</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <select class="form-select" id="branch_process_id" name="branch_process_id">
                                <option value="">Pilih Branch</option>
                            </select>
                            <!--end::Editor-->
                        </div>
                    </div>
                </div>
                <!--end::Input group-->
            </div>
            <div class="d-flex flex-column gap-10 mb-3">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-9">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Pilih Pelanggan</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <select class="form-select" id="customer_id" name="customer_id">
                                <option value="">Pilih Pelanggan</option>
                            </select>
                            <!--end::Editor-->
                        </div>
                    </div>
                    <input type="hidden" name="tier_id" id="tier_id">
                    <div class="col-3 mt-8">
                        <button @click="addCustomer()" aria-label="Tambah pelanggan"
                            class="btn btn-outline btn-outline-dashed btn-outline-primary btn-active-light-primary">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>

                </div>
                <!--end::Input group-->
            </div>
            <div class="d-flex flex-column gap-10">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Tanggal Transaksi</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}">
                            <!--end::Editor-->
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Nomor Faktur</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control" name="invoice_number" value="{{ $invoice_number }}"
                                readonly>
                            <!--end::Editor-->
                        </div>
                    </div>
                </div>
                <!--end::Input group-->
            </div>
        </div></section><section class="erp-editor-section"><header><i class="ph ph-shopping-cart" aria-hidden="true"></i><h2>Item transaksi</h2><div style="margin-left:auto"><div class="btn-group">
                        <button type="button"
                            class="btn btn-outline btn-outline-dashed btn-outline-primary dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tambah item transaksi">
                            <i class="fa fa-plus"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button class="dropdown-item" @click="openAddModal()">
                                    <i class="ki-duotone ki-purchase text-success me-2 fs-5"></i> Buah
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" @click="openParcelModal()">
                                    <i class="ki-duotone ki-purchase text-success me-2 fs-5"></i> Parcel
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" @click="openJusModal()">
                                    <i class="ki-duotone ki-purchase text-success me-2 fs-5"></i> Jus
                                </button>
                            </li>
                        </ul>
                    </div></div></header>@include('pos::pos.segment.cart')</section></div><aside class="erp-editor-summary"><h2>Ringkasan transaksi</h2>
                {{-- Ringkasan --}}
                @include('pos::pos.segment.ringkasan')
            </aside></div>

            @include('pos::pos.segment.modal-product')
            @include('pos::pos.segment.modal-customer')
            @include('pos::pos.segment.modal-gift')
            @include('pos::pos.segment.modal-parcel')
            @include('pos::pos.segment.modal-jus')

    </div>
    <!--end::Aside column-->
    @include('pos::pos.js-create2')
@endsection
