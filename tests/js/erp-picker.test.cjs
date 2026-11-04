const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '../..');
const asset = path.join(root, 'public/assets/js/erp-picker.js');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

function picker() {
    assert.ok(fs.existsSync(asset), 'erp-picker.js must exist');
    return require(asset);
}

test('normalizeItems keeps id and label, maps custom rows and drops rows without an id', () => {
    const { normalizeItems } = picker();
    const rows = [{ product_id: 7, products: { name: 'Jus Alpukat' }, unit: 'Cup' }, { products: { name: 'Tanpa id' } }, { product_id: 0, products: { name: 'Nol' } }];
    const items = normalizeItems(rows, (row) => ({ id: row.product_id, text: row.products?.name, meta: row.unit }));

    assert.deepEqual(items, [{ id: '7', text: 'Jus Alpukat', meta: 'Cup' }, { id: '0', text: 'Nol', meta: '' }]);
});

test('normalizeItems accepts the plain {id,text} shape and a {results} envelope', () => {
    const { normalizeItems } = picker();

    assert.deepEqual(normalizeItems([{ id: 3, text: 'Toko GKB' }]), [{ id: '3', text: 'Toko GKB', meta: '' }]);
    assert.deepEqual(normalizeItems({ results: [{ id: 4, text: 'Toko GBA' }] }), [{ id: '4', text: 'Toko GBA', meta: '' }]);
    assert.deepEqual(normalizeItems(null), []);
});

test('filterItems matches label and meta case-insensitively and ignores blank terms', () => {
    const { filterItems } = picker();
    const items = [{ id: '1', text: 'Jus Alpukat', meta: 'Cup' }, { id: '2', text: 'Rujak mix', meta: 'Box' }];

    assert.equal(filterItems(items, '').length, 2);
    assert.deepEqual(filterItems(items, 'ALPU').map((i) => i.id), ['1']);
    assert.deepEqual(filterItems(items, 'box').map((i) => i.id), ['2']);
    assert.deepEqual(filterItems(items, 'zzz'), []);
});

test('selectedLabel reads the chosen option and reports nothing for an empty value', () => {
    const { selectedLabel } = picker();

    assert.equal(selectedLabel({ value: '9', selectedOptions: [{ text: ' Jus Melon ' }] }), 'Jus Melon');
    assert.equal(selectedLabel({ value: '', selectedOptions: [{ text: 'Pilih Produk' }] }), '');
    assert.equal(selectedLabel({ value: '', selectedOptions: [] }), '');
});

test('the picker drives the original select and the select2 events the page handlers already use', () => {
    const js = fs.readFileSync(asset, 'utf8');

    assert.match(js, /select2:select/);
    assert.match(js, /select2:clear/);
    assert.match(js, /new Option\(/);
    assert.match(js, /X-Requested-With/);
    // Search results are written with textContent, never as markup.
    assert.ok(!js.includes('innerHTML'), 'picker must not inject server text as HTML');
});

test('picker assets load in both ERP layouts', () => {
    for (const layout of ['resources/views/template/root.blade.php', 'resources/views/layouts/erp-tailwind.blade.php']) {
        const source = read(layout);
        assert.match(source, /assets\/css\/erp-picker\.css/, `${layout} must load the picker css`);
        assert.match(source, /assets\/js\/erp-picker\.js/, `${layout} must load the picker js`);
    }
});

test('Produksi form swaps the select2 look for pickers without changing ids or handlers', () => {
    const js = read('Modules/Transaction/Resources/views/production/js-create.blade.php');

    assert.match(js, /ErpPicker\.attach\(\s*'#product_id'/);
    assert.match(js, /ErpPicker\.attach\(\s*'#branch_id'/);
    assert.match(js, /route\('products\.get-receipt'\)/);
    assert.match(js, /route\('ajax\.getBranch'\)/);
    assert.match(js, /\.on\('select2:select'/);
    assert.match(js, /loadRecipeIngredients\(selectedProductId\)/);
});
