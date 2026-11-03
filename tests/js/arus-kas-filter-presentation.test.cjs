const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const cssPath = path.join(__dirname, '../../public/assets/css/arus-kas-filter.css');

test('mobile cash-flow filter has reference-sized custom radios, not native black rings', () => {
    assert.equal(fs.existsSync(cssPath), true);
    const css = fs.readFileSync(cssPath, 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
    assert.match(css, /@media\s*\(max-width:\s*639\.98px\)/);
    assert.match(css, /appearance:\s*none/);
    assert.match(css, /border:\s*2px solid #d1d5db/);
    assert.match(css, /radial-gradient\(circle,\s*#10b981 0 4px,\s*transparent 4px\)/);
    assert.match(css, /:focus-visible/);
    assert.match(css, /outline:\s*2px solid #10b981/);
    for (const selector of css.matchAll(/([^{}]+)\{/g)) {
        const value = selector[1].trim();
        if (!value.startsWith('@')) assert.ok(value.startsWith('.arus-kas-filter-sheet'), value);
    }
    assert.match(css, /max-height:\s*144px/);
    assert.match(css, /scrollbar-width:\s*none/);
    assert.match(css, /padding:\s*16px/);
    assert.ok(!/height:\s*315px/.test(css), 'reference screenshot height must not become a forced fixed body height');
});
