const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const root = path.join(__dirname, '../..');
const listViewPath = path.join(root, 'Modules/Master/Resources/views/partials/mobile-index.blade.php');
const editOverlayPath = path.join(root, 'Modules/Master/Resources/views/staff/edit-overlay.blade.php');
const editScriptPath = path.join(root, 'Modules/Master/Resources/views/staff/js-edit.blade.php');
const createScriptPath = path.join(root, 'Modules/Master/Resources/views/staff/js-create.blade.php');
const listView = fs.readFileSync(listViewPath, 'utf8');

function extractScript(filePath) {
    const view = fs.readFileSync(filePath, 'utf8');
    const start = view.indexOf('<script>');
    const end = view.indexOf('</script>', start);
    assert.ok(start >= 0 && end > start, `${path.basename(filePath)} contains a script`);
    return view.slice(start + '<script>'.length, end);
}

test('staff edit action opens the employee overlay instead of navigating to a new page', () => {
    const start = listView.indexOf('openEdit(record) {');
    const end = listView.indexOf('\n        async openCustomerEdit', start);
    assert.ok(start >= 0 && end > start, 'masterList openEdit handler exists');
    const handler = listView.slice(start, end);
    const staffBranch = handler.indexOf("this.routeBase === 'staff'");
    const pageFallback = handler.indexOf("this.mode !== 'ajax'");
    assert.ok(staffBranch >= 0 && staffBranch < pageFallback, 'staff uses its overlay before the page-mode fallback');
    assert.match(handler, /CustomEvent\('staff-edit-open',\s*\{\s*detail:\s*record\s*\}\)/);
});

test('edit overlay has the reference sheet header, employee fields, and pinned actions', () => {
    assert.equal(fs.existsSync(editOverlayPath), true, 'staff edit overlay partial exists');
    const view = fs.readFileSync(editOverlayPath, 'utf8');
    assert.match(view, /data-staff-edit-overlay/);
    assert.match(view, /aria-modal="true"/);
    assert.match(view, /Edit Karyawan/);
    assert.match(view, /max-h-\[90dvh\]/);
    assert.match(view, /x-html="formHtml"/);
    assert.match(view, /@include\('master::staff\.form-fields'/);
    assert.match(view, /@staff-edit-open\.window=/);
    assert.match(view, /@keydown\.escape\.window=/);
});

test('staff edit overlay fetches an authorized form fragment and preserves list history', async () => {
    const script = extractScript(editScriptPath);
    const historyCalls = [];
    const fetchCalls = [];
    const bodyClasses = [];
    const location = {href: 'http://127.0.0.1:8000/staff?q=Farida#list'};
    const syncLocation = (value) => {
        const url = new URL(value, location.href);
        Object.assign(location, {href: url.href, pathname: url.pathname, search: url.search, hash: url.hash, origin: url.origin});
    };
    syncLocation(location.href);
    const history = {
        state: null,
        pushState(state, _title, url) { this.state = state; historyCalls.push(['push', url]); syncLocation(url); },
        replaceState(state, _title, url) { this.state = state; historyCalls.push(['replace', url]); syncLocation(url); },
        back() { historyCalls.push(['back']); },
    };
    const context = {
        URL,
        URLSearchParams,
        window: {location, history, Swal: {fire: async () => {}}},
        document: {body: {classList: {toggle: (...args) => bodyClasses.push(args)}}},
        fetch: async (url, options) => {
            fetchCalls.push([url, options]);
            return {ok: true, redirected: false, text: async () => '<form data-staff-form></form>'};
        },
    };
    vm.runInNewContext(`${script}\nglobalThis.staffEditOverlay = window.staffEditOverlay;`, context);
    const app = context.staffEditOverlay(false, null, '/staff', '/staff');

    await app.openStaffEdit({id: 10});

    assert.equal(app.drawerOpen, true);
    assert.match(fetchCalls[0][0], /\/staff\/10\/edit\?overlay=1$/);
    assert.equal(fetchCalls[0][1].headers['X-Requested-With'], 'XMLHttpRequest');
    assert.equal(app.formHtml, '<form data-staff-form></form>');
    assert.equal(location.pathname, '/staff/10/edit');
    assert.equal(historyCalls[0][0], 'push');
    assert.equal(historyCalls[0][1], '/staff/10/edit');
    assert.equal(history.state.staffEditReturnUrl, '/staff?q=Farida#list');
    assert.deepEqual(bodyClasses.at(-1), ['overflow-hidden', true]);

    app.closeStaffEdit();
    assert.equal(app.drawerOpen, false);
    assert.deepEqual(historyCalls.at(-1), ['back']);
});

test('both staff overlay scripts parse as browser JavaScript', () => {
    assert.doesNotThrow(() => new vm.Script(extractScript(createScriptPath)), 'create overlay script is valid');
    assert.doesNotThrow(() => new vm.Script(extractScript(editScriptPath)), 'edit overlay script is valid');
});
