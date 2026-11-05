#!/usr/bin/env node
/**
 * Detektor class yang bertabrakan di satu elemen (docs/tailwind-migration-plan.md R18).
 *
 * Dua utility dari kelompok yang sama (mis. `bg-white` + `bg-emerald-50`) dimenangkan oleh
 * URUTAN di file CSS, bukan urutan di atribut. Urutan itu berbeda antara CDN dan build
 * compiled, sehingga tampilan berubah diam-diam. Aturan: jangan taruh dua utility dari
 * kelompok yang sama di satu elemen; pindahkan salah satunya ke cabang kondisi eksklusif.
 *
 *   node scripts/ui-migrate/class-conflicts.mjs [--strict] <file|folder>...
 *
 * Yang diperiksa: atribut class statis, dan class statis melawan literal di :class (Alpine).
 * Cabang `:class` yang saling eksklusif (true/false) tidak dibandingkan satu sama lain.
 */
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const COL = '(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|primary|accent|cream|strawberry|canvas|white|black|transparent|current|inherit)';

/** Kelompok properti sebuah token, atau null bila bukan kelompok yang dilacak. Variant (md:, hover:) ikut kunci. */
export function family(token) {
    const at = token.lastIndexOf(':');
    const variant = at === -1 ? '' : token.slice(0, at + 1);
    const base = (at === -1 ? token : token.slice(at + 1)).replace(/^!/, '');
    // Utility responsif ala Bootstrap (w-lg-50, d-md-none) bukan Tailwind; bukan tabrakan dengan w-100.
    if (/-(?:sm|md|lg|xl|xxl)-/.test(base)) return null;
    const rules = [
        ['text-size', new RegExp(`^text-(?:xs|sm|base|lg|[2-9]?xl|body|\\[\\d[\\d.]*(?:px|rem|em)\\])$`)],
        ['text-color', new RegExp(`^text-${COL}(?:-\\d+|-light|-dark|-hover)?(?:/\\d+)?$|^text-\\[#[0-9a-fA-F]+\\]$`)],
        ['font-weight', /^font-(?:thin|extralight|light|normal|medium|semibold|bold|extrabold|black)$/],
        ['bg-color', new RegExp(`^bg-${COL}(?:-\\d+|-light|-dark)?(?:/\\d+)?$|^bg-\\[#[0-9a-fA-F]+\\]$`)],
        ['border-color', new RegExp(`^border-${COL}(?:-\\d+)?(?:/\\d+)?$|^border-\\[#[0-9a-fA-F]+\\]$`)],
        ['rounded', /^rounded(?:-(?:none|sm|md|lg|xl|2xl|3xl|full|control|panel|\[[^\]]+\]))?$/],
        ['display', /^(?:flex|block|hidden|inline-flex|inline-block|grid|inline|table)$/],
        ['p', /^p-(?:\d|\[)/], ['px', /^px-/], ['py', /^py-/],
        ['w', /^w-/], ['h', /^h-/], ['leading', /^leading-/], ['shadow', /^shadow(?:-(?:sm|md|lg|xl|2xl|inner|none|soft|card|glass|input|sidebar|2xs|xs|\[[^\]]+\]))?$/],
    ];
    for (const [name, re] of rules) if (re.test(base)) return variant + name;
    return null;
}

function conflicts(tokensA, tokensB = null) {
    const groups = new Map();
    const add = (t, side) => {
        const f = family(t);
        if (!f) return;
        if (!groups.has(f)) groups.set(f, { a: new Set(), b: new Set() });
        groups.get(f)[side].add(t);
    };
    tokensA.forEach((t) => add(t, 'a'));
    (tokensB || []).forEach((t) => add(t, 'b'));
    const out = [];
    for (const [fam, { a, b }] of groups) {
        if (tokensB === null ? a.size > 1 : (a.size && b.size && [...b].some((t) => !a.has(t)))) {
            out.push({ family: fam, tokens: tokensB === null ? [...a] : [...a, ...b] });
        }
    }
    return out;
}

const stripBlade = (s) => s.replace(/\{\{[\s\S]*?\}\}|\{!![\s\S]*?!!\}|@\w+(?:\([^)]*\))?/g, ' ').replace(/\$\{[^}]*\}/g, ' ');
const words = (s) => stripBlade(s).split(/\s+/).filter(Boolean);

/** @returns {Array<{line:number, kind:'static'|'dynamic', family:string, tokens:string[]}>} */
export function findConflicts(source) {
    const found = [];
    const tagRe = /<[a-zA-Z][\w:-]*\b((?:[^>"']|"[^"]*"|'[^']*')*)>/g;
    for (const tag of source.matchAll(tagRe)) {
        const attrs = tag[1];
        const staticMatch = attrs.match(/(?<![:\w-])class\s*=\s*(?:"([^"]*)"|'([^']*)')/);
        const dynMatch = attrs.match(/(?:^|\s)(?::class|x-bind:class)\s*=\s*(?:"([^"]*)"|'([^']*)')/);
        const staticTokens = staticMatch ? words(staticMatch[1] ?? staticMatch[2] ?? '') : [];
        const line = source.slice(0, tag.index).split('\n').length;

        for (const c of conflicts(staticTokens)) found.push({ line, kind: 'static', ...c });

        if (dynMatch && staticTokens.length) {
            const expr = dynMatch[1] ?? dynMatch[2] ?? '';
            for (const lit of expr.matchAll(/'([^']*)'|`([^`]*)`/g)) {
                for (const c of conflicts(staticTokens, words(lit[1] ?? lit[2] ?? ''))) {
                    found.push({ line, kind: 'dynamic', ...c });
                }
            }
        }
    }
    return found;
}

function* walk(target) {
    const stat = statSync(target);
    if (stat.isFile()) { yield target; return; }
    for (const entry of readdirSync(target)) {
        if (['node_modules', 'vendor', '.git', 'template - Copy'].includes(entry)) continue;
        yield* walk(join(target, entry));
    }
}

function main(argv) {
    const strict = argv.includes('--strict');
    const paths = argv.filter((a) => !a.startsWith('--'));
    if (!paths.length) { console.error('Pemakaian: class-conflicts.mjs [--strict] <file|folder>...'); process.exit(2); }
    let total = 0;
    for (const p of paths) {
        for (const file of walk(resolve(p))) {
            if (!/\.(php|js|html)$/.test(file) || /\.bundle\./.test(file)) continue;
            for (const c of findConflicts(readFileSync(file, 'utf8'))) {
                total += 1;
                console.log(`${file}:${c.line}  [${c.kind}] ${c.family}: ${c.tokens.join(' vs ')}`);
            }
        }
    }
    console.log(`\n${total} tabrakan`);
    if (strict && total) process.exit(1);
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) main(process.argv.slice(2));
