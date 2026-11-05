#!/usr/bin/env node
/**
 * Codemod class dan ikon Bootstrap/Metronic -> Tailwind + Phosphor.
 * docs/tailwind-migration-plan.md §4. Hanya mengganti pasangan yang AMAN 1:1; sisanya
 * dilaporkan untuk dikerjakan manual (komponen, bentrok nama, ikon tanpa padanan).
 *
 *   node scripts/ui-migrate/codemod.mjs <file|folder>...             # laporan saja (default)
 *   node scripts/ui-migrate/codemod.mjs --write <file|folder>...     # tulis perubahan
 *   node scripts/ui-migrate/codemod.mjs --convert-rem --write <...>  # konversi spasi 13px -> 16px
 *   node scripts/ui-migrate/codemod.mjs --strict <...>               # exit 1 bila masih ada sisa
 *
 * --convert-rem: hanya untuk file yang extends template.root. Halaman itu dirender di
 * root 13px (Metronic), layout baru 16px; flag ini menjaga ukuran px tetap sama.
 */
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const classMap = JSON.parse(readFileSync(join(here, 'class-map.json'), 'utf8'));
const iconMap = JSON.parse(readFileSync(join(here, 'icon-map.json'), 'utf8'));

const BREAKPOINT = classMap.breakpoints;
const MANUAL = new Set(classMap.manualOnly);
const SPACING = classMap.spacingRem13to16;

/** Keluarga utility berbasis rem yang ikut bergeser saat root 13px -> 16px. */
const REM_FAMILIES = 'p|px|py|pt|pb|pl|pr|ps|pe|m|mx|my|mt|mb|ml|mr|ms|me|gap|gap-x|gap-y|space-x|space-y|top|right|bottom|left|inset|inset-x|inset-y|w|h|size|min-w|min-h';
const REM_RE = new RegExp(`^(-?)(${REM_FAMILIES})-(\\d+(?:\\.\\d+)?)$`);
const NEG_RE = /^(m[xytblrse]?)-n(\d+)$/;
const PX_RE = /^(w|h|min-w|min-h|mw|mh)-(\d+)px$/;
const PX_TARGET = { w: 'w', h: 'h', 'min-w': 'min-w', 'min-h': 'min-h', mw: 'max-w', mh: 'max-h' };

/** Token yang masih jelas bergaya Bootstrap/Metronic tetapi tidak ada di peta: perlu manual. */
const LEGACY_RE = /^(?:d-|fs-|fw-|col(?:-|$)|row$|btn(?:-|$)|badge(?:-|$)|form-|modal|card(?:-|$)|dropdown|nav(?:-|$)|accordion|symbol|menu-|aside|app-|bg-light|text-hover-|bg-hover-|text-active-|flex-center|align-(?:items|self|content)-|justify-content-|g[xy]?-\d|m[xytblrse]?-n\d|[wh]-\d+px$|mw-|mh-|rounded-[0-4]$|shadow(?:-sm|-lg)?$)/;

const ICON_BASES = new Set(['bi', 'fa', 'fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands', 'ki-duotone', 'ki-outline', 'ki-solid']);
const ICON_NAME_RE = /^(?:ki|bi|fa)-[a-z0-9-]+$/;

const PLACEHOLDER = (n) => `\u0000${n}\u0000`;

/** Sembunyikan ekspresi Blade supaya tidak ikut dipecah menjadi token. */
function mask(value) {
    const parts = [];
    const masked = value.replace(/\{\{[\s\S]*?\}\}|\{!![\s\S]*?!!\}|@\w+(?:\([^)]*\))?/g, (m) => {
        parts.push(m);
        return PLACEHOLDER(parts.length - 1);
    });
    return { masked, restore: (s) => s.replace(/\u0000(\d+)\u0000/g, (_, i) => parts[Number(i)]) };
}

function splitVariant(token) {
    const at = token.lastIndexOf(':');
    return at === -1 ? ['', token] : [token.slice(0, at + 1), token.slice(at + 1)];
}

function convertRem(base) {
    const neg = base.match(NEG_RE);
    if (neg) {
        const to = SPACING[neg[2]];
        return to === undefined ? null : `-${neg[1]}-${to}`;
    }
    const m = base.match(REM_RE);
    if (!m) return null;
    const to = SPACING[m[3]];
    return to === undefined ? null : `${m[1]}${m[2]}-${to}`;
}

/** Terjemahkan satu token (tanpa variant). Mengembalikan string, atau null bila tidak ada padanan. */
function mapBase(base, opts) {
    if (MANUAL.has(base)) return { manual: true };

    if (classMap.exact[base] !== undefined) return { to: classMap.exact[base] };
    if (classMap.fontSizeMetronic[base] !== undefined) return { to: classMap.fontSizeMetronic[base] };

    const px = base.match(PX_RE);
    if (px) return { to: `${PX_TARGET[px[1]]}-[${px[2]}px]` };

    if (opts.convertRem) {
        const to = convertRem(base);
        if (to !== null && to !== base) return { to };
    }

    // Responsif Bootstrap: d-md-none, text-lg-center, flex-xl-column ...
    const resp = base.match(/^(.+?)-(sm|md|lg|xl|xxl)-(.+)$/);
    if (resp) {
        const inner = mapBase(`${resp[1]}-${resp[3]}`, opts);
        if (inner && inner.to) {
            return { to: inner.to.split(/\s+/).map((c) => `${BREAKPOINT[resp[2]]}:${c}`).join(' ') };
        }
    }
    return null;
}

/**
 * Proses isi atribut class.
 * @returns {{ out: string, changed: Array<[string,string]>, manual: string[], unknown: string[], icons: string[], missingIcons: string[] }}
 */
export function mapClassList(value, opts = {}) {
    const { masked, restore } = mask(value);
    const tokens = masked.split(/(\s+)/);
    const changed = [];
    const manual = [];
    const unknown = [];
    const icons = [];
    const missingIcons = [];

    // Ikon: kumpulkan nama ikon lama dan basisnya dalam satu atribut.
    const names = tokens.filter((t) => ICON_NAME_RE.test(t) && !ICON_BASES.has(t) && !classMap.exact[t]);
    const hasIconBase = tokens.some((t) => ICON_BASES.has(t));
    const iconTargets = new Map();
    if (names.length && (hasIconBase || names.some((n) => n.startsWith('ki-')))) {
        for (const name of names) {
            const to = iconMap.map[name];
            if (to) iconTargets.set(name, to);
            else missingIcons.push(name);
        }
    }

    // Atribut dengan ikon tanpa padanan dibiarkan utuh (jangan setengah terkonversi).
    if (missingIcons.length) iconTargets.clear();

    const out = [];
    const emitted = new Set();
    for (const token of tokens) {
        if (/^\s+$/.test(token) || token === '') { out.push(token); continue; }
        if (token.includes('\u0000')) { out.push(token); continue; }

        if (ICON_BASES.has(token) && iconTargets.size) {
            changed.push([token, '(basis ikon dihapus)']);
            continue; // basis digantikan oleh kelas Phosphor
        }
        if (iconTargets.has(token)) {
            const to = iconTargets.get(token);
            icons.push(token);
            changed.push([token, to]);
            if (!emitted.has(to)) { out.push(to); emitted.add(to); }
            continue;
        }
        if (missingIcons.includes(token)) { out.push(token); continue; }

        const [variant, base] = splitVariant(token);
        const result = mapBase(base, opts);
        if (result && result.manual) { manual.push(token); out.push(token); continue; }
        if (result && result.to) {
            const to = variant ? result.to.split(/\s+/).map((c) => `${variant}${c}`).join(' ') : result.to;
            changed.push([token, to]);
            out.push(to);
            continue;
        }
        if (LEGACY_RE.test(base)) unknown.push(token);
        out.push(token);
    }

    // Rapikan spasi ganda akibat token yang dihapus.
    const joined = out.join('').replace(/[ \t]{2,}/g, ' ').replace(/^ | $/g, (m) => (m === ' ' ? '' : m));
    return { out: restore(joined), changed, manual, unknown, icons, missingIcons };
}

/** Buang <span class="path1"></span> bawaan ikon duotone Keenicons. */
export function stripDuotonePaths(source) {
    return source.replace(/<span class="path\d+"><\/span>/g, '');
}

/** Terapkan ke seluruh atribut class="..." / class='...' di sebuah berkas. Atribut :class dibiarkan. */
export function transformSource(source, opts = {}) {
    const report = { changed: [], manual: [], unknown: [], missingIcons: [], paths: 0 };
    const hadPaths = (source.match(/<span class="path\d+"><\/span>/g) || []).length;
    const out = stripDuotonePaths(source).replace(/(?<![:\w-])class\s*=\s*(["'])([\s\S]*?)\1/g, (full, quote, value) => {
        const r = mapClassList(value, opts);
        report.changed.push(...r.changed);
        report.manual.push(...r.manual);
        report.unknown.push(...r.unknown);
        report.missingIcons.push(...r.missingIcons);
        return `class=${quote}${r.out}${quote}`;
    });
    report.paths = hadPaths;
    return { out, report };
}

function* walk(target) {
    const stat = statSync(target);
    if (stat.isFile()) { yield target; return; }
    for (const entry of readdirSync(target)) {
        if (['node_modules', 'vendor', '.git'].includes(entry)) continue;
        yield* walk(join(target, entry));
    }
}

function main(argv) {
    const flags = new Set(argv.filter((a) => a.startsWith('--')));
    const paths = argv.filter((a) => !a.startsWith('--'));
    if (!paths.length) {
        console.error('Pemakaian: codemod.mjs [--write] [--convert-rem] [--strict] <file|folder>...');
        process.exit(2);
    }
    const opts = { convertRem: flags.has('--convert-rem') };
    let leftovers = 0;
    let files = 0;
    const missing = new Set();

    for (const p of paths) {
        for (const file of walk(resolve(p))) {
            if (!/\.(php|js|html)$/.test(file)) continue;
            const source = readFileSync(file, 'utf8');
            const { out, report } = transformSource(source, opts);
            const rest = report.manual.length + report.unknown.length + report.missingIcons.length;
            if (!report.changed.length && !rest && !report.paths) continue;
            files += 1;
            leftovers += rest;
            report.missingIcons.forEach((n) => missing.add(n));
            console.log(`${file}\n  diganti: ${report.changed.length}${report.paths ? `, span ikon dibuang: ${report.paths}` : ''}`);
            if (report.manual.length) console.log(`  MANUAL (bentrok nama/komponen): ${[...new Set(report.manual)].join(' ')}`);
            if (report.unknown.length) console.log(`  BELUM ADA PETA: ${[...new Set(report.unknown)].join(' ')}`);
            if (report.missingIcons.length) console.log(`  IKON TANPA PADANAN: ${[...new Set(report.missingIcons)].join(' ')}`);
            if (flags.has('--write') && out !== source) writeFileSync(file, out);
        }
    }
    console.log(`\n${files} file${flags.has('--write') ? ' diproses' : ' terdampak (laporan saja; tambahkan --write untuk menulis)'}; sisa manual: ${leftovers}`);
    if (missing.size) console.log(`Tambahkan ke icon-map.json: ${[...missing].join(', ')}`);
    if (flags.has('--strict') && leftovers) process.exit(1);
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) main(process.argv.slice(2));
