const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('Modules/Pos/Resources/views/pos/js/js-buah.blade.php', 'utf8');

function appMethod(name) {
    const start = source.indexOf(`            ${name}(`);
    assert.notEqual(start, -1, `Missing posApp method ${name}`);
    const endMarker = '\n            },';
    const end = source.indexOf(endMarker, start);
    assert.notEqual(end, -1, `Could not find end of posApp method ${name}`);
    return source.slice(start, end + endMarker.length);
}

function tabFunctionContext(referencePage, app, timers = []) {
    const start = source.indexOf('    function posSwitchAddTab(');
    const end = source.indexOf('    window.posSwitchAddTab =', start);
    assert.notEqual(start, -1);
    assert.notEqual(end, -1);
    const functionSource = source.slice(start, end);
    const root = {};
    const context = {
        Alpine: { $data: () => app },
        document: {
            querySelector(selector) {
                if (selector === '[x-data="posApp()"]') return root;
                if (selector === '.pos-add-page') return referencePage ? {} : null;
                return null;
            },
        },
        setTimeout(callback, delay) {
            timers.push({callback, delay});
        },
    };
    vm.runInNewContext(`${functionSource}\nposSwitchAddTab('parcel', 'buah');`, context);
    return context;
}

test('reference tab changes call the in-page category switch without closing a view or timer', () => {
    const calls = [];
    const app = {
        switchAddPageTab(target) { calls.push(['switch', target]); },
        closeAddModal() { calls.push(['close-fruit']); },
        openParcelModal() { calls.push(['open-parcel']); },
    };
    const timers = [];

    tabFunctionContext(true, app, timers);

    assert.deepEqual(calls, [['switch', 'parcel']]);
    assert.equal(timers.length, 0);
});

test('legacy sibling tab flow retains its modal close and delayed open behavior', () => {
    const calls = [];
    const app = {
        closeAddModal() { calls.push('close-fruit'); },
        openParcelModal() { calls.push('open-parcel'); },
        switchAddPageTab() { calls.push('unexpected-page-switch'); },
    };
    const timers = [];

    tabFunctionContext(false, app, timers);

    assert.deepEqual(calls, ['close-fruit']);
    assert.equal(timers.length, 1);
    assert.equal(timers[0].delay, 350);
    timers[0].callback();
    assert.deepEqual(calls, ['close-fruit', 'open-parcel']);
});

test('page category state switches without resetting prior category input or invoking a closer', () => {
    const methods = vm.runInNewContext(`({${appMethod('switchAddPageTab')}})`);
    const calls = [];
    const app = Object.assign({
        activeAddCategory: 'buah',
        showAddPage: false,
        isReferenceAddPage: () => true,
        openAddModal() { calls.push('buah'); },
        openParcelModal() { calls.push('parcel'); },
        openJusModal() { calls.push('jus'); },
        closeAddModal() { calls.push('closed'); },
    }, methods);

    app.switchAddPageTab('parcel');
    app.switchAddPageTab('jus');

    assert.equal(app.showAddPage, true);
    assert.equal(app.activeAddCategory, 'jus');
    assert.deepEqual(calls, ['parcel', 'jus']);
});

test('reference entry opens the category page without activating Bootstrap modal state', () => {
    const fruit = vm.runInNewContext(`({${appMethod('openAddModal')}})`);
    const calls = [];
    const app = Object.assign({
        showAddPage: false,
        activeAddCategory: 'parcel',
        showAddModal: false,
        productSearch: 'old query',
        isReferenceAddPage: () => true,
        loadProductList() { calls.push('load'); },
        setTimeout() { throw new Error('Reference flow must not open a Bootstrap modal'); },
    }, fruit);

    app.openAddModal();

    assert.equal(app.showAddPage, true);
    assert.equal(app.activeAddCategory, 'buah');
    assert.equal(app.productSearch, '');
    assert.equal(app.showAddModal, false);
    assert.deepEqual(calls, ['load']);
});

test('Parcel and Jus category entry leave Bootstrap modal flags off in reference mode', () => {
    const callbacks = [];
    const globals = {setTimeout(callback, delay) { callbacks.push({callback, delay}); }};
    const parcel = vm.runInNewContext(`({${appMethod('openParcelModal')}})`, globals);
    const jus = vm.runInNewContext(`({${appMethod('openJusModal')}})`, globals);
    const app = Object.assign({
        showAddPage: true,
        showParcelModal: false,
        showJusModal: false,
        isReferenceAddPage: () => true,
    }, parcel, jus);

    app.openParcelModal();
    assert.equal(app.activeAddCategory, 'parcel');
    assert.equal(app.showParcelModal, false);
    app.openJusModal();
    assert.equal(app.activeAddCategory, 'jus');
    assert.equal(app.showJusModal, false);
    assert.equal(callbacks.length, 2);
    assert.ok(callbacks.every(timer => timer.delay === 0));
});

test('topbar back closes the active add page and leaves normal navigation alone', () => {
    const methods = vm.runInNewContext(`({${appMethod('closeAddPage')}})`);
    const calls = [];
    const app = Object.assign({
        showAddPage: true,
        activeAddCategory: 'parcel',
        closeParcelModal() { calls.push('close-parcel'); this.showAddPage = false; },
    }, methods);

    assert.equal(app.closeAddPage(), true);
    assert.deepEqual(calls, ['close-parcel']);
    assert.equal(app.closeAddPage(), false);
});
