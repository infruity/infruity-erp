const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const viewPath = path.join(__dirname, '../../Modules/Pos/Resources/views/order-book/index.blade.php');
const view = fs.readFileSync(viewPath, 'utf8');

function loadOrderBookUiHelpers() {
    const helper = view.match(/const orderBookReferenceUi = \(\(\) => \{[\s\S]*?\}\)\(\);/);
    assert.ok(helper, 'the order-book reference UI helpers must be present in the Blade view');
    return vm.runInNewContext(`${helper[0]}; orderBookReferenceUi;`);
}

test('mobile customer avatar uses initials rather than the generic receipt icon', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.initials('Kak Alvi (8219)'), 'KA');
    assert.equal(ui.initials('Bu Enda'), 'BE');
});

test('mobile order dates use Indonesian month labels and reference punctuation', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.formatDate('27 Oct 2026 09:00'), '27 Okt 2026, 09:00');
    assert.equal(ui.formatDate('27 Okt 2026 09:00'), '27 Okt 2026, 09:00');
});

test('mobile status labels preserve order-book meanings', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.status('pending'), 'Pending');
    assert.equal(ui.status('Proses'), 'Proses');
    assert.equal(ui.status('Selesai'), 'Selesai');
});

test('mobile invoice uses the related POS number and a dash for pending orders', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.invoice('Pending', { invoice_number: 'POS-2026-1064' }, 'ORD20261001'), '-');
    assert.equal(ui.invoice('Proses', { invoice_number: 'POS-2026-1064' }, 'ORD20261001'), 'POS-2026-1064');
    assert.equal(ui.invoice('Selesai', { invoice_number: 'ORD202607088' }, 'ORD202607088'), 'ORD202607088');
    assert.equal(ui.invoice('Selesai', null, 'ORD20261001'), 'ORD20261001');
});
