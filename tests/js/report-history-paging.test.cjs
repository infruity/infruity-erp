const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const path = require('node:path');

const script = fs.readFileSync(path.join(__dirname, '../../public/assets/js/report-mobile.js'), 'utf8');
const view = fs.readFileSync(path.join(__dirname, '../../Modules/Report/Resources/views/partials/mobile-detail.blade.php'), 'utf8');

test('report history draws its cards in steps and keeps totals on the whole period', () => {
    assert.match(view, /x-for="\(entry, index\) in pagedHistory"/);
    assert.match(view, /showMoreHistory\(\)/);
    assert.match(script, /get pagedHistory\(\) \{ return this\.filteredHistory\.slice\(0, this\.historyVisible\); \}/);
    assert.ok(script.includes('this.filteredHistory.reduce('), 'totals still use every row');
    assert.strictEqual(script.split('this.historyVisible = this.historyPageSize;').length - 1, 2, 'both ways of opening the history start from the first step');
});
