const {test} = require('node:test');
const assert = require('node:assert/strict');
const PosListUi = require('../../public/assets/js/pos-list-reference.js');

const config = {timezone: 'Asia/Jakarta', branches: [{id: '1', name: 'Toko GKB'}, {id: '15', name: 'Toko GBA'}], step: 50};
const create = () => PosListUi.createFilters(config);

test('opening hands the sheet the applied filters', () => {
    const f = create();
    const sent = [];
    f.$dispatch = (name, detail) => sent.push({name, detail});
    f.applied = {status: 'paid', branch: '15', start: '2026-11-01', end: '2026-11-07'};

    f.open();

    assert.equal(f.filtersOpen, true);
    assert.deepEqual(JSON.parse(JSON.stringify(sent)), [{name: 'erp-filter-open', detail: {id: 'pos', values: {status: 'paid', branch: '15', start: '2026-11-01', end: '2026-11-07'}}}]);
});

test('applying stores the chosen values, closes the sheet and reports them once', () => {
    const f = create();
    const seen = [];
    f.onApply = value => seen.push(value);
    f.filtersOpen = true;

    f.applyFilters({status: 'partial', branch: '15', start: '', end: '', ignored: 'x'});

    assert.equal(f.filtersOpen, false);
    assert.deepEqual(f.applied, {status: 'partial', branch: '15', start: '', end: ''});
    assert.equal(seen.length, 1);
    assert.deepEqual(seen[0], f.applied);
});

test('applying with missing values falls back to no filter', () => {
    const f = create();
    f.onApply = () => {};

    f.applyFilters({});

    assert.deepEqual(f.applied, {status: 'all', branch: 'all', start: '', end: ''});
});

test('closing the sheet leaves the applied filters alone', () => {
    const f = create();
    f.applied = {status: 'paid', branch: 'all', start: '', end: ''};
    f.filtersOpen = true;

    f.close();

    assert.equal(f.filtersOpen, false);
    assert.equal(f.applied.status, 'paid');
});

test('reset clears the applied filters, and the dot follows the applied state', () => {
    const f = create();
    f.onApply = () => {};
    f.applyFilters({status: 'paid', branch: 'all', start: '', end: ''});
    assert.equal(f.hasActive, true);

    f.reset();

    assert.deepEqual(f.applied, {status: 'all', branch: 'all', start: '', end: ''});
    assert.equal(f.hasActive, false);
});

test('load more grows by the step from the current length and never shrinks the list', () => {
    assert.equal(PosListUi.nextLength(50, 50), 100);
    assert.equal(PosListUi.nextLength(-1, 50), 100);
    assert.equal(PosListUi.nextLength(10, 50), 100);
    assert.equal(PosListUi.nextLength(100, 50), 150);
});

test('remaining label is the count still hidden', () => {
    assert.equal(PosListUi.remainingLabel({end: 50, recordsDisplay: 120}), '(70 tersisa)');
    assert.equal(PosListUi.remainingLabel({end: 120, recordsDisplay: 120}), '');
});

test('avatar initials follow the reference rules', () => {
    assert.equal(PosListUi.customerInitial('Noorman (7050)'), 'NO');
    assert.equal(PosListUi.customerInitial('Ainun Nisa (8219)'), 'AN');
    assert.equal(PosListUi.customerInitial('Pelanggan Umum'), 'PU');
    assert.equal(PosListUi.customerInitial(''), '??');
    assert.equal(PosListUi.customerInitial('  (1234) '), '??');
});

test('a single-branch user keeps their branch as the default and does not show an active dot', () => {
    const f = PosListUi.createFilters({branches: [{id: '1', name: 'GKB'}], defaultBranch: '1'});
    assert.equal(f.applied.branch, '1');
    assert.equal(f.hasActive, false);
    f.onApply = () => {};
    f.reset();
    assert.equal(f.applied.branch, '1');
});

test('load more shows only while rows are hidden and ignores repeat taps while loading', () => {
    const f = create();
    let calls = 0;
    f.onLoadMore = () => calls++;
    f.onDrawn({end: 50, recordsDisplay: 120});
    assert.equal(f.hasMore, true);
    assert.equal(f.remaining, '(70 tersisa)');
    f.loadMore(); f.loadMore();
    assert.equal(calls, 1);
    f.onDrawn({end: 120, recordsDisplay: 120});
    assert.equal(f.hasMore, false);
    assert.equal(f.loading, false);
    f.loadMore();
    assert.equal(calls, 1);
});

const row = (over) => Object.assign({
    id: 9, customer: 'Noorman', phone: '6281230607050', phone_last4: '7050', status_key: 'unpaid',
    show_url: '/pos/show/9', pay_url: '/pos/payment/9', edit_url: null, can_delete: true, edited_label: '',
}, over);

test('detail primary action follows the reference per status', () => {
    assert.deepEqual(PosListUi.detailPrimary(row({status_key: 'pending', edit_url: '/pos/9/edit'})), {label: 'Lanjutkan Transaksi', url: '/pos/9/edit'});
    assert.deepEqual(PosListUi.detailPrimary(row({status_key: 'unpaid'})), {label: 'Bayar', url: '/pos/payment/9'});
    assert.deepEqual(PosListUi.detailPrimary(row({status_key: 'partial'})), {label: 'Bayar', url: '/pos/payment/9'});
    assert.deepEqual(PosListUi.detailPrimary(row({status_key: 'paid', pay_url: null})), {label: 'Lihat Nota Transaksi', url: '/pos/show/9'});
    assert.deepEqual(PosListUi.detailPrimary(row({status_key: 'canceled'})), {label: 'Lihat Nota Transaksi', url: '/pos/show/9'});
});

test('a pending row without an edit link falls back to the receipt instead of a dead button', () => {
    assert.deepEqual(PosListUi.detailPrimary(row({status_key: 'pending', edit_url: null})), {label: 'Lihat Nota Transaksi', url: '/pos/show/9'});
});

test('delete is offered only where the server allows it and never for settled receipts', () => {
    assert.equal(PosListUi.canDelete(row({status_key: 'unpaid', can_delete: true})), true);
    assert.equal(PosListUi.canDelete(row({status_key: 'unpaid', can_delete: false})), false);
    assert.equal(PosListUi.canDelete(row({status_key: 'paid', can_delete: true})), false);
    assert.equal(PosListUi.canDelete(row({status_key: 'canceled', can_delete: true})), false);
});

test('whatsapp link uses digits only and is empty without a number', () => {
    assert.equal(PosListUi.whatsappUrl('+62 812-3060 7050'), 'https://wa.me/6281230607050');
    assert.equal(PosListUi.whatsappUrl(''), '');
    assert.equal(PosListUi.whatsappUrl(null), '');
});

test('a linked POS opens the same detail popup without changing list filters', async () => {
    const f = create();
    const calls = [];
    f.applied.status = 'unpaid';
    f.applied.start = '2026-11-01';
    const linked = row({id: 1316, status_key: 'paid', pay_url: null, show_url: '/pos/show/1316'});
    const opening = f.openLinkedDetail('?detail=1316', async id => { calls.push(id); return linked; });
    assert.equal(f.detailLinkLoading, true);
    await opening;
    assert.deepEqual(calls, ['1316']);
    assert.equal(f.detailOpen, true);
    assert.equal(f.detail, linked);
    assert.equal(f.detailLinkLoading, false);
    assert.equal(f.detailLinkError, '');
    assert.equal(f.applied.status, 'unpaid');
    assert.equal(f.applied.start, '2026-11-01');
    assert.equal(f.detailPrimary(f.detail).label, 'Lihat Nota Transaksi');
    f.closeDetail();
    assert.equal(f.detailOpen, false);
});

test('no linked ID leaves the ordinary POS list untouched', async () => {
    const f = create();
    let calls = 0;
    await f.openLinkedDetail('?other=1', () => { calls++; });
    assert.equal(calls, 0);
    assert.equal(f.detailOpen, false);
    assert.equal(f.detailLinkError, '');
});

test('invalid or repeated linked IDs are rejected before lookup', async () => {
    for (const search of ['?detail=', '?detail=0', '?detail=-1', '?detail=01', '?detail=1.5', '?detail=abc', '?detail=1&detail=2', '?detail=99999999999999999999']) {
        const f = create();
        let called = false;
        await f.openLinkedDetail(search, () => { called = true; });
        assert.equal(called, false, search);
        assert.match(f.detailLinkError, /tidak valid/);
        assert.equal(f.detailOpen, false);
    }
});

test('missing or mismatched linked transactions never open an unrelated detail', async () => {
    for (const response of [null, row({id: 8})]) {
        const f = create();
        await f.openLinkedDetail('?detail=9', async () => response);
        assert.equal(f.detailOpen, false);
        assert.match(f.detailLinkError, /tidak ditemukan atau tidak dapat diakses/);
        assert.equal(f.detailLinkLoading, false);
    }
});

test('failed linked lookup reports an error without opening the payment or receipt', async () => {
    const f = create();
    await f.openLinkedDetail('?detail=9', async () => { throw new Error('HTTP 403'); });
    assert.equal(f.detailOpen, false);
    assert.match(f.detailLinkError, /Gagal memuat/);
    assert.equal(f.detailLinkLoading, false);
});

test('late linked lookup cannot replace a detail the user already opened', async () => {
    const f = create();
    let resolve;
    const opening = f.openLinkedDetail('?detail=9', () => new Promise(done => { resolve = done; }));
    f.openDetail(row({id: 3}));
    resolve(row({id: 9}));
    await opening;
    assert.equal(f.detail.id, 3);
    assert.equal(f.detailLinkLoading, false);
});

test('parseDetail returns null for bad JSON, and the dialog opens and closes with the row', () => {
    assert.equal(PosListUi.parseDetail('{oops'), null);
    assert.equal(PosListUi.parseDetail(''), null);
    const f = create();
    f.openDetail(row());
    assert.equal(f.detailOpen, true);
    assert.equal(f.detail.id, 9);
    f.closeDetail();
    assert.equal(f.detailOpen, false);
    f.openDetail(null);
    assert.equal(f.detailOpen, false);
});
