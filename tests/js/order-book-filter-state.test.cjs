const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const view = fs.readFileSync(path.join(__dirname, '../../Modules/Pos/Resources/views/order-book/index.blade.php'), 'utf8');

function createState() {
    const start = view.indexOf('function orderBookFilterSheet() {');
    const end = view.indexOf('$(document).ready(function()', start);
    assert.ok(start >= 0 && end > start, 'the order-book filter state must be present in the Blade view');

    const controls = { status: { value: 'all' }, branch: { value: 'all' }, range: { value: '' } };
    const events = [];
    const document = {
        getElementById: (id) => (id === 'kt_ecommerce_sales_flatpickr' ? controls.range : null),
        querySelector: (selector) => (selector.includes('status') ? controls.status : (selector.includes('cabang') ? controls.branch : null)),
        dispatchEvent: (event) => { events.push(event); return true; },
    };
    class TestCustomEvent { constructor(type) { this.type = type; } }
    const context = vm.createContext({ document, CustomEvent: TestCustomEvent });
    const state = vm.runInContext(`${view.slice(start, end)}\norderBookFilterSheet;`, context)();
    const dispatched = [];
    state.$dispatch = (name, detail) => dispatched.push({ name, detail });

    return { state, controls, events, dispatched };
}

test('opening hands the sheet the values the list is using', () => {
    const { state, controls, dispatched } = createState();
    controls.status.value = 'draft';
    controls.branch.value = '42';
    controls.range.value = '2026-11-01 to 2026-11-07';

    state.openFilter();

    assert.equal(state.filterOpen, true);
    assert.deepEqual(JSON.parse(JSON.stringify(dispatched)), [{
        name: 'erp-filter-open',
        detail: { id: 'order-book', values: { status: 'draft', branch: '42', start: '2026-11-01', end: '2026-11-07' } },
    }]);
});

test('a single picked day reads back as a one-day range', () => {
    const { state, controls } = createState();
    controls.range.value = '2026-11-05';

    assert.equal(state.appliedValues().start, '2026-11-05');
    assert.equal(state.appliedValues().end, '2026-11-05');
});

test('chosen values reach the request controls only through apply, then the list redraws', () => {
    const { state, controls, events } = createState();
    state.init();

    assert.deepEqual([controls.status.value, controls.branch.value, controls.range.value], ['all', 'all', '']);

    state.applyFilters({ status: 'draft', branch: '42', start: '2026-11-01', end: '' });

    assert.deepEqual([controls.status.value, controls.branch.value, controls.range.value], ['draft', '42', '2026-11-01 to 2026-11-01']);
    assert.equal(state.hasActiveFilters, true);
    assert.deepEqual(events.map((event) => event.type), ['order-book:filters-applied']);
});

test('clearing every filter empties the date range and the active mark', () => {
    const { state, controls } = createState();
    state.applyFilters({ status: 'draft', branch: '42', start: '2026-11-01', end: '2026-11-02' });

    state.applyFilters({ status: 'all', branch: 'all', start: '', end: '' });

    assert.equal(controls.range.value, '');
    assert.equal(state.hasActiveFilters, false);
});

test('closing the sheet clears the open flag', () => {
    const { state } = createState();
    state.filterOpen = true;

    state.closeFilter();

    assert.equal(state.filterOpen, false);
});
