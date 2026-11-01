(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.DeliveryOrderMobileCards = api;
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';

    const avatarThemes = ['emerald', 'blue', 'purple', 'amber', 'teal', 'rose', 'indigo'];

    function phoneSuffix(phone) {
        const digits = String(phone || '').replace(/\D/g, '');
        return digits.length >= 4 ? digits.slice(-4) : '';
    }

    function customerInitials(name) {
        const cleaned = String(name || '').replace(/\s*\(.*?\)\s*/g, '').replace(/#\d+/g, '').trim();
        if (!cleaned) return '??';
        const parts = cleaned.split(/\s+/);
        return parts.length === 1
            ? parts[0].slice(0, 2).toUpperCase()
            : (parts[0][0] + parts[1][0]).toUpperCase();
    }

    function avatarTheme(name) {
        const value = String(name || '');
        let hash = 0;
        for (let index = 0; index < value.length; index += 1) {
            hash = value.charCodeAt(index) + ((hash << 5) - hash);
        }
        return avatarThemes[Math.abs(hash) % avatarThemes.length];
    }

    function deliveryState(status) {
        const value = String(status || '').trim().toLowerCase();
        return value === 'delivered' || value === 'received' || value === 'diterima'
            || (value.includes('diterima') && !value.includes('belum'))
            ? 'received'
            : 'unreceived';
    }

    function paymentState(status) {
        const value = String(status || '').trim().toLowerCase();
        return value === 'paid' || value === 'lunas' || value === 'sudah lunas'
            || value === 'telah lunas'
            ? 'paid'
            : 'unpaid';
    }

    function amountValue(value) {
        if (typeof value === 'number') return Number.isFinite(value) ? value : 0;
        const raw = String(value ?? '').trim();
        const numeric = /^-?\d+(?:\.\d+)?$/.test(raw)
            ? Number(raw)
            : Number(raw.replace(/[^\d-]/g, ''));
        return Number.isFinite(numeric) ? numeric : 0;
    }

    function formatAmount(value) {
        return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(amountValue(value))}`;
    }

    // Use the fields already serialized by get_data; never infer an unpaid balance from a badge.
    function buildBillingSummary(raw) {
        const unavailable = { available: false };
        if (!raw || !Object.prototype.hasOwnProperty.call(raw, 'voucher')) return unavailable;
        const cents = value => {
            if (value === null || value === undefined || !/^\d+(?:\.\d{1,2})?$/.test(String(value))) return null;
            const amount = Math.round(Number(value) * 100);
            return Number.isSafeInteger(amount) && amount >= 0 ? amount : null;
        };
        const total = cents(raw.total);
        const voucher = cents(raw.voucher ?? 0);
        let paid = cents(raw.paid);
        if (Array.isArray(raw.payment_details)) {
            const payments = raw.payment_details.map(payment => cents(payment?.total));
            if (payments.includes(null)) return unavailable;
            paid = payments.reduce((sum, amount) => sum + amount, 0);
        }
        if (total === null || voucher === null || paid === null || voucher > total || !Number.isSafeInteger(paid)) return unavailable;
        return {
            available: true,
            invoiceTotal: (total - voucher) / 100,
            voucher: voucher / 100,
            previousPaid: paid / 100,
            remaining: Math.max(0, total - voucher - paid) / 100
        };
    }

    function buildViewModel(order) {
        const customerName = String(order.customerName || 'Pelanggan Umum').trim();
        const suffix = phoneSuffix(order.customerPhone);
        const delivery = deliveryState(order.deliveryStatus);
        const payment = paymentState(order.paymentStatus);
        const total = amountValue(order.total);
        const amount = total > 0 ? total : amountValue(order.shippingFee);

        return {
            initials: customerInitials(customerName),
            customerName,
            phoneSuffix: suffix,
            customerLabel: suffix ? `${customerName} (${suffix})` : customerName,
            amountLabel: formatAmount(amount),
            deliveryLabel: delivery === 'received' ? 'Sudah Diterima' : 'Belum Diterima',
            deliveryState: delivery,
            paymentLabel: payment === 'paid' ? 'Sudah Lunas'
                : order.billing?.available && order.billing.previousPaid > 0 && order.billing.remaining > 0 ? 'Bayar Sebagian' : 'Belum Lunas',
            paymentState: payment,
            courierName: String(order.courierName || 'Kurir belum dipilih').trim(),
            avatarTheme: avatarTheme(customerName)
        };
    }

    function normalizeCourierType(value) {
        const type = String(value || '').trim().toLowerCase();
        if (type === 'internal') return 'internal';
        if (type === 'external' || type === 'eksternal') return 'external';
        return type;
    }

    function displayPhone(value) {
        const raw = String(value || '').trim();
        const digits = raw.replace(/\D/g, '');
        return digits.startsWith('62') ? `0${digits.slice(2)}` : raw;
    }

    function buildDetailViewModel(order) {
        const grandTotal = amountValue(order.total ?? order.grandTotal);
        const shippingFee = amountValue(order.shippingFee);
        const subtotal = order.subtotal === undefined || order.subtotal === null
            ? Math.max(0, grandTotal - shippingFee)
            : amountValue(order.subtotal);
        const delivery = deliveryState(order.deliveryStatus);
        const payment = paymentState(order.paymentStatus);

        return {
            orderId: String(order.orderId || ''),
            customerName: String(order.customerName || 'Pelanggan Umum').trim(),
            customerPhone: displayPhone(order.customerPhone),
            address: String(order.address || '').trim(),
            subtotal,
            shippingFee,
            grandTotal,
            subtotalLabel: formatAmount(subtotal),
            shippingFeeLabel: formatAmount(shippingFee),
            grandTotalLabel: formatAmount(grandTotal),
            deliveryState: delivery,
            actionLabel: delivery === 'received' ? 'Tutup' : 'Sudah Diterima',
            actionMethod: payment === 'paid' ? 'setSelesai' : 'setBayar',
            paymentState: payment,
            courierType: normalizeCourierType(order.courierType),
            billing: order.billing || { available: false },
            paymentUrl: order.paymentUrl || '',
            posUrl: order.posUrl || ''
        };
    }

    function requiresPaymentReceiptStep(order) {
        if (!order) return false;
        const delivery = order.deliveryState || deliveryState(order.deliveryStatus);
        const payment = order.paymentState || paymentState(order.paymentStatus);
        return delivery === 'unreceived' && payment === 'unpaid';
    }

    function parseReceiptAmount(value) {
        const raw = String(value ?? '').trim();
        if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)$/.test(raw)) return null;
        const amount = Number(raw.replace(/\./g, ''));
        return Number.isSafeInteger(amount) ? amount : null;
    }

    function validateReceipt(order, value, zeroConfirmed) {
        if (!requiresPaymentReceiptStep(order) || !order.billing?.available || order.billing.remaining <= 0) {
            return 'Rincian pembayaran belum dapat dipastikan. Periksa pembayaran melalui POS.';
        }
        const amount = parseReceiptAmount(value);
        if (amount === null) return 'Masukkan nominal rupiah yang valid. Kosong bukan berarti Rp0.';
        if (amount === 0 && !zeroConfirmed) return 'Centang konfirmasi belum ada pembayaran untuk menerima barang tanpa pembayaran.';
        return '';
    }

    function buildPaymentBalance(grandTotal, receivedAmount) {
        const difference = Math.round((amountValue(receivedAmount) - amountValue(grandTotal)) * 100) / 100;
        if (difference === 0) return { type: 'paid', label: 'Lunas :', value: 'Rp 0' };
        if (difference < 0) {
            return {
                type: 'underpaid',
                label: 'Kurang Bayar :',
                value: formatAmount(Math.abs(difference))
            };
        }
        return { type: 'overpaid', label: 'Lebih Bayar :', value: formatAmount(difference) };
    }

    function text(element) {
        return element ? element.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    function rowData(row) {
        if (row.classList.contains('child')) row = row.previousElementSibling;
        const cells = Array.from(row.cells || []);
        const nameCell = cells[0];
        const courierCell = cells[2];
        const statusCell = cells[3];
        const nameLink = nameCell?.querySelector('a[data-delivery-order-phone], a');
        const courierNode = courierCell?.querySelector('[data-delivery-order-courier-name]');
        const courierTypeNode = courierCell?.querySelector('[data-delivery-order-courier-type]');
        const totalNode = courierCell?.querySelector('[data-delivery-order-total]');
        const shippingFeeNode = courierCell?.querySelector('[data-delivery-order-shipping-fee]');
        const deliveryNode = statusCell?.querySelector('[data-delivery-order-delivery-status]');
        const paymentNode = statusCell?.querySelector('[data-delivery-order-payment-status]');
        const badges = Array.from(statusCell?.querySelectorAll('.badge') || []);
        const amountText = Array.from(courierCell?.querySelectorAll('span') || []).find(node => /Rp\.?\s*\d/i.test(text(node)));

        const orderId = nameLink?.getAttribute('data-delivery-order-id') || '';
        const table = row.closest('table');
        let raw = null;
        if (table && window.jQuery?.fn?.dataTable?.isDataTable(table)) {
            const data = window.jQuery(table).DataTable().row(row).data();
            if (data && String(data.id) === orderId) raw = data;
        }

        return {
            orderId,
            billing: buildBillingSummary(raw),
            paymentUrl: row.querySelector('.bi-cash-stack')?.closest('a')?.getAttribute('href') || '',
            posUrl: nameLink?.getAttribute('href') || '',
            customerName: text(nameLink) || 'Pelanggan Umum',
            customerPhone: nameLink?.getAttribute('data-delivery-order-phone') || '',
            address: nameLink?.getAttribute('data-delivery-order-address') || text(nameCell?.querySelector('span')),
            total: totalNode?.getAttribute('data-delivery-order-total') || amountText?.textContent || '',
            shippingFee: shippingFeeNode?.getAttribute('data-delivery-order-shipping-fee') || amountText?.textContent || '',
            courierType: courierTypeNode?.getAttribute('data-delivery-order-courier-type') || '',
            deliveryStatus: deliveryNode?.getAttribute('data-delivery-order-delivery-status') || text(badges[0]),
            paymentStatus: paymentNode?.getAttribute('data-delivery-order-payment-status') || text(badges[1]),
            courierName: text(courierNode) || text(courierCell?.querySelector('span')) || 'Kurir belum dipilih'
        };
    }

    let activeOrder = null;
    let activeTrigger = null;
    let previousBodyOverflow = '';
    let paymentSaving = false;

    function setDetailField(modal, field, value) {
        const node = modal.querySelector(`[data-delivery-order-detail-field="${field}"]`);
        if (node) node.textContent = value;
    }

    function setPaymentField(modal, field, value) {
        const node = modal.querySelector(`[data-delivery-order-payment-field="${field}"]`);
        if (node) node.textContent = value;
    }

    function renderBilling(modal, order) {
        const billing = order.billing;
        const available = billing.available;
        const mismatch = available && ((order.paymentState === 'paid') !== (billing.remaining === 0));
        const view = buildViewModel({ paymentStatus: order.paymentState, billing });
        setDetailField(modal, 'delivery-status', order.deliveryState === 'received' ? 'Sudah Diterima' : 'Belum Diterima');
        setDetailField(modal, 'payment-status', view.paymentLabel);
        for (const field of ['delivery-status', 'payment-status']) {
            const node = modal.querySelector(`[data-delivery-order-detail-field="${field}"]`);
            if (node) node.dataset.state = field === 'delivery-status' ? order.deliveryState : order.paymentState;
        }
        for (const field of ['previous-paid', 'remaining', 'voucher']) {
            const amount = field === 'previous-paid' ? billing.previousPaid : field === 'voucher' ? billing.voucher : billing.remaining;
            modal.querySelectorAll(`[data-delivery-order-billing-field="${field}"]`).forEach(node => {
                node.textContent = available ? formatAmount(amount) : 'Belum tersedia';
            });
        }
        modal.querySelectorAll('[data-delivery-order-voucher-row]').forEach(node => { node.hidden = !available || billing.voucher <= 0; });
        const message = !available ? 'Rincian pembayaran belum tersedia. Periksa melalui POS sebelum mencatat pembayaran.'
            : mismatch ? 'Saldo dan status pembayaran tidak sesuai. Periksa pembayaran melalui POS.'
                : order.deliveryState === 'received' && order.paymentState === 'unpaid' ? 'Barang sudah diterima. Sisa pembayaran tetap belum lunas.' : '';
        modal.querySelectorAll('[data-delivery-order-billing-note]').forEach(node => {
            node.textContent = message;
            node.hidden = !message;
        });
        modal.querySelectorAll('[data-delivery-order-continue-payment]').forEach(link => {
            link.hidden = true;
            link.removeAttribute('href');
            const href = order.paymentUrl || order.posUrl;
            if (!href || (order.paymentState === 'paid' && !mismatch && available)) return;
            if (link.closest('[data-delivery-order-payment-step]') && available && !mismatch) return;
            try {
                const url = new URL(href, window.location.href);
                if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) return;
                link.href = url.href;
                link.textContent = order.deliveryState === 'received' && order.paymentUrl ? 'Lanjutkan Pembayaran' : 'Periksa Pembayaran di POS';
                link.hidden = false;
            } catch (_) { /* A malformed link must never become a payment destination. */ }
        });
    }

    function showDetailStep(modal, focusAction = false) {
        modal.querySelector('[data-delivery-order-detail-header]')?.removeAttribute('hidden');
        modal.querySelector('[data-delivery-order-detail-body]')?.removeAttribute('hidden');
        modal.querySelector('[data-delivery-order-detail-footer]')?.removeAttribute('hidden');
        modal.querySelector('[data-delivery-order-payment-step]')?.setAttribute('hidden', '');
        modal.querySelector('[role="dialog"]')?.setAttribute('aria-labelledby', 'delivery-order-detail-title');
        modal.dataset.deliveryOrderStep = 'detail';
        if (focusAction) modal.querySelector('[data-delivery-order-detail-action]')?.focus({ preventScroll: true });
    }

    function updatePaymentBalance(modal, receivedAmount) {
        const balance = receivedAmount === null || !activeOrder?.billing.available
            ? { type: 'pending', label: 'Masukkan nominal :', value: '—' }
            : buildPaymentBalance(activeOrder.billing.remaining, receivedAmount);
        const container = modal.querySelector('[data-delivery-order-payment-balance]');
        const label = modal.querySelector('[data-delivery-order-payment-balance-label]');
        const value = modal.querySelector('[data-delivery-order-payment-balance-value]');
        const icon = modal.querySelector('[data-delivery-order-payment-balance-icon]');
        if (container) container.dataset.state = balance.type;
        if (label) label.textContent = balance.label;
        if (value) value.textContent = balance.value;
        const outcome = modal.querySelector('[data-delivery-order-receipt-outcome]');
        if (outcome) outcome.textContent = balance.type === 'underpaid'
            ? `Setelah disimpan, barang diterima dan sisa ${balance.value} tetap belum lunas.`
            : balance.type === 'paid' ? 'Preview lunas. Pembayaran baru tercatat setelah berhasil disimpan.'
                : balance.type === 'overpaid' ? `Preview lunas dengan kembalian ${balance.value}. Belum tersimpan.`
                    : 'Preview belum tersimpan. Masukkan uang baru yang diterima, bukan total pembayaran sebelumnya.';
        const submit = modal.querySelector('[data-delivery-order-payment-submit]');
        if (submit) submit.textContent = receivedAmount === 0 ? 'Terima Barang' : 'Bayar';
        if (icon) {
            icon.className = balance.type === 'paid'
                ? 'ph-bold ph-check-circle'
                : balance.type === 'underpaid'
                    ? 'ph-bold ph-warning-circle'
                    : balance.type === 'overpaid' ? 'ph-bold ph-arrow-u-up-left' : 'ph-bold ph-pencil-simple';
        }
    }

    function showPaymentStep(modal) {
        if (!activeOrder || !requiresPaymentReceiptStep(activeOrder)) return false;
        modal.querySelector('[data-delivery-order-detail-header]')?.setAttribute('hidden', '');
        modal.querySelector('[data-delivery-order-detail-body]')?.setAttribute('hidden', '');
        modal.querySelector('[data-delivery-order-detail-footer]')?.setAttribute('hidden', '');
        const step = modal.querySelector('[data-delivery-order-payment-step]');
        step?.removeAttribute('hidden');
        modal.querySelector('[role="dialog"]')?.setAttribute('aria-labelledby', 'delivery-order-payment-title');
        modal.dataset.deliveryOrderStep = 'payment';
        setPaymentField(modal, 'subtotal', formatAmount(activeOrder.subtotal));
        setPaymentField(modal, 'shipping-fee', formatAmount(activeOrder.shippingFee));
        setPaymentField(modal, 'grand-total', activeOrder.billing.available ? formatAmount(activeOrder.billing.invoiceTotal) : 'Belum tersedia');

        const input = modal.querySelector('[data-delivery-order-payment-amount]');
        const ready = activeOrder.billing.available && activeOrder.billing.remaining > 0;
        if (input) {
            input.value = '';
            input.disabled = !ready;
            input.removeAttribute('aria-invalid');
        }
        const zero = modal.querySelector('[data-delivery-order-zero-confirm]');
        if (zero) zero.checked = false;
        modal.querySelector('[data-delivery-order-zero-confirm-wrap]')?.setAttribute('hidden', '');
        const error = modal.querySelector('[data-delivery-order-payment-error]');
        if (error) {
            error.textContent = '';
            error.hidden = true;
        }
        const back = modal.querySelector('[data-delivery-order-payment-back]');
        const submit = modal.querySelector('[data-delivery-order-payment-submit]');
        if (back) back.disabled = false;
        if (submit) submit.disabled = !ready;
        if (!ready) showPaymentError(modal, 'Rincian pembayaran belum dapat dipastikan. Periksa pembayaran melalui POS.');
        updatePaymentBalance(modal, null);
        const body = modal.querySelector('.delivery-order-payment-step__body');
        if (body) body.scrollTop = 0;
        const focus = ready ? input : back;
        focus?.focus({ preventScroll: true });
        focus?.scrollIntoView({ block: 'nearest' });
        return true;
    }

    function showPaymentError(modal, message) {
        const error = modal.querySelector('[data-delivery-order-payment-error]');
        if (!error) return;
        error.textContent = message;
        error.hidden = false;
    }

    function submitPaymentAndReceive() {
        const modal = document.getElementById('delivery-order-detail-modal');
        const input = modal?.querySelector('[data-delivery-order-payment-amount]');
        const submit = modal?.querySelector('[data-delivery-order-payment-submit]');
        const back = modal?.querySelector('[data-delivery-order-payment-back]');
        if (!modal || !activeOrder || !input || paymentSaving || !requiresPaymentReceiptStep(activeOrder)) return;

        const zero = modal.querySelector('[data-delivery-order-zero-confirm]');
        const validation = validateReceipt(activeOrder, input.value, zero?.checked === true);
        if (validation) {
            showPaymentError(modal, validation);
            input.setAttribute('aria-invalid', 'true');
            const focus = parseReceiptAmount(input.value) === 0 && !zero?.checked ? zero : input;
            focus?.focus({ preventScroll: true });
            focus?.scrollIntoView({ block: 'nearest' });
            return;
        }
        input.removeAttribute('aria-invalid');
        const nominal = parseReceiptAmount(input.value);
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!window.jQuery || typeof window.jQuery.ajax !== 'function') {
            showPaymentError(modal, 'Tidak dapat mengirim pembayaran. Silakan coba lagi.');
            return;
        }

        paymentSaving = true;
        input.disabled = true;
        if (zero) zero.disabled = true;
        modal.querySelector('[role="dialog"]')?.setAttribute('aria-busy', 'true');
        if (submit) submit.disabled = true;
        if (back) back.disabled = true;
        const error = modal.querySelector('[data-delivery-order-payment-error]');
        if (error) error.hidden = true;

        window.jQuery.ajax({
            url: `/delivery-order/set-selesai/${encodeURIComponent(activeOrder.orderId)}`,
            type: 'PUT',
            data: { _token: token, nominal }
        }).done(response => {
            if (response?.success !== true) {
                showPaymentError(modal, response?.message || 'Pembayaran belum tersimpan. Periksa kembali sebelum mencoba lagi.');
                return;
            }
            paymentSaving = false;
            closeDetailModal();
            if (window.Swal?.fire) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response?.message || 'Data berhasil diubah.',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
            if (typeof window.reloadDataTable === 'function') window.reloadDataTable();
        }).fail(xhr => {
            showPaymentError(modal, xhr.responseJSON?.message || 'Terjadi kesalahan saat menerima pembayaran.');
        }).always(() => {
            paymentSaving = false;
            input.disabled = false;
            if (zero) zero.disabled = false;
            modal.querySelector('[role="dialog"]')?.removeAttribute('aria-busy');
            if (submit) submit.disabled = false;
            if (back) back.disabled = false;
        });
    }

    function whatsappHref(phone) {
        const digits = String(phone || '').replace(/\D/g, '');
        if (!digits) return '';
        const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits;
        return normalized.length >= 7 ? `https://wa.me/${normalized}` : '';
    }

    function openDetailModal(order, trigger) {
        const modal = document.getElementById('delivery-order-detail-modal');
        if (!modal || paymentSaving) return false;

        const detail = buildDetailViewModel(order);
        setDetailField(modal, 'customer', detail.customerName);
        setDetailField(modal, 'subtotal', detail.subtotalLabel);
        setDetailField(modal, 'shipping-fee', detail.shippingFeeLabel);
        setDetailField(modal, 'grand-total', detail.billing.available ? formatAmount(detail.billing.invoiceTotal) : detail.grandTotalLabel);
        setDetailField(modal, 'grand-total-label', detail.billing.available ? 'Total Tagihan (Setelah Voucher)' : 'Total Belanja (Include Ongkir)');
        renderBilling(modal, detail);

        const phone = modal.querySelector('[data-delivery-order-detail-field="phone"]');
        const phoneUrl = whatsappHref(detail.customerPhone);
        if (phone) {
            phone.textContent = detail.customerPhone || 'Tidak ada nomor telepon';
            if (phoneUrl) {
                phone.href = phoneUrl;
                phone.removeAttribute('aria-disabled');
            } else {
                phone.removeAttribute('href');
                phone.setAttribute('aria-disabled', 'true');
            }
        }

        const address = modal.querySelector('[data-delivery-order-detail-field="address"]');
        if (address) {
            address.textContent = detail.address || '-';
            if (detail.address) {
                address.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(detail.address)}`;
                address.removeAttribute('aria-disabled');
            } else {
                address.removeAttribute('href');
                address.setAttribute('aria-disabled', 'true');
            }
        }

        const action = modal.querySelector('[data-delivery-order-detail-action]');
        if (action) {
            action.textContent = detail.actionLabel;
            action.disabled = detail.deliveryState !== 'received' && !detail.orderId;
        }

        activeOrder = detail;
        activeTrigger = trigger || document.activeElement;
        paymentSaving = false;
        showDetailStep(modal);
        if (modal.hidden) previousBodyOverflow = document.body.style.overflow;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('[role="dialog"]')?.focus({ preventScroll: true });
        return true;
    }

    function openPaymentReceiptStep(orderId, trigger = null) {
        const modal = document.getElementById('delivery-order-detail-modal');
        if (!modal) return false;

        const requestedId = String(orderId);
        const activeTrigger = trigger && typeof trigger.closest === 'function'
            ? trigger
            : document.activeElement;
        let row = activeTrigger?.closest?.('tr') || null;
        if (row?.classList.contains('child')) row = row.previousElementSibling;

        let nameLink = row?.querySelector('[data-delivery-order-id]') || null;
        if (nameLink?.getAttribute('data-delivery-order-id') !== requestedId) {
            nameLink = Array.from(document.querySelectorAll('[data-delivery-order-id]'))
                .find(node => node.getAttribute('data-delivery-order-id') === requestedId) || null;
            row = nameLink?.closest('tr') || null;
            if (row?.classList.contains('child')) row = row.previousElementSibling;
        }

        if (!row || !nameLink) return false;
        const order = rowData(row);
        if (order.orderId !== requestedId || !requiresPaymentReceiptStep(buildDetailViewModel(order))) return false;

        const returnFocus = activeTrigger?.closest?.('tr') === row ? activeTrigger : nameLink;
        if (!openDetailModal(order, returnFocus)) return false;
        return showPaymentStep(modal);
    }

    function closeDetailModal() {
        const modal = document.getElementById('delivery-order-detail-modal');
        if (!modal || modal.hidden || paymentSaving) return;

        showDetailStep(modal);
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousBodyOverflow;
        const trigger = activeTrigger;
        activeOrder = null;
        activeTrigger = null;
        if (trigger?.isConnected) trigger.focus({ preventScroll: true });
    }

    function handleDetailAction() {
        if (!activeOrder) return;
        const order = activeOrder;
        if (order.deliveryState === 'received' || !order.orderId) {
            closeDetailModal();
            return;
        }
        if (requiresPaymentReceiptStep(order)) {
            showPaymentStep(document.getElementById('delivery-order-detail-modal'));
            return;
        }

        closeDetailModal();
        const action = window[order.actionMethod];
        if (typeof action === 'function') action(order.orderId);
    }

    function bindDetailModal() {
        const modal = document.getElementById('delivery-order-detail-modal');
        if (!modal || modal.dataset.deliveryOrderModalBound === 'true') return;
        modal.dataset.deliveryOrderModalBound = 'true';
        modal.addEventListener('click', event => {
            if (event.target.closest('[data-delivery-order-detail-close]')) closeDetailModal();
        });
        modal.querySelector('[data-delivery-order-detail-action]')?.addEventListener('click', handleDetailAction);
        modal.querySelector('[data-delivery-order-payment-back]')?.addEventListener('click', () => {
            if (!paymentSaving) showDetailStep(modal, true);
        });
        modal.querySelector('[data-delivery-order-payment-submit]')?.addEventListener('click', submitPaymentAndReceive);
        modal.querySelector('[data-delivery-order-payment-amount]')?.addEventListener('input', event => {
            const amount = parseReceiptAmount(event.currentTarget.value);
            event.currentTarget.removeAttribute('aria-invalid');
            const error = modal.querySelector('[data-delivery-order-payment-error]');
            if (error) error.hidden = true;
            const zero = modal.querySelector('[data-delivery-order-zero-confirm]');
            if (zero) zero.checked = false;
            const wrap = modal.querySelector('[data-delivery-order-zero-confirm-wrap]');
            if (wrap) wrap.hidden = amount !== 0;
            updatePaymentBalance(modal, amount);
        });
        modal.querySelector('[data-delivery-order-payment-amount]')?.addEventListener('focus', event => {
            const amount = parseReceiptAmount(event.currentTarget.value);
            if (amount !== null) event.currentTarget.value = String(amount);
        });
        modal.querySelector('[data-delivery-order-payment-amount]')?.addEventListener('blur', event => {
            const amount = parseReceiptAmount(event.currentTarget.value);
            if (amount !== null) event.currentTarget.value = new Intl.NumberFormat('id-ID').format(amount);
        });
        document.addEventListener('keydown', event => {
            if (modal.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDetailModal();
                return;
            }
            if (event.key !== 'Tab') return;

            const dialog = modal.querySelector('[role="dialog"]');
            const focusable = Array.from(modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])'))
                .filter(node => node.tabIndex >= 0 && !node.closest('[hidden]'));
            if (!focusable.length) {
                event.preventDefault();
                dialog?.focus();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || document.activeElement === dialog)) {
                event.preventDefault();
                first.focus();
            }
        });
    }

    function element(tag, className, value) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (value !== undefined) node.textContent = value;
        return node;
    }

    function decorateCard(card, row) {
        const head = card.querySelector('.erp-record__head');
        if (!head) return;

        const order = rowData(row);
        card.__deliveryOrderData = order;
        const view = buildViewModel(order);
        const customer = element('span', 'delivery-order-card__customer');
        const avatar = element('span', 'delivery-order-card__avatar', view.initials);
        avatar.dataset.avatarTheme = view.avatarTheme;
        const payment = element('span', 'delivery-order-card__payment');
        payment.dataset.paymentState = view.paymentState;
        payment.title = view.paymentLabel;
        payment.setAttribute('aria-label', `Pembayaran: ${view.paymentLabel}`);
        payment.append(element('i', 'ph-bold ph-money', ''));
        avatar.append(payment);

        const copy = element('span', 'delivery-order-card__copy');
        const title = element('span', 'delivery-order-card__title', view.customerName);
        if (view.phoneSuffix) {
            title.append(element('span', 'delivery-order-card__phone', ` (${view.phoneSuffix})`));
        }
        copy.append(title, element('span', 'delivery-order-card__amount', view.amountLabel));
        customer.append(avatar, copy);

        const delivery = element('span', 'delivery-order-card__delivery', view.deliveryLabel);
        delivery.dataset.deliveryState = view.deliveryState;
        const courier = element('span', 'delivery-order-card__courier', view.courierName);
        const details = element('span', 'delivery-order-card__status');
        details.append(delivery, courier);

        head.replaceChildren(customer, details);
        head.classList.add('delivery-order-card__head');
        head.setAttribute('aria-label', `Detail pengiriman ${view.customerLabel}, ${view.deliveryLabel}`);
        card.classList.add('delivery-order-card');
        card.dataset.deliveryOrderCard = 'true';
        if (head.dataset.deliveryOrderDetailBound !== 'true') {
            head.dataset.deliveryOrderDetailBound = 'true';
            head.addEventListener('click', event => {
                event.preventDefault();
                event.stopImmediatePropagation();
                openDetailModal(card.__deliveryOrderData, head);
            }, { capture: true });
        }
    }

    function attach(dataTable) {
        const table = dataTable?.table?.().node?.();
        const host = table?.closest('[data-erp-collection]');
        if (!table || !host || !window.requestAnimationFrame) return false;
        bindDetailModal();
        if (table.dataset.deliveryOrderDetailActionsBound !== 'true') {
            table.dataset.deliveryOrderDetailActionsBound = 'true';
            table.addEventListener('click', event => {
                const trigger = event.target.closest('[data-delivery-order-detail-trigger]');
                if (!trigger) return;
                event.preventDefault();
                event.stopPropagation();
                const row = trigger.closest('tr');
                if (row) openDetailModal(rowData(row), trigger);
            });
        }

        const render = () => {
            window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
                const list = host.querySelector('.erp-record-list');
                if (!list) return;
                const rows = Array.from(table.tBodies).flatMap(body => Array.from(body.rows))
                    .filter(row => !row.classList.contains('child') && row.cells.length > 1 && row.style.display !== 'none');
                const cards = Array.from(list.querySelectorAll(':scope > article.erp-record'));
                cards.forEach((card, index) => {
                    if (rows[index]) decorateCard(card, rows[index]);
                });
            }));
        };

        window.jQuery(table).on('draw.dt.deliveryOrderCards', render);
        render();
        return true;
    }

    return {
        attach,
        buildViewModel,
        buildDetailViewModel,
        requiresPaymentReceiptStep,
        buildPaymentBalance,
        buildBillingSummary,
        parseReceiptAmount,
        validateReceipt,
        openPaymentReceiptStep
    };
});
