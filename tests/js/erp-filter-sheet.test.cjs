const assert = require('node:assert/strict');
const test = require('node:test');

const erpFilterSheet = require('../../public/assets/js/erp-filter-sheet.js');

globalThis.requestAnimationFrame = () => {};

function createSheet(config = {}) {
    const sheet = erpFilterSheet({ id: 'wholesale', ...config });
    sheet.$nextTick = (callback) => callback();
    sheet.$refs = {};
    return sheet;
}

test('calendar shows the correct month grid and keeps the picked range sorted', () => {
    const sheet = createSheet();
    sheet.calendarMonth = 10;
    sheet.calendarYear = 2026;

    assert.equal(sheet.calendarHeading, 'November 2026');
    assert.equal(Array.from(sheet.calendarBlankDays).length, 0);
    assert.equal(Array.from(sheet.calendarDays).length, 30);

    sheet.selectCalendarDate(20);
    sheet.selectCalendarDate(11);
    assert.equal(sheet.start, '2026-11-11');
    assert.equal(sheet.end, '2026-11-20');

    sheet.selectCalendarDate(4);
    assert.equal(sheet.start, '2026-11-04');
    assert.equal(sheet.end, '');
});

test('the end-date trigger opens at the currently selected end month', () => {
    const sheet = createSheet();
    sheet.start = '2026-11-30';
    sheet.end = '2026-12-02';

    sheet.openCalendar({}, 'end');

    assert.equal(sheet.calendarMonth, 11);
    assert.equal(sheet.calendarYear, 2026);
    assert.equal(sheet.calendarOpen, true);
});

test('calendar supports month navigation across year boundaries', () => {
    const sheet = createSheet();
    sheet.calendarMonth = 0;
    sheet.calendarYear = 2027;

    sheet.moveCalendar(-1);
    assert.equal(sheet.calendarMonth, 11);
    assert.equal(sheet.calendarYear, 2026);

    sheet.moveCalendar(1);
    assert.equal(sheet.calendarMonth, 0);
    assert.equal(sheet.calendarYear, 2027);
});

test('opening starts the draft from the values the page passes in, for its own id only', () => {
    const sheet = createSheet();

    sheet.onOpen({ detail: { id: 'other', values: { branch: '9' } } });
    assert.equal(sheet.open, false);

    sheet.onOpen({ detail: { id: 'wholesale', values: { branch: '3', status: 'draft', start: '2026-11-01', end: '2026-11-07' } } });
    assert.equal(sheet.open, true);
    assert.deepEqual([sheet.branch, sheet.status, sheet.start, sheet.end], ['3', 'draft', '2026-11-01', '2026-11-07']);

    sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
    assert.deepEqual([sheet.branch, sheet.status, sheet.start, sheet.end], ['all', 'all', '', '']);
});

function stubBrowser() {
    const events = [];
    const body = { style: { overflow: '' } };
    const main = { style: { overflow: 'auto' } };
    const trigger = { focused: 0, focus() { this.focused++; } };
    globalThis.CustomEvent = class { constructor(type, init) { this.type = type; this.detail = init?.detail; } };
    globalThis.dispatchEvent = (event) => events.push(event);
    globalThis.document = {
        body,
        activeElement: trigger,
        querySelector: (selector) => (selector === '.erp-main' ? main : null),
    };
    return { events, body, main, trigger };
}

function restoreBrowser() {
    delete globalThis.CustomEvent;
    delete globalThis.dispatchEvent;
    delete globalThis.document;
}

test('changes stay in the draft until apply, which hands them to the page and closes', () => {
    const { events } = stubBrowser();
    const sheet = createSheet();

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
        sheet.branch = '2';
        sheet.status = 'complete';
        assert.equal(events.length, 0);

        sheet.apply();
    } finally {
        restoreBrowser();
    }

    assert.deepEqual(events.map((event) => event.type), ['erp-filter-apply', 'erp-filter-close']);
    assert.deepEqual(events[0].detail, { id: 'wholesale', values: { branch: '2', status: 'complete', start: '', end: '' } });
    assert.deepEqual(events[1].detail, { id: 'wholesale' });
    assert.equal(sheet.open, false);
});

test('opening locks page scroll and focuses the close button; closing restores both', () => {
    const { body, main, trigger } = stubBrowser();
    const sheet = createSheet();
    const closeButton = { focused: 0, focus() { this.focused++; } };
    sheet.$refs = { closeButton };

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
        assert.equal(body.style.overflow, 'hidden');
        assert.equal(main.style.overflow, 'hidden');
        assert.equal(closeButton.focused, 1);

        sheet.close();
        assert.equal(body.style.overflow, '');
        assert.equal(main.style.overflow, 'auto');
        assert.equal(trigger.focused, 1);
    } finally {
        restoreBrowser();
    }
});

test('closing a sheet that is not open does nothing', () => {
    const { events } = stubBrowser();
    const sheet = createSheet();

    try {
        sheet.close();
    } finally {
        restoreBrowser();
    }

    assert.equal(events.length, 0);
});

test('reset applies and closes when the page asks for it', () => {
    const { events } = stubBrowser();
    const sheet = createSheet({ resetApplies: true });

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: { branch: '2', status: 'draft', start: '2026-11-01', end: '2026-11-02' } } });
        sheet.reset();
    } finally {
        restoreBrowser();
    }

    assert.deepEqual(events[0].detail.values, { branch: 'all', status: 'all', start: '', end: '' });
    assert.equal(sheet.open, false);
});

test('tab stays inside the sheet', () => {
    const sheet = createSheet();
    const visible = { getClientRects: () => [1] };
    const first = { ...visible, focus() { first.focused = true; } };
    const last = { ...visible, focus() { last.focused = true; } };
    sheet.$refs = { panel: { querySelectorAll: () => [first, last], focus() {} } };
    globalThis.document = { activeElement: last };
    let prevented = 0;

    try {
        sheet.trapFocus({ shiftKey: false, preventDefault: () => prevented++ });
        assert.equal(first.focused, true);

        globalThis.document.activeElement = first;
        sheet.trapFocus({ shiftKey: true, preventDefault: () => prevented++ });
        assert.equal(last.focused, true);
    } finally {
        restoreBrowser();
    }

    assert.equal(prevented, 2);
});

test('reset clears only the draft', () => {
    const sheet = createSheet();
    sheet.branch = '2';
    sheet.status = 'draft';
    sheet.start = '2026-11-01';
    sheet.branchSearch = 'gk';

    sheet.reset();

    assert.deepEqual([sheet.branch, sheet.status, sheet.start, sheet.end, sheet.branchSearch], ['all', 'all', '', '', '']);
});

test('branch search narrows the list and the empty state follows it', () => {
    const sheet = createSheet({ showBranchAll: true, branches: [{ id: 1, name: 'Toko GKB' }, { id: 2, name: 'Toko GBA' }] });

    assert.equal(sheet.visibleBranchCount, 3);

    sheet.branchSearch = 'gkb';
    assert.equal(sheet.visibleBranchCount, 1);
    assert.equal(sheet.branchMatches(sheet.branches[0].search), true);
    assert.equal(sheet.branchMatches(sheet.branches[1].search), false);

    sheet.branchSearch = 'zzz';
    assert.equal(sheet.visibleBranchCount, 0);
});

test('quick picks fill today and the Monday to Sunday week', () => {
    const sheet = createSheet();

    sheet.setDateRange('today');
    assert.equal(sheet.start, sheet.end);

    sheet.setDateRange('week');
    const start = new Date(`${sheet.start}T00:00:00`);
    const end = new Date(`${sheet.end}T00:00:00`);
    assert.equal(start.getDay(), 1);
    assert.equal((end - start) / 86400000, 6);
});

test('dates read as day, short month and year', () => {
    const sheet = createSheet();

    assert.equal(sheet.formatDate('2026-08-18'), '18 Agu 2026');
    assert.equal(sheet.formatDate(''), 'Pilih Tanggal');
});

test('"Hari Ini" and "Minggu Ini" are read in the configured timezone, not the browser one', () => {
    const instant = new Date('2026-10-31T17:30:00.000Z');

    assert.equal(erpFilterSheet.today('Asia/Jakarta', instant), '2026-11-01');
    assert.equal(erpFilterSheet.today('America/Los_Angeles', instant), '2026-10-31');
});

test('the week quick pick is Monday through Sunday across the year boundary', () => {
    const range = erpFilterSheet.week('Asia/Jakarta', new Date('2026-12-31T17:30:00.000Z'));

    assert.deepEqual({ ...range }, { start: '2026-12-28', end: '2027-01-03' });
});

test('the calendar grid starts on Monday or Sunday as configured', () => {
    // 1 November 2026 is a Sunday.
    assert.equal(erpFilterSheet.monthGridOffset(2026, 10, 'monday'), 6);
    assert.equal(erpFilterSheet.monthGridOffset(2026, 10, 'sunday'), 0);
    assert.equal(erpFilterSheet.daysInMonth(2026, 10), 30);

    const monday = createSheet({ weekStart: 'monday' });
    const sunday = createSheet();
    monday.calendarMonth = sunday.calendarMonth = 10;
    monday.calendarYear = sunday.calendarYear = 2026;
    assert.equal(Array.from(monday.calendarBlankDays).length, 6);
    assert.equal(Array.from(sunday.calendarBlankDays).length, 0);
    assert.deepEqual(monday.weekdays.map((day) => day.initial).join(''), 'SSRKJSM');
    assert.deepEqual(sunday.weekdays.map((day) => day.initial).join(''), 'MSSRKJS');
});

test('quick picks move the calendar to the month they filled', () => {
    const sheet = createSheet({ timezone: 'Asia/Jakarta' });
    sheet.calendarMonth = 0;
    sheet.calendarYear = 2020;

    sheet.setDateRange('today');

    const [year, month] = sheet.start.split('-').map(Number);
    assert.deepEqual([sheet.calendarYear, sheet.calendarMonth], [year, month - 1]);
});

test('calendar focus waits for the frame that reveals the new Alpine view', () => {
    const sheet = createSheet();
    const focuses = [];
    const frames = [];
    const originalFrame = globalThis.requestAnimationFrame;
    globalThis.requestAnimationFrame = (callback) => frames.push(callback);
    sheet.$refs = { calendarBack: { getClientRects: () => [1], focus: () => focuses.push('calendar-back'), scrollIntoView() {} } };
    const trigger = { getClientRects: () => [1], focus: () => focuses.push('trigger'), scrollIntoView() {} };

    try {
        sheet.openCalendar(trigger, 'start');
        assert.deepEqual(focuses, [], 'a nextTick alone can focus a still-hidden x-show control');
        frames.shift()();
        assert.deepEqual(focuses, ['calendar-back']);

        sheet.closeCalendar();
        frames.shift()();
        assert.deepEqual(focuses, ['calendar-back', 'trigger']);
    } finally {
        globalThis.requestAnimationFrame = originalFrame;
    }
});

test('the week quick pick also spans a month boundary in the application timezone', () => {
    const range = erpFilterSheet.week('Asia/Jakarta', new Date('2026-10-31T18:30:00Z'));

    assert.deepEqual({ ...range }, { start: '2026-10-26', end: '2026-11-01' });
});

test('calendar month and year pickers drill down view by view', () => {
    const sheet = createSheet();
    sheet.calendarMonth = 0;
    sheet.calendarYear = 2027;
    assert.equal(sheet.calendarHeading, 'Januari 2027');

    sheet.moveCalendar(-1);
    assert.equal(Array.from(sheet.calendarDays).length, 31);
    assert.equal(Array.from(sheet.calendarBlankDays).length, 2);

    sheet.calendarView = 'year';
    sheet.chooseCalendarYear(2028);
    assert.equal(sheet.calendarView, 'month');
    assert.equal(sheet.calendarYear, 2028);

    sheet.chooseCalendarMonth(1);
    assert.equal(sheet.calendarView, 'date');
    assert.equal(sheet.calendarMonth, 1);

    sheet.cycleCalendarView();
    assert.equal(sheet.calendarView, 'month');
    sheet.cycleCalendarView();
    assert.equal(sheet.calendarView, 'year');
    sheet.cycleCalendarView();
    assert.equal(sheet.calendarView, 'date');
});

test('picked range highlights the days between the endpoints', () => {
    const sheet = createSheet();
    sheet.calendarMonth = 10;
    sheet.calendarYear = 2026;
    sheet.selectCalendarDate(11);
    sheet.selectCalendarDate(20);

    assert.equal(sheet.isCalendarDateInRange(15), true);
    assert.equal(sheet.isCalendarDateInRange(11), false);
    assert.equal(sheet.isCalendarDateSelected(11), true);
    assert.equal(sheet.isCalendarDateSelected(20), true);
});

test('a single-branch user keeps their branch as the default; reset returns to it', () => {
    const { events } = stubBrowser();
    const sheet = createSheet({ defaultBranch: '1', resetApplies: true, branches: [{ id: 1, name: 'GKB' }] });

    try {
        assert.equal(sheet.branch, '1');
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
        assert.equal(sheet.branch, '1');

        sheet.branch = '2';
        sheet.reset();
    } finally {
        restoreBrowser();
    }

    assert.equal(events[0].detail.values.branch, '1');
});

test('extra groups start at their defaults and come from the page when opening', () => {
    const sheet = createSheet({ groups: [{ key: 'delivery', default: 'all' }, { key: 'sort', default: 'name' }] });
    assert.deepEqual({ ...sheet.extra }, { delivery: 'all', sort: 'name' });

    const { events } = stubBrowser();
    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: { extra: { delivery: 'received' } } } });
        assert.deepEqual({ ...sheet.extra }, { delivery: 'received', sort: 'name' });

        sheet.extra.sort = 'stock';
        sheet.apply();
    } finally {
        restoreBrowser();
    }

    assert.deepEqual({ ...events[0].detail.values.extra }, { delivery: 'received', sort: 'stock' });
});

test('reset returns the extra groups to their defaults', () => {
    const { events } = stubBrowser();
    const sheet = createSheet({ resetApplies: true, groups: [{ key: 'delivery', default: 'all' }] });

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: { extra: { delivery: 'received' } } } });
        sheet.reset();
    } finally {
        restoreBrowser();
    }

    assert.deepEqual({ ...events[0].detail.values.extra }, { delivery: 'all' });
});

test('pages without groups keep the original event shape', () => {
    const { events } = stubBrowser();
    const sheet = createSheet();

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
        sheet.apply();
    } finally {
        restoreBrowser();
    }

    assert.deepEqual(Object.keys(events[0].detail.values), ['branch', 'status', 'start', 'end']);
});

test('"Bulan Ini" fills the whole current month in the application timezone', () => {
    // 31 Oct 18:30 UTC is already 1 Nov in Jakarta.
    assert.deepEqual({ ...erpFilterSheet.monthRange('Asia/Jakarta', new Date('2026-10-31T18:30:00Z')) }, { start: '2026-11-01', end: '2026-11-30' });
    assert.deepEqual({ ...erpFilterSheet.monthRange('UTC', new Date('2028-02-10T00:00:00Z')) }, { start: '2028-02-01', end: '2028-02-29' });

    const sheet = createSheet({ timezone: 'UTC' });
    sheet.setDateRange('month');
    assert.match(sheet.start, /^\d{4}-\d{2}-01$/);
    assert.equal(sheet.end.slice(0, 7), sheet.start.slice(0, 7));
    assert.deepEqual([sheet.calendarYear, sheet.calendarMonth], [Number(sheet.start.slice(0, 4)), Number(sheet.start.slice(5, 7)) - 1]);
});

function stubViewport(matches) {
    const listeners = [];
    globalThis.matchMedia = (query) => ({ matches, media: query, addEventListener: (type, fn) => listeners.push(fn) });
    return { listeners, restore: () => { delete globalThis.matchMedia; } };
}

test('a phone-only sheet refuses to open on a desktop viewport and never locks scroll', () => {
    const { body } = stubBrowser();
    const viewport = stubViewport(true);
    const sheet = createSheet();

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
    } finally {
        viewport.restore();
        restoreBrowser();
    }

    assert.equal(sheet.open, false);
    assert.equal(body.style.overflow, '');
});

test('a modal sheet opens on any viewport', () => {
    stubBrowser();
    const viewport = stubViewport(true);
    const sheet = createSheet({ desktop: 'modal' });

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
    } finally {
        viewport.restore();
        restoreBrowser();
    }

    assert.equal(sheet.open, true);
});

test('a phone-only sheet closes itself when the window grows to desktop width', () => {
    stubBrowser();
    const viewport = stubViewport(false);
    const sheet = createSheet();

    try {
        sheet.init();
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
        assert.equal(sheet.open, true);

        viewport.listeners[0]({ matches: true });
        assert.equal(sheet.open, false);
    } finally {
        viewport.restore();
        restoreBrowser();
    }
});

test('the optional search box starts from the page value and comes back trimmed in the apply event', () => {
    const { events } = stubBrowser();
    const sheet = createSheet({ search: true });

    try {
        sheet.onOpen({ detail: { id: 'wholesale', values: { search: 'apel' } } });
        assert.equal(sheet.query, 'apel');

        sheet.query = '  jeruk ';
        sheet.apply();
    } finally {
        restoreBrowser();
    }

    assert.equal(events[0].detail.values.search, 'jeruk');
});

test('reset clears the search box; sheets without a search never report one', () => {
    const { events } = stubBrowser();
    const withSearch = createSheet({ search: true, resetApplies: true });
    const without = createSheet();

    try {
        withSearch.onOpen({ detail: { id: 'wholesale', values: { search: 'apel' } } });
        withSearch.reset();
        without.onOpen({ detail: { id: 'wholesale', values: { search: 'apel' } } });
        without.apply();
    } finally {
        restoreBrowser();
    }

    assert.equal(events[0].detail.values.search, '');
    assert.equal('search' in events[2].detail.values, false);
});

test('an opening can rename the sheet and the next one restores the defaults', () => {
    const title = { textContent: 'Filter Riwayat Stok' };
    const subtitle = { textContent: 'Sesuaikan riwayat stok' };
    stubBrowser();
    globalThis.document.getElementById = (id) => ({ 'wholesale-filter-title': title, 'wholesale-filter-subtitle': subtitle })[id] || null;
    const sheet = createSheet();

    try {
        sheet.onOpen({ detail: { id: 'wholesale', title: 'Filter Riwayat HPP', subtitle: 'Sesuaikan riwayat HPP', values: {} } });
        assert.deepEqual([title.textContent, subtitle.textContent], ['Filter Riwayat HPP', 'Sesuaikan riwayat HPP']);

        sheet.close();
        sheet.onOpen({ detail: { id: 'wholesale', values: {} } });
        assert.deepEqual([title.textContent, subtitle.textContent], ['Filter Riwayat Stok', 'Sesuaikan riwayat stok']);
    } finally {
        restoreBrowser();
    }
});

test('choosing a value can reset dependent groups to their own defaults', () => {
    const sheet = createSheet({ groups: [{ key: 'year', default: 'all', resets: ['week'] }, { key: 'week', default: '' }] });
    sheet.extra.year = '2026';
    sheet.extra.week = '2026-01-03|2026-01-09';

    sheet.setExtra('year', '2025');

    assert.deepEqual({ ...sheet.extra }, { year: '2025', week: '' });
});

test('a group without dependents leaves the others alone', () => {
    const sheet = createSheet({ groups: [{ key: 'a', default: 'all' }, { key: 'b', default: 'all' }] });
    sheet.extra.b = 'x';

    sheet.setExtra('a', 'y');

    assert.deepEqual({ ...sheet.extra }, { a: 'y', b: 'x' });
});

test('matches follows the current group values, with one or several allowed values', () => {
    const sheet = createSheet({ groups: [{ key: 'timeGroup', default: 'daily' }, { key: 'groupBy', default: 'product' }] });

    assert.equal(sheet.matches(null), true);
    assert.equal(sheet.matches({ timeGroup: 'daily' }), true);
    assert.equal(sheet.matches({ timeGroup: 'weekly' }), false);
    assert.equal(sheet.matches({ timeGroup: ['weekly', 'daily'] }), true);
    assert.equal(sheet.matches({ timeGroup: 'daily', groupBy: 'date' }), false);

    sheet.setExtra('timeGroup', 'weekly');
    assert.equal(sheet.matches({ timeGroup: 'weekly' }), true);
});
