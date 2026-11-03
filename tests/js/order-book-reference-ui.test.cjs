const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const viewPath = path.join(__dirname, '../../Modules/Pos/Resources/views/order-book/index.blade.php');
const view = fs.readFileSync(viewPath, 'utf8');

function loadOrderBookUiHelpers() {
    const helper = view.match(/const orderBookReferenceUi = \(\(\) => \{[\s\S]*?\}\)\(\);/);
    assert.ok(helper, 'the order-book reference UI helpers must be present in the Blade view');
    return vm.runInNewContext(`${helper[0]}; orderBookReferenceUi;`);
}

function loadOrderBookFilterUiHelpers() {
    const helper = view.match(/const orderBookFilterUi = \(\(\) => \{[\s\S]*?\}\)\(\);/);
    assert.ok(helper, 'the order-book filter date helpers must be present in the Blade view');
    return vm.runInNewContext(`${helper[0]}; orderBookFilterUi;`);
}

function createOrderBookFilterSheet({ deferFrames = false } = {}) {
    const helper = view.match(/const orderBookFilterUi = \(\(\) => \{[\s\S]*?\}\)\(\);/);
    assert.ok(helper, 'the order-book filter date helpers must be present in the Blade view');
    const factoryStart = view.indexOf('function orderBookFilterSheet() {');
    const factoryEnd = view.indexOf('$(document).ready(function()', factoryStart);
    assert.ok(factoryStart >= 0 && factoryEnd > factoryStart, 'the order-book filter state must be present in the Blade view');

    const controls = {
        status: { value: 'all' },
        branch: { value: 'all' },
        range: { value: '' }
    };
    const main = { style: { overflow: 'auto' } };
    const events = [];
    const focuses = [];
    const frames = [];
    const document = {
        body: { style: { overflow: '' } },
        activeElement: null,
        getElementById(id) {
            if (id === 'order-book-filter-config') {
                return { textContent: JSON.stringify({ branches: [{ id: '42', name: 'Cabang Uji' }], timezone: 'Asia/Jakarta' }) };
            }
            return id === 'kt_ecommerce_sales_flatpickr' ? controls.range : null;
        },
        querySelector(selector) {
            if (selector.includes('status')) return controls.status;
            if (selector.includes('cabang')) return controls.branch;
            return selector === '.erp-main' ? main : null;
        },
        dispatchEvent(event) {
            events.push(event);
            return true;
        }
    };
    class TestCustomEvent {
        constructor(type) {
            this.type = type;
        }
    }

    const factorySource = view.slice(factoryStart, factoryEnd);
    const context = vm.createContext({
        document,
        CustomEvent: TestCustomEvent,
        requestAnimationFrame(callback) {
            if (deferFrames) frames.push(callback);
            else callback();
        }
    });
    const create = vm.runInContext(`${helper[0]}\n${factorySource}\norderBookFilterSheet;`, context);
    const sheet = create();
    sheet.$nextTick = callback => callback();
    sheet.$refs = {
        filterTrigger: { focus() {} },
        filterCloseButton: { focus() {} },
        filterPanel: { querySelectorAll: () => [], focus() {} },
        calendarHeadingButton: { focus: () => focuses.push('calendar-heading') },
        dateRangeStart: { focus: () => focuses.push('date-range-start') },
        dateRangeEnd: { focus: () => focuses.push('date-range-end') }
    };

    return { sheet, controls, events, focuses, frames, document, main };
}

test('mobile customer avatar uses initials rather than the generic receipt icon', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.initials('Kak Alvi (8219)'), 'KA');
    assert.equal(ui.initials('Bu Enda'), 'BE');
});

test('mobile order dates use Indonesian month labels and reference punctuation', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.formatDate('27 Oct 2026 09:00'), '27 Okt 2026, 09:00');
    assert.equal(ui.formatDate('27 Okt 2026 09:00'), '27 Okt 2026, 09:00');
});

test('mobile status labels preserve order-book meanings', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.status('pending'), 'Pending');
    assert.equal(ui.status('Proses'), 'Proses');
    assert.equal(ui.status('Selesai'), 'Selesai');
});

test('mobile invoice uses the related POS number and a dash for pending orders', () => {
    const ui = loadOrderBookUiHelpers();

    assert.equal(ui.invoice('Pending', { invoice_number: 'POS-2026-1064' }, 'ORD20261001'), '-');
    assert.equal(ui.invoice('Proses', { invoice_number: 'POS-2026-1064' }, 'ORD20261001'), 'POS-2026-1064');
    assert.equal(ui.invoice('Selesai', { invoice_number: 'ORD202607088' }, 'ORD202607088'), 'ORD202607088');
    assert.equal(ui.invoice('Selesai', null, 'ORD20261001'), 'ORD20261001');
});

test('filter presets use the configured timezone instead of browser-local dates', () => {
    const ui = loadOrderBookFilterUiHelpers();
    const instant = new Date('2026-10-31T17:30:00.000Z');

    assert.equal(ui.today('Asia/Jakarta', instant), '2026-11-01');
    assert.equal(ui.today('America/Los_Angeles', instant), '2026-10-31');
});

test('filter week preset is Monday through Sunday across the year boundary', () => {
    const ui = loadOrderBookFilterUiHelpers();
    const range = ui.week('Asia/Jakarta', new Date('2026-12-31T17:30:00.000Z'));

    assert.deepEqual(JSON.parse(JSON.stringify(range)), { start: '2026-12-28', end: '2027-01-03' });
});

test('filter calendar uses Monday-first weeks and Indonesian date labels', () => {
    const ui = loadOrderBookFilterUiHelpers();

    assert.equal(ui.monthGridOffset(2026, 10), 6);
    assert.equal(ui.daysInMonth(2026, 10), 30);
    assert.equal(ui.formatDisplayDate('2027-01-02'), '2 Jan 2027');
});

test('Pending filter maps to the Laravel order-book draft status', () => {
    const { sheet } = createOrderBookFilterSheet();
    const pending = sheet.statusOptions.find(status => status.label === 'Pending');

    assert.equal(pending.value, 'draft');
});

test('draft filter choices reach the request controls only after Apply', () => {
    const { sheet, controls, events } = createOrderBookFilterSheet();
    sheet.init();
    sheet.openFilter();
    sheet.draftStatus = sheet.statusOptions.find(status => status.label === 'Pending').value;
    sheet.draftBranch = '42';
    sheet.draftStartDate = '2026-11-01';
    sheet.draftEndDate = '2026-11-01';

    assert.deepEqual([controls.status.value, controls.branch.value, controls.range.value], ['all', 'all', '']);

    sheet.applyFilters();

    assert.deepEqual([controls.status.value, controls.branch.value, controls.range.value], ['draft', '42', '2026-11-01 to 2026-11-01']);
    assert.deepEqual(events.map(event => event.type), ['order-book:filters-applied']);
});

test('calendar view changes keep keyboard focus on visible controls', () => {
    const { sheet, focuses } = createOrderBookFilterSheet();

    sheet.toggleCalendar('end');
    assert.deepEqual(focuses, ['calendar-heading']);

    sheet.calendarView = 'year';
    sheet.selectCalendarYear(2027);
    assert.equal(sheet.calendarView, 'month');
    sheet.selectCalendarMonth(1);
    assert.equal(sheet.calendarView, 'date');
    sheet.closeCalendar();
    assert.deepEqual(focuses, ['calendar-heading', 'calendar-heading', 'calendar-heading', 'date-range-end']);
});

test('calendar focus waits for the frame that reveals the new Alpine view', () => {
    const { sheet, focuses, frames } = createOrderBookFilterSheet({ deferFrames: true });

    sheet.toggleCalendar('start');
    assert.deepEqual(focuses, [], 'a nextTick alone can focus a still-hidden x-show control');
    assert.equal(frames.length, 1);
    frames.shift()();
    assert.deepEqual(focuses, ['calendar-heading']);

    sheet.closeCalendar();
    assert.deepEqual(focuses, ['calendar-heading']);
    frames.shift()();
    assert.deepEqual(focuses, ['calendar-heading', 'date-range-start']);
});
