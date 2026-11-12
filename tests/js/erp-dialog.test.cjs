const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '../..');
const ErpDialog = require(path.join(root, 'public/assets/js/erp-dialog.js'));
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

function fakeSwal(result = { isConfirmed: true }) {
    const calls = [];
    return { calls, fire(options) { calls.push(options); return Promise.resolve(result); } };
}

test('a destructive confirmation is red, cancel is on the left with the focus on it, and the texts are Indonesian', () => {
    const o = ErpDialog.normalize([{ title: 'Hapus Satuan?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete!', cancelButtonText: 'No, return', confirmButtonColor: '#be123c', buttonsStyling: false, customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-active-light', popup: 'keep-me' } }]);

    assert.equal(o.confirmButtonColor, '#dc2626', 'one red for every destructive action');
    assert.equal(o.cancelButtonColor, '#4b5563');
    assert.equal(o.confirmButtonText, 'Ya, hapus');
    assert.equal(o.cancelButtonText, 'Kembali');
    assert.equal(o.reverseButtons, true);
    assert.equal(o.focusCancel, true, 'Enter must not delete');
    assert.equal(o.buttonsStyling, true, 'native buttons, not the Metronic btn classes');
    assert.deepEqual(o.customClass, { popup: 'keep-me' });
});

test('a plain confirmation is the primary colour and asks "Ya" / "Batal" when the page gave no texts', () => {
    const o = ErpDialog.normalize([{ title: 'Simpan?', icon: 'question', showCancelButton: true }]);

    assert.equal(o.confirmButtonColor, '#0b595b');
    assert.equal(o.confirmButtonText, 'Ya');
    assert.equal(o.cancelButtonText, 'Batal');
    assert.ok(!o.focusCancel);
});

test('an explicit tone wins over the icon', () => {
    assert.equal(ErpDialog.normalize([{ icon: 'warning', showCancelButton: true, tone: 'primary' }]).confirmButtonColor, '#0b595b');
    assert.equal(ErpDialog.normalize([{ icon: 'question', showCancelButton: true, tone: 'danger' }]).confirmButtonColor, '#dc2626');
    assert.ok(!('tone' in ErpDialog.normalize([{ showCancelButton: true, tone: 'danger' }])), 'tone is ours, not a SweetAlert option');
});

test('a message dialog has one OK button; the Metronic "Ok, got it!" and "Mengerti" become OK', () => {
    for (const text of ['Ok, got it!', 'Mengerti', 'Ok', undefined]) {
        const o = ErpDialog.normalize([{ icon: 'error', title: 'Gagal', text: 'x', confirmButtonText: text }]);
        assert.equal(o.confirmButtonText, 'OK');
        assert.equal(o.confirmButtonColor, '#0b595b', 'an error message is not a destructive action');
        assert.ok(!o.showCancelButton);
    }
});

test('the shorthand Swal.fire(title, text, icon) works, and everything else a call passes is untouched', () => {
    const short = ErpDialog.normalize(['Gagal', 'Tidak bisa', 'error']);
    assert.equal(short.title, 'Gagal');
    assert.equal(short.text, 'Tidak bisa');
    assert.equal(short.icon, 'error');
    assert.equal(short.confirmButtonText, 'OK');

    const preConfirm = () => 1;
    const o = ErpDialog.normalize([{ html: '<b>x</b>', input: 'text', preConfirm, timer: 1500, allowOutsideClick: false, showCancelButton: true }]);
    assert.equal(o.html, '<b>x</b>');
    assert.equal(o.input, 'text');
    assert.equal(o.preConfirm, preConfirm);
    assert.equal(o.timer, 1500);
    assert.equal(o.allowOutsideClick, false);
});

test('toasts and dialogs without a confirm button are left alone', () => {
    const toast = { toast: true, position: 'top-end', icon: 'success', title: 'Tersimpan', timer: 2000, showConfirmButton: false };
    assert.deepEqual(ErpDialog.normalize([toast]), toast);

    const silent = ErpDialog.normalize([{ icon: 'info', title: 'Memuat', showConfirmButton: false, timer: 800 }]);
    assert.ok(!('confirmButtonColor' in silent));
    assert.ok(!('confirmButtonText' in silent));
});

test('install wraps Swal.fire once, so existing calls and the helpers go through the same normalisation', async () => {
    const swal = fakeSwal();
    ErpDialog.install(swal);
    ErpDialog.install(swal); // a second install must not wrap twice

    await swal.fire({ title: 'Hapus?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete!' });
    assert.equal(swal.calls.length, 1);
    assert.equal(swal.calls[0].confirmButtonText, 'Ya, hapus');
    assert.equal(swal.calls[0].confirmButtonColor, '#dc2626');
});

test('confirmDelete asks the one question and resolves true / false', async () => {
    const swal = fakeSwal({ isConfirmed: true });
    ErpDialog.install(swal);

    assert.equal(await ErpDialog.confirmDelete({ what: 'Satuan', name: 'Kg' }), true);
    const asked = swal.calls[0];
    assert.equal(asked.title, 'Hapus Satuan?');
    assert.equal(asked.text, 'Satuan Kg akan dihapus permanen.');
    assert.equal(asked.confirmButtonText, 'Hapus');
    assert.equal(asked.confirmButtonColor, '#dc2626');
    assert.equal(asked.focusCancel, true);

    await ErpDialog.confirmDelete({ what: 'resep', text: 'Data resep akan dihapus permanen.' });
    assert.equal(swal.calls[1].text, 'Data resep akan dihapus permanen.');
    await ErpDialog.confirmDelete();
    assert.equal(swal.calls[2].title, 'Hapus data?');

    const declined = fakeSwal({ isConfirmed: false, isDismissed: true });
    ErpDialog.install(declined);
    assert.equal(await ErpDialog.confirmDelete({ what: 'Satuan' }), false);
});

test('error, success, warning and info have their default titles', async () => {
    const swal = fakeSwal();
    ErpDialog.install(swal);
    await ErpDialog.error('Gagal memuat.');
    await ErpDialog.success('Tersimpan.');
    await ErpDialog.warning('Cek lagi.');
    await ErpDialog.info('Catatan.');
    await ErpDialog.error('x', 'Judul lain');

    assert.deepEqual(swal.calls.map(c => [c.icon, c.title]), [['error', 'Gagal'], ['success', 'Berhasil'], ['warning', 'Perhatian'], ['info', 'Info'], ['error', 'Judul lain']]);
});

test('both layouts load the dialog script right after SweetAlert, and the delete confirmations use the helper', () => {
    const root_ = read('resources/views/template/root.blade.php');
    const tailwind = read('resources/views/layouts/erp-tailwind.blade.php');
    assert.ok(root_.indexOf('assets/js/erp-dialog.js') > root_.indexOf('plugins.bundle.js'), 'after the bundle that holds SweetAlert');
    assert.ok(tailwind.indexOf('assets/js/erp-dialog.js') > tailwind.indexOf('sweetalert2@11'));

    for (const view of ['Modules/Master/Resources/views/unit/index.blade.php', 'Modules/Transaction/Resources/views/receipt/index.blade.php', 'Modules/Master/Resources/views/partials/mobile-index.blade.php']) {
        const source = read(view);
        assert.match(source, /ErpDialog\.confirmDelete\(/, view);
        assert.ok(!/title: *['`]Hapus/.test(source), `${view} still builds its own delete dialog`);
    }
    assert.ok(!/js\.map|innerHTML/.test(read('public/assets/js/erp-dialog.js')));
});
