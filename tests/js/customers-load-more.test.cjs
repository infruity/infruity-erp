const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const repo = path.join(__dirname, '../..');
const partial = fs.readFileSync(path.join(repo, 'Modules/Master/Resources/views/partials/mobile-index.blade.php'), 'utf8');

// The customers list loads 50 at a time from the server; "Muat Pelanggan Lagi" appends the next page like the product list.
function list({ pages, search = '', initial = pages[0] }) {
    const requests = [];
    const alerts = [];
    const context = {
        URLSearchParams,
        Set,
        String,
        window: { location: { pathname: '/customers', search, reload() {} }, Swal: { fire: (...args) => alerts.push(args) } },
        fetch: async (url, options) => {
            requests.push({ url, options });
            const page = Number(new URL(url, 'http://x').searchParams.get('page'));
            const body = pages[page - 1];
            return body ? { ok: true, status: 200, json: async () => body } : { ok: false, status: 500, json: async () => ({}) };
        },
    };
    const script = partial.slice(partial.indexOf('function masterList('), partial.lastIndexOf('</script>'));
    vm.runInNewContext(script, context);
    const model = context.masterList(initial.data, [], 'customers', 'name', [], 'Pelanggan', 'server', search.replace('?q=', ''), {}, false, '', '', false, '', { total: initial.total, nextPage: initial.next_page });
    return { model, requests, alerts };
}

const rows = (from, to) => Array.from({ length: to - from + 1 }, (_, i) => ({ id: from + i, name: `Pelanggan ${from + i}` }));
const pages = [
    { data: rows(1, 50), total: 120, next_page: 2 },
    { data: rows(51, 100), total: 120, next_page: 3 },
    { data: rows(101, 120), total: 120, next_page: null },
];

test('loading more appends the next page to the list and updates the counters', async () => {
    const { model, requests } = list({ pages });
    assert.equal(model.records.length, 50);
    assert.equal(model.total, 120);
    assert.equal(model.nextPage, 2);

    await model.loadMore();
    assert.equal(model.records.length, 100);
    assert.equal(model.visible.length, 100, 'every loaded row is shown, not just the first 50');
    assert.equal(model.nextPage, 3);
    assert.match(requests[0].url, /^\/customers\?page=2$/);
    assert.equal(requests[0].options.headers.Accept, 'application/json');

    await model.loadMore();
    assert.equal(model.records.length, 120);
    assert.equal(model.nextPage, null, 'the button disappears after the last page');
    assert.deepEqual(model.records.slice(98, 102).map(r => r.id), [99, 100, 101, 102]);

    await model.loadMore();
    assert.equal(requests.length, 2, 'nothing more is requested once everything is loaded');
});

test('the search term is kept and rows already shown are never duplicated', async () => {
    const overlapping = [pages[0], { data: rows(45, 60), total: 60, next_page: null }];
    const { model, requests } = list({ pages: overlapping, search: '?q=ani' });
    await model.loadMore();
    assert.match(requests[0].url, /page=2/);
    assert.match(requests[0].url, /q=ani/);
    assert.equal(model.records.length, 60, 'ids 45-50 were already there');
    assert.equal(new Set(model.records.map(r => r.id)).size, 60);
    assert.equal(model.total, 60);
});

test('a failed request keeps the list, re-enables the button and tells the user', async () => {
    const { model, alerts } = list({ pages: [pages[0]] });
    await model.loadMore();
    assert.equal(model.records.length, 50);
    assert.equal(model.nextPage, 2);
    assert.equal(model.loadingMore, false);
    assert.equal(alerts.length, 1);
});

test('the customers page uses the same load-more control as the product list instead of a pager', () => {
    assert.ok(partial.includes('x-erp.load-more @click="loadMore()" loading="loadingMore">Muat {{ $pageTitle }} Lagi'));
    assert.ok(!partial.includes('Selanjutnya'), 'the previous/next pager is gone');
    assert.ok(!partial.includes('$records->links()'));
});
