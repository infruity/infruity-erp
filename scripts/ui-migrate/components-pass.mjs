#!/usr/bin/env node
/**
 * Lanjutan codemod.mjs untuk kelas komponen Bootstrap yang tidak punya padanan 1:1 di class-map
 * (form-control, form-select, form-label, btn*). Hanya menyentuh isi atribut class="...".
 *
 *   node scripts/ui-migrate/components-pass.mjs <file...>            # laporan
 *   node scripts/ui-migrate/components-pass.mjs --write <file...>    # tulis
 *
 * Jalankan SETELAH codemod.mjs. Memetakan: form-control* -> erp-input, form-select* -> erp-select,
 * form-label -> erp-label (+ erp-label--required bila ada `required`), btn-primary/success -> erp-action--primary,
 * btn-danger* -> erp-action--danger, btn lain -> erp-action; btn-sm/lg/icon/active-* dibuang.
 */
import { readFileSync, writeFileSync } from 'node:fs';

export function convertClassList(list) {
    const tokens = list.split(/\s+/).filter(Boolean);
    const out = [];
    const add = (t) => { if (!out.includes(t)) out.push(t); };
    const has = (re) => tokens.some((t) => re.test(t));
    const isRequired = tokens.includes('required') && has(/^form-label$/);
    let changed = false;

    for (const t of tokens) {
        if (/^form-control(-lg|-sm|-solid|-flush|-transparent)?$/.test(t)) { add('erp-input'); changed = true; continue; }
        if (/^form-select(-lg|-sm|-solid)?$/.test(t)) { add('erp-select'); changed = true; continue; }
        if (t === 'form-label') { add('erp-label'); if (isRequired) add('erp-label--required'); changed = true; continue; }
        if (t === 'required' && isRequired) { changed = true; continue; }
        if (t === 'btn') { add('erp-action'); changed = true; continue; }
        if (/^btn-(primary|success)$/.test(t)) { add('erp-action--primary'); changed = true; continue; }
        if (/^btn-(light-)?danger$/.test(t)) { add('erp-action--danger'); changed = true; continue; }
        if (/^btn-(sm|lg|icon|active-.*|light.*|secondary|outline-.*|info|warning|dark|link|bg-.*)$/.test(t)) { changed = true; continue; }
        add(t);
    }
    // btn-* tanpa kelas dasar "btn" (jarang) tidak dipetakan sendiri.
    return { value: out.join(' '), changed };
}

export function convert(source) {
    let count = 0;
    const next = source.replace(/class="([^"]*)"/g, (whole, list) => {
        if (!/(^|\s)(form-control|form-select|form-label|btn)(-|\s|$)/.test(list)) return whole;
        const { value, changed } = convertClassList(list);
        if (!changed) return whole;
        count += 1;
        return `class="${value}"`;
    });
    return { next, count };
}

if (process.argv[1] && process.argv[1].endsWith('components-pass.mjs')) {
    const args = process.argv.slice(2);
    const write = args.includes('--write');
    let total = 0;
    for (const file of args.filter((a) => !a.startsWith('--'))) {
        const src = readFileSync(file, 'utf8');
        const { next, count } = convert(src);
        total += count;
        if (count) console.log(`${file}: ${count} atribut class`);
        if (write && count) writeFileSync(file, next);
    }
    console.log(`${write ? 'Ditulis' : 'Laporan'}: ${total} atribut class`);
}
