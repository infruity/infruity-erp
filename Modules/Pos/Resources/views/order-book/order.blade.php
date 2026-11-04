@extends('template.root')
@section('title', 'Proses Pesanan')

@section('content')
    <link rel="stylesheet" href="{{ asset('assets/css/pos-form.css') }}?v={{ filemtime(public_path('assets/css/pos-form.css')) }}">
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
        <div class="erp-editor"><div class="erp-editor-main"><section class="erp-editor-section"><header><h2>Informasi pesanan</h2></header><div class="erp-editor-body">
            <div class="d-flex flex-column gap-10 mb-3">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-5">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Pilih Branch</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Pilih Branch</option>
                            </select>
                            <!--end::Editor-->
                        </div>
                    </div>
                    <div class="col-5">
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
                    <div class="col-2 mt-8">
                        <button type="button"
                            class="btn btn-outline btn-outline-dashed btn-outline-primary btn-active-light-primary"
                            data-bs-toggle="modal" data-bs-target="#orderBookModal">
                            <i class="fa fa-file"></i>
                        </button>
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
                        <button @click="addCustomer()"
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
        </div></section><section class="erp-editor-section"><div class="erp-editor-body">
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fs-5 fw-bold d-flex">Produk yang dijual</span>
                        <span class="text-danger">Diperbarui per {{ date('d/m/Y') }}</span>
                    </div>
                    <div class="btn-group">
                        <button type="button"
                            class="btn btn-outline btn-outline-dashed btn-outline-primary dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
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
                            <li>
                                <button class="dropdown-item" @click="openGiftModal()" {{-- x-show="isShowGiftButton" --}}>
                                    <i class="ki-duotone ki-purchase text-success me-2 fs-5"></i>
                                    <span class="text-success">Hadiah</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                {{-- <button @click="openGiftModal()" x-show="isShowGiftButton" class="btn rounded-circle position-fixed"
                    style="bottom: 60px; right: 25px; width: 60px; height: 60px; z-index: 1050; display: flex; align-items: center; justify-content: center;">
                    <i class="ki-duotone ki-gift" style="font-size: 30px; color: green;">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                </button> --}}
                {{-- <!-- Cart --> --}}
                @include('pos::pos.segment.cart')
            </div></section></div><aside class="erp-editor-summary"><h2>Ringkasan pesanan</h2>
                {{-- Ringkasan --}}
                @include('pos::pos.segment.ringkasan')
            </aside></div>

            <div class="modal fade" id="orderBookModal" tabindex="-1" aria-labelledby="orderBookModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="orderBookModalLabel">Order Book</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Isi modal di sini -->
                            <textarea name="order_note" id="order_note" class="form-control" cols="30" rows="10">{{ $data->note }}</textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
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
