const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { pathToFileURL } = require('node:url');

const load = () => import(pathToFileURL(path.resolve(__dirname, '../../scripts/ui-migrate/components-pass.mjs')).href);

test('form controls become erp-input/erp-select and required labels get the modifier', async () => {
    const { convertClassList } = await load();

    assert.equal(convertClassList('form-control form-control-lg mb-2').value, 'erp-input mb-2');
    assert.equal(convertClassList('form-select form-select-solid').value, 'erp-select');
    assert.equal(convertClassList('required form-label').value, 'erp-label erp-label--required');
    assert.equal(convertClassList('form-label mt-3').value, 'erp-label mt-3');
});

test('buttons map to erp-action variants and drop Bootstrap size/variant tokens', async () => {
    const { convertClassList } = await load();

    assert.equal(convertClassList('btn btn-primary').value, 'erp-action erp-action--primary');
    assert.equal(convertClassList('btn btn-sm btn-light-danger').value, 'erp-action erp-action--danger');
    assert.equal(convertClassList('btn btn-light me-3').value, 'erp-action me-3');
    assert.equal(convertClassList('btn btn-icon btn-active-light').value, 'erp-action');
});

test('convert only rewrites class attributes that carry Bootstrap component classes', async () => {
    const { convert } = await load();
    const html = '<input class="form-control" name="a"><div class="flex gap-2"></div><button class="btn btn-secondary">x</button>';
    const { next, count } = convert(html);

    assert.equal(count, 2);
    assert.equal(next, '<input class="erp-input" name="a"><div class="flex gap-2"></div><button class="erp-action">x</button>');
});
