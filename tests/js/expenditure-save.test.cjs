const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function harness(response = { ok: true, body: { success: true, transaksi_id: 11 } }) {
    let source = fs.readFileSync(path.join(__dirname, '../../Modules/Pos/Resources/views/expenditure/js/js-product.blade.php'), 'utf8');
    source = source.replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '')
        .replace(/@json\(\$data \?\? null\)/g, 'null')
        .replace(/@json\(\$detail \?\? null\)/g, 'null')
        .replace(/\{\{ Request::segment\(3\) \}\}/g, '')
        .replace(/\{\{ \$data->type \?\? 'pemasukan' \}\}/g, 'pemasukan');
    const requests = [], errors = [];
    let redirects = 0;
    const fields = { branch_id: '1', type: 'pengeluaran', date: '2026-10-29', invoice_number: 'EXP202610001', payment_id: '1' };
    const context = {
        console: { log() {}, error() {} },
        document: { querySelector(selector) {
            if (selector.startsWith('meta')) return { getAttribute: () => 'fixture-csrf' };
            const name = selector.match(/name="([^"]+)"/)[1];
            return { value: fields[name] };
        } },
        Swal: { fire: value => errors.push(value) },
        redirectToHome: () => { redirects++; },
        fetch: async (url, options) => {
            requests.push({ url, payload: JSON.parse(options.body) });
            if (response.networkError) throw new Error('Fixture connection failure');
            return { ok: response.ok, status: response.status ?? 200, json: async () => response.body };
        },
        setTimeout, clearTimeout,
    };
    vm.createContext(context);
    vm.runInContext(source, context);
    const app = context.posApp();
    app.cart = [{ id: 99, name: 'Fixture expense', unit: 'Item', price: 10000, qty: 1, discount: 0, total_input: 10000 }];
    return { app, requests, errors, redirects: () => redirects };
}

const flush = () => new Promise(resolve => setImmediate(resolve));

test('denied, final-state, validation, and server failures keep the cart and never redirect', async () => {
    for (const status of [403, 409, 422, 500]) {
        for (const method of ['saveTransaction', 'saveToOrderBook']) {
            const h = harness({ ok: false, status, body: { success: false, message: 'Fixture rejection' } });
            const cart = JSON.stringify(h.app.cart);
            let released = 0;
            await h.app[method](() => released++);
            await flush();
            assert.equal(h.redirects(), 0);
            assert.equal(h.errors.length, 1);
            assert.equal(h.errors[0].text, 'Fixture rejection');
            assert.equal(released, 1);
            assert.equal(JSON.stringify(h.app.cart), cart);
        }
    }
});

test('new transaction sends an explicit null identity and only successful saves redirect', async () => {
    for (const method of ['saveTransaction', 'saveToOrderBook']) {
        const h = harness();
        let released = 0;
        await h.app[method](() => released++);
        assert.equal(h.requests[0].payload.expenditure_id, null);
        assert.equal(h.redirects(), 1);
        assert.equal(h.app.expenditureId, 11);
        assert.equal(released, 1);
    }
});

test('network and application failures preserve state and release save controls', async () => {
    for (const response of [{ networkError: true }, { ok: true, body: { success: false, message: 'Fixture denied' } }]) {
        const h = harness(response);
        let released = 0;
        await h.app.saveTransaction(() => released++);
        assert.equal(h.redirects(), 0);
        assert.equal(h.errors.length, 1);
        assert.equal(h.app.cart.length, 1);
        assert.equal(released, 1);
    }
});

test('empty cart never submits and releases the save button', async () => {
    const h = harness(); h.app.cart = [];
    let released = 0;
    await h.app.saveTransaction(() => released++);
    assert.equal(h.requests.length, 0);
    assert.equal(h.redirects(), 0);
    assert.equal(released, 1);
});

test('loading an existing item preserves its product ID instead of reusing the detail row ID', () => {
    const h = harness();
    h.app.cart = [];
    h.app.loadExistingData({ id: 11, paid: 0 }, [{ id: 44, product_id: 99, product_name: 'Fixture item', product_unit: 'Item', price: 10000, quantity: 1, subtotal: 10000 }]);
    assert.equal(h.app.cart[0].id, 99);
});

test('edit saves send explicit expenditure identity for draft and final instead of targeting invoice previews', async () => {
    for (const method of ['saveTransaction', 'saveToOrderBook']) {
        const h = harness();
        h.app.loadExistingData({ id: 11, paid: 5000 }, []);
        await h.app[method]();
        await flush();
        assert.equal(h.requests.length, 1);
        assert.equal(h.requests[0].url, '/expenditure/save-transaction');
        assert.equal(h.requests[0].payload.expenditure_id, 11);
        assert.equal(h.requests[0].payload.status, method === 'saveTransaction' ? 'draft' : 'paid');
    }
});
