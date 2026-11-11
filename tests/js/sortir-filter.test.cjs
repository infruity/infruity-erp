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

test('opening either date card opens the selected calendar month without changing the draft range', () => {
    const value = app();
    value.filters = {status:'all', branch:'all', start:'2026-11-01', end:'2026-12-15'};
    value.openFilter();

    value.openFilterCalendar({id:'sortir-date-end'});

    assert.equal(value.calendarOpen, true);
    assert.equal(value.calendarView, 'date');
    assert.equal(value.calendarMonth, 11);
    assert.equal(value.calendarYear, 2026);
    assert.equal(value.filterCalendarTrigger.id, 'sortir-date-end');
    assert.equal(value.pending.start, '2026-11-01');
    assert.equal(value.pending.end, '2026-12-15');
    assert.deepEqual(value.filters, {status:'all', branch:'all', start:'2026-11-01', end:'2026-12-15'});
});

test('calendar range selection sorts endpoints and keeps changes pending until Apply', () => {
    const value = app();
    value.filters = {status:'all', branch:'all', start:'', end:''};
    value.pending = {...value.filters};
    value.calendarMonth = 10;
    value.calendarYear = 2026;
    value.calendarOpen = true;

    value.selectFilterCalendarDate(20);
    assert.equal(value.pending.start, '2026-11-20');
    assert.equal(value.pending.end, '');
    value.selectFilterCalendarDate(11);
    assert.equal(value.pending.start, '2026-11-11');
    assert.equal(value.pending.end, '2026-11-20');
    assert.equal(value.isFilterCalendarDateInRange(15), true);
    assert.equal(value.isFilterCalendarDateSelected(11), true);
    assert.equal(value.isFilterCalendarDateSelected(20), true);
    assert.equal(value.calendarOpen, true);
    assert.deepEqual(value.filters, {status:'all', branch:'all', start:'', end:''});
    assert.equal(value.loadCount || 0, 0);

    value.applyFilter();
    assert.deepEqual(value.filters, {status:'all', branch:'all', start:'2026-11-11', end:'2026-11-20'});
    assert.equal(value.loadCount, 1);
    assert.equal(value.calendarOpen, false);

    value.openFilter();
    value.openFilterCalendar({id:'sortir-date-start'});
    value.selectFilterCalendarDate(24);
    assert.equal(value.pending.start, '2026-11-24');
    assert.equal(value.pending.end, '');
    assert.deepEqual(value.filters, {status:'all', branch:'all', start:'2026-11-11', end:'2026-11-20'});
});

test('calendar navigation spans month/year boundaries and supports month/year selection', () => {
    const value = app();
    value.calendarMonth = 0;
    value.calendarYear = 2027;
    assert.equal(value.filterCalendarHeading, 'Januari 2027');

    value.moveFilterCalendar(-1);
    assert.equal(value.calendarMonth, 11);
    assert.equal(value.calendarYear, 2026);
    assert.equal(Array.from(value.filterCalendarDays).length, 31);
    assert.equal(Array.from(value.filterCalendarBlankDays).length, 2);

    value.calendarView = 'year';
    value.chooseFilterCalendarYear(2028);
    assert.equal(value.calendarView, 'month');
    assert.equal(value.calendarYear, 2028);
    value.chooseFilterCalendarMonth(1);
    assert.equal(value.calendarView, 'date');
    assert.equal(value.calendarMonth, 1);
});

test('returning from the calendar preserves pending dates and restores its trigger', () => {
    const value = app();
    const calls = {focused:[]};
    const trigger = {id:'sortir-date-start', getClientRects:() => [{}], focus:() => calls.focused.push('trigger'), scrollIntoView:() => {}};
    const hadAnimationFrame = Object.prototype.hasOwnProperty.call(global, 'requestAnimationFrame');
    const previousAnimationFrame = global.requestAnimationFrame;

    try {
        global.requestAnimationFrame = callback => callback();
        value.$nextTick = callback => callback();
        value.$refs = {filterCalendarBack:{getClientRects:() => [{}], focus:() => calls.focused.push('back'), scrollIntoView:() => {}}};
        value.pending.start = '2026-11-03';
        value.filterCalendarTrigger = trigger;
        value.openFilterCalendar(trigger);
        value.closeFilterCalendar();

        assert.equal(value.calendarOpen, false);
        assert.equal(value.pending.start, '2026-11-03');
        assert.equal(value.pending.end, '');
        assert.deepEqual(calls.focused, ['back', 'trigger']);
    } finally {
        if (hadAnimationFrame) global.requestAnimationFrame = previousAnimationFrame;
        else delete global.requestAnimationFrame;
    }
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
