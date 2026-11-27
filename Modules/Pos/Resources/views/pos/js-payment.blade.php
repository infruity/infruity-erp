@section('script')
    <script src="{{ asset('assets/js/pos-payment-attempt.js') }}?v={{ filemtime(public_path('assets/js/pos-payment-attempt.js')) }}"></script>
    <script type="text/javascript">
        $('#payment_id').select2({
            placeholder: 'Pilih Pembayaran',
            ajax: {
                url: '{{ route('ajax.getPaymentMethod') }}',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    search: params.term
                }),
                processResults: data => {
                    return {
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name
                        }))
                    };
                }
            }
        });

        // $('#branch_id').select2({
        //     placeholder: 'Pilih Cabang',
        //     ajax: {
        //         url: '{{ route('ajax.getBranch') }}',
        //         dataType: 'json',
        //         delay: 250,
        //         data: params => ({
        //             search: params.term
        //         }),
        //         processResults: data => {
        //             return {
        //                 results: data.map(item => ({
        //                     id: item.id,
        //                     text: item.name
        //                 }))
        //             };
        //         }
        //     }
        // });
        $.ajax({
            url: '{{ route('ajax.getBranch') }}',
            dataType: 'json',
            success: function(data) {
                const defaultBranch = data.find(item => item.id === 1);
                if (defaultBranch) {
                    const option = new Option(defaultBranch.name, defaultBranch.id, true, true);
                    $('#branch_id').append(option).trigger('change');
                }
            }
        });

        function posApp() {
            return {
                @php
                    // From PosVoucher, as the payment itself computes it. A voucher never takes more than the bill.
                    $voucherCap = isset($voucherDeposit) && $voucherDeposit ? (float) $voucherDeposit->voucher : (float) ($voucherValue ?? 0);
                @endphp
                totalDue: {{ max(0, round($data->total - $data->paid - min($voucherCap, (float) $data->total))) }},
                totalPayment: {{ round($data->total - $data->paid) }},
                paymentDifference: 0,
                // Diskon Nota / Ongkos Kirim typed on this page: every figure follows the typing at once, the save runs on change.
                itemsSubtotal: {{ (float) $itemsSubtotal }},
                paidAmount: {{ (float) ($data->paid ?? 0) }},
                voucherAmount: {{ (float) $voucherCap }},
                shippingDiscountValue: {{ (float) $shippingDiscountValue }},
                savedDiscount: {{ (int) $discountValue }},
                discountType: {!! json_encode($discountType) !!},
                savedDiscountType: {!! json_encode($discountType) !!},
                shippingDiscountType: {!! json_encode($shippingDiscountType) !!},
                savedOngkir: {{ (int) $data->ongkir }},
                discountInput: {!! json_encode($discountValue > 0 ? number_format($discountValue, 0, ',', '.') : '') !!},
                ongkirInput: {!! json_encode((float) $data->ongkir > 0 ? number_format((float) $data->ongkir, 0, ',', '.') : '') !!},
                totalsSaving: false,
                totalsError: '',
                digitsOf(value) { return parseInt(String(value || '').replace(/\D/g, ''), 10) || 0; },
                fmtDigits(value) { const n = this.digitsOf(value); return n ? n.toLocaleString('id-ID') : ''; },
                unitAmount(value, type, base) { return type === 'percent' ? base * value / 100 : value; },
                setDiscountType(type) { this.discountType = type; this.saveTotals(); },
                get liveDiscountValue() { return this.digitsOf(this.discountInput); },
                get liveDiscountAmount() { return this.unitAmount(this.liveDiscountValue, this.discountType, this.itemsSubtotal); },
                get liveOngkir() { return this.digitsOf(this.ongkirInput); },
                get liveShippingNet() { return Math.max(0, this.liveOngkir - this.unitAmount(this.shippingDiscountValue, this.shippingDiscountType, this.liveOngkir)); },
                // Whole rupiah, rounded as the server does; the inner round drops binary noise (18629.499999 is 18629.50).
                wholeRupiah(value) { return Math.round(Math.round(value * 100) / 100); },
                get liveTotal() { return this.wholeRupiah(Math.max(0, this.itemsSubtotal - this.liveDiscountAmount + this.liveShippingNet)); },
                get liveDue() { return this.wholeRupiah(this.liveTotal - this.paidAmount); },
                get liveDiscountLabel() {
                    return 'Diskon Transaksi' + (this.discountType === 'percent' && this.liveDiscountValue > 0 ? ' (' + this.liveDiscountValue + '%)' : '');
                },
                get liveDiscountText() { return 'Rp ' + this.formatRupiah(Math.round(this.liveDiscountAmount)); },
                get liveShippingText() { return 'Rp ' + this.formatRupiah(Math.round(this.liveShippingNet)); },
                get totalsMessage() {
                    if (this.liveDiscountAmount > this.itemsSubtotal) return 'Diskon nota tidak boleh melebihi subtotal item.';
                    return this.totalsError;
                },
                async saveTotals() {
                    const discount = this.liveDiscountValue;
                    const ongkir = this.liveOngkir;
                    const discountType = this.discountType;
                    if (discount === this.savedDiscount && discountType === this.savedDiscountType && ongkir === this.savedOngkir) { this.totalsError = ''; return; }
                    if (this.liveDiscountAmount > this.itemsSubtotal) return;
                    this.totalsSaving = true;
                    this.totalsError = '';
                    try {
                        const response = await fetch({!! json_encode(route('pos.updateTotals', $data->id)) !!}, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': {!! json_encode(csrf_token()) !!}},
                            body: JSON.stringify({discount, discount_type: discountType, ongkir}),
                        });
                        const json = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.totalsError = json.message || Object.values(json.errors || {}).flat()[0] || 'Gagal menyimpan diskon dan ongkos kirim.';
                        } else {
                            this.savedDiscount = discount;
                            this.savedDiscountType = discountType;
                            this.savedOngkir = ongkir;
                        }
                    } catch (error) {
                        this.totalsError = 'Gagal menyimpan diskon dan ongkos kirim.';
                    }
                    this.totalsSaving = false;
                },
                loading: false,
                paymentMethods: [],
                paymentAttempt: null,
                retryPending: false,

                async init() {
                    // The amount due follows Diskon Nota / Ongkos Kirim; a single payment row keeps matching it.
                    this.$watch('liveDue', (due) => {
                        this.totalDue = Math.max(0, this.wholeRupiah(due - Math.min(this.voucherAmount, this.liveTotal)));
                        if (this.payments.length === 1 && !this.split) {
                            this.payments[0].amount = this.totalDue;
                            this.formatAmount(0);
                        }
                    });
                    this.paymentAttempt = createPaymentAttempt({
                        storage: window.sessionStorage,
                        storageKey: 'infruity-payment-{{ auth()->id() }}-{{ $data->id }}',
                        uuid: () => crypto.randomUUID(),
                        send: (payload, key) => fetch("{{ route('pos.savePayment') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Idempotency-Key': key },
                            body: JSON.stringify(payload),
                        }),
                    });
                    this.paymentDifference = 0;

                    try {
                        let res = await fetch("{{ route('ajax.getPaymentMethod') }}");
                        let data = await res.json();
                        this.paymentMethods = data; // pastikan server return array JSON [{id:1, name:'Cash'}, ...]
                    } catch (e) {
                        console.error("Gagal ambil payment methods:", e);
                    }

                    if (this.payments.length > 0) {
                        this.payments[0].amount = this.totalDue;
                    }

                    // Like the reference: the first row starts on Tunai (cash) when that method exists.
                    if (this.payments.length > 0 && !this.payments[0].payment_id) {
                        const cash = this.paymentMethods.find(m => /^(tunai|cash)$/i.test(String(m.name).trim()));
                        if (cash) {
                            this.payments[0].payment_id = cash.id;
                            this.payments[0].payment_name = cash.name;
                        }
                    }

                    if (this.paymentAttempt.pending) {
                        const pending = this.paymentAttempt.pending;
                        this.payments = pending.payments;
                        this.split = this.payments.length > 1;
                        if (this.split) {
                            const cash = this.cashMethod();
                            this.entry = {payment_id: cash ? cash.id : '', payment_name: cash ? cash.name : '', amount: ''};
                        }
                        document.querySelector('[name="date"]').value = pending.date;
                        this.retryPending = true;
                    }
                    this.payments.forEach((p, i) => this.formatAmount(i));
                },

                sweepOpen: false,
                touchY: 0,
                split: false,
                // Split on: the top row is the entry for the next payment, "+ Tambah" commits it as a line of the composition.
                entry: {payment_id: '', payment_name: '', amount: ''},
                get topRow() { return this.split ? this.entry : this.payments[0]; },
                cashMethod() {
                    return this.paymentMethods.find(m => /^(tunai|cash)$/i.test(String(m.name).trim())) || null;
                },
                setTopName() {
                    const row = this.topRow;
                    const method = this.paymentMethods.find(m => m.id == row.payment_id);
                    row.payment_name = method ? method.name : '';
                },
                formatTopAmount() {
                    const row = this.topRow;
                    const digits = (row.amount || '').toString().replace(/\D/g, '');
                    row.amount = digits === '' ? '' : (parseInt(digits, 10) || 0).toLocaleString('id-ID');
                },
                setSplit(on) {
                    if (on === this.split) return;
                    if (on) {
                        const first = this.payments[0] || {};
                        const cash = this.cashMethod();
                        this.entry = {
                            payment_id: first.payment_id || (cash ? cash.id : ''),
                            payment_name: first.payment_name || (cash ? cash.name : ''),
                            amount: '',
                        };
                        this.payments = [];
                        this.split = true;
                    } else {
                        const row = this.entry;
                        this.split = false;
                        this.payments = [{
                            payment_id: row.payment_id || '',
                            payment_name: row.payment_name || '',
                            amount: this.totalDue.toLocaleString('id-ID'),
                        }];
                        this.entry = {payment_id: '', payment_name: '', amount: ''};
                    }
                },

                payments: [{
                    payment_id: '',
                    payment_name: '',
                    amount: this.totalPayment,
                }],
                // "+ Tambah" (split only): commits the entry; an empty amount takes what is still due.
                addPayment() {
                    if (!this.split) return;
                    const cash = this.cashMethod();
                    const methodId = this.entry.payment_id || (cash ? cash.id : '');
                    const method = this.paymentMethods.find(m => m.id == methodId);
                    if (!method) {
                        Swal.fire('Perhatian', 'Harus pilih metode pembayaran', 'warning');
                        return;
                    }
                    const typed = parseInt((this.entry.amount || '').toString().replace(/\D/g, ''), 10) || 0;
                    const amount = typed > 0 ? typed : Math.max(0, this.totalDue - this.totalPayment);
                    if (amount <= 0) return;
                    const now = new Date();
                    const pad = n => String(n).padStart(2, '0');
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    this.payments.push({
                        payment_id: method.id,
                        payment_name: method.name,
                        amount: amount.toLocaleString('id-ID'),
                        userName: @json(auth()->user()->nm_user ?? auth()->user()->username ?? auth()->user()->name ?? ''),
                        dateTime: `${pad(now.getDate())} ${months[now.getMonth()]} ${now.getFullYear()}, ${pad(now.getHours())}:${pad(now.getMinutes())}`,
                    });
                    this.entry = {payment_id: cash ? cash.id : '', payment_name: cash ? cash.name : '', amount: ''};
                },

                removePayment(index) {
                    this.payments.splice(index, 1);
                },

                get formattedTotalPayment() {
                    return this.formatRupiah(this.totalPayment);
                },
                set formattedTotalPayment(val) {
                    const raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                    this.totalPayment = Number(raw || 0);
                    this.paymentDifference = this.totalPayment - this.totalDue;

                    // update payment pertama jika ada
                    if (this.payments.length > 0) {
                        this.payments[0].amount = this.totalPayment;
                    }
                },

                formatAmount(index) {
                    let val = (this.payments[index].amount || '').toString().replace(/\D/g, "");
                    let num = parseInt(val) || 0;
                    this.payments[index].amount = num.toLocaleString('id-ID');
                },

                get totalPayment() {
                    return this.payments.reduce((sum, p) => {
                        let val = parseInt((p.amount || '').toString().replace(/\D/g, "")) || 0;
                        return sum + val;
                    }, 0);
                },

                get paymentStatus() {
                    if (this.totalPayment > this.totalDue) {
                        return {
                            status: 'Lebih Bayar',
                            selisih: this.totalPayment - this.totalDue
                        };
                    } else if (this.totalPayment < this.totalDue) {
                        return {
                            status: 'Kurang Bayar',
                            selisih: this.totalDue - this.totalPayment
                        };
                    }
                    return {
                        status: 'Pembayaran pas ✔ ',
                        selisih: 0
                    };
                },

                setPaymentName(index) {
                    const selectedId = this.payments[index].payment_id;
                    const method = this.paymentMethods.find(m => m.id == selectedId);
                    this.payments[index].payment_name = method ? method.name : '';
                },

                updatePayment(e) {
                    this.formattedTotalPayment = e.target.value;
                },

                formatRupiah(angka) {
                    if (typeof angka !== 'number') angka = parseInt(angka);
                    return angka.toLocaleString('id-ID');
                },

                formatDateDMY(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    if (isNaN(date)) return '-';

                    const day = date.getDate().toString().padStart(2, '0');
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                    ];
                    const month = monthNames[date.getMonth()];
                    const year = date.getFullYear().toString().slice(-2);

                    return `${day} ${month} ${year}`;
                },

                confirmOpen: false,

                buildPayload() {
                    return {
                        date: document.querySelector('[name="date"]').value,
                        payments: this.payments,
                        // account_id: document.querySelector('[name="account_id"]').value,
                        total_payment: this.totalPayment,
                        // branch_id: document.querySelector('[name="branch_id"]').value,
                        customer_id: '{{ $data->customer_id ?? '' }}', // kalau tersedia dari server
                        transaction_id: '{{ $data->id ?? '' }}'
                    };
                },

                // Bayar validates and opens the confirmation; nothing is sent until confirmPayment().
                submitPayment() {
                    if (this.loading || this.confirmOpen) return;
                    if (this.paymentAttempt?.pending) {
                        // Retry of an attempt the cashier already confirmed: same payload, same key.
                        this.loading = true;
                        this.doSavePayment(this.paymentAttempt.pending);
                        return;
                    }

                    const payload = this.buildPayload();

                    if (this.payments.length === 0) {
                        Swal.fire('Perhatian', this.split ? 'Tambahkan minimal satu pembayaran ke komposisi' : 'Harus pilih metode pembayaran', 'warning');
                        return;
                    }

                    for (let p of this.payments) {
                        if (!p.payment_id || !p.payment_name) {
                            Swal.fire('Perhatian', 'Harus pilih metode pembayaran', 'warning');
                            return;
                        }
                    }

                    // Ongkir diisi tapi pilihannya "Ambil di toko" (tanpa kurir).
                    const courierSelect = document.getElementById('pay_courier');
                    if (courierSelect && this.liveOngkir > 0 && (!courierSelect.value || courierSelect.value === '0')) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Kurir belum dipilih',
                            text: 'Anda memasukkan biaya ongkir, tetapi tidak memilih kurir!',
                        });
                        return;
                    }

                    if (!payload.payments || !payload.date) {
                        Swal.fire('Lengkapi data', 'Semua input wajib diisi.', 'warning');
                        return;
                    }

                    // Check if total penjualan is 0 and payment is 0
                    const totalPenjualan = {{ round($data->total) }};
                    if (totalPenjualan == 0 && this.totalPayment == 0) {
                        this.loading = true;
                        Swal.fire({
                            title: 'Peringatan',
                            text: 'Total Penjualan dan Jumlah Pembayaran adalah 0. Apakah Anda yakin ingin menyimpan?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Simpan',
                            cancelButtonText: 'Batal',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.doSavePayment(payload);
                            } else {
                                this.loading = false;
                            }
                        });
                        return;
                    }

                    if (payload.total_payment === undefined || payload.total_payment === null) {
                        Swal.fire('Lengkapi data', 'Jumlah pembayaran wajib diisi.', 'warning');
                        return;
                    }

                    this.confirmOpen = true;
                    // Focus Batal, not the primary button: Space released on Bayar must not confirm.
                    this.$nextTick(() => setTimeout(() => this.$refs.confirmCancel?.focus(), 50));
                },

                confirmPayment() {
                    if (this.loading) return;
                    this.confirmOpen = false;
                    this.loading = true;
                    this.doSavePayment(this.buildPayload());
                },

                async doSavePayment(payload) {
                    try {
                        const response = await this.paymentAttempt.submit(payload);
                        if (response.ignored) return;
                        this.retryPending = false;
                        window.location.href = '/pos/payment-notification/' + response.result.payment.id;
                    } catch (error) {
                        this.retryPending = !!this.paymentAttempt.pending;
                        this.loading = false;
                        Swal.fire('Pembayaran belum selesai', error.message || 'Periksa koneksi dan coba ulang pembayaran yang sama.', 'error');
                    }
                },

                transaction_id: {{ $data->id ?? 'null' }},
                previousPayments: [],
                loadPreviousPayments() {
                    if (!this.transaction_id) return;

                    fetch(`/pos/listPayment/${this.transaction_id}`)
                        .then(res => res.json())
                        .then(data => {
                            console.log('Pembayaran sebelumnya:', data);
                            this.previousPayments = data.filter(payment => parseFloat(payment.total) > 0);
                        })
                        .catch(error => {
                            console.error('Gagal mengambil pembayaran sebelumnya:', error);
                        });
                }

            }
        }
    </script>
@endsection

@if (in_array($data->status, ['temp', 'draft', 'pending'], true))
<script>
    // Metode Pengiriman (courier or Pick Up): a dropdown like the reference. The choice is saved on the transaction right away (it is not part of the payment).
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('pay_courier');
        const trigger = document.getElementById('pay_courier_trigger');
        if (!select || !trigger || !window.ErpPicker) return;

        const nameEl = trigger.querySelector('[data-courier-name]');
        const typeEl = trigger.querySelector('[data-courier-type]');
        const emptyEl = trigger.querySelector('[data-courier-empty]');
        const types = {};
        let saved = {id: select.value, type: (select.selectedOptions[0] && select.selectedOptions[0].dataset.type) || ''};

        // Pick Up (no courier) locks the address: nothing is delivered.
        const customer = document.getElementById('pay_customer');
        const applyPickup = (pickup) => {
            if (!customer) return;
            const chevron = customer.querySelector('.pos-pay-customer__chevron');
            customer.classList.toggle('is-locked', pickup);
            window.dispatchEvent(new CustomEvent('pos-pickup-change', {detail: {pickup}}));
            if (chevron) chevron.hidden = pickup;
            if (pickup) {
                customer.setAttribute('aria-disabled', 'true');
                customer.tabIndex = -1;
            } else {
                customer.removeAttribute('aria-disabled');
                customer.removeAttribute('tabindex');
            }
        };

        const render = (id, name, type) => {
            const none = !id || id === '0';
            nameEl.textContent = none ? '' : name;
            typeEl.textContent = !none && type ? '(' + type.charAt(0).toUpperCase() + type.slice(1) + ')' : '';
            emptyEl.hidden = !none;
            applyPickup(none);
        };

        ErpPicker.attach(select, {
            trigger: trigger,
            title: 'Metode Pengiriman',
            placeholder: 'Cari kurir...',
            variant: 'compact',
            prepend: [{id: '0', text: 'Ambil di toko', title: 'Ambil di toko', aside: '-'}],
            url: '/ajax/getKurir',
            searchParam: 'term',
            map: item => {
                types[item.id] = item.type;
                return {id: item.id, text: item.name, title: item.name, aside: item.type === 'internal' ? 'Internal' : 'External'};
            }
        });

        let handled = select.value;
        const onChange = () => {
            const id = select.value;
            if (id === handled) return;
            handled = id;
            const option = select.selectedOptions[0];
            const name = option ? option.textContent.trim() : '';
            const type = (id && id !== '0') ? (types[id] || (option && option.dataset.type) || '') : '';
            render(id, name, type);
            fetch("{{ route('pos.updateCourier', $data->id) }}", {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({courier_id: (id && id !== '0') ? id : null, courier_type: type || null}),
            }).then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                saved = {id: id, type: type};
            }).catch(() => {
                select.value = saved.id;
                const previous = select.selectedOptions[0];
                render(saved.id, previous ? previous.textContent.trim() : '', saved.type);
                handled = saved.id;
                if (window.Swal) Swal.fire({icon: 'error', title: 'Kurir gagal disimpan', text: 'Coba pilih kurir sekali lagi.'});
            });
        };
        select.addEventListener('change', onChange);
        if (window.jQuery) window.jQuery(select).on('change', onChange);
    });
</script>
@endif
