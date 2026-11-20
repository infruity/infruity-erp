@extends('template.root')
@section('title', 'Pembayaran')
@section('content')
@php
    $rawPhone = preg_replace('/\D/', '', (string) ($data->customer?->whatsapp ?? ''));
    $localPhone = preg_replace('/^(62|0)/', '', $rawPhone);
    $phoneLabel = $localPhone !== '' ? '(+62) ' . substr($localPhone, 0, 3) . (strlen($localPhone) > 3 ? '-' . substr($localPhone, 3, 4) : '') . (strlen($localPhone) > 7 ? '-' . substr($localPhone, 7) : '') : '';
    $isDraftLike = in_array($data->status, ['temp', 'draft', 'pending'], true);
    $dueLabel = ($data->paid ?? 0) > 0 ? 'Sisa Tagihan' : 'Total Belanja';
    // Display-only figures for the confirmation; saveTransaction owns the real maths (100 or less = percent, above = rupiah).
    // The header `subtotal` is empty on older drafts, so the item lines are the source.
    $itemsSubtotal = (float) $detail->sum('subtotal');
    $discountValue = (float) ($data->discount ?? 0);
    $discountAmount = $discountValue > 0 && $discountValue <= 100 ? $itemsSubtotal * $discountValue / 100 : $discountValue;
    $shippingDiscountValue = (float) ($data->ongkir_discount ?? 0);
    $shippingDiscountAmount = $shippingDiscountValue > 0 && $shippingDiscountValue <= 100 ? (float) $data->ongkir * $shippingDiscountValue / 100 : $shippingDiscountValue;
    $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $last4 = strlen($rawPhone) >= 4 ? substr($rawPhone, -4) : '';
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/pos-form.css') }}?v={{ filemtime(public_path('assets/css/pos-form.css')) }}">
<div x-data="posApp()" class="pos-form pos-form--reference">
    <div x-show="retryPending" x-cloak role="status" class="pos-pay-alert">Status percobaan sebelumnya belum dipastikan. Coba ulang mengirim pembayaran yang sama.</div>

    <section class="pos-card pos-card--fields pos-card--pay" aria-label="Pelanggan dan pengiriman">
        @php
            $savedAddress = trim((string) $data->ongkir_address);
            $isPickup = $isDraftLike && ! $data->courier;
            $addressText = ! in_array($savedAddress, ['', '-'], true) ? $savedAddress : ($isDraftLike ? 'Belum ada alamat pengiriman. Klik untuk mengatur alamat.' : 'Tidak ada alamat pengiriman');
            $pickupText = 'Ambil di toko: alamat pengiriman tidak digunakan.';
        @endphp
        <div class="pos-field">
            <span class="pos-pay-label">METODE PENGIRIMAN</span>
            @if ($isDraftLike)
                <select id="pay_courier" class="visually-hidden" tabindex="-1" aria-hidden="true">
                    <option value="0" @if (! $data->courier) selected @endif>Ambil di toko</option>
                    @if ($data->courier)<option value="{{ $data->courier->id }}" data-type="{{ $data->courier_type ?? $data->courier->type }}" selected>{{ $data->courier->name }}</option>@endif
                </select>
                <button type="button" class="pos-pay-readonly pos-pay-courier" id="pay_courier_trigger" aria-label="Metode pengiriman">
                    <span class="pos-pay-courier__text">
                        <strong data-courier-name>{{ $data->courier?->name }}</strong>
                        <span data-courier-type>@if ($data->courier && $data->courier_type)({{ ucfirst($data->courier_type) }})@endif</span>
                        <em data-courier-empty @if ($data->courier) hidden @endif>Ambil di toko</em>
                    </span>
                    <i class="ph-bold ph-caret-down" aria-hidden="true"></i>
                </button>
            @else
            <div class="pos-pay-readonly">
                @if ($data->courier)
                    <strong>{{ $data->courier->name }}</strong>@if ($data->courier_type)<span>({{ ucfirst($data->courier_type) }})</span>@endif
                @else
                    <span>Ambil di toko</span>
                @endif
            </div>
            @endif
        </div>
        @include('pos::pos.segment.payment-address')
    </section>

    <section class="pos-card pos-card--fields pos-card--pay pos-card--pay-main" aria-labelledby="pos-pay-method-title">
        <div class="pos-pay-head"><h2 id="pos-pay-method-title" class="pos-pay-label">METODE PEMBAYARAN</h2><button type="button" class="pos-pay-head__add" :disabled="!split || loading || retryPending" @click="addPayment()">+ Tambah</button></div>
        <div class="pos-pay-row">
            <div class="pos-pay-select" x-data="{ methodOpen: false }" @click.outside="methodOpen = false" @keydown.escape.window="methodOpen = false">
                <button type="button" class="pos-pay-method pos-pay-select__btn" :class="{ 'is-open': methodOpen }" :disabled="loading || retryPending" @click="methodOpen = !methodOpen" aria-haspopup="listbox" :aria-expanded="methodOpen" aria-label="Metode pembayaran">
                    <span :class="{ 'is-placeholder': !topRow.payment_id }" x-text="topRow.payment_name || 'Pilih metode'"></span>
                    <i class="ph-bold ph-caret-down" :class="{ 'is-open': methodOpen }" aria-hidden="true"></i>
                </button>
                <div class="pos-pay-select__menu" x-show="methodOpen" x-cloak x-transition.opacity.duration.120ms role="listbox">
                    <template x-for="method in paymentMethods" :key="method.id">
                        <button type="button" role="option" class="pos-pay-select__option" :class="{ 'is-selected': topRow.payment_id == method.id }" :aria-selected="topRow.payment_id == method.id" @click="topRow.payment_id = method.id; setTopName(); methodOpen = false" x-text="method.name"></button>
                    </template>
                </div>
            </div>
            <div class="pos-pay-amount"><span>Rp</span><input type="text" inputmode="numeric" placeholder="0" :disabled="loading || retryPending" x-model="topRow.amount" @input="formatTopAmount()" aria-label="Jumlah pembayaran"></div>
        </div>
        <div class="pos-pay-split" :class="{ 'is-on': split }">
            <label for="pos-pay-split">Split Pembayaran</label>
            <button type="button" id="pos-pay-split" role="switch" :aria-checked="split" class="pos-switch" :class="split ? 'is-on' : ''" :disabled="loading || retryPending" @click="setSplit(!split)"><span></span></button>
        </div>
        <div class="pos-ref-card pos-pay-composition">
            <h3>Komposisi Pembayaran</h3>
            <div class="pos-pay-lines" x-show="split" x-cloak>
                <template x-for="(payment, index) in payments" :key="index">
                    <div class="pos-cart-swipe pos-pay-swipe"
                        x-data="{ startX: 0, startY: 0, open: false,
                            down(e) { if (e.target.closest('button')) return; const t = e.touches ? e.touches[0] : e; this.startX = t.clientX; this.startY = t.clientY; },
                            move(e) { if (!this.startX) return; const t = e.touches ? e.touches[0] : e; const dx = this.startX - t.clientX; const dy = Math.abs(this.startY - t.clientY);
                                if (dx > 35 && dx > dy) this.open = true; else if (dx < -25 && Math.abs(dx) > dy) this.open = false; },
                            up() { this.startX = 0; this.startY = 0; }
                        }"
                        @click.outside="open = false">
                        <button type="button" class="pos-cart-swipe__remove" @click.stop="open = false; removePayment(index)" :disabled="loading || retryPending" aria-label="Hapus metode pembayaran" title="Hapus Pembayaran Ini">
                            <i class="ph-bold ph-trash" aria-hidden="true"></i><span>Hapus</span>
                        </button>
                        <div class="pos-pay-line" :class="{ 'is-open': open }"
                            @touchstart.passive="down($event)" @touchmove.passive="move($event)" @touchend="up()"
                            @mousedown="down($event)" @mousemove="if (startX) move($event)" @mouseup="up()" @mouseleave="up()">
                            <div class="pos-pay-line__main"><strong x-text="payment.payment_name || ('Pembayaran ' + (index + 1))"></strong><small x-text="payment.userName"></small></div>
                            <div class="pos-pay-line__amount"><div><span>Rp</span><b x-text="payment.amount"></b></div><small x-text="payment.dateTime"></small></div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="pos-pay-total">
                <span>TOTAL TERBAYAR:</span>
                <strong>Rp <span x-text="formatRupiah(totalPayment)"></span></strong>
                <p class="pos-pay-status" :class="paymentStatus.selisih === 0 ? 'is-even' : (paymentStatus.status === 'Lebih Bayar' ? 'is-over' : 'is-short')" aria-live="polite" x-text="paymentStatus.selisih === 0 ? '-' : ((paymentStatus.status === 'Lebih Bayar' ? 'Kembali' : 'Kurang') + ' Rp ' + paymentStatus.selisih.toLocaleString('id-ID'))"></p>
            </div>
        </div>
        {{-- The reference shows no payment date field; the payment is dated today (the saved value is still sent as `date`). --}}
        <input id="payment-date" type="hidden" name="date" value="{{ date('Y-m-d') }}">
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

    @php
        $discountIsPercent = $discountValue > 0 && $discountValue <= 100;
        $shippingNet = max(0, (float) $data->ongkir - $shippingDiscountAmount);
    @endphp
    <div class="pos-ref-root">
    <div class="pos-ref-backdrop" x-show="sweepOpen" x-cloak x-transition:enter="pos-fade-in" x-transition:enter-start="pos-fade-from" x-transition:enter-end="pos-fade-to" x-transition:leave="pos-fade-out" x-transition:leave-start="pos-fade-to" x-transition:leave-end="pos-fade-from" @click="sweepOpen = false" @keydown.escape.window="sweepOpen = false"></div>
    <div class="pos-form-footer pos-ref-footer" :class="{ 'is-open': sweepOpen }" @touchstart.passive="touchY = $event.touches[0].clientY" @touchend.passive="const dy = $event.changedTouches[0].clientY - touchY; if (dy > 40 && sweepOpen) sweepOpen = false; else if (dy < -40 && !sweepOpen) sweepOpen = true;">
        <div class="pos-form-footer__inner">
            <button type="button" class="pos-ref-handle" @click="sweepOpen = !sweepOpen" :aria-expanded="sweepOpen" aria-controls="collapseTotal" aria-label="Rincian total"><span></span></button>
            {{-- Rincian total: Diskon Nota and Ongkos Kirim are editable while the transaction is still a draft; they are saved on change. --}}
            <div id="collapseTotal" class="pos-collapse" x-show="sweepOpen" x-cloak x-transition:enter="pos-collapse-anim" x-transition:enter-start="pos-collapse-from" x-transition:enter-end="pos-collapse-to" x-transition:leave="pos-collapse-anim" x-transition:leave-start="pos-collapse-to" x-transition:leave-end="pos-collapse-from"><div class="pos-ref-sheet">
                <div class="pos-ref-summary">
                    <div class="pos-ref-summary__inputs">
                        <div class="pos-ref-money">
                            <span class="pos-ref-money__title">Diskon Nota</span>
                            @if ($isDraftLike)
                            <span class="pos-ref-money__field"><span x-text="discountPrefix">{{ $discountIsPercent ? '%' : 'Rp' }}</span><input type="text" class="form-control" inputmode="numeric" autocomplete="off" x-model="discountInput" @input="discountInput = fmtDigits(discountInput)" @change="saveTotals()" @keydown.enter.prevent="$event.target.blur()" placeholder="0" aria-label="Diskon nota"></span>
                            @else
                            <span class="pos-ref-money__field is-readonly"><span>{{ $discountIsPercent ? '%' : 'Rp' }}</span><input type="text" class="form-control" value="{{ $discountValue > 0 ? number_format($discountValue, 0, ',', '.') : '' }}" placeholder="0" readonly aria-label="Diskon nota"></span>
                            @endif
                        </div>
                        <div class="pos-ref-money">
                            <span class="pos-ref-money__title">Ongkos Kirim</span>
                            @if ($isDraftLike)
                            <span class="pos-ref-money__field"><span>Rp</span><input type="text" class="form-control" inputmode="numeric" autocomplete="off" x-model="ongkirInput" @input="ongkirInput = fmtDigits(ongkirInput)" @change="saveTotals()" @keydown.enter.prevent="$event.target.blur()" placeholder="0" aria-label="Ongkos kirim"></span>
                            @else
                            <span class="pos-ref-money__field is-readonly"><span>Rp</span><input type="text" class="form-control" value="{{ (float) $data->ongkir > 0 ? number_format((float) $data->ongkir, 0, ',', '.') : '' }}" placeholder="0" readonly aria-label="Ongkos kirim"></span>
                            @endif
                        </div>
                        <p class="pos-ref-money__error" x-show="totalsMessage" x-cloak x-text="totalsMessage" role="alert"></p>
                    </div>
                    <dl class="pos-ref-lines">
                        <div><dt>Subtotal Item ({{ $detail->count() }})</dt><dd>{{ $rupiah($itemsSubtotal) }}</dd></div>
                        <div><dt @if ($isDraftLike) x-text="liveDiscountLabel" @endif>Diskon Transaksi{{ $discountIsPercent ? ' ('.rtrim(rtrim(number_format($discountValue, 2, ',', '.'), '0'), ',').'%)' : '' }}</dt><dd @if ($isDraftLike) x-text="liveDiscountText" @endif>{{ $rupiah($discountAmount) }}</dd></div>
                        <div><dt>Ongkos Kirim</dt><dd @if ($isDraftLike) x-text="liveShippingText" @endif>{{ $rupiah($shippingNet) }}</dd></div>
                    </dl>
                </div>
            </div></div>
            <div class="pos-ref-total is-toggle" @click="sweepOpen = !sweepOpen" role="button" tabindex="0" @keydown.enter.self="sweepOpen = !sweepOpen" @keydown.space.prevent.self="sweepOpen = !sweepOpen" :aria-expanded="sweepOpen" aria-controls="collapseTotal" aria-label="Rincian total">
                <div class="pos-ref-total__label">{{ $dueLabel }} <span>({{ $detail->count() }})</span></div>
                <div class="pos-ref-total__amount" @if ($isDraftLike) x-text="'Rp ' + formatRupiah(liveDue)" @endif>Rp {{ number_format(floor($data->total - $data->paid), 0, ',', '.') }}</div>
            </div>
            <div class="pos-ref-actions">
                <a href="{{ $isDraftLike ? route('pos.edit', $data->id) : route('pos.index') }}" class="pos-ref-icon" aria-label="Kembali" title="{{ $isDraftLike ? 'Kembali / Edit Lagi' : 'Daftar POS' }}"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
                <a href="{{ route('pos.printDraftPayment', ['id' => $data->id, 'preview' => 'mobile']) }}" class="pos-ref-icon pos-nota-mobile" aria-label="Lihat nota" title="Lihat nota"><i class="ph-bold ph-receipt" aria-hidden="true"></i></a>
                <a href="{{ route('pos.printDraftPayment', $data->id) }}" class="pos-ref-icon pos-nota-desktop" aria-label="Cetak draf" title="Cetak draf"><i class="ph-bold ph-receipt" aria-hidden="true"></i></a>
                <div class="pos-ref-pay">
                    <button type="button" class="pos-ref-pay__btn" :disabled="loading || totalsSaving || !!totalsMessage" :aria-busy="loading" :aria-label="loading ? 'Memproses...' : (retryPending ? 'Coba ulang pembayaran' : 'Bayar')" @click="submitPayment()">
                        <span x-show="loading" x-cloak class="spinner-border spinner-border-sm align-middle me-2" aria-hidden="true"></span><span x-text="loading ? 'Memproses...' : (retryPending ? 'Coba ulang pembayaran' : 'Bayar')">Bayar</span>
                    </button>
                </div>
            </div>
            <a href="{{ route('pos.index') }}" class="visually-hidden">Daftar POS</a>
        </div>
    </div>
    </div>
    <div x-show="confirmOpen" x-cloak x-transition.opacity.duration.200ms class="pos-confirm" @keydown.escape.window="confirmOpen = false">
        <div class="pos-confirm__backdrop" @click="confirmOpen = false"></div>
        <div class="pos-confirm__card" role="dialog" aria-modal="true" aria-labelledby="pos-confirm-title">
            <h2 id="pos-confirm-title"><span class="pos-confirm__mobile">Bayar Transaksi?</span><span class="pos-confirm__desktop">Selesaikan Transaksi?</span></h2>
            <p>Transaksi ini akan disimpan dan diproses<br>di Kasir / POS. Lanjutkan?</p>
            <dl class="pos-confirm__summary">
                <div><dt>Cabang</dt><dd>{{ $data->branch?->name ?? '-' }}</dd></div>
                <div><dt>Pelanggan</dt><dd>{{ $data->customer?->name ?? 'Pelanggan Umum' }}@if ($last4 !== '')<small> ({{ $last4 }})</small>@endif</dd></div>
                <div><dt>Item Belanja</dt><dd>{{ $detail->count() }} item ({{ $rupiah($itemsSubtotal) }})</dd></div>
                @if ($discountAmount > 0)<div><dt>Diskon Transaksi</dt><dd>-{{ $rupiah($discountAmount) }}</dd></div>@endif
                @if ((float) $data->ongkir > 0)<div><dt>Ongkos Kirim</dt><dd>+{{ $rupiah($data->ongkir) }}</dd></div>@endif
                @if ($shippingDiscountAmount > 0)<div><dt>Diskon Ongkir</dt><dd>-{{ $rupiah($shippingDiscountAmount) }}</dd></div>@endif
                <div class="is-total"><dt>Total Belanja</dt><dd @if ($isDraftLike) x-text="'Rp ' + formatRupiah(Math.round(liveTotal))" @endif>{{ $rupiah($data->total) }}</dd></div>
                <div class="is-paid"><dt>Dibayar</dt><dd><span x-text="'Rp ' + formatRupiah(totalPayment)"></span> · <span x-text="paymentStatus.status"></span></dd></div>
            </dl>
            <div class="pos-confirm__actions">
                <button type="button" x-ref="confirmCancel" @click="confirmOpen = false" :disabled="loading">Batal</button>
                <button type="button" class="is-primary" @click="confirmPayment()" :disabled="loading" :aria-busy="loading">
                    <span x-show="loading" x-cloak class="spinner-border spinner-border-sm align-middle me-2" aria-hidden="true"></span><span class="pos-confirm__mobile">Bayar</span><span class="pos-confirm__desktop">Selesaikan Transaksi</span>
                </button>
            </div>
        </div>
    </div>
</div>
@include('pos::pos.js-payment')
@endsection
