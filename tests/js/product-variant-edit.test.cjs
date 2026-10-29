const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Execute the actual delegated edit binding, not a duplicate handler.
const source = fs.readFileSync(path.join(__dirname, '../../Modules/Master/Resources/views/products/partials/create-script.blade.php'), 'utf8');
const binding = source.match(/\$\('#variant_table'\)\.on\('click', '\.edit-variant', function\(\) \{[\s\S]*?\n\s*\}\);/);
assert.ok(binding, 'Variant edit binding must be present.');

// Minimal jQuery/input boundary doubles. Match jQuery's data-attribute coercion;
// PHP tests cover real endpoint HTML and DOM decoding, not this boundary double.
function dataValue(value) {
    if (value === 'true') return true;
    if (value === 'false') return false;
    if (value === 'null') return null;
    if (value === String(+value)) return +value;
    if (/^(?:\{[\s\S]*\}|\[[\s\S]*\])$/.test(value)) {
        try { return JSON.parse(value); } catch {}
    }
    return value;
}

for (const name of [
    'Apel "Fuji" & O\'Reilly <Premium>',
    '"><img src=x onerror="window.__variantInjected=1">',
    '&quot; &#34; &amp;',
    'Jus 🍊 – Crème fraîche',
    'null',
    '{"name":"Manis"}',
    '["Apel","Jeruk"]',
]) {
    test(`variant editor preserves the literal name ${JSON.stringify(name)}`, () => {
        const button = { dataset: { id: '20', name, price: '12500' } };
        const values = {};
        let edit, opened = 0;
        function $(target) {
            if (target === '#variant_table') return { on(event, selector, callback) {
                assert.equal(event, 'click');
                assert.equal(selector, '.edit-variant');
                edit = callback;
            } };
            if (target === button) return {
                attr: key => button.dataset[key.replace(/^data-/, '')],
                data: key => dataValue(button.dataset[key]),
            };
            return { val(value) { values[target] = value == null ? '' : String(value); } };
        }
        const document = { getElementById(id) {
            assert.equal(id, 'editVariantModal');
            return { showModal() { opened++; } };
        } };
        vm.runInNewContext(binding[0], { $, document });
        assert.equal(typeof edit, 'function');
        edit.call(button);
        assert.equal(values['#variant_id'], '20');
        assert.equal(values['#variant_price'], '12500');
        assert.equal(values['#variant_name'], name);
        assert.equal(opened, 1);
    });
}
