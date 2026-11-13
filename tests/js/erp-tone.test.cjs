const test = require('node:test');
const assert = require('node:assert/strict');
const ErpTone = require('../../public/assets/js/erp-tone.js');

// App\Support\AvatarTone must give the same numbers (tests/Unit/AvatarToneTest.php pins the same table).
const TABLE = { 'Toko GKB': 0, 'Toko GBA': 8, 'Gresik / Driyorejo - 001': 4, 'Surabaya / Rungkut - 001': 6, 'Pelanggan Ünï': 7, '': 1, x: 5 };

test('the tone is stable per name, ignores case and spacing, and matches the PHP side', () => {
    for (const [name, index] of Object.entries(TABLE)) assert.equal(ErpTone.index(name), index, name);
    assert.equal(ErpTone.index('  toko gkb '), ErpTone.index('Toko GKB'));
    assert.equal(ErpTone.className('Toko GKB'), 'erp-tone-0');
});

test('neighbouring names do not share a tone and all ten tones are used', () => {
    assert.notEqual(ErpTone.index('Toko GKB'), ErpTone.index('Toko GBA'));
    const used = new Set(Array.from({ length: 300 }, (_, i) => ErpTone.index('Cabang ' + i)));
    assert.equal(used.size, ErpTone.TONES.length);
    assert.deepEqual(Object.keys(ErpTone.style('Toko GKB')), ['background', 'color', 'borderColor']);
});
