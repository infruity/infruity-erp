const {test} = require('node:test');
const assert = require('node:assert/strict');
const createTransferList = require('../../public/assets/js/transfer-list.js');

function app() {
    const value = createTransferList({
        endpoint: '/transfer-data',
        side: 'transfer-pengirim',
        receiving: false,
        timezone: 'Asia/Jakarta',
        branches: [],
    });
    value.loadCount = 0;
    value.load = () => { value.loadCount += 1; };
    return value;
}

test('quick date presets use the application timezone and stay pending until Apply', () => {
    const value = app();
    value.filters = {status: 'pending', branch: '2', start: '2026-10-01', end: '2026-10-31'};
    value.pending = {...value.filters};
    const applied = {...value.filters};
    const now = new Date('2026-11-01T18:30:00Z');

    assert.equal(typeof value.setPendingDateRange, 'function');
    value.setPendingDateRange('today', now);
    assert.equal(value.pending.start, '2026-11-02');
    assert.equal(value.pending.end, '2026-11-02');
    value.setPendingDateRange('week', now);
    assert.equal(value.pending.start, '2026-11-02');
    assert.equal(value.pending.end, '2026-11-08');
    assert.deepEqual(value.filters, applied);
    assert.equal(value.loadCount, 0);
});

test('week preset spans month and year boundaries in the application timezone', () => {
    const value = app();
    const cases = [
        [new Date('2026-10-31T18:30:00Z'), '2026-10-26', '2026-11-01'],
        [new Date('2026-12-31T18:00:00Z'), '2026-12-28', '2027-01-03'],
    ];

    assert.equal(typeof value.setPendingDateRange, 'function');
    for (const [now, expectedStart, expectedEnd] of cases) {
        value.setPendingDateRange('week', now);
        assert.equal(value.pending.start, expectedStart);
        assert.equal(value.pending.end, expectedEnd);
    }
});

test('section resets change only pending filter values', () => {
    const value = app();
    value.filters = {status: 'pending', branch: '2', start: '2026-10-01', end: '2026-10-31'};
    value.pending = {...value.filters};
    value.branchSearchQuery = 'GBA';

    assert.equal(typeof value.resetPendingFilter, 'function');
    value.resetPendingFilter('branch');
    assert.equal(value.pending.branch, 'all');
    assert.equal(value.branchSearchQuery, 'GBA');
    value.resetPendingFilter('status');
    assert.equal(value.pending.status, 'all');
    value.resetPendingFilter('date');
    assert.equal(value.pending.start, '');
    assert.equal(value.pending.end, '');
    assert.equal(value.filters.branch, '2');
    assert.equal(value.filters.status, 'pending');
    assert.equal(value.filters.start, '2026-10-01');
    assert.equal(value.loadCount, 0);
});
