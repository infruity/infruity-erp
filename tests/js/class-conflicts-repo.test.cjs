const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { pathToFileURL } = require('node:url');

const root = path.resolve(__dirname, '../..');

function* files(dir) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        if (entry.name === 'template - Copy') continue;
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) yield* files(full);
        else if (/\.blade\.php$/.test(entry.name)) yield full;
    }
}

// Dua utility dari kelompok yang sama di satu elemen dimenangkan oleh urutan CSS, bukan urutan
// atribut, dan urutan itu berbeda antara CDN dan build compiled (docs/tailwind-migration-plan.md R18).
test('no view puts two utilities of the same group on one element', async () => {
    const { findConflicts } = await import(pathToFileURL(path.join(root, 'scripts/ui-migrate/class-conflicts.mjs')).href);
    const dirs = [path.join(root, 'resources/views')];
    for (const module of fs.readdirSync(path.join(root, 'Modules'))) {
        const views = path.join(root, 'Modules', module, 'Resources/views');
        if (fs.existsSync(views)) dirs.push(views);
    }

    const problems = [];
    for (const dir of dirs) {
        for (const file of files(dir)) {
            for (const c of findConflicts(fs.readFileSync(file, 'utf8'))) {
                problems.push(`${path.relative(root, file)}:${c.line} [${c.kind}] ${c.family}: ${c.tokens.join(' vs ')}`);
            }
        }
    }

    assert.deepEqual(problems, [], `Class bertabrakan. Pindahkan salah satunya ke cabang :class yang eksklusif.\n${problems.join('\n')}`);
});
