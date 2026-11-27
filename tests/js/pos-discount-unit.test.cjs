// No Rp / % buttons (owner's decision, 2026-11-27): the typed number picks the unit, 100 or less is a percentage and
// above that rupiah. The unit is still saved with the sale, and a reopened draft keeps its saved unit until retyped.
const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '..', '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8').replace(/\r/g, '');
const cashier = read('Modules/Pos/Resources/views/pos/js/js-buah.blade.php');
const payment = read('Modules/Pos/Resources/views/pos/js-payment.blade.php');

/** The source of one object method, found by name and matched to its closing brace. */
function method(source, name) {
    const start = source.search(new RegExp(`\\n\\s*${name}\\([^)\\n]*\\)\\s*\\{`));
    assert.notStrictEqual(start, -1, `${name}() not found`);
    let depth = 0;
    for (let i = source.indexOf('{', start); i < source.length; i++) {
        if (source[i] === '{') depth++;
        if (source[i] === '}' && --depth === 0) return source.slice(start, i + 1).trim();
    }
    throw new Error(`${name}() is not closed`);
}

function component(source, names, state) {
    const body = names.map(name => method(source, name)).join(',\n');
    const object = new Function(`return ({\n${body}\n});`)();
    return Object.assign(object, { formatRupiah: n => String(n), $nextTick: () => {} }, state);
}

const typing = value => ({ target: { value, selectionStart: value.length, setSelectionRange() {} } });

const handlers = ['typedDiscountUnit', 'updateDiscountValue', 'updateEditDiscountValue', 'updateDiskonGlobal', 'updateDiskonOngkir'];
const till = () => component(cashier, handlers, {
    addProduct: { qty: 1, price: 20000, discountType: 'amount' },
    editQty: 1, editPrice: 20000, editDiscountType: 'amount',
    subtotal: 20000, diskonGlobal: 0, diskonGlobalType: 'amount',
    ongkirGlobal: 15000, diskonOngkir: 0, diskonOngkirType: 'amount',
});

const fields = {
    'item discount': [(c, v) => c.updateDiscountValue(typing(v)), c => [c.addProduct.discountType, c.addProduct.discountNominal, c.addProduct.discount]],
    'edited item discount': [(c, v) => c.updateEditDiscountValue(typing(v)), c => [c.editDiscountType, c.editDiscountNominal, c.editDiscount]],
    'note discount': [(c, v) => c.updateDiskonGlobal(typing(v)), c => [c.diskonGlobalType, c.diskonGlobal]],
    'shipping discount': [(c, v) => c.updateDiskonOngkir(typing(v)), c => [c.diskonOngkirType, c.diskonOngkir]],
};

for (const [label, [type, result]] of Object.entries(fields)) {
    test(`${label}: 100 or less is a percentage, above that rupiah`, () => {
        for (const [typed, unit] of [['10', 'percent'], ['100', 'percent'], ['101', 'amount'], ['5000', 'amount']]) {
            const c = till();
            type(c, typed);
            assert.deepStrictEqual(result(c).slice(0, 2), [unit, Number(typed)], typed);
        }
    });

    test(`${label}: the unit follows the number while it is being typed`, () => {
        const c = till();
        const seen = ['5', '50', '500', '50'].map(typed => (type(c, typed), result(c)[0]));
        assert.deepStrictEqual(seen, ['percent', 'percent', 'amount', 'percent']);
    });
}

test('10 on an item of 20.000 takes 2.000 off', () => {
    const c = till();
    c.updateDiscountValue(typing('10'));
    assert.strictEqual(c.addProduct.discount, 2000);
});

test('a rupiah discount larger than the line is capped and stays rupiah', () => {
    const c = till();
    c.addProduct.price = 80;
    c.updateDiscountValue(typing('500'));
    assert.deepStrictEqual([c.addProduct.discountType, c.addProduct.discountNominal], ['amount', 80]);
});

test('Diskon Nota on the payment page picks the unit the same way', () => {
    const page = component(payment, ['typedDiscountUnit', 'typeDiscount'], {
        discountType: 'amount', discountInput: '', fmtDigits: v => v,
        get liveDiscountValue() { return Number(this.discountInput || 0); },
    });
    Object.defineProperty(page, 'liveDiscountValue', { get() { return Number(this.discountInput || 0); } });
    for (const [typed, unit] of [['10', 'percent'], ['100', 'percent'], ['101', 'amount']]) {
        page.discountInput = typed;
        page.typeDiscount();
        assert.strictEqual(page.discountType, unit, typed);
    }
    assert.match(read('Modules/Pos/Resources/views/pos/payment.blade.php'), /@input="typeDiscount\(\)"/);
});

test('no screen shows the Rp / % buttons any more', () => {
    for (const file of ['create2', 'payment', 'segment/ringkasan', 'segment/modal-product', 'segment/modal-jus']) {
        assert.doesNotMatch(read(`Modules/Pos/Resources/views/pos/${file}.blade.php`), /unit-toggle|pos-unit/, file);
    }
    assert.doesNotMatch(read('Modules/Pos/Resources/views/order-book/order.blade.php'), /unit-toggle|pos-unit/);
    assert.ok(!fs.existsSync(path.join(root, 'Modules/Pos/Resources/views/pos/segment/unit-toggle.blade.php')));
});

test('a reopened draft keeps its saved unit', () => {
    assert.match(cashier, /diskonGlobalType = transactionData\.discount_type \|\| legacyUnit\(this\.diskonGlobal\)/);
    assert.match(cashier, /diskonOngkirType = transactionData\.ongkir_discount_type \|\| legacyUnit\(this\.diskonOngkir\)/);
});
