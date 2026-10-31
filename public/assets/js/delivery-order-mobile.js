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
        return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amountValue(value))}`;
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
            paymentLabel: payment === 'paid' ? 'Sudah Lunas' : 'Belum Lunas',
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
            courierType: normalizeCourierType(order.courierType)
        };
    }

    function requiresPaymentReceiptStep(order) {
        if (!order) return false;
        const delivery = order.deliveryState || deliveryState(order.deliveryStatus);
        const payment = order.paymentState || paymentState(order.paymentStatus);
        return delivery === 'unreceived' && payment === 'unpaid';
    }

    function buildPaymentBalance(grandTotal, receivedAmount) {
        const difference = amountValue(receivedAmount) - amountValue(grandTotal);
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

        return {
            orderId: nameLink?.getAttribute('data-delivery-order-id') || '',
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
        const balance = buildPaymentBalance(activeOrder?.grandTotal || 0, receivedAmount);
        const container = modal.querySelector('[data-delivery-order-payment-balance]');
        const label = modal.querySelector('[data-delivery-order-payment-balance-label]');
        const value = modal.querySelector('[data-delivery-order-payment-balance-value]');
        const icon = modal.querySelector('[data-delivery-order-payment-balance-icon]');
        if (container) container.dataset.state = balance.type;
        if (label) label.textContent = balance.label;
        if (value) value.textContent = balance.value;
        if (icon) {
            icon.className = balance.type === 'paid'
                ? 'ph-bold ph-check-circle'
                : balance.type === 'underpaid'
                    ? 'ph-bold ph-warning-circle'
                    : 'ph-bold ph-arrow-u-up-left';
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
        setPaymentField(modal, 'grand-total', formatAmount(activeOrder.grandTotal));

        const input = modal.querySelector('[data-delivery-order-payment-amount]');
        if (input) input.value = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(activeOrder.grandTotal);
        const error = modal.querySelector('[data-delivery-order-payment-error]');
        if (error) {
            error.textContent = '';
            error.hidden = true;
        }
        const back = modal.querySelector('[data-delivery-order-payment-back]');
        const submit = modal.querySelector('[data-delivery-order-payment-submit]');
        if (back) back.disabled = false;
        if (submit) submit.disabled = false;
        updatePaymentBalance(modal, activeOrder.grandTotal);
        const body = modal.querySelector('.delivery-order-payment-step__body');
        if (body) body.scrollTop = 0;
        input?.focus({ preventScroll: true });
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

        const nominal = Number(input.value.replace(/\D/g, '')) || 0;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!window.jQuery || typeof window.jQuery.ajax !== 'function') {
            showPaymentError(modal, 'Tidak dapat mengirim pembayaran. Silakan coba lagi.');
            return;
        }

        paymentSaving = true;
        if (submit) submit.disabled = true;
        if (back) back.disabled = true;
        const error = modal.querySelector('[data-delivery-order-payment-error]');
        if (error) error.hidden = true;

        window.jQuery.ajax({
            url: `/delivery-order/set-selesai/${encodeURIComponent(activeOrder.orderId)}`,
            type: 'PUT',
            data: { _token: token, nominal }
        }).done(response => {
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
        if (!modal) return false;

        const detail = buildDetailViewModel(order);
        setDetailField(modal, 'customer', detail.customerName);
        setDetailField(modal, 'subtotal', detail.subtotalLabel);
        setDetailField(modal, 'shipping-fee', detail.shippingFeeLabel);
        setDetailField(modal, 'grand-total', detail.grandTotalLabel);

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
        previousBodyOverflow = document.body.style.overflow;
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
        modal.querySelector('[data-delivery-order-payment-back]')?.addEventListener('click', () => showDetailStep(modal, true));
        modal.querySelector('[data-delivery-order-payment-submit]')?.addEventListener('click', submitPaymentAndReceive);
        modal.querySelector('[data-delivery-order-payment-amount]')?.addEventListener('input', event => {
            const digits = event.currentTarget.value.replace(/\D/g, '');
            const amount = Number(digits) || 0;
            event.currentTarget.value = digits
                ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount)
                : '';
            updatePaymentBalance(modal, amount);
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
        openPaymentReceiptStep
    };
});
