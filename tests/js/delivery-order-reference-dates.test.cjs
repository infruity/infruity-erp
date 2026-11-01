const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const asset = path.join(__dirname, '../../public/assets/js/delivery-order-reference-dates.js');
function helpers() {
    assert.ok(fs.existsSync(asset), 'Delivery Order reference date helpers must exist');
    return require(asset);
}

test('presets use the app timezone and Monday–Sunday across month/year boundaries', () => {
    const dates = helpers();
    const now = new Date('2026-12-31T18:30:00Z');
    assert.equal(dates.today('Asia/Jakarta', now), '2027-01-01');
    assert.deepEqual(dates.week('Asia/Jakarta', now), ['2026-12-28', '2027-01-03']);
    assert.equal(dates.today('America/Los_Angeles', now), '2026-12-31');
    assert.deepEqual(dates.week('Asia/Jakarta', new Date('2026-11-01T06:00:00Z')), ['2026-10-26', '2026-11-01']);
});

test('range parsing, labels, and calendar grid preserve the existing date request format', () => {
    const dates = helpers();
    assert.deepEqual(dates.range('2026-11-02 to 2026-11-10'), ['2026-11-02', '2026-11-10']);
    assert.deepEqual(dates.range('2026-11-02'), ['2026-11-02', '']);
    assert.deepEqual(dates.range('2026-02-31 to garbage'), ['', '']);
    assert.equal(dates.format('2026-11-02'), '2 Nov 2026');
    assert.equal(dates.format(''), 'Pilih Tanggal');
    const grid = dates.grid(2026, 10);
    assert.equal(grid[0], null, 'November starts on Sunday, so Monday-first grid has six blanks');
    assert.equal(grid[6], '2026-11-01');
    assert.equal(grid.at(-1), '2026-11-30');
});

test('reference calendar mounts into the existing filter state and is shared by both triggers', () => {
    const dates = helpers();
    assert.equal(typeof dates.attach, 'function');
    const view = fs.readFileSync(path.join(__dirname, '../../Modules/Pos/Resources/views/delivery-order/index.blade.php'), 'utf8');
    assert.ok(view.includes("@include('pos::delivery-order.partials.reference-dates')"));
    assert.ok(view.includes('window.DeliveryOrderReferenceDates.attach('));
    assert.ok(view.includes('dateFilterUi.open();'));
    assert.ok(view.includes('dateFilterUi.close();'));
    assert.ok(!view.includes('if (!mobileViewport.matches) return;'));
    assert.ok(view.includes("$desktopFilterTrigger.on('click', openMobileFilterSheet)"));
});

test('responsive presentation is local to Delivery Order and keeps the original detail/payment implementation', () => {
    const css = path.join(__dirname, '../../public/assets/css/delivery-order-reference.css');
    assert.ok(fs.existsSync(css), 'Scoped reference styling must exist');
    const source = fs.readFileSync(css, 'utf8');
    assert.ok(source.includes('#delivery-order-mobile-filter-sheet'));
    assert.ok(source.includes('#delivery-order-detail-modal'));
    assert.ok(source.includes('max-height:calc(100dvh - 32px)'));
    assert.ok(source.includes('font-size:18px'));
    assert.ok(!source.includes('order-book'));
});

test('calendar and presets only update pending state until the existing Apply runs', () => {
    const dates = helpers();
    const filters = require('../../public/assets/js/delivery-order-filter-state.js');
    const state = filters.create({ branch: '17' });
    const week = dates.week('Asia/Jakarta', new Date('2026-11-01T06:00:00Z'));
    state.open();
    state.setPending('dateRange', week.join(' to '));
    assert.equal(state.applied.dateRange, '');
    assert.equal(state.pending.dateRange, '2026-10-26 to 2026-11-01');
    state.discard();
    assert.equal(state.pending.dateRange, '');
    state.setPending('dateRange', '2026-11-01');
    assert.equal(state.apply().dateRange, '2026-11-01');
});
