@extends('layouts.erp')
@section('title', 'Transaksi pengeluaran')
@section('page-title', 'Transaksi Pengeluaran')
@section('page-breadcrumb')
    <span>Keuangan</span><i class="ph ph-caret-right text-[0.55rem]" aria-hidden="true"></i><span>Pengeluaran</span>
@endsection

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

    <div class="w-full" x-data="posApp()" x-init="init()">
        <div class="erp-editor"><div class="erp-editor-main"><section class="erp-editor-section"><header><i class="ph ph-identification-card" aria-hidden="true"></i><h2>Informasi transaksi</h2></header><div class="erp-editor-body">
            <div class="mb-3 space-y-3">
                <div x-data="transactionInput()">
                    <span class="erp-label erp-label--required">Pilih Tipe</span>
                    <input type="hidden" id="type" name="type" :value="type">
                    <div class="flex items-center gap-2">
                        <x-erp.button class="shrink-0" aria-label="Ubah tipe transaksi" ::class="type === 'pemasukan' ? 'text-emerald-700' : 'text-red-600'" @click="toggleType()">
                            <template x-if="type === 'pemasukan'"><i class="ph-bold ph-plus" aria-hidden="true"></i></template>
                            <template x-if="type === 'pengeluaran'"><i class="ph-bold ph-minus" aria-hidden="true"></i></template>
                        </x-erp.button>
                        <input type="text" name="amount_formatted" class="erp-input text-right"
                            x-model="amountFormatted" @input="formatAmount($event)"
                            :placeholder="type === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'" aria-label="Nominal" readonly>
                        <input type="hidden" name="amount" :value="amount">
                    </div>
                </div>
                <x-erp.field label="Pilih Cabang" for="branch_id" required>
                    <select class="erp-select" id="branch_id" name="branch_id">
                        <option value="">Pilih Cabang</option>
                    </select>
                </x-erp.field>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-erp.field label="Tanggal Transaksi" for="expenditure-date" required><input type="date" id="expenditure-date" class="erp-input" name="date" value="{{ date('Y-m-d') }}"></x-erp.field>
                    <x-erp.field label="Nomor Faktur" for="expenditure-invoice" required><input type="text" id="expenditure-invoice" class="erp-input" name="invoice_number" value="{{ $invoice_number }}" readonly></x-erp.field>
                </div>
            </div>
        </div></section><section class="erp-editor-section"><header><i class="ph ph-shopping-cart" aria-hidden="true"></i><h2>Item transaksi</h2><div class="ml-auto"><x-erp.button icon="plus" aria-label="Tambah item" @click="openAddModal()" /></div></header>@include('pos::expenditure.segment.cart')</section></div><aside class="erp-editor-summary"><h2>Ringkasan transaksi</h2>
                @include('pos::expenditure.segment.ringkasan')
            </aside></div>

            @include('pos::expenditure.segment.modal-product')

    </div>
    @include('pos::expenditure.js-create')
@endsection
