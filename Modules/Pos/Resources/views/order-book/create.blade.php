@extends('template.root')
@section('title', 'Pesanan')

@section('content')
    <link rel="stylesheet" href="{{ asset('assets/css/pos-form.css') }}?v={{ filemtime(public_path('assets/css/pos-form.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/order-book-form.css') }}?v={{ filemtime(public_path('assets/css/order-book-form.css')) }}">
    <div class="w-100 flex-lg-row-auto" x-data="posApp()" x-init="init()">
        <div class="pos-form pos-form--reference ob-form" x-data="{ noteLength: {{ strlen($data->note ?? '') }}, noteBlank: {{ trim((string) ($data->note ?? '')) === '' ? 'true' : 'false' }} }">
            <section class="ob-card ob-card--branch" aria-labelledby="ob-branch-label">
                <label class="ob-label" id="ob-branch-label" for="branch_id"><span>Cabang</span><span class="ob-required" aria-hidden="true">*</span></label>
                <select class="form-control" name="branch_id" id="branch_id">
                    <option value="">Pilih Cabang</option>
                </select>
                <label class="ob-label ob-label--spaced" for="customer_id"><span>Pelanggan</span></label>
                <select class="form-select" id="customer_id" name="customer_id">
                    <option value="">Pelanggan Umum</option>
                </select>
                {{-- Date and invoice number stay fixed (today and the next number), as the reference form has no fields for them. --}}
                <div class="ob-hidden-fields" hidden>
                    <input type="hidden" name="tier_id" id="tier_id">
                    <input type="date" id="ob_date" name="date" value="{{ date('Y-m-d') }}">
                    <input type="text" id="ob_invoice" name="invoice_number" value="{{ $invoice_number }}" readonly>
                </div>
            </section>

            <section class="ob-card ob-card--note" aria-labelledby="ob-note-title">
                <div class="ob-card__head"><h2 id="ob-note-title">Catatan Pesanan</h2><span class="ob-required" aria-hidden="true">*</span></div>
                <div class="ob-card__body">
                    <label for="note" class="visually-hidden">Catatan pesanan</label>
                    <textarea name="note" id="note" class="form-control" placeholder="Tuliskan catatan pesanan..."
                        @input="noteLength = $event.target.value.length; noteBlank = $event.target.value.trim() === ''">{{ $data->note ?? '' }}</textarea>
                    <div class="ob-note-count"><span x-text="noteLength + ' karakter'"></span></div>
                </div>
            </section>

            <div class="ob-footer" x-data="{ loading: false }">
                <div class="ob-footer__pair">
                    <a href="{{ url('/order-book') }}" class="ob-footer__draft ob-footer__icon pos-ref-icon" aria-label="Kembali" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
                    <button type="button" class="ob-footer__draft ob-footer__icon pos-ref-icon" @click="loading = true; saveDraft(() => loading = false)" :disabled="loading || noteBlank" aria-label="Simpan sebagai draf" title="Simpan draf"><i class="ph-bold ph-bookmark-simple" aria-hidden="true"></i></button>
                </div>
                <button type="button" class="ob-footer__submit" @click="loading = true; saveTransaction(() => loading = false)" :disabled="loading || noteBlank">Buat Pesanan</button>
            </div>
        </div>
            @include('pos::pos.segment.modal-customer')
    </div>
@section('script')
    @include('pos::pos.js.js-header')
    <script>
        function posApp() {
            return {
                init() {
                    const self = this; // simpan konteks Alpine
                    $('#customer_id').on('select2:select', function(e) {
                        const data = e.params.data;
                        self.setMinimalPurchase(data.minimalPurchase || 0);
                        self.setVoucher(data.voucher || 0);
                        self.setDiscountGlobal(data.discount || 0);
                    });

                    if (data?.customer_id && data.customer) {
                        let optionCustomer = new Option(data.customer.name, data.customer.id, true, true);
                        $('#customer_id').append(optionCustomer).val(data.customer.id).trigger('change');
                    }
                },

                addCustomer() {
                    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
                    modal.show();
                },

                saveCustomer() {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                    const name = document.querySelector('[x-model="customerName"]').value;
                    const phone = document.querySelector('[x-model="customerPhone"]').value;
                    const address = document.querySelector('[x-model="customerAddress"]').value;

                    if (!name || !phone) {
                        Swal.fire('Lengkapi data', 'Nama dan nomor telepon wajib diisi.', 'warning');
                        return;
                    }

                    fetch('/pos/customers', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                name,
                                phone,
                                address
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                modal.hide();
                                console.log(res);

                                const c = res.customer;

                                // Buat <option> baru dengan atribut tambahan
                                const option = new Option(c.name, c.id, true, true);
                                $(option).attr({
                                    'data-name': c.name,
                                    'data-address': c.address,
                                    'data-whatsapp': c.phone,
                                    'data-tier_id': c.tier_id || '',
                                    'data-tier_name': c.tier_name || '-',
                                    'data-tier_style': c.tier_style || 'badge-light-secondary'
                                });

                                // Tambahkan ke select2
                                $('#customer_id').append(option).trigger('change');
                                $('#ongkir_address').text(c.address);

                                // Swal.fire('Berhasil', 'Customer berhasil ditambahkan.', 'success');
                            } else {
                                Swal.fire('Gagal', res.message ?? 'Gagal menyimpan customer.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
                        });
                },

                saveTransaction(doneCallback) {

                    const customerId = document.querySelector('select[name="customer_id"]').value;
                    const transactionDate = document.querySelector('input[name="date"]').value;
                    const note = document.querySelector('textarea[name="note"]').value;
                    const invoice_number = document.querySelector('input[name="invoice_number"]').value;
                    const branchId = document.querySelector('select[name="branch_id"]').value;

                    if (note == null || note == '') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Inputan Kosong',
                            text: 'Silahkan masukkan pesanan!',
                        });
                        if (typeof doneCallback === 'function') doneCallback();
                        return;
                    }


                    const data = {
                        customer_id: customerId,
                        date: transactionDate,
                        status: 'process',
                        note: note,
                        invoice_number: invoice_number,
                        branch_id: branchId,
                    };

                    // Simulasi kirim ke server
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/order-book/save-transaction', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(data),
                        })
                        .then(async res => {
                            const json = await res.json().catch(() => ({}));

                            // 🔥 Jika error (422, 500, dll)
                            if (!res.ok) {

                                // Jika VALIDASI ERROR
                                if (json.errors) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Validasi gagal',
                                        html: Object.values(json.errors)
                                            .map(msg => `<div>${msg}</div>`)
                                            .join('')
                                    });
                                } else {
                                    // Error lain
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal',
                                        text: json.message || 'Terjadi kesalahan',
                                    });
                                }

                                throw new Error("Request Failed");
                            }

                            // 🔥 SUCCESS → langsung redirect
                            window.location.href = '/order-book/' + json.order_book_id + '/order';

                            if (typeof doneCallback === 'function') doneCallback();
                        })
                        .catch(err => {
                            console.error(err);
                            if (typeof doneCallback === 'function') doneCallback();
                        });

                },

                saveDraft(doneCallback) {

                    const customerId = document.querySelector('select[name="customer_id"]').value;
                    const transactionDate = document.querySelector('input[name="date"]').value;
                    const note = document.querySelector('textarea[name="note"]').value;
                    const invoice_number = document.querySelector('input[name="invoice_number"]').value;
                    const branchId = document.querySelector('select[name="branch_id"]').value;

                    if (note == null || note == '') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Inputan Kosong',
                            text: 'Silahkan masukkan pesanan!',
                        });
                        if (typeof doneCallback === 'function') doneCallback();
                        return;
                    }


                    const data = {
                        customer_id: customerId,
                        date: transactionDate,
                        status: 'draft',
                        note: note,
                        invoice_number: invoice_number,
                        branch_id: branchId,
                    };

                    // Simulasi kirim ke server
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch('/order-book/save-transaction', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(data),
                        })
                        .then(async res => {
                            const json = await res.json().catch(() => ({}));

                            // 🔥 Jika error (422, 500, dll)
                            if (!res.ok) {

                                // Jika VALIDASI ERROR
                                if (json.errors) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Validasi gagal',
                                        html: Object.values(json.errors)
                                            .map(msg => `<div>${msg}</div>`)
                                            .join('')
                                    });
                                } else {
                                    // Error lain
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal',
                                        text: json.message || 'Terjadi kesalahan',
                                    });
                                }

                                throw new Error("Request Failed");
                            }

                            // 🔥 SUCCESS → langsung redirect
                            window.location.href = '/order-book/';

                            if (typeof doneCallback === 'function') doneCallback();
                        })
                        .catch(err => {
                            console.error(err);
                            if (typeof doneCallback === 'function') doneCallback();
                        });

                },
            }
        }
    </script>
@endsection
@endsection
