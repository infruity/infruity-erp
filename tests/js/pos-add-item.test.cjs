const {test} = require('node:test');
const assert = require('node:assert/strict');
const PosAddItem = require('../../public/assets/js/pos-add-item.js');

const api = (over) => Object.assign({
    id: 7, name: 'Apel Fuji', unit: {abbreviation: 'Kg'}, price: 45000, hpp: 30000,
    product_receipt: null, get_stock: {stock_available: 12.5}, parent_product: null,
}, over);

test('product row keeps every field the select2 flow read, with raw stock', () => {
    const row = PosAddItem.mapProductRow(api());
    assert.deepEqual(row, {
        id: 7, text: 'Apel Fuji', unit: {abbreviation: 'Kg'}, price: 45000, hpp: 30000, receipt: null,
        original_stock: 12.5, parent_id: 7,
    });
});

test('parent id follows the parent product when the item is a variant', () => {
    assert.equal(PosAddItem.mapProductRow(api({parent_product: {parent_id: 3}})).parent_id, 3);
});

test('missing stock counts as zero instead of NaN', () => {
    assert.equal(PosAddItem.mapProductRow(api({get_stock: null})).original_stock, 0);
});

test('available stock subtracts what the cart already holds and never goes below zero for the label', () => {
    assert.equal(PosAddItem.availableStock({original_stock: 10}, 4), 6);
    assert.equal(PosAddItem.availableStock({original_stock: 2}, 5), -3);
    assert.equal(PosAddItem.stockLabel(6.5, 'Kg'), '6,5 Kg');
    assert.equal(PosAddItem.stockLabel(0, 'Kg'), 'Habis');
    assert.equal(PosAddItem.stockLabel(-3, 'Kg'), 'Habis');
    assert.equal(PosAddItem.stockLabel(12, ''), '12');
});

test('discount prefix follows the existing rule: Rp above 100, percent otherwise', () => {
    assert.equal(PosAddItem.discountPrefix(0), '%');
    assert.equal(PosAddItem.discountPrefix(10), '%');
    assert.equal(PosAddItem.discountPrefix(100), '%');
    assert.equal(PosAddItem.discountPrefix(101), 'Rp');
    assert.equal(PosAddItem.discountPrefix(25000), 'Rp');
    assert.equal(PosAddItem.discountPrefix('abc'), '%');
});

test('price label carries the unit like the reference', () => {
    assert.equal(PosAddItem.priceLabel('Kg'), 'HARGA JUAL /KG');
    assert.equal(PosAddItem.priceLabel(''), 'HARGA JUAL');
    assert.equal(PosAddItem.quantityLabel('Kg'), 'KUANTITAS (KG)');
    assert.equal(PosAddItem.quantityLabel(undefined), 'KUANTITAS');
});

test('list query reuses the select2 request parameters', () => {
    assert.deepEqual(PosAddItem.listQuery({term: ' apel ', branch: '1'}), {term: 'apel', branch: '1', status: 'aktif', limit: 50});
    assert.deepEqual(PosAddItem.listQuery({term: '', branch: ''}), {term: '', branch: '', status: 'aktif', limit: 50});
});

test('a stale response is ignored when a newer search started', () => {
    const guard = PosAddItem.createRequestGuard();
    const first = guard.next();
    const second = guard.next();
    assert.equal(guard.isCurrent(first), false);
    assert.equal(guard.isCurrent(second), true);
});

test('unit suffixes are separate so the view can print them lighter than the label', () => {
    assert.equal(PosAddItem.priceUnit('Kg'), '/KG');
    assert.equal(PosAddItem.priceUnit(''), '');
    assert.equal(PosAddItem.quantityUnit('cup'), '(CUP)');
    assert.equal(PosAddItem.quantityUnit(undefined), '');
});
