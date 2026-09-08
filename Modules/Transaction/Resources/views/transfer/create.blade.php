@extends('template.root')

@section('content')
    @php
        $is_view = $is_view ?? false;
    @endphp

    <div class="mb-3">
        @php
            $backUrl = isset($type) ? route($type . '.index') : route('transfer.index');
        @endphp
        <a href="{{ $backUrl }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

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
    <div class="w-100 flex-lg-row-auto me-7 me-lg-10" x-data="posApp()" x-init="init()">
        <div class="card card-body mb-3">
            <div class="d-flex flex-column gap-10">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Cabang</label>
                            <!--end::Label-->
                            <!--begin::Select-->
                            <select class="form-control" name="branch_id" id="branch_id" {{ $is_view ? 'disabled' : '' }}>
                                <option value="">Pilih Cabang</option>
                            </select>
                            <!--end::Select-->
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Cabang Tujuan</label>
                            <!--end::Label-->
                            <!--begin::Select-->
                            <select class="form-control" name="branch_destination_id" id="branch_destination_id" {{ $is_view ? 'disabled' : '' }}>
                                <option value="">Pilih Cabang</option>
                            </select>
                            <!--end::Select-->
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Tanggal Transaksi</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}" {{ $is_view ? 'readonly' : '' }}>
                            <!--end::Editor-->
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Nomor Faktur</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="hidden" name="transfer_id" value="{{ $data->id ?? '' }}">
                            <input type="text" class="form-control" name="invoice_number" value="{{ $invoice_number }}"
                                readonly>
                            <!--end::Editor-->
                        </div>
                    </div>
                </div>
                <!--end::Input group-->
            </div>
        </div>
        <div>
            <div class="card card-body mb-3">
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fs-5 fw-bold d-flex">Produk Yang di Transfer</span>
                        <span class="text-danger">Diperbarui per {{ date('d/m/Y') }}</span>
                    </div>
                    @if(!$is_view)
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline btn-outline-dashed btn-outline-primary"
                            @click="openAddModal()">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                    @endif
                </div>
                {{-- <!-- Cart --> --}}
                @include('transaction::transfer.segment.cart')
            </div>

            <div class="card card-body">
                {{-- Ringkasan --}}
                @include('transaction::transfer.segment.ringkasan', ['is_view' => $is_view])
            </div>

            @if(!$is_view)
            @include('transaction::transfer.segment.modal-product')
            @endif
            @if($is_view)
            @include('transaction::transfer.segment.modal-correction')
            @endif
        </div>
    </div>
    <!--end::Aside column-->
    @include('transaction::transfer.js-create')
@endsection
