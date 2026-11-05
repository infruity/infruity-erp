const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { pathToFileURL } = require('node:url');

const load = (file) => import(pathToFileURL(path.resolve(__dirname, '../../resources/js/erp', file)).href);

/* DOM tiruan minimal: cukup untuk event delegation dan <dialog>. */
function makeDoc() {
    const listeners = {};
    const doc = {
        listeners,
        activeElement: null,
        dialogs: [],
        documentElement: { classes: new Set(), classList: { toggle(c, on) { on ? doc.documentElement.classes.add(c) : doc.documentElement.classes.delete(c); } } },
        addEventListener(type, fn) { (listeners[type] ||= []).push(fn); },
        getElementById(id) { return doc.dialogs.find((d) => d.id === id) || null; },
        querySelectorAll(sel) { return sel === 'dialog[open]' ? doc.dialogs.filter((d) => d.open) : []; },
        fire(type, event) { (listeners[type] || []).forEach((fn) => fn(event)); },
    };
    return doc;
}

function el(doc, tag, attrs = {}, parent = null) {
    const node = {
        tagName: tag.toUpperCase(), parent, attrs, isConnected: true, events: [], focused: 0,
        hasAttribute: (n) => n in attrs,
        getAttribute: (n) => attrs[n],
        dispatchEvent(e) { node.events.push(e.type); return true; },
        focus() { node.focused += 1; },
        closest(sel) {
            for (let n = node; n; n = n.parent) {
                if (sel === 'dialog' ? n.tagName === 'DIALOG' : n.hasAttribute(sel.replace(/^\[|\]$/g, ''))) return n;
            }
            return null;
        },
    };
    return node;
}

function makeDialog(doc, id, attrs = {}) {
    const d = el(doc, 'dialog', { 'data-erp-dialog': '', ...attrs });
    d.id = id;
    d.open = false;
    d.returnValue = '';
    d.showModal = () => { d.open = true; };
    d.close = (rv) => { if (!d.open) return; d.open = false; d.returnValue = rv ?? ''; doc.fire('close', { target: d }); };
    doc.dialogs.push(d);
    return d;
}

const click = (doc, target) => {
    const event = { target, prevented: false, preventDefault() { this.prevented = true; } };
    doc.fire('click', event);
    return event;
};

test('data-erp-open opens the dialog, locks scroll and announces it', async () => {
    const { install, LOCK_CLASS } = await load('dialog.js');
    const doc = makeDoc();
    const dialog = makeDialog(doc, 'edit');
    const button = el(doc, 'button', { 'data-erp-open': '#edit' });
    install(doc);

    const event = click(doc, button);

    assert.equal(dialog.open, true);
    assert.equal(event.prevented, true);
    assert.ok(doc.documentElement.classes.has(LOCK_CLASS));
    assert.deepEqual(dialog.events, ['erp:dialog-open']);
});

test('data-erp-close closes, unlocks scroll, announces and returns focus to the opener', async () => {
    const { install, LOCK_CLASS } = await load('dialog.js');
    const doc = makeDoc();
    const dialog = makeDialog(doc, 'edit');
    const opener = el(doc, 'button', { 'data-erp-open': '#edit' });
    const closer = el(doc, 'button', { 'data-erp-close': '' }, dialog);
    install(doc);

    click(doc, opener);
    click(doc, closer);

    assert.equal(dialog.open, false);
    assert.ok(!doc.documentElement.classes.has(LOCK_CLASS));
    assert.deepEqual(dialog.events, ['erp:dialog-open', 'erp:dialog-close']);
    assert.equal(opener.focused, 1);
});

test('clicking the backdrop closes a normal dialog but not a static one', async () => {
    const { install } = await load('dialog.js');
    const doc = makeDoc();
    const normal = makeDialog(doc, 'a');
    const fixed = makeDialog(doc, 'b', { 'data-static': '' });
    install(doc);
    normal.open = true;
    fixed.open = true;

    click(doc, normal);
    click(doc, fixed);

    assert.equal(normal.open, false);
    assert.equal(fixed.open, true);
});

test('Esc (cancel) is prevented for static dialogs only', async () => {
    const { install } = await load('dialog.js');
    const doc = makeDoc();
    const normal = makeDialog(doc, 'a');
    const fixed = makeDialog(doc, 'b', { 'data-static': '' });
    install(doc);

    const cancelNormal = { target: normal, prevented: false, preventDefault() { this.prevented = true; } };
    const cancelFixed = { target: fixed, prevented: false, preventDefault() { this.prevented = true; } };
    doc.fire('cancel', cancelNormal);
    doc.fire('cancel', cancelFixed);

    assert.equal(cancelNormal.prevented, false);
    assert.equal(cancelFixed.prevented, true);
});

test('scroll stays locked until the last open dialog closes', async () => {
    const { install, LOCK_CLASS } = await load('dialog.js');
    const doc = makeDoc();
    const a = makeDialog(doc, 'a');
    const b = makeDialog(doc, 'b');
    const api = install(doc);

    api.open('a');
    api.open('b');
    api.close('b');
    assert.ok(doc.documentElement.classes.has(LOCK_CLASS));
    api.close('a');
    assert.ok(!doc.documentElement.classes.has(LOCK_CLASS));
    assert.equal(a.open || b.open, false);
});

test('opening an unknown dialog or an already open one is a no-op', async () => {
    const { install } = await load('dialog.js');
    const doc = makeDoc();
    const dialog = makeDialog(doc, 'a');
    const api = install(doc);

    assert.equal(api.open('missing'), null);
    api.open('a');
    api.open('a');
    assert.deepEqual(dialog.events, ['erp:dialog-open']);
});

test('check-all state covers none, some and all rows', async () => {
    const { state } = await load('check-all.js');

    assert.deepEqual(state(0, 0), { checked: false, indeterminate: false });
    assert.deepEqual(state(0, 5), { checked: false, indeterminate: false });
    assert.deepEqual(state(2, 5), { checked: false, indeterminate: true });
    assert.deepEqual(state(5, 5), { checked: true, indeterminate: false });
});

test('legacy bridge: Modal.show/hide drive the dialog and emit bootstrap-style events', async () => {
    const { install } = await load('legacy-bridge.js');
    const calls = [];
    const target = { open: false, events: [], showModal() {}, addEventListener(t, fn) { (this.l ||= {})[t] = fn; }, dispatchEvent(e) { this.events.push(e.type); return true; } };
    const win = { document: { querySelector: () => target }, console: { warn() {} } };
    const erpDialog = { open: (e) => { e.open = true; e.l['erp:dialog-open'](); }, close: (e) => { e.open = false; e.l['erp:dialog-close'](); } };

    const { Modal } = install(erpDialog, win);
    const modal = Modal.getOrCreateInstance(target);

    assert.equal(Modal.getOrCreateInstance(target), modal);
    modal.show();
    modal.hide();

    assert.deepEqual(target.events, ['show.bs.modal', 'shown.bs.modal', 'hide.bs.modal', 'hidden.bs.modal']);
    assert.equal(win.bootstrap.Modal, Modal);
});

test('legacy bridge never overrides a real bootstrap.Modal and adds $.fn.modal only when missing', async () => {
    const { install } = await load('legacy-bridge.js');
    const real = class RealModal {};
    const jq = Object.assign(() => {}, { fn: {} });
    const win = { bootstrap: { Modal: real }, jQuery: jq, document: { querySelector: () => null }, console: { warn() {} } };

    install({ open() {}, close() {} }, win);

    assert.equal(win.bootstrap.Modal, real);
    assert.equal(typeof jq.fn.modal, 'function');

    const existing = () => 'mine';
    const win2 = { jQuery: Object.assign(() => {}, { fn: { modal: existing } }), document: { querySelector: () => null }, console: { warn() {} } };
    install({ open() {}, close() {} }, win2);
    assert.equal(win2.jQuery.fn.modal, existing);
});

test('toast configures a SweetAlert toast and falls back to the info icon', async () => {
    const { toast } = await load('toast.js');
    let config;
    let fired;
    const Swal = { mixin: (c) => { config = c; return { fire: (f) => { fired = f; return 'ok'; } }; } };

    assert.equal(toast({ type: 'success', message: 'Tersimpan' }, Swal), 'ok');
    assert.equal(config.toast, true);
    assert.equal(config.showConfirmButton, false);
    assert.deepEqual(fired, { icon: 'success', title: 'Tersimpan' });

    toast({ type: 'weird', message: 'x' }, Swal);
    assert.equal(fired.icon, 'info');
    assert.equal(toast({ message: 'x' }, null), null);
});

function makeTable(count, preChecked = []) {
    const listeners = {};
    const doc = {
        addEventListener(type, fn) { (listeners[type] ||= []).push(fn); },
        fire(type, event) { (listeners[type] || []).forEach((fn) => fn(event)); },
    };
    const rows = Array.from({ length: count }, (_, i) => ({
        checked: preChecked.includes(i), disabled: false,
        matches: (sel) => sel === '[data-erp-check]',
        dispatchEvent(e) { doc.fire('change', { target: this }); return true; },
    }));
    const root = { querySelectorAll: () => rows, contains: (el) => rows.includes(el) };
    const master = {
        checked: false, indeterminate: false,
        matches: (sel) => sel === '[data-erp-check-all]',
        getAttribute: () => '#tbl',
    };
    doc.querySelector = () => root;
    doc.querySelectorAll = () => [master];
    const toggleMaster = (value) => { master.checked = value; doc.fire('change', { target: master }); };
    const toggleRow = (i, value) => { rows[i].checked = value; doc.fire('change', { target: rows[i] }); };
    return { doc, rows, master, toggleMaster, toggleRow };
}

test('check-all: checking the master checks every row (regression: header sync used to interrupt the loop)', async () => {
    const { install } = await load('check-all.js');
    const t = makeTable(4);
    install(t.doc, {});

    t.toggleMaster(true);

    assert.deepEqual(t.rows.map((r) => r.checked), [true, true, true, true]);
    assert.equal(t.master.checked, true);
    assert.equal(t.master.indeterminate, false);

    t.toggleMaster(false);
    assert.deepEqual(t.rows.map((r) => r.checked), [false, false, false, false]);
});

test('check-all: the master becomes indeterminate when only some rows are checked, then checked when all are', async () => {
    const { install } = await load('check-all.js');
    const t = makeTable(3);
    install(t.doc, {});

    t.toggleRow(0, true);
    assert.deepEqual([t.master.checked, t.master.indeterminate], [false, true]);

    t.toggleRow(1, true);
    t.toggleRow(2, true);
    assert.deepEqual([t.master.checked, t.master.indeterminate], [true, false]);

    t.toggleRow(1, false);
    assert.deepEqual([t.master.checked, t.master.indeterminate], [false, true]);
});

test('legacy bridge ignores Modal on a non-dialog element instead of throwing', async () => {
    const { install } = await load('legacy-bridge.js');
    const target = { events: [], addEventListener() {}, dispatchEvent(e) { this.events.push(e.type); return true; } };
    const win = { document: { querySelector: () => target }, console: { warn() {} } };
    const { Modal } = install({ open() { throw new Error('tidak boleh dipanggil'); }, close() { throw new Error('tidak boleh dipanggil'); } }, win);

    const modal = Modal.getOrCreateInstance(target);
    modal.show();
    modal.hide();

    assert.deepEqual(target.events, []);
});

test('DataTables defaults keep the markup of the old Metronic bundle', async () => {
    const { install } = await load('../vendor/datatables-bootstrap.js');

    const merge = (target, source) => {
        for (const [k, v] of Object.entries(source)) {
            target[k] = v && typeof v === 'object' && !Array.isArray(v) ? merge(target[k] || {}, v) : v;
        }
        return target;
    };
    const dataTable = { defaults: {}, ext: { classes: {}, renderer: { pageButton: {} } } };
    const $ = { fn: { dataTable }, extend: (deep, target, source) => (typeof deep === 'boolean' ? merge(target, source) : Object.assign(deep, target)) };

    install($, {});

    assert.equal(dataTable.defaults.renderer, 'bootstrap');
    assert.ok(dataTable.defaults.dom.startsWith("<'table-responsive'tr>"));
    assert.equal(dataTable.defaults.language.lengthMenu, '_MENU_');
    assert.equal(dataTable.defaults.language.info, 'Showing _START_ to _END_ of _TOTAL_ records');
    assert.equal(dataTable.defaults.language.paginate.next, '<i class="next"></i>');
    assert.equal(dataTable.ext.classes.sWrapper, 'dataTables_wrapper dt-bootstrap4');
    assert.equal(dataTable.ext.classes.sLengthSelect, 'form-select form-select-sm form-select-solid');
    assert.equal(dataTable.ext.classes.sPageButton, 'paginate_button page-item');
    assert.equal(typeof dataTable.ext.renderer.pageButton.bootstrap, 'function');
});
