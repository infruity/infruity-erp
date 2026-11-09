const {test} = require('node:test');
const assert = require('node:assert/strict');
const PosListUi = require('../../public/assets/js/pos-list-reference.js');

const config = {timezone: 'Asia/Jakarta', branches: [{id: '1', name: 'Toko GKB'}, {id: '15', name: 'Toko GBA'}], step: 50};
const create = () => PosListUi.createFilters(config);

test('quick date presets use the application timezone and stay pending until Apply', () => {
    const f = create();
    const now = new Date('2026-11-01T18:30:00Z'); // already Monday 2 Nov in Asia/Jakarta

    f.setDateRange('today', now);
    assert.deepEqual([f.draft.start, f.draft.end], ['2026-11-02', '2026-11-02']);
    f.setDateRange('week', now);
    assert.deepEqual([f.draft.start, f.draft.end], ['2026-11-02', '2026-11-08']);
    assert.deepEqual([f.applied.start, f.applied.end], ['', '']);
});

test('week range starts on Monday across a month boundary', () => {
    const f = create();
    f.setDateRange('week', new Date('2026-10-31T05:00:00Z')); // Saturday 31 Oct, 12:00 WIB
    assert.deepEqual([f.draft.start, f.draft.end], ['2026-10-26', '2026-11-01']);
});

test('apply copies the draft, closes the sheet and reports the applied filters once', () => {
    const f = create();
    const seen = [];
    f.onApply = value => seen.push(value);
    f.open();
    f.draft.status = 'partial';
    f.draft.branch = '15';
    assert.equal(f.applied.status, 'all');
    f.apply();
    assert.equal(f.filtersOpen, false);
    assert.deepEqual(f.applied, {status: 'partial', branch: '15', start: '', end: ''});
    assert.equal(seen.length, 1);
    assert.deepEqual(seen[0], f.applied);
});

test('a reversed date range cannot be applied', () => {
    const f = create();
    let applied = 0;
    f.onApply = () => applied++;
    f.open();
    f.draft.start = '2026-09-01';
    f.draft.end = '2026-08-01';
    assert.match(f.dateError, /Tanggal akhir/);
    f.apply();
    assert.equal(applied, 0);
    assert.equal(f.filtersOpen, true);
});

test('reset clears the draft and the applied filters, and the dot follows the applied state', () => {
    const f = create();
    f.onApply = () => {};
    f.open(); f.draft.status = 'paid'; f.apply();
    assert.equal(f.hasActive, true);
    f.reset();
    assert.deepEqual(f.applied, {status: 'all', branch: 'all', start: '', end: ''});
    assert.equal(f.hasActive, false);
});

test('opening the sheet starts from the applied filters, not from an abandoned draft', () => {
    const f = create();
    f.onApply = () => {};
    f.open(); f.draft.status = 'debt'; f.filtersOpen = false;
    f.open();
    assert.equal(f.draft.status, 'all');
});

test('branch search filters the visible branches and keeps the all option in sync', () => {
    const f = create();
    f.branchSearch = 'gba';
    assert.deepEqual(f.visibleBranches.map(b => b.id), ['15']);
    assert.equal(f.showAllBranches, false);
    f.branchSearch = 'semua';
    assert.equal(f.showAllBranches, true);
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

test('avatar theme is stable per name and always one of the seven themes', () => {
    const a = PosListUi.avatarTheme('Noorman');
    assert.equal(PosListUi.avatarTheme('Noorman'), a);
    assert.equal(PosListUi.avatarTheme(''), 0);
    for (const name of ['A', 'Budi Santoso', 'Pelanggan Umum', 'x'.repeat(40)]) {
        const theme = PosListUi.avatarTheme(name);
        assert.ok(Number.isInteger(theme) && theme >= 0 && theme < 7);
    }
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
