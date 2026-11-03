@extends('template.root')
@section('title', 'Pesanan')

@section('content')
    <div class="w-100 flex-lg-row-auto" x-data="posApp()" x-init="init()">
        <div class="erp-editor"><div class="erp-editor-main"><section class="erp-editor-section"><header><i class="ph ph-note-pencil" aria-hidden="true"></i><h2>Informasi pesanan</h2></header><div class="erp-editor-body">
            <div class="d-flex flex-column gap-10 mb-3">
                <!--begin::Input group-->
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="fv-row">
                            <!--begin::Label-->
                            <label class="required form-label">Cabang</label>
                            <!--end::Label-->
                            <!--begin::Select-->
                            <select class="form-control" name="branch_id" id="branch_id">
                                <option value="">Pilih Cabang</option>
                            </select>
                            <!--end::Select-->
                        </div>
                    </div>
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
        </div></section></div><aside class="erp-editor-summary"><h2>Rincian pesanan</h2><label for="note" class="form-label">Produk dan catatan pesanan</label><textarea name="note" id="note" rows="10" class="form-control" placeholder="Masukkan produk dan catatan pesanan">{{ $data->note ?? '' }}</textarea></aside></div>
            @include('pos::pos.segment.modal-customer')
        <div class="erp-editor-actions">
            <div class="flex-shrink-0 me-2">
                <a href="{{ url('/order-book') }}"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 flex-nowrap ms-auto" x-data="{ loading: false }">
                <button type="button"
                    class="btn btn-sm btn-light d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm text-nowrap"
                    @click="loading = true; saveDraft(() => loading = false)" :disabled="loading">
                    <template x-if="!loading">
                        <span class="d-inline-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-text"></i>
                            <span>Draft</span>
                        </span>
                    </template>
                    <template x-if="loading">
                        <span>
                            <span class="spinner-border spinner-border-sm align-middle me-2"></span>
                            Memproses...
                        </span>
                    </template>
                </button>
                <button type="button"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm text-nowrap"
                    @click="loading = true; saveTransaction(() => loading = false)" :disabled="loading">
                    <template x-if="!loading">
                        <span class="d-inline-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle"></i>
                            <span>Simpan & Proses</span>
                        </span>
                    </template>
                    <template x-if="loading">
                        <span>
                            <span class="spinner-border spinner-border-sm align-middle me-2"></span>
                            Memproses...
                        </span>
                    </template>
                </button>
            </div>
        </div>
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
