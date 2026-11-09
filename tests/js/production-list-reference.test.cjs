const test=require('node:test');
const assert=require('node:assert/strict');
const ui=require('../../public/assets/js/production-list-reference.js');
test('recorded server clock is formatted without browser timezone conversion',()=>{
 assert.equal(ui.recordedTime('19/08/2026 02:03'),'19 Agu 2026 · 02:03');
 assert.equal(ui.recordedTime('01/11/2026 10:15'),'1 Nov 2026 · 10:15');
 for(const text of ['',null,'31/02/2026 10:00','19/08/2026 25:03','<img onerror=x>']) assert.equal(ui.recordedTime(text),'—');
});
test('status captions preserve the ready-to-produce distinction',()=>{
 assert.equal(ui.statusLabel('is-temp'),'Draf');
 assert.equal(ui.statusLabel('is-draft'),'Siap Produksi');
 assert.equal(ui.statusLabel('is-posting'),'Selesai');
 assert.equal(ui.statusLabel('is-complete'),'Selesai');
 assert.equal(ui.statusLabel('is-muted'),null);
});
test('output quantity retains existing numeric precision without inventing a unit',()=>{
 assert.equal(ui.quantity('12.25'),'12,25');assert.equal(ui.quantity(1),'1');
 for(const v of [null,undefined,'',Infinity,'invalid']) assert.equal(ui.quantity(v),null);
});
test('row enhancement reuses server clock and is idempotent without inventing missing quantities', () => {
 let time = null, appended = 0;
 const attributes = {};
 const copy = { querySelector: () => time, append: node => { time = node; appended++; } };
 const status = { classList: ['production-status', 'is-temp'], textContent: 'Temp' };
 const qty = { textContent: 'Qty: 12', setAttribute: (key, value) => { attributes[key] = value; } };
 const nodes = {
  '.production-copy': copy,
  '.production-status': status,
  '.production-qty': qty,
  '.production-sub': { textContent: 'PRO202608391 · 19/08/2026 02:03 · 1 bahan' },
  'td:nth-child(3) span': { textContent: '19/08/2026 02:03' },
 };
 const previousDocument = global.document;
 global.document = { createElement: () => ({ setAttribute() {} }) };
 try {
  const row = { querySelector: selector => nodes[selector] };
  ui.enhanceRow(row, { quantity: '12.25' });
  assert.equal(nodes['.production-sub'].textContent, 'PRO202608391 · 1 bahan');
  assert.equal(time.textContent, '19 Agu 2026 · 02:03');
  assert.equal(status.textContent, 'Draf');
  assert.equal(qty.textContent, '12,25');
  assert.equal(attributes['aria-label'], 'Hasil produksi: 12,25');
  ui.enhanceRow(row, { quantity: null });
  assert.equal(appended, 1);
  assert.equal(qty.textContent, '12,25');
 } finally {
  global.document = previousDocument;
 }
});
test('row quantity appends the server-provided unit and stays unit-less when none is provided', () => {
 const run = dataset => {
  const attributes = {};
  const qty = { textContent: 'Qty: 12', dataset, setAttribute: (key, value) => { attributes[key] = value; } };
  const nodes = { '.production-qty': qty };
  const row = { querySelector: selector => nodes[selector] };
  const previousDocument = global.document;
  global.document = { createElement: () => ({ setAttribute() {} }) };
  try { ui.enhanceRow(row, { quantity: '12.25' }); } finally { global.document = previousDocument; }
  return { text: qty.textContent, label: attributes['aria-label'] };
 };
 assert.deepEqual(run({ unit: 'pcs' }), { text: '12,25 pcs', label: 'Hasil produksi: 12,25 pcs' });
 assert.deepEqual(run({}), { text: '12,25', label: 'Hasil produksi: 12,25' });
 assert.deepEqual(run({ unit: '' }), { text: '12,25', label: 'Hasil produksi: 12,25' });
});
