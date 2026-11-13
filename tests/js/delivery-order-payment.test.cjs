globalThis.ErpTone = require('../../public/assets/js/erp-tone.js');
const test = require('node:test');
const assert = require('node:assert/strict');
const cards = require('../../public/assets/js/delivery-order-mobile.js');

test('actual amount input handler supports sequential typing and formatting on blur', () => {
    const fs = require('node:fs'), vm = require('node:vm'), path = require('node:path');
    const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/delivery-order-mobile.js'), 'utf8');
    const start = source.indexOf("        modal.querySelector('[data-delivery-order-payment-amount]')?.addEventListener('input'");
    const end = source.indexOf("        document.addEventListener('keydown'", start);
    assert.ok(start >= 0 && end > start);
    const handlers = {}, balances = [];
    const input = { value: '', removeAttribute() {}, addEventListener: (name, fn) => { handlers[name] = fn; } };
    const zero = { checked: false }, wrap = { hidden: true }, error = { hidden: true };
    const modal = { querySelector: selector => ({ '[data-delivery-order-payment-amount]': input, '[data-delivery-order-zero-confirm]': zero, '[data-delivery-order-zero-confirm-wrap]': wrap, '[data-delivery-order-payment-error]': error })[selector] };
    vm.runInNewContext(source.slice(start, end), { modal, parseReceiptAmount: cards.parseReceiptAmount, updatePaymentBalance: (_, amount) => balances.push(amount), Intl });
    for (const digit of '50000') {
        input.value += digit;
        handlers.input({ currentTarget: input });
    }
    assert.equal(cards.parseReceiptAmount(input.value), 50000);
    assert.equal(balances.at(-1), 50000);
    assert.equal(typeof handlers.blur, 'function');
    handlers.blur({ currentTarget: input });
    assert.equal(input.value, '50.000');
    handlers.focus({ currentTarget: input });
    assert.equal(input.value, '50000');
    input.value += '0';
    handlers.input({ currentTarget: input });
    assert.equal(balances.at(-1), 500000);
    for (const invalid of ['-100', '1.5', '1e5', '']) {
        input.value = invalid;
        handlers.input({ currentTarget: input });
        handlers.blur({ currentTarget: input });
        assert.equal(input.value, invalid);
        assert.equal(balances.at(-1), null);
    }
    input.value = '0';
    zero.checked = true;
    handlers.input({ currentTarget: input });
    assert.equal(zero.checked, false);
    assert.equal(wrap.hidden, false);
});

test('delivery-only confirmation delegates to the shared panel without writing', () => {
    const view = require('node:fs').readFileSync(require('node:path').join(__dirname, '../../Modules/Pos/Resources/views/delivery-order/index.blade.php'), 'utf8');
    const action = view.slice(view.indexOf('        function setSelesai(id) {'), view.indexOf('        function setBayar(id) {'));
    const opened = [];
    require('node:vm').runInNewContext(action + '\nsetSelesai(42);', { setBayar: id => opened.push(id) });
    assert.deepEqual(opened, [42]);
});

test('partial receipts keep server payment state separate from the delivery state', () => {
    const billing = cards.buildBillingSummary({ total: 190000, voucher: 10000, payment_details: [{ total: 100000 }] });
    const order = { orderId: '42', total: 190000, deliveryStatus: 'received', paymentStatus: 'unpaid', billing, paymentUrl: '/pos/42/payment' };
    assert.equal(cards.buildViewModel(order).paymentLabel, 'Bayar Sebagian');
    const detail = cards.buildDetailViewModel(order);
    assert.deepEqual(detail.billing, billing);
    assert.equal(detail.paymentUrl, '/pos/42/payment');
    assert.equal(detail.actionLabel, 'Tutup');
    assert.equal(cards.requiresPaymentReceiptStep(detail), false);
});

test('payment shell provides explicit zero confirmation, cumulative figures, and no legacy unvalidated payment popup', () => {
    const fs = require('node:fs');
    const view = fs.readFileSync(require('node:path').join(__dirname, '../../Modules/Pos/Resources/views/delivery-order/index.blade.php'), 'utf8');
    for (const selector of ['data-delivery-order-zero-confirm', 'data-delivery-order-continue-payment', 'data-delivery-order-billing-field="previous-paid"', 'data-delivery-order-billing-field="remaining"']) assert.ok(view.includes(selector), selector);
    assert.ok(!view.includes('nominalBayar'));
    assert.ok(require('node:fs').readFileSync(require('node:path').join(__dirname, '../../public/assets/js/delivery-order-mobile.js'), 'utf8').includes('if (response?.success !== true)'));
});

test('receipt input distinguishes blank, invalid, positive and explicit zero', () => {
    assert.equal(typeof cards.parseReceiptAmount, 'function');
    for (const value of ['', ' ', '-100', '1e5', '1.2', '1,5', 'NaN', '9007199254740992']) assert.equal(cards.parseReceiptAmount(value), null);
    assert.equal(cards.parseReceiptAmount('0'), 0);
    assert.equal(cards.parseReceiptAmount('50.000'), 50000);
    assert.equal(cards.parseReceiptAmount('50000'), 50000);
});

test('receipt validation requires explicit zero acknowledgement and a known positive outstanding balance', () => {
    assert.equal(typeof cards.validateReceipt, 'function');
    const order = { deliveryState: 'unreceived', paymentState: 'unpaid', billing: { available: true, remaining: 80000 } };
    assert.ok(cards.validateReceipt(order, '', false));
    assert.ok(cards.validateReceipt(order, '-100', false));
    assert.ok(cards.validateReceipt(order, '0', false));
    assert.equal(cards.validateReceipt(order, '0', true), '');
    assert.equal(cards.validateReceipt(order, '50.000', false), '');
    assert.ok(cards.validateReceipt({ ...order, billing: { available: false } }, '50.000', false));
    assert.ok(cards.validateReceipt({ ...order, billing: { available: true, remaining: 0 } }, '50.000', false));
    assert.ok(cards.validateReceipt({ ...order, paymentState: 'paid' }, '50.000', false));
    assert.ok(cards.validateReceipt({ ...order, deliveryState: 'received' }, '50.000', false));
});

test('fractional remaining balances do not round away a shortage', () => {
    assert.deepEqual(cards.buildPaymentBalance(100.50, 100), { type: 'underpaid', label: 'Kurang Bayar :', value: 'Rp 0,5' });
});

test('billing uses existing net invoice and cumulative ledger, not a new payment as the whole total', () => {
    assert.equal(typeof cards.buildBillingSummary, 'function');
    const summary = cards.buildBillingSummary({ total: '190000.00', voucher: '10000.00', paid: '100000.00', payment_details: [{ total: '60000.00' }, { total: '40000.00' }] });
    assert.equal(summary.available, true);
    assert.equal(summary.invoiceTotal, 180000);
    assert.equal(summary.voucher, 10000);
    assert.equal(summary.previousPaid, 100000);
    assert.equal(summary.remaining, 80000);
    assert.deepEqual(cards.buildPaymentBalance(summary.remaining, 50000), { type: 'underpaid', label: 'Kurang Bayar :', value: 'Rp 30.000' });
});

test('billing distinguishes known zero from missing, invalid, or incomplete monetary data', () => {
    assert.equal(typeof cards.buildBillingSummary, 'function');
    assert.equal(cards.buildBillingSummary({ total: '62000', voucher: null, payment_details: [] }).previousPaid, 0);
    assert.equal(cards.buildBillingSummary({ total: 62000, voucher: 0, paid: 20000 }).remaining, 42000);
    for (const raw of [null, {}, { total: 62000, paid: 0 }, { total: 62000, voucher: 0 }, { total: 62000, voucher: 0, payment_details: [{ total: null }] }, { total: 'garbage', voucher: 0, paid: 0 }, { total: 10, voucher: 20, paid: 0 }]) {
        assert.equal(cards.buildBillingSummary(raw).available, false);
    }
});

test('billing follows ledger totals when cached paid differs and retains fractional invoice balances', () => {
    const summary = cards.buildBillingSummary({ total: '100.50', voucher: '0.25', paid: '1', payment_details: [{ total: '10.25' }] });
    assert.equal(summary.previousPaid, 10.25);
    assert.equal(summary.remaining, 90);
});


function receiptHarness() {
    const source = require('node:fs').readFileSync(require('node:path').join(__dirname, '../../public/assets/js/delivery-order-mobile.js'), 'utf8');
    const nodes = new Map();
    function node(selector) {
        if (!nodes.has(selector)) nodes.set(selector, {
            value: '', hidden: false, disabled: false, readOnly: false, checked: false, textContent: '', dataset: {}, handlers: {},
            addEventListener(name, fn) { this.handlers[name] = fn; },
            setAttribute(name, value) { if (name === 'hidden') this.hidden = true; this[name] = value; },
            removeAttribute(name) { if (name === 'hidden') this.hidden = false; else delete this[name]; },
            focus() {}, scrollIntoView() {}, closest() { return null; }
        });
        return nodes.get(selector);
    }
    const modal = { ...node('modal'), querySelector: node, querySelectorAll: selector => [node(selector)] };
    const requests = [], alerts = [];
    let reloads = 0;
    const $ = { ajax(options) {
        const callbacks = {};
        requests.push({ options, callbacks });
        const deferred = {};
        for (const method of ['done', 'fail', 'always']) deferred[method] = fn => { callbacks[method] = fn; return deferred; };
        return deferred;
    } };
    const context = { module: { exports: {} }, document: {
        getElementById: () => modal, querySelector: () => ({ content: 'fixture-token' }),
        body: { style: { overflow: '' } }, addEventListener() {}
    }, window: { jQuery: $, Swal: { fire: alert => alerts.push(alert) }, reloadDataTable: () => reloads++, location: { href: 'https://example.test/delivery-order', origin: 'https://example.test' } }, Intl, URL };
    require('node:vm').runInNewContext(source.replace('        attach,', '        attach, openDetailModal, bindDetailModal,'), context);
    const api = context.module.exports;
    api.bindDetailModal();
    return { api, node, modal, requests, alerts, reloads: () => reloads };
}

for (const mode of ['paid', 'unpaid', 'paid-unavailable']) {
    test(`actual shared Submit handler preserves ${mode} request and failure contracts`, () => {
        const h = receiptHarness();
        const paid = mode.startsWith('paid');
        const billing = mode === 'paid-unavailable' ? { available: false } : cards.buildBillingSummary({ total: 190000, voucher: 10000, paid: paid ? 180000 : 100000 });
        h.api.openDetailModal({ orderId: '42', deliveryStatus: 'unreceived', paymentStatus: paid ? 'paid' : 'unpaid', total: 190000, shippingFee: 10000, billing });
        h.node('[data-delivery-order-detail-action]').handlers.click();
        assert.equal(h.modal.dataset.deliveryOrderStep, 'payment');
        assert.equal(h.requests.length, 0, 'opening confirmation cannot write');
        const input = h.node('[data-delivery-order-payment-amount]');
        const submit = h.node('[data-delivery-order-payment-submit]');
        const balance = h.node('[data-delivery-order-payment-balance]');
        assert.equal(input.readOnly, paid);
        assert.equal(h.node('[data-delivery-order-payment-amount-label]').hidden, paid);
        assert.equal(input['aria-label'], paid ? 'Pembayaran sudah tercatat' : 'Nominal pembayaran yang diterima');
        assert.equal(input.value, paid ? (billing.available ? '180.000' : '') : '');
        assert.equal(input.placeholder, mode === 'paid-unavailable' ? 'Belum tersedia' : 'Masukkan nominal');
        assert.equal(h.node('.delivery-order-billing-summary').hidden, paid);
        assert.equal(h.node('[data-delivery-order-payment-amount-label]').textContent, paid ? 'Pembayaran sudah tercatat' : 'Uang diterima saat ini');
        assert.equal(balance.hidden, !paid || !billing.available);
        assert.equal(submit.textContent, paid ? 'Terima Barang' : 'Bayar');
        if (!paid) {
            submit.handlers.click();
            assert.equal(h.requests.length, 0, 'blank is not zero');
            input.value = '50000';
            input.handlers.input({ currentTarget: input });
            assert.equal(balance.hidden, false);
            assert.equal(h.node('[data-delivery-order-payment-balance-value]').textContent, 'Rp 30.000');
            input.value = 'invalid';
            input.handlers.input({ currentTarget: input });
            assert.equal(balance.hidden, true);
            input.value = '50000';
        }
        submit.handlers.click();
        submit.handlers.click();
        assert.equal(h.requests.length, 1, 'double-click is guarded');
        const request = h.requests[0];
        assert.equal(request.options.url, '/delivery-order/set-selesai/42');
        assert.equal(request.options.type, 'PUT');
        assert.deepEqual(JSON.parse(JSON.stringify(request.options.data)), paid ? { _token: 'fixture-token' } : { _token: 'fixture-token', nominal: 50000 });
        h.modal.handlers.click({ target: { closest: () => true } });
        assert.equal(h.modal.hidden, false, 'close is blocked while saving');
        request.callbacks.done({ success: false, message: 'Fixture business failure' });
        request.callbacks.always();
        assert.equal(h.modal.hidden, false);
        assert.equal(h.modal.dataset.deliveryOrderStep, 'payment');
        assert.equal(input.value, paid ? (billing.available ? '180.000' : '') : '50000');
        assert.equal(h.node('[data-delivery-order-payment-error]').textContent, 'Fixture business failure');
        assert.equal(h.reloads(), 0);
        assert.equal(h.alerts.length, 0);
        assert.equal(input.readOnly, paid);
        submit.handlers.click();
        h.requests[1].callbacks.done({ success: true });
        h.requests[1].callbacks.always();
        assert.equal(h.modal.hidden, true);
        assert.equal(h.reloads(), 1);
        assert.equal(h.alerts[0].icon, 'success');
    });
}

test('removed detail status and outcome writers do not remain in runtime', () => {
    const source = require('node:fs').readFileSync(require('node:path').join(__dirname, '../../public/assets/js/delivery-order-mobile.js'), 'utf8');
    assert.ok(!source.includes("setDetailField(modal, 'delivery-status'"));
    assert.ok(!source.includes("setDetailField(modal, 'payment-status'"));
    assert.ok(!source.includes('data-delivery-order-receipt-outcome'));
    assert.ok(!source.includes("label: 'Masukkan nominal :'"));
});
