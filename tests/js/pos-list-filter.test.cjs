const {test} = require('node:test');
const assert = require('node:assert/strict');
const ui = require('../../public/assets/js/pos-list-reference.js');

const now = new Date('2026-11-11T04:00:00.000Z');

function filters() {
    return ui.createFilters({timezone:'Asia/Jakarta', branches:[{id:1,name:'Toko GKB'},{id:2,name:'Toko GBA'}]});
}

test('calendar opens for one date endpoint without changing applied filters', () => {
    const state = filters();
    state.draft.start = '2026-11-10';
    state.draft.end = '2026-11-20';
    state.applied = {...state.draft};
    const trigger = {focus() { this.focused = true; }};

    state.openCalendar('end', trigger, now);

    assert.equal(state.calendarOpen, true);
    assert.equal(state.calendarSide, 'end');
    assert.equal(state.calendarMonth, 10);
    assert.equal(state.calendarYear, 2026);
    assert.deepEqual(state.applied, {status:'all', branch:'all', start:'2026-11-10', end:'2026-11-20'});
    assert.deepEqual(state.draft, state.applied);
});

test('calendar supports month and year navigation and month selection', () => {
    const state = filters();
    state.openCalendar('start', null, now);
    state.calendarMonth = 0;
    state.calendarYear = 2026;

    state.navigateCalendar(-1);
    assert.equal(state.calendarMonth, 11);
    assert.equal(state.calendarYear, 2025);
    state.setCalendarView('month');
    state.selectCalendarMonth(2);
    assert.equal(state.calendarMonth, 2);
    assert.equal(state.calendarYear, 2025);
    assert.equal(state.calendarView, 'date');
    state.setCalendarView('year');
    state.selectCalendarYear(2024);
    assert.equal(state.calendarYear, 2024);
    assert.equal(state.calendarView, 'month');
});

test('selecting a POS date preserves endpoint semantics and invalid-range validation', () => {
    const state = filters();
    state.draft.start = '2026-11-10';
    state.draft.end = '2026-11-20';
    state.applied = {...state.draft};
    state.openCalendar('end', null, now);

    const day = state.calendarDays.find(item => item && item.date === '2026-11-05');
    assert.ok(day);
    state.selectCalendarDate(day);

    assert.equal(state.draft.start, '2026-11-10');
    assert.equal(state.draft.end, '2026-11-05');
    assert.equal(state.dateError, 'Tanggal akhir harus sama atau setelah tanggal mulai.');
    assert.equal(state.calendarOpen, false);
    assert.deepEqual(state.applied, {status:'all', branch:'all', start:'2026-11-10', end:'2026-11-20'});
});

test('POS calendar matches the transfer date grid order and weekday labels', () => {
    const state = filters();
    state.openCalendar('start', null, now);

    assert.deepEqual(state.calendarWeekdays, ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
    assert.equal(state.calendarDays[0].date, '2026-11-01');
    assert.equal(state.calendarDays[0].day, 1);
});

test('POS calendar marks the active endpoint and shades only a valid pending range', () => {
    const state = filters();
    state.draft.start = '2026-11-11';
    state.draft.end = '2026-11-20';
    state.openCalendar('start', null, now);

    assert.equal(state.isCalendarDateSelected('2026-11-11'), true);
    assert.equal(state.isCalendarDateSelected('2026-11-20'), false);
    assert.equal(state.isCalendarDateInRange('2026-11-15'), true);
    assert.equal(state.isCalendarDateInRange('2026-11-11'), false);
    state.openCalendar('end', null, now);
    assert.equal(state.isCalendarDateSelected('2026-11-11'), false);
    assert.equal(state.isCalendarDateSelected('2026-11-20'), true);
});


test('quick-period selected state follows the actual pending date range', () => {
    const state = filters();
    state.setDateRange('week', now);
    assert.equal(state.isDateRangeActive('week', now), true);
    assert.equal(state.isDateRangeActive('today', now), false);
    state.draft.end = '2026-11-10';
    assert.equal(state.isDateRangeActive('week', now), false);
    state.setDateRange('today', now);
    assert.equal(state.isDateRangeActive('today', now), true);
});

test('closing the calendar restores focus to its date-card trigger', () => {
    const state = filters();
    const trigger = {focused:false, focus() { this.focused = true; }};
    state.openCalendar('start', trigger, now);

    state.closeCalendar();

    assert.equal(state.calendarOpen, false);
    assert.equal(trigger.focused, true);
});
