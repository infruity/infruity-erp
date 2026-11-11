const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const viewPath = path.join(__dirname, '../../Modules/Transaction/Resources/views/wholesale/index.blade.php');
const view = fs.readFileSync(viewPath, 'utf8');

function loadCriteriaHelper() {
    const match = view.match(/function hasWholesaleFilterCriteria\(filters\)\s*\{[\s\S]*?\n        \}/);
    assert.ok(match, 'the wholesale empty-state criteria helper must exist');
    return vm.runInNewContext(`(${match[0]})`);
}

test('wholesale empty-state criteria detects applied filters and nonblank searches', () => {
    const hasCriteria = loadCriteriaHelper();

    assert.equal(hasCriteria({}), false);
    assert.equal(hasCriteria({ status: 'complete' }), true);
    assert.equal(hasCriteria({ branch: '12' }), true);
    assert.equal(hasCriteria({ start: '2026-11-01' }), true);
    assert.equal(hasCriteria({ end: '2026-11-30' }), true);
    assert.equal(hasCriteria({ search: 'PO-123' }), true);
    assert.equal(hasCriteria({ search: '   ' }), false);
});
