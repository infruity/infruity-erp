const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const { pathToFileURL } = require('node:url');

const load = (file) => import(pathToFileURL(path.resolve(__dirname, '../../scripts/ui-migrate', file)).href);

test('codemod maps the safe 1:1 utilities', async () => {
    const { mapClassList } = await load('codemod.mjs');
    const r = mapClassList('d-flex align-items-center justify-content-between w-100 fw-bold text-muted');

    assert.equal(r.out, 'flex items-center justify-between w-full font-semibold text-gray-400');
    assert.deepEqual(r.manual, []);
    assert.deepEqual(r.unknown, []);
});

test('codemod shifts Metronic font weights and gray scale, not just the names', async () => {
    const { mapClassList } = await load('codemod.mjs');

    // fw-semibold = 500, fw-bold = 600 (bukan 600/700 seperti Tailwind)
    assert.equal(mapClassList('fw-semibold').out, 'font-medium');
    assert.equal(mapClassList('fw-bold').out, 'font-semibold');
    assert.equal(mapClassList('fw-bolder').out, 'font-bold');
    // text-gray-600 Metronic (#78829D) setara gray-500 Tailwind
    assert.equal(mapClassList('text-gray-600 bg-gray-100').out, 'text-gray-500 bg-gray-50');
    assert.equal(mapClassList('fs-6 fs-7 fs-4').out, 'text-sm text-xs text-base');
});

test('codemod translates responsive Bootstrap prefixes to Tailwind breakpoints', async () => {
    const { mapClassList } = await load('codemod.mjs');

    assert.equal(mapClassList('d-none d-md-block').out, 'hidden md:block');
    assert.equal(mapClassList('d-xxl-flex').out, '2xl:flex');
    assert.equal(mapClassList('d-lg-none').out, 'lg:hidden');
});

test('codemod never touches names that collide with Tailwind, it reports them', async () => {
    const { mapClassList } = await load('codemod.mjs');
    const r = mapClassList('collapse card btn rounded shadow-sm modal');

    assert.equal(r.out, 'collapse card btn rounded shadow-sm modal');
    assert.deepEqual(r.manual.sort(), ['btn', 'card', 'collapse', 'modal', 'rounded', 'shadow-sm'].sort());
});

test('codemod reports legacy tokens that have no mapping yet', async () => {
    const { mapClassList } = await load('codemod.mjs');
    const r = mapClassList('col-md-6 btn-primary form-control p-2');

    assert.deepEqual(r.unknown.sort(), ['btn-primary', 'col-md-6']);
    assert.deepEqual(r.manual, ['form-control']);
    assert.ok(r.out.includes('p-2'));
});

test('codemod converts rem spacing from the old 13px root only when asked', async () => {
    const { mapClassList } = await load('codemod.mjs');

    assert.equal(mapClassList('p-5 mb-4 px-10 gap-3').out, 'p-5 mb-4 px-10 gap-3');
    assert.equal(mapClassList('p-5 mb-4 px-10 gap-3', { convertRem: true }).out, 'p-4 mb-3 px-8 gap-2.5');
    assert.equal(mapClassList('md:p-5 -mt-3 m-n3', { convertRem: true }).out, 'md:p-4 -mt-2.5 -m-2.5');
    assert.equal(mapClassList('w-50px h-100px mw-300px').out, 'w-[50px] h-[100px] max-w-[300px]');
});

test('codemod swaps icons, drops the base class and keeps other classes', async () => {
    const { mapClassList } = await load('codemod.mjs');

    assert.equal(mapClassList('bi bi-trash me-2').out, 'ph ph-trash me-2');
    assert.equal(mapClassList('bi bi-three-dots-vertical').out, 'ph-bold ph-dots-three-vertical');
    assert.equal(mapClassList('fa fa-print').out, 'ph ph-printer');
    assert.equal(mapClassList('ki-duotone ki-cross fs-2').out, 'ph ph-x text-xl');
    assert.equal(mapClassList('bi bi-check-circle-fill').out, 'ph-fill ph-check-circle');
});

test('codemod fails loudly for an icon that has no Phosphor equivalent', async () => {
    const { mapClassList } = await load('codemod.mjs');
    const r = mapClassList('bi bi-totally-unknown');

    assert.deepEqual(r.missingIcons, ['bi-totally-unknown']);
    assert.ok(r.out.includes('bi-totally-unknown'));
    assert.ok(r.out.includes('bi '), 'ikon lama tidak boleh dibuang setengah jalan');
});

test('codemod leaves Blade expressions intact', async () => {
    const { mapClassList } = await load('codemod.mjs');
    const r = mapClassList("d-flex {{ $active ? 'fw-bold' : 'fw-normal' }} @if($x) p-5 @endif w-100");

    assert.ok(r.out.includes("{{ $active ? 'fw-bold' : 'fw-normal' }}"));
    assert.ok(r.out.startsWith('flex '));
    assert.ok(r.out.endsWith(' w-full'));
});

test('transformSource rewrites class attributes, strips duotone spans, leaves :class alone', async () => {
    const { transformSource } = await load('codemod.mjs');
    const src = '<i class="ki-duotone ki-cross"><span class="path1"></span><span class="path2"></span></i>\n<div class="d-flex" :class="open ? \'d-none\' : \'\'"></div>';
    const { out, report } = transformSource(src);

    assert.equal(out, '<i class="ph ph-x"></i>\n<div class="flex" :class="open ? \'d-none\' : \'\'"></div>');
    assert.equal(report.paths, 2);
});

test('conflict detector flags two utilities of the same group on one element', async () => {
    const { findConflicts } = await load('class-conflicts.mjs');
    const found = findConflicts('<div class="text-xs text-gray-700 font-bold text-gray-900"></div>');

    assert.equal(found.length, 1);
    assert.equal(found[0].kind, 'static');
    assert.equal(found[0].family, 'text-color');
    assert.deepEqual(found[0].tokens, ['text-gray-700', 'text-gray-900']);
});

test('conflict detector compares a static class against a dynamic :class literal', async () => {
    const { findConflicts } = await load('class-conflicts.mjs');
    const found = findConflicts(`<span class="font-medium" :class="on ? 'font-bold text-[#111827]' : 'text-[#374151]'"></span>`);

    assert.equal(found.length, 1);
    assert.equal(found[0].kind, 'dynamic');
    assert.equal(found[0].family, 'font-weight');
});

test('conflict detector does not compare the exclusive branches of a :class ternary', async () => {
    const { findConflicts } = await load('class-conflicts.mjs');

    assert.deepEqual(findConflicts(`<span class="text-xs" :class="on ? 'font-bold text-gray-900' : 'font-medium text-gray-700'"></span>`), []);
});

test('conflict detector ignores different variants and unrelated groups', async () => {
    const { findConflicts } = await load('class-conflicts.mjs');

    assert.deepEqual(findConflicts('<div class="p-2 md:p-4 text-sm text-gray-700 bg-white hover:bg-gray-50"></div>'), []);
});

test('conflict detector sees the five real collisions fixed in B0 would have been caught', async () => {
    const { findConflicts } = await load('class-conflicts.mjs');
    const cases = [
        `<div class="flex rounded-lg border bg-white" :class="[group.iconBg, 'bg-emerald-50']"></div>`,
        `<button class="rounded-[12px] font-medium" :class="a ? 'font-bold' : ''"></button>`,
        `<span class="text-xs text-gray-700" :class="s ? 'font-bold text-gray-900' : 'font-medium'"></span>`,
    ];

    assert.equal(findConflicts(cases[0]).length, 1);
    assert.equal(findConflicts(cases[1]).length, 1);
    assert.equal(findConflicts(cases[2]).length, 1);
});
