@extends('template.root')
@section('title', 'Pembayaran')
@section('content')
@php
    $rawPhone = preg_replace('/\D/', '', (string) ($data->customer?->whatsapp ?? ''));
    $localPhone = preg_replace('/^(62|0)/', '', $rawPhone);
    $phoneLabel = $localPhone !== '' ? '(+62) ' . substr($localPhone, 0, 3) . (strlen($localPhone) > 3 ? '-' . substr($localPhone, 3, 4) : '') . (strlen($localPhone) > 7 ? '-' . substr($localPhone, 7) : '') : '';
    $isDraftLike = in_array($data->status, ['temp', 'draft', 'pending'], true);
    $dueLabel = ($data->paid ?? 0) > 0 ? 'Sisa Tagihan' : 'Total Belanja';
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/pos-form.css') }}?v={{ filemtime(public_path('assets/css/pos-form.css')) }}">
<div x-data="posApp()" class="pos-form pos-form--reference">
    <div x-show="retryPending" x-cloak role="status" class="pos-pay-alert">Status percobaan sebelumnya belum dipastikan. Coba ulang mengirim pembayaran yang sama.</div>

    <section class="pos-card pos-card--fields" aria-label="Pelanggan dan pengiriman">
        <div class="pos-pay-customer">
            <div class="pos-pay-customer__name"><strong>{{ $data->customer?->name ?? 'Pelanggan Umum' }}</strong>@if ($phoneLabel !== '')<span>{{ $phoneLabel }}</span>@endif</div>
            <p class="pos-pay-customer__address">{{ $data->ongkir_address ?: 'Tidak ada alamat pengiriman' }}</p>
        </div>
        <div class="pos-field">
            <span class="pos-pay-label">PILIH KURIR</span>
            <div class="pos-pay-readonly">
                @if ($data->courier)
                    <strong>{{ $data->courier->name }}</strong>@if ($data->courier_type)<span>({{ $data->courier_type }})</span>@endif
                @else
                    <span>Tanpa kurir</span>
                @endif
            </div>
        </div>
    </section>

    <section class="pos-card pos-card--fields" aria-labelledby="pos-pay-method-title">
        <div class="pos-pay-head"><h2 id="pos-pay-method-title" class="pos-pay-label">METODE PEMBAYARAN</h2></div>
        <div class="pos-pay-row">
            <select :disabled="loading || retryPending" class="form-select pos-pay-method" x-model="payments[0].payment_id" @change="setPaymentName(0)" aria-label="Metode pembayaran">
                <option value="">Pilih metode</option>
                <template x-for="method in paymentMethods" :key="method.id"><option :value="method.id" x-text="method.name"></option></template>
            </select>
            <div class="pos-pay-amount"><span>Rp</span><input type="text" inputmode="numeric" :disabled="loading || retryPending" x-model="payments[0].amount" @input="formatAmount(0)" aria-label="Jumlah pembayaran"></div>
        </div>
        <div class="pos-pay-split">
            <label for="pos-pay-split">Split Pembayaran</label>
            <button type="button" id="pos-pay-split" role="switch" :aria-checked="split" class="pos-switch" :class="split ? 'is-on' : ''" :disabled="loading || retryPending" @click="setSplit(!split)"><span></span></button>
        </div>
        <div class="pos-ref-card pos-pay-composition">
            <h3>Komposisi Pembayaran</h3>
            <template x-for="(payment, index) in payments" :key="index">
                <div class="pos-pay-extra" x-show="index > 0">
                    <select :disabled="loading || retryPending" class="form-select" x-model="payment.payment_id" @change="setPaymentName(index)" :aria-label="'Metode pembayaran ' + (index + 1)">
                        <option value="">Pilih metode</option>
                        <template x-for="method in paymentMethods" :key="method.id"><option :value="method.id" x-text="method.name"></option></template>
                    </select>
                    <div class="pos-pay-amount"><span>Rp</span><input type="text" inputmode="numeric" :disabled="loading || retryPending" x-model="payment.amount" @input="formatAmount(index)" :aria-label="'Jumlah pembayaran ' + (index + 1)"></div>
                    <button type="button" class="pos-cart-remove" @click="removePayment(index)" :disabled="loading || retryPending" aria-label="Hapus metode pembayaran"><i class="ph ph-trash" aria-hidden="true"></i></button>
                </div>
            </template>
            <button type="button" class="pos-pay-add" x-show="split" x-cloak @click="addPayment()" :disabled="loading || retryPending"><i class="ph ph-plus" aria-hidden="true"></i>Tambah metode</button>
            <div class="pos-pay-total">
                <span>TOTAL TERBAYAR:</span>
                <strong>Rp <span x-text="formatRupiah(totalPayment)"></span></strong>
                <p class="pos-pay-status" aria-live="polite"><span x-text="paymentStatus.status"></span><span x-show="paymentStatus.selisih > 0"> · Rp <span x-text="paymentStatus.selisih.toLocaleString('id-ID')"></span></span></p>
            </div>
        </div>
        <div class="pos-pay-date">
            <label for="payment-date" class="pos-pay-label">TANGGAL PEMBAYARAN</label>
            <input id="payment-date" type="date" :disabled="loading || retryPending" class="form-control" name="date" value="{{ date('Y-m-d') }}">
        </div>
        @if(isset($deposito) && $data->total >= $deposito->voucher)
            <dl class="pos-ref-lines pos-pay-voucher"><div><dt>Voucher</dt><dd>−Rp {{ toNumber($deposito->voucher ?? 0) }}</dd></div><div><dt>Sisa voucher</dt><dd>{{ $deposito->quantity ?? 0 }}</dd></div></dl>
        @endif
    </section>

    <section class="pos-card pos-pay-previous" aria-label="Pembayaran sebelumnya" x-show="previousPayments.length > 0" x-cloak>
        <div class="accordion mb-4" id="accordionPembayaranSebelumnya" x-show="previousPayments.length > 0"
                x-init="loadPreviousPayments()">
                <div class="accordion-item border-0 shadow-sm rounded">
                    <h2 class="accordion-header" id="headingPembayaran">
                        <button class="accordion-button bg-light text-dark fw-bold rounded collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapsePembayaran" aria-expanded="false"
                            aria-controls="collapsePembayaran">
                            <i class="fa-solid fa-receipt me-2 text-primary"></i> Pembayaran Sebelumnya
                        </button>
                    </h2>
                    <div id="collapsePembayaran" class="accordion-collapse collapse" aria-labelledby="headingPembayaran"
                        data-bs-parent="#accordionPembayaranSebelumnya">
                        <div class="accordion-body">
                            <template x-for="(payment, index) in previousPayments" :key="index">
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Tanggal</span>
                                        <span class="fw-semibold" x-text="formatDateDMY(payment.date)"></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Metode</span>
                                        <span class="fw-semibold" x-text="payment.payment_method.name"></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Jumlah</span>
                                        <span class="fw-bold text-success">Rp <span
                                                x-text="formatRupiah(payment.total)"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-end mt-2">
                                        <a class="btn btn-sm btn-primary"
                                            :href="`/{{ Request::segment(1) }}/printNota/${payment.id}`" target="_blank">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            
    </section>

    <div class="pos-form-footer pos-ref-footer">
        <div class="pos-form-footer__inner">
            <span class="pos-ref-handle" aria-hidden="true"><span></span></span>
            <div class="pos-ref-total">
                <div class="pos-ref-total__label">{{ $dueLabel }} <span>({{ $detail->count() }})</span></div>
                <div class="pos-ref-total__amount">Rp {{ number_format(floor($data->total - $data->paid), 0, ',', '.') }}</div>
            </div>
            <div class="pos-ref-actions">
                <a href="{{ $isDraftLike ? route('pos.edit', $data->id) : route('pos.index') }}" class="pos-ref-icon" aria-label="Kembali" title="{{ $isDraftLike ? 'Kembali / Edit Lagi' : 'Daftar POS' }}"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
                <a href="{{ route('pos.printDraftPayment', $data->id) }}" class="pos-ref-icon" aria-label="Cetak draf" title="Cetak draf"><i class="ph-bold ph-note" aria-hidden="true"></i></a>
                <div class="pos-ref-pay">
                    <button type="button" class="pos-ref-pay__btn" :disabled="loading" :aria-busy="loading" :aria-label="loading ? 'Memproses...' : (retryPending ? 'Coba ulang pembayaran' : 'Bayar')" @click="submitPayment()">
                        <span x-show="loading" x-cloak class="spinner-border spinner-border-sm align-middle me-2" aria-hidden="true"></span><span x-text="loading ? 'Memproses...' : (retryPending ? 'Coba ulang pembayaran' : 'Bayar')">Bayar</span>
                    </button>
                </div>
            </div>
            <a href="{{ route('pos.index') }}" class="visually-hidden">Daftar POS</a>
        </div>
    </div>
</div>
@include('pos::pos.js-payment')
@endsection
