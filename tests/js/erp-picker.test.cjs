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

test('normalizeItems carries the extra fields page handlers read from the select2 data object', () => {
    const { normalizeItems } = picker();
    const [item] = normalizeItems([{ id: 5, name: 'Bu Ani', tier_id: 2, address: 'Jl. Uji' }], (row) => ({ id: row.id, text: row.name, meta: 'Tier 2', data: row }));

    assert.equal(item.id, '5');
    assert.equal(item.data.tier_id, 2);
    assert.equal(item.data.address, 'Jl. Uji');
    // Rows without extra data keep the exact plain shape.
    assert.ok(!('data' in normalizeItems([{ id: 1, text: 'A' }])[0]));
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
    // With a select2 instance the selection goes through select2 itself, so select2('data') keeps the extra fields.
    assert.match(js, /trigger\('select', \{ data:/);
    assert.match(js, /X-Requested-With/);
    // Search results are written with textContent, never as markup.
    assert.ok(!js.includes('innerHTML'), 'picker must not inject server text as HTML');
});

test('picker assets load in both ERP layouts', () => {
    for (const layout of ['resources/views/template/root.blade.php', 'resources/views/layouts/erp.blade.php']) {
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

test('the picker pages through long lists and can pin fixed items on the first page', () => {
    const js = fs.readFileSync(asset, 'utf8');

    assert.match(js, /pagination/);
    assert.match(js, /options\.prepend/);
    assert.match(js, /scrollHeight/);
});

test('POS header swaps customer and branch selects for pickers and keeps the select2 handlers', () => {
    const js = read('Modules/Pos/Resources/views/pos/js/js-header.blade.php');

    assert.match(js, /ErpPicker\.attach\(\s*'#customer_id'/);
    assert.match(js, /ErpPicker\.attach\(\s*'#branch_id'/);
    assert.match(js, /route\('customer\.get-customer'\)/);
    assert.match(js, /\$\('#customer_id'\)\.on\('select2:select'/);
    // The walk-in customer stays selectable and keeps the fields the handler reads.
    assert.match(js, /Pelanggan Umum/);
    assert.match(js, /tier_id/);
    assert.match(js, /ongkir_address/);
});

test('optionItems lists a static select as picker items and skips the empty placeholder', () => {
    const { optionItems } = picker();
    const items = optionItems({ options: [{ value: '', text: 'Pilih' }, { value: '1', text: ' Toko GKB ' }, { value: '2', text: 'Toko GBA' }] });

    assert.deepEqual(items, [{ id: '1', text: 'Toko GKB', meta: '' }, { id: '2', text: 'Toko GBA', meta: '' }]);
});

test('the picker supports a static source, extra request params and living inside a Bootstrap modal', () => {
    const js = fs.readFileSync(asset, 'utf8');

    assert.match(js, /optionItems\(select\)/);
    assert.match(js, /options\.params/);
    // Bootstrap traps focus inside the open modal, so the overlay must be hosted inside it.
    assert.match(js, /closest\('\.modal'\)/);
});

test('Stok Opname form uses pickers for branch and product and keeps the select2 data contract', () => {
    const view = read('Modules/Transaction/Resources/views/stock-opname/index.blade.php');

    assert.match(view, /ErpPicker\.attach\(\s*'#branch_id'/);
    assert.match(view, /ErpPicker\.attach\(\s*'#product_id'/);
    assert.match(view, /route\('ajax\.stock-available'\)/);
    // The select2:select handler still receives stock and unit price from the picker's data object.
    assert.match(view, /stock_available:\s*item\.stock_available/);
    assert.match(view, /unit_price:/);
    assert.match(view, /branch_id:\s*\$\('#branch_id'\)\.val\(\)/);
});

test('POS Buah, Hadiah and Jus sheets use one list-product picker and keep the stock rules and handlers', () => {
    const js = read('Modules/Pos/Resources/views/pos/js/js-buah.blade.php');

    assert.equal((js.match(/this\.attachListProductPicker\(\{/g) || []).length, 3);
    for (const id of ['#select_product', '#select_gift', '#select_jus']) {
        assert.match(js, new RegExp(`selector: '${id}'`), `${id} must use the picker`);
        // The select2 handler that writes addProduct stays in place.
        assert.ok(js.includes(`$('${id}').select2(`), `${id} select2 must stay`);
    }
    // Same data contract the select2 results had: stock in cart, original stock, parent id, stock map.
    for (const token of ['window.productStockMap', 'calculateUsedStock', 'stock_available', 'original_stock', 'parent_id', 'receipt: item.product_receipt', "url: '/ajax/listProduct'", "searchParam: 'term'"]) {
        assert.ok(js.includes(token), `Missing ${token}`);
    }
    assert.match(js, /produksi otomatis/);
    // Out-of-stock fruit is still stopped by the existing handler, not by hiding it.
    assert.match(js, /Stok tidak mencukupi/);
});
