@extends('template.root')
@section('title', 'Proses Pesanan')

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
    <link rel="stylesheet" href="{{ asset('assets/css/order-book-detail.css') }}?v={{ filemtime(public_path('assets/css/order-book-detail.css')) }}">
    <!--begin::Aside column-->
    <div class="w-100 flex-lg-row-auto" x-data="posApp()" x-init="init()">
        <div class="pos-form pos-form--reference">
            <section class="pos-card pos-card--fields" aria-label="Cabang dan pelanggan">
                <div class="pos-field">
                    <label class="pos-field__label" for="branch_id">Cabang <span class="pos-field__req" aria-hidden="true">*</span></label>
                    <div class="pos-customer pos-branch" x-data="{ noteOpen: false }">
                        <select class="form-select" id="branch_id" name="branch_id">
                            <option value="">Pilih Branch</option>
                        </select>
                        <button type="button" class="pos-customer__add pos-branch__note" @click="noteOpen = true" aria-label="Lihat catatan pesanan" title="Catatan pesanan" aria-haspopup="dialog"><i class="ph-bold ph-note" aria-hidden="true"></i></button>
                    <template x-teleport="body">
                        <x-erp.detail-dialog open="noteOpen" close="noteOpen = false" title="Catatan Pesanan" labelledby="order-note-title" id="order-book-detail-dialog">
                            <x-slot:subtitle><p class="order-book-detail__branch">{{ $data->branch->name ?? '-' }}</p><p class="order-book-detail__branch">Dibuat oleh {{ $data->user->nm_user ?? '-' }}</p></x-slot:subtitle>
                            @if (trim((string) ($data->note ?? '')) !== '')
                                <p class="order-book-detail__note">{{ $data->note }}</p>
                            @else
                                <p class="order-book-detail__note is-empty">Tidak ada catatan.</p>
                            @endif
                            <x-slot:actions>
                                <div class="order-book-detail__footer"><div class="order-book-detail__actions"><button type="button" class="order-book-detail__action order-book-detail__action--primary" @click="noteOpen = false">Tutup</button></div></div>
                            </x-slot:actions>
                        </x-erp.detail-dialog>
                    </template>
                    </div>
                </div>
                <div class="pos-field">
                    <label class="pos-field__label" for="customer_id">Pelanggan <span class="pos-field__req" aria-hidden="true">*</span></label>
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
                <details class="pos-extra-settings"><summary>Pengaturan tambahan</summary>
            <div class="pos-ref-more">
                <details class="pos-ref-group" open>
                    <summary>Catatan Pesanan</summary>
                    <div class="pos-ref-group__body">
                        <label class="form-label visually-hidden" for="order_note">Catatan pesanan</label>
                        <textarea name="order_note" id="order_note" cols="30" rows="4" class="form-control" readonly>{{ $data->note }}</textarea>
                    </div>
                </details>
                <details class="pos-ref-group">
                    <summary>Catatan</summary>
                    <div class="pos-ref-group__body">
                        <label class="form-label" for="note">Note</label>
                        <textarea name="note" id="note" cols="30" rows="4" class="form-control"></textarea>
                    </div>
                </details>
                <details class="pos-ref-group">
                    <summary>Pengiriman</summary>
                    <div class="pos-ref-group__body">
                        <div class="mb-3">
                            <label class="form-label mb-1">Alamat Pengiriman</label>
                            <select class="form-control" name="ongkir_address" id="address_id"></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label mb-1">Pilih Kurir</label>
                            <select class="form-control" name="courier_id" id="courier_id">
                                <option value="">Pilih Kurir</option>
                            </select>
                            <input type="hidden" name="courier_type" id="courier_type" value="">
                        </div>
                        <div class="mb-3 row">
                            <div class="col">
                                <label class="form-label mb-1" for="ongkir_date">Jadwal</label>
                                <input type="date" class="form-control" name="ongkir_date" id="ongkir_date" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col mt-1">
                                <label class="form-label mb-1" for="ongkir_time"></label>
                                <input type="time" class="form-control" name="ongkir_time" id="ongkir_time">
                            </div>
                        </div>
                        <div>
                            <label class="form-label mb-1">Diskon Ongkir</label>
                            <input type="text" class="form-control text-end" inputmode="numeric" :value="formatRupiah(diskonOngkir)" @input="updateDiskonOngkir" @focus="$event.target.select()">
                        </div>
                    </div>
                </details>
                <details class="pos-ref-group">
                    <summary>Pengaturan Transaksi</summary>
                    <div class="pos-ref-group__body">
                        <div class="mb-3">
                            <label class="form-label" for="branch_process_id">Di Proses oleh</label>
                            <select class="form-select" id="branch_process_id" name="branch_process_id">
                                <option value="">Pilih Branch</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pos_date">Tanggal Transaksi</label>
                            <input type="date" class="form-control" id="pos_date" name="date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="form-label" for="invoice_number">Nomor Faktur</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ $invoice_number }}" readonly>
                        </div>
                    </div>
                </details>
            </div>
                </details>
                <div class="pos-card__body pos-card__body--list">
                    @include('pos::pos.segment.cart')
                </div>
            </section>

            {{-- Footer total + simpan/bayar; lembar ringkasan memuat diskon, catatan, ongkir, dan pengaturan transaksi --}}
            @include('pos::pos.segment.ringkasan', ['backUrl' => url('/order-book'), 'backLabel' => 'Kembali ke daftar pesanan'])
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
