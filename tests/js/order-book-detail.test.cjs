const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const asset = path.join(__dirname, '../../public/assets/js/order-book-detail.js');

function helpers() {
    assert.ok(fs.existsSync(asset), 'Order Book detail helpers must exist');
    return require(asset);
}

test('Order Book mounts the shared detail dialog independently of its filter and collection rewrite', () => {
    const view = fs.readFileSync(path.join(__dirname, '../../Modules/Pos/Resources/views/order-book/index.blade.php'), 'utf8');
    assert.match(view, /@section\('content'\)\s+@include\('pos::order-book.partials.detail'\)/);
    const partial = fs.readFileSync(path.join(__dirname, '../../Modules/Pos/Resources/views/order-book/partials/detail.blade.php'), 'utf8');
    assert.match(partial, /x-data="orderBookDetailDialog\(\)"/);
    assert.match(partial, /<x-erp\.detail-dialog/);
    assert.match(partial, /x-teleport="body"/);
    for (const field of ['customerDisplay', 'phone', 'branch', 'createdAt', 'createdBy', 'editedAt', 'editedBy']) {
        assert.ok(partial.includes(`x-text="selected.${field}"`), `Missing safe detail field ${field}`);
    }
    assert.ok(!partial.includes('x-html'));
    assert.match(partial, /assets\/js\/order-book-detail\.js/);
});

test('detail uses persisted labels with honest missing-value fallbacks', () => {
    const { normalize } = helpers();
    const detail = normalize({ customer_name: 'Pelanggan Uji', customer_phone: '081234564311', branch_name: 'Toko Uji', created_by_name: 'Pembuat Uji', updated_by_name: null, updated_at: null });
    assert.equal(detail.customerDisplay, 'Pelanggan Uji (4311)');
    assert.equal(detail.branch, 'Toko Uji');
    assert.equal(detail.createdBy, 'Dibuat oleh Pembuat Uji');
    assert.equal(detail.editedAt, '-');
    assert.equal(detail.editedBy, 'Diedit');
    assert.equal(normalize({}).createdBy, 'Dibuat oleh -');
    assert.equal(normalize({ customer_name: 'Uji (4311)', customer_phone: '081234564311' }).customerDisplay, 'Uji (4311)');
});

test('WhatsApp links accept phone formats but never a script or arbitrary URL', () => {
    const { whatsappUrl } = helpers();
    assert.equal(whatsappUrl('081234564311'), 'https://wa.me/6281234564311');
    assert.equal(whatsappUrl('+62 (812) 3456-4311'), 'https://wa.me/6281234564311');
    assert.equal(whatsappUrl('6281234564311'), 'https://wa.me/6281234564311');
    for (const value of ['', null, 'javascript:081234564311', '<img src=x>', 'https://wa.me/081234564311', '123', '12345678901234567890']) {
        assert.equal(whatsappUrl(value), null);
    }
});

test('detail reads existing customer data and rendered branch without inventing audit names', () => {
    const { fromRow } = helpers();
    assert.equal(typeof fromRow, 'function', 'Existing DataTables rows must supply the detail data');
    const first = { querySelector: selector => ({
        'a[data-ob-name]': { textContent: 'Pelanggan & Uji (4311)' },
        '[data-ob-branch]': { textContent: 'Cabang & Uji' }
    })[selector] || null };
    const date = { querySelector: () => ({ textContent: '31 Jul 2026 17:27' }) };
    const action = { textContent: 'Locked' };
    const detail = fromRow({ cells: [first, date, action] }, {
        customer: { name: 'Pelanggan &amp; Uji', whatsapp: '081234564311' },
        updated_at: '2026-07-31T11:45:00.000000Z', created_by: 42, updated_by: 43
    }, 'Asia/Jakarta');
    assert.equal(detail.customerDisplay, 'Pelanggan & Uji (4311)');
    assert.equal(detail.whatsappUrl, 'https://wa.me/6281234564311');
    assert.equal(detail.branch, 'Cabang & Uji');
    assert.equal(detail.createdAt, '31 Jul 2026, 17:27');
    assert.equal(detail.editedAt, '31 Jul 2026, 18:45');
    assert.equal(detail.createdBy, 'Dibuat oleh -');
    assert.equal(detail.editedBy, 'Waktu Pesanan Diedit');
    assert.equal(detail.locked, true);
});

test('dialog installs and cleans up its own Order Book event binding', () => {
    const { create } = helpers();
    const state = create();
    assert.equal(typeof state.init, 'function', 'Alpine must bind list clicks without changing shared collection code');
    assert.equal(typeof state.destroy, 'function', 'Removed scopes must release their handlers');
});

test('action proxies allow only same-origin links and the existing delete command', () => {
    const { actionFor } = helpers();
    assert.equal(typeof actionFor, 'function');
    const original = (href, onclick = null) => ({ getAttribute: name => ({ href, onclick })[name] ?? null });
    assert.equal(actionFor(original('/order-book/9/edit'), null, 'http://localhost').label, 'Edit');
    assert.equal(actionFor(original('http://localhost/order-book/order/9'), null, 'http://localhost').label, 'Proses Pesanan');
    assert.equal(actionFor(original('javascript:void(0)', 'deleteProduct(9)'), null, 'http://localhost').label, 'Hapus Pesanan');
    for (const href of ['//evil.test/orders', 'https://evil.test/', 'javascript:alert(1)', 'data:text/html,test', '#']) {
        assert.equal(actionFor(original(href), null, 'http://localhost'), null);
    }
    assert.equal(actionFor(original('javascript:void(0)', 'deleteProduct(9);alert(1)'), null, 'http://localhost'), null);
});

test('detail labels preserve text, not executable markup', () => {
    const { normalize } = helpers();
    const value = '<img src=x onerror=alert(1)> & "Uji"';
    assert.equal(normalize({ customer_name: value }).customerDisplay, value);
    assert.ok(!fs.readFileSync(asset, 'utf8').includes('innerHTML'));
});

test('detail shows the real creator name when the list response provides it', () => {
    const { fromRow } = helpers();
    const first = { querySelector: selector => ({
        'a[data-ob-name]': { textContent: 'Pelanggan Uji' },
        '[data-ob-branch]': { textContent: 'Cabang Uji' }
    })[selector] || null };
    const date = { querySelector: () => ({ textContent: '31 Jul 2026 17:27' }) };
    const detail = fromRow({ cells: [first, date, { textContent: '' }] }, { created_by: 42, created_by_name: 'Pembuat Uji' }, 'Asia/Jakarta');
    assert.equal(detail.createdBy, 'Dibuat oleh Pembuat Uji');
    // An id alone must never be turned into an invented name.
    assert.equal(fromRow({ cells: [first, date, { textContent: '' }] }, { created_by: 42 }, 'Asia/Jakarta').createdBy, 'Dibuat oleh -');
});
