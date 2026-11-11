const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const PosListUi = require('../../public/assets/js/pos-list-reference.js');
const view = fs.readFileSync('Modules/Pos/Resources/views/pos/index2.blade.php', 'utf8');

function loader(fetch) {
    const source = view.match(/        async function loadLinkedPosDetail\(id\) \{[\s\S]*?(?=        \/\/ Reference avatar)/)?.[0];
    assert.ok(source, 'The POS page needs its real read-only lookup loader');
    const context = {URL, fetch, PosListUi, AbortSignal,
        document: {createElement: () => ({content: {querySelector: () => null}})} };
    vm.createContext(context);
    vm.runInContext(source.replace("{{ route('pos-data') }}", 'http://localhost/pos/data'), context);
    return context.loadLinkedPosDetail;
}

test('POS initializes linked detail once and exposes loading and failure messages', () => {
    assert.equal(view.split('posUi().openLinkedDetail(window.location.search, loadLinkedPosDetail)').length - 1, 1);
    assert.match(view, /role="status"[^>]*x-show="detailLinkLoading"/);
    assert.match(view, /role="alert"[^>]*x-show="detailLinkError"[^>]*x-text="detailLinkError"/);
});

test('linked detail uses a single exact-ID GET independent of table pagination and filters', async () => {
    const load = loader(async (url, options) => {
        assert.equal(url.origin + url.pathname, 'http://localhost/pos/data');
        assert.equal(url.searchParams.get('detail_id'), '1316');
        assert.equal(url.searchParams.get('start'), '0');
        assert.equal(url.searchParams.get('length'), '1');
        assert.equal(url.searchParams.has('status_filter'), false);
        assert.equal(url.searchParams.has('cabang_filter'), false);
        assert.equal(options.method, 'GET');
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.headers.Accept, 'application/json');
        assert.equal(options.body, undefined);
        assert.ok(options.signal);
        return {ok: true, redirected: false, json: async () => ({data: []})};
    });
    assert.equal(await load('1316'), null);
});

test('lookup rejects HTTP errors, login redirects and DataTables business errors', async () => {
    for (const response of [
        {ok: false, status: 403},
        {ok: true, redirected: true},
        {ok: true, json: async () => ({error: 'query failed', data: []})},
        {ok: true, json: async () => ({})},
    ]) {
        await assert.rejects(loader(async () => response)('1316'));
    }
});
