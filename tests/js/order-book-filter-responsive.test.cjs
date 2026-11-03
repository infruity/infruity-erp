const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '../..');
const cssPath = path.join(root, 'public/assets/css/order-book-filter-responsive.css');
const css = () => { assert.ok(fs.existsSync(cssPath), 'Responsive filter stylesheet must exist'); return fs.readFileSync(cssPath, 'utf8'); };

test('Order Book loads the scoped responsive filter stylesheet with cache busting', () => {
    const view = fs.readFileSync(path.join(root, 'Modules/Pos/Resources/views/order-book/index.blade.php'), 'utf8');
    assert.ok(view.includes("asset('assets/css/order-book-filter-responsive.css')"));
    assert.ok(view.includes("filemtime(public_path('assets/css/order-book-filter-responsive.css'))"));
});

test('filter width and padding adapt rather than using the same desktop cap at every width', () => {
    const source = css();
    assert.match(source, /@media\s*\(min-width:\s*640px\)/);
    assert.match(source, /max-width:\s*clamp\(384px,\s*50vw,\s*512px\)/);
    assert.match(source, /clamp\(12px,\s*4vw,\s*20px\)/);
    assert.match(source, /@media\s*\(min-width:\s*480px\)/);
});

test('short viewports scroll only the filter body and retain the header and actions', () => {
    const source = css();
    assert.match(source, /100dvh/);
    assert.match(source, /safe-area-inset-top/);
    assert.match(source, /min-height:\s*0/);
    assert.match(source, /overflow-y:\s*auto/);
    assert.match(source, /overflow-x:\s*hidden/);
    assert.match(source, /flex-shrink:\s*0/);
    assert.match(source, /@media\s*\(max-height:\s*600px\)/);
});

test('responsive rules are confined to the Order Book filter, not global font or detail dialog', () => {
    const source = css();
    const selectors = [...source.matchAll(/([^{}]+)\{/g)].map(match => match[1].replace(/\/\*[\s\S]*?\*\//g, '').trim());
    assert.ok(selectors.every(selector => selector.startsWith('#order-book-filter-sheet') || selector.startsWith('@media')), 'Every style rule must be local to the filter');
    assert.ok(!source.includes('order-book-detail'));
    assert.ok(!source.includes('font-size:'));
});
