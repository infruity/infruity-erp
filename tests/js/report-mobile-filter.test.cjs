const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function load() {
    const context = { window: {}, URLSearchParams, fetch: async () => { throw new Error('offline'); } };
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../public/assets/js/report-mobile.js'), 'utf8'), context);
    return context.window.erpReportMobile;
}

function create(type = 'sales') {
    const state = load()({ type, today: '2026-11-12', branches: [], couriers: [], endpoint: '/x', dateEndpoint: '/d' });
    state.reload = () => { state.reloads = (state.reloads || 0) + 1; };
    return state;
}

test('opening hands the sheet what the report is filtered by', () => {
    const state = create('shipping');
    const sent = [];
    state.$dispatch = (name, detail) => sent.push({ name, detail });
    state.branch = '2';
    state.courier = '7';
    state.startDate = '2026-11-01';
    state.endDate = '2026-11-07';

    state.openFilter();

    assert.equal(state.filterOpen, true);
    assert.deepEqual(JSON.parse(JSON.stringify(sent)), [{
        name: 'erp-filter-open',
        detail: { id: 'report', values: { branch: '2', start: '2026-11-01', end: '2026-11-07', extra: { groupBy: 'product', timeGroup: 'daily', courier: '7', year: '2026', week: '' } } },
    }]);
});

test('a daily report keeps the picked date range and reloads once', () => {
    const state = create();

    state.applyFilters({ branch: '3', start: '2026-11-01', end: '2026-11-07', extra: { groupBy: 'date', timeGroup: 'daily', year: 'all', week: '' } });

    assert.deepEqual([state.branch, state.groupBy, state.timeGroup, state.startDate, state.endDate], ['3', 'date', 'daily', '2026-11-01', '2026-11-07']);
    assert.equal(state.reloads, 1);
});

test('a weekly report uses the picked week, else the whole year, else no period', () => {
    const state = create();
    const apply = (extra) => state.applyFilters({ branch: 'all', start: '2026-01-01', end: '2026-01-02', extra: { groupBy: 'product', timeGroup: 'weekly', ...extra } });

    apply({ year: '2026', week: '2026-08-08|2026-08-14' });
    assert.deepEqual([state.startDate, state.endDate, state.selectedWeekValue], ['2026-08-08', '2026-08-14', '2026-08-08|2026-08-14']);

    apply({ year: '2025', week: '' });
    assert.deepEqual([state.startDate, state.endDate], ['2025-01-01', '2025-12-31']);

    apply({ year: 'all', week: '' });
    assert.deepEqual([state.startDate, state.endDate], ['', '']);
});

test('the courier only counts for the shipping report grouped by courier', () => {
    const shipping = create('shipping');
    shipping.applyFilters({ branch: 'all', start: '', end: '', extra: { groupBy: 'product', timeGroup: 'daily', courier: '7' } });
    assert.equal(shipping.courier, '7');

    shipping.applyFilters({ branch: 'all', start: '', end: '', extra: { groupBy: 'date', timeGroup: 'daily', courier: '7' } });
    assert.equal(shipping.courier, 'all');

    const sales = create('sales');
    sales.applyFilters({ branch: 'all', start: '', end: '', extra: { groupBy: 'product', timeGroup: 'daily', courier: '7' } });
    assert.equal(sales.courier, 'all');
});

test('the asset report only filters by branch', () => {
    const state = create('asset');

    state.applyFilters({ branch: '4', start: '2026-11-01', end: '2026-11-07', extra: { groupBy: 'date', timeGroup: 'weekly', year: '2026', week: 'x|y' } });

    assert.deepEqual([state.branch, state.groupBy, state.timeGroup, state.startDate, state.endDate], ['4', 'product', 'daily', '', '']);
});

test('reset values mean every report row again', () => {
    const state = create();
    state.applyFilters({ branch: '3', start: '2026-11-01', end: '2026-11-07', extra: { groupBy: 'date', timeGroup: 'daily' } });

    state.applyFilters({ branch: 'all', start: '', end: '', extra: { groupBy: 'product', timeGroup: 'daily', year: 'all', week: '' } });

    assert.equal(state.hasFilter, false);
    assert.deepEqual([state.groupBy, state.startDate, state.endDate], ['product', '', '']);
});

test('closing the sheet only clears the open flag', () => {
    const state = create();
    state.filterOpen = true;
    state.branch = '3';

    state.closeFilter();

    assert.equal(state.filterOpen, false);
    assert.equal(state.branch, '3');
});
