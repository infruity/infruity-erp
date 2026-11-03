@extends('template.root')
@section('title', 'Pembayaran')
@section('content')
<div x-data="posApp()" class="erp-editor erp-payment-editor">
 <div class="erp-editor-main">
 <section class="erp-editor-section"><header><i class="ph ph-credit-card" aria-hidden="true"></i><h2>Metode pembayaran</h2></header><div class="erp-editor-body">
 <div x-show="retryPending" x-cloak role="status" class="alert alert-warning">Status percobaan sebelumnya belum dipastikan. Coba ulang mengirim pembayaran yang sama.</div>
 <div class="mb-5"><label for="payment-date" class="form-label required">Tanggal pembayaran</label><input id="payment-date" type="date" :disabled="loading || retryPending" class="form-control" name="date" value="{{ date('Y-m-d') }}"></div>
 <template x-for="(payment,index) in payments" :key="index"><fieldset class="erp-payment-method"><legend>Metode <span x-text="index + 1"></span></legend><div class="erp-payment-fields"><label><span class="form-label">Tipe pembayaran</span><select :disabled="loading || retryPending" class="form-select" x-model="payment.payment_id" @change="setPaymentName(index)"><option value="">Pilih metode</option><template x-for="method in paymentMethods" :key="method.id"><option :value="method.id" x-text="method.name"></option></template></select></label><label><span class="form-label">Jumlah pembayaran</span><div class="input-group"><span class="input-group-text">Rp</span><input type="text" inputmode="numeric" :disabled="loading || retryPending" class="form-control" x-model="payment.amount" @input="formatAmount(index)"></div></label><button type="button" class="erp-action erp-action--danger" @click="removePayment(index)" :disabled="loading || retryPending" aria-label="Hapus metode pembayaran"><i class="ph ph-trash" aria-hidden="true"></i></button></div></fieldset></template>
 <button type="button" class="erp-action" @click="addPayment()" :disabled="loading || retryPending"><i class="ph ph-plus" aria-hidden="true"></i>Tambah metode</button>
 </div></section><section class="erp-editor-section"><div class="accordion mb-4" id="accordionPembayaranSebelumnya" x-show="previousPayments.length > 0"
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
            </div></section>
 </div><aside class="erp-editor-summary"><h2>Ringkasan pembayaran</h2><dl class="erp-payment-bill"><div><dt>Pelanggan</dt><dd>{{ $data->customer?->name ?? 'Pelanggan Umum' }}</dd></div><div><dt>Total penjualan</dt><dd>Rp {{ toNumber(floor($data->total)) }}</dd></div>
 @if(isset($deposito) && $data->total >= $deposito->voucher)<div><dt>Voucher</dt><dd>−Rp {{ toNumber($deposito->voucher ?? 0) }}</dd></div><div><dt>Sisa voucher</dt><dd>{{ $deposito->quantity ?? 0 }}</dd></div>@endif
 <div class="erp-payment-bill-total"><dt>Dibayar</dt><dd>Rp <span x-text="formatRupiah(totalPayment)"></span></dd></div></dl><p class="erp-payment-status" aria-live="polite"><span x-text="paymentStatus.status"></span><span x-show="paymentStatus.selisih > 0"> · Rp <span x-text="paymentStatus.selisih.toLocaleString('id-ID')"></span></span></p>
 <div class="erp-payment-actions"><button type="button" class="erp-action erp-action--primary" :disabled="loading" :aria-busy="loading" :aria-label="loading ? 'Memproses...' : (retryPending ? 'Coba ulang pembayaran' : 'Bayar')" @click="submitPayment()"><i x-show="!loading" class="ph ph-credit-card" aria-hidden="true"></i><span x-show="loading" class="spinner-border spinner-border-sm" aria-hidden="true"></span><span x-text="loading ? 'Memproses...' : (retryPending ? 'Coba ulang pembayaran' : 'Bayar')"></span></button><a href="{{ route('pos.printDraftPayment', $data->id) }}" class="erp-action"><i class="ph ph-printer" aria-hidden="true"></i>Cetak draf</a><a href="{{ route('pos.edit', $data->id) }}" class="erp-action">Kembali / Edit Lagi</a><a href="{{ route('pos.index') }}" class="erp-action">Daftar POS</a></div></aside>
</div>
@include('pos::pos.js-payment')
@endsection
