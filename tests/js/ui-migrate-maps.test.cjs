const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..', '..');
const iconMap = JSON.parse(fs.readFileSync(path.join(root, 'scripts/ui-migrate/icon-map.json'), 'utf8'));
const classMap = JSON.parse(fs.readFileSync(path.join(root, 'scripts/ui-migrate/class-map.json'), 'utf8'));

const phosphorCss = ['regular', 'bold', 'fill', 'duotone']
    .map((w) => fs.readFileSync(path.join(root, `public/assets/plugins/phosphor/${w}/style.css`), 'utf8'))
    .join('\n');

test('every target icon in icon-map exists in the Phosphor font', () => {
    const missing = [];
    for (const [from, to] of Object.entries(iconMap.map)) {
        const name = to.split(/\s+/).find((c) => c.startsWith('ph-') && !['ph-bold', 'ph-fill', 'ph-duotone'].includes(c));
        if (!name || !phosphorCss.includes(`.${name}:before`)) missing.push(`${from} -> ${to}`);
    }
    assert.deepEqual(missing, []);
});

test('icon-map sources are legacy icon classes only', () => {
    for (const from of Object.keys(iconMap.map)) {
        assert.match(from, /^(ki|bi|fa)-[a-z0-9-]+$/, from);
    }
});

test('class-map never maps a manual-only token', () => {
    const manual = new Set(classMap.manualOnly);
    const bad = Object.keys(classMap.exact).filter((k) => manual.has(k));
    assert.deepEqual(bad, []);
});

test('class-map targets are not themselves legacy tokens', () => {
    const legacy = /(^|\s)(d-|fw-|fs-|flex-column|justify-content-|align-items-|text-muted|w-100|h-100)/;
    const bad = Object.entries(classMap.exact).filter(([, v]) => legacy.test(v));
    assert.deepEqual(bad, []);
});

test('spacing conversion only yields valid Tailwind spacing steps', () => {
    const scale = new Set(['0', '0.5', '1', '1.5', '2', '2.5', '3', '3.5', '4', '5', '6', '7', '8', '9', '10', '11', '12', '14', '16', '20', '24']);
    const table = classMap.spacingRem13to16;
    const bad = Object.entries(table).filter(([k, v]) => k !== '_doc' && !scale.has(v));
    assert.deepEqual(bad, []);
    // px asli (N * 3.25) harus paling dekat dengan hasilnya
    for (const [k, v] of Object.entries(table)) {
        if (k === '_doc') continue;
        const target = Number(k) * 3.25;
        const best = [...scale].map(Number).sort((a, b) => Math.abs(a * 4 - target) - Math.abs(b * 4 - target) || a - b)[0];
        assert.equal(Number(v), best, `N=${k}`);
    }
});
