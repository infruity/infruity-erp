const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function harness(amount = '10.000') {
    const source = fs.readFileSync(path.join(__dirname, '../../Modules/Pos/Resources/views/delivery-order/index.blade.php'), 'utf8');
    const start = source.includes('        const deliverySubmissions') ? source.indexOf('        const deliverySubmissions') : source.indexOf('        function setSelesai');
    const script = source.slice(start, source.indexOf("        $('#kt_modal_add_customer').on", start));
    const requests = [], dialogs = [], validation = [];
    let reloads = 0;
    const input = { value: amount, addEventListener() {} };
    const $ = () => ({ attr: () => 'fixture-csrf' });
    $.ajax = options => requests.push(options);
    const context = { $, console, Set, document: { getElementById: () => input }, reloadDataTable: () => reloads++, Swal: {
        fire(options) {
            dialogs.push(options);
            const value = options.preConfirm ? options.preConfirm() : undefined;
            return Promise.resolve({ isConfirmed: value !== false, value });
        },
        showValidationMessage: message => validation.push(message),
    } };
    vm.createContext(context); vm.runInContext(script, context);
    return { context, requests, dialogs, validation, reloads: () => reloads };
}
const flush = () => new Promise(resolve => setImmediate(resolve));

test('a business rejection with HTTP 200 never becomes a success toast or reload', async () => {
    const h = harness(); h.context.setBayar(11); await flush();
    h.requests[0].success({ success: false, message: 'Fixture rejection' });
    assert.equal(h.dialogs.filter(d => d.icon === 'success').length, 0);
    assert.equal(h.dialogs.filter(d => d.icon === 'error').length, 1);
    assert.equal(h.reloads(), 0);
});

test('duplicate in-flight confirmations submit once and completion releases the control', async () => {
    const h = harness(); h.context.setBayar(11); h.context.setBayar(11); await flush();
    assert.equal(h.requests.length, 1);
    assert.equal(h.requests[0].data.nominal, '10.000');
    h.requests[0].complete();
    h.context.setBayar(11); await flush();
    assert.equal(h.requests.length, 2);
});

test('explicit zero can request voucher-covered settlement without inventing cash', async () => {
    const h = harness('0'); h.context.setBayar(11); await flush();
    assert.equal(h.requests.length, 1);
    assert.equal(h.requests[0].data.nominal, '0');
});

test('invalid or empty cash is rejected before sending a COD request', async () => {
    for (const value of ['', '12,50', '1e3', '1.00.000', '-100', '100000000000000']) {
        const h = harness(value); h.context.setBayar(11); await flush();
        assert.equal(h.requests.length, 0, value);
        assert.equal(h.validation.length, 1, value);
    }
});

test('receipt without payment preserves its payload and server conflicts refresh the read-only listing', async () => {
    const h = harness(); h.context.setSelesai(11); await flush();
    assert.equal(h.requests[0].type, 'PUT');
    assert.equal(h.requests[0].url, '/delivery-order/set-selesai/11');
    assert.equal(h.requests[0].data.nominal, undefined);
    h.requests[0].error({ status: 409, responseJSON: { message: 'Already delivered' } });
    assert.equal(h.dialogs.filter(d => d.icon === 'success').length, 0);
    assert.equal(h.reloads(), 1);
});
