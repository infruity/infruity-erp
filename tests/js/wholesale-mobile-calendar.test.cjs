const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const viewPath = path.join(__dirname, '../../Modules/Transaction/Resources/views/wholesale/index.blade.php');
const view = fs.readFileSync(viewPath, 'utf8');

function createFilterState() {
    const match = view.match(/x-data="([\s\S]*?)"\s+class="flex min-h-0 flex-1 flex-col -mx-4 md:mx-0"/);
    assert.ok(match, 'the wholesale filter state must be present in the Blade view');
    return vm.runInNewContext(`(${match[1]})`);
}

test('wholesale mobile filter calendar shows the correct month grid and sorted date range', () => {
    const state = createFilterState();
    state.calendarMonth = 10;
    state.calendarYear = 2026;

    assert.equal(state.mobileCalendarHeading, 'November 2026');
    assert.equal(Array.from(state.mobileCalendarBlankDays).length, 0);
    assert.equal(Array.from(state.mobileCalendarDays).length, 30);

    state.selectMobileCalendarDate(20);
    state.selectMobileCalendarDate(11);
    assert.equal(state.mobileStartDate, '2026-11-11');
    assert.equal(state.mobileEndDate, '2026-11-20');

    state.selectMobileCalendarDate(4);
    assert.equal(state.mobileStartDate, '2026-11-04');
    assert.equal(state.mobileEndDate, '');
});

test('the end-date trigger opens at the currently selected end month', () => {
    const state = createFilterState();
    state.$nextTick = () => {};
    state.mobileStartDate = '2026-11-30';
    state.mobileEndDate = '2026-12-02';

    state.openMobileCalendar({ id: 'wholesale-mobile-end-date-trigger' });

    assert.equal(state.calendarMonth, 11);
    assert.equal(state.calendarYear, 2026);
    assert.equal(state.calendarOpen, true);
});

test('wholesale mobile calendar supports month navigation across year boundaries', () => {
    const state = createFilterState();
    state.calendarMonth = 0;
    state.calendarYear = 2027;

    state.moveMobileCalendar(-1);
    assert.equal(state.calendarMonth, 11);
    assert.equal(state.calendarYear, 2026);

    state.moveMobileCalendar(1);
    assert.equal(state.calendarMonth, 0);
    assert.equal(state.calendarYear, 2027);
});
