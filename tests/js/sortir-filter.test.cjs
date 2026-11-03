const {test} = require('node:test');
const assert = require('node:assert/strict');
const createList = require('../../public/assets/js/sortir-list.js');

function app() {
    const value = createList({
        endpoint: '/sortir/data',
        timezone: 'Asia/Jakarta',
        branches: [
            {id: '1', name: 'Toko GKB'},
            {id: '2', name: 'Gresik / Driyorejo - 001'},
        ],
    });
    value.$nextTick = () => {};
    value.load = () => { value.loadCount = (value.loadCount || 0) + 1; };
    return value;
}

test('branch search and section reset change only the pending filter', () => {
    const value = app();
    value.filters.branch = '2';
    value.openFilter();
    value.branchSearch = 'gkb';

    assert.deepEqual(value.visibleBranches.map(branch => branch.id), ['1']);
    value.pending.branch = '1';
    value.resetPendingFilter('branch');
    assert.equal(value.pending.branch, 'all');
    assert.equal(value.branchSearch, '');
    assert.equal(value.filters.branch, '2');
    assert.equal(value.loadCount || 0, 0);
});

test('status and date section resets modify only pending filters', () => {
    const value = app();
    value.filters = {status: 'paid', branch: '2', start: '2026-10-01', end: '2026-10-31'};
    value.openFilter();

    value.pending.status = 'draft';
    value.resetPendingFilter('status');
    assert.equal(value.pending.status, 'all');
    assert.equal(value.filters.status, 'paid');

    value.pending.start = '2026-11-01';
    value.pending.end = '2026-11-30';
    value.resetPendingFilter('date');
    assert.equal(value.pending.start, '');
    assert.equal(value.pending.end, '');
    assert.equal(value.filters.start, '2026-10-01');
    assert.equal(value.filters.end, '2026-10-31');
    assert.equal(value.loadCount || 0, 0);
});

test('quick date presets update only draft dates using application calendar dates', () => {
    const value = app();
    const now = new Date('2026-11-04T05:00:00Z');

    value.setPendingDateRange('today', now);
    assert.equal(value.pending.start, '2026-11-04');
    assert.equal(value.pending.end, '2026-11-04');
    value.setPendingDateRange('week', now);
    assert.equal(value.pending.start, '2026-11-02');
    assert.equal(value.pending.end, '2026-11-08');
    assert.deepEqual(value.filters, {status: 'all', branch: 'all', start: '', end: ''});
    assert.equal(value.loadCount || 0, 0);
});

test('branch search controls the all-branch choice and empty state', () => {
    const value = app();
    assert.equal(value.showAllBranchOption, true);
    value.branchSearch = 'semua';
    assert.equal(value.showAllBranchOption, true);
    value.branchSearch = 'tidak ada';
    assert.equal(value.visibleBranches.length, 0);
    assert.equal(value.showAllBranchOption, false);
});

test('filter date card has a placeholder and formats selected dates in Indonesian', () => {
    const value = app();
    assert.equal(value.formatFilterDate(''), 'Pilih Tanggal');
    assert.match(value.formatFilterDate('2026-11-04'), /2026/);
});

test('quick presets use the Laravel timezone when browser timezone differs', () => {
    const value = app();
    const now = new Date('2026-11-01T18:30:00Z');

    value.setPendingDateRange('today', now);
    assert.equal(value.pending.start, '2026-11-02');
    assert.equal(value.pending.end, '2026-11-02');
    value.setPendingDateRange('week', now);
    assert.equal(value.pending.start, '2026-11-02');
    assert.equal(value.pending.end, '2026-11-08');
});

test('week preset spans month and year boundaries in the application timezone', () => {
    const value = app();
    const cases = [
        [new Date('2026-10-31T18:30:00Z'), '2026-10-26', '2026-11-01'],
        [new Date('2026-12-31T18:00:00Z'), '2026-12-28', '2027-01-03'],
    ];

    for (const [now, expectedStart, expectedEnd] of cases) {
        value.setPendingDateRange('week', now);
        assert.equal(value.pending.start, expectedStart);
        assert.equal(value.pending.end, expectedEnd);
    }
});
