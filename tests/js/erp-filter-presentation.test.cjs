const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const asset = path.join(__dirname, '../../public/assets/css/erp-filters.css');

test('all filter families and nested report filters use the shared scale', () => {
    const css = fs.readFileSync(asset, 'utf8');
    for (const hook of [
        '#sortir-filter-dialog', '#transfer-filter-dialog', '#stock-filters', '#stock-detail-filters',
        '.report-mobile-sheet--filter', '.report-mobile-history-filter-popover',
        '#mobile-user-role-filter', '#receipt-category-mobile', '#receipt-category-desktop-button',
        'Filter pengadaan', '#wholesale-mobile-branch-search', 'periodSearchQuery',
        'cabangFilter', '.erp-operations-filter', '.erp-operations-inline-select', '.nota-branch-picker',
        'data-kt-menu', '#kt_ecommerce_sales_flatpickr',
    ]) assert.ok(css.includes(hook), `Missing visual family hook: ${hook}`);
});

test('mobile operations filters anchor to the full toolbar without changing desktop or hidden controls', () => {
    const css = fs.readFileSync(asset, 'utf8');
    assert.match(css, /@media\s*\(max-width:\s*767\.98px\)/);
    assert.match(css, /\.erp-workspace-toolbar:has\([^{}]+\)\s*\{\s*position:\s*relative/);
    assert.match(css, /details\.erp-operations-filter:has\([^{}]+\)\s*\{\s*position:\s*static/);
    assert.match(css, /\.erp-operations-filter__body\s*\{\s*right:\s*20px/);
    assert.doesNotMatch(css, /display:\s*[^;{}]+!important/, 'Do not unhide backing inputs or responsive variants');
});

test('body normalization does not leak a fixed line-height into calendars or secondary captions', () => {
    const css = fs.readFileSync(asset, 'utf8');
    const body = css.match(/\/\* Sheet bodies \*\/([\s\S]*?)\{([^}]+)\}/);
    assert.ok(body, 'Missing scoped sheet body rule');
    assert.doesNotMatch(body[2], /line-height:/, 'Calendar/secondary type must not inherit a new fixed line-height');
});

test('footer spacing keeps safe-area ownership in normal and compact viewports', () => {
    const css = fs.readFileSync(asset, 'utf8');
    assert.match(css, /--erp-filter-footer-inset:\s*env\(safe-area-inset-bottom,\s*0px\)/);
    assert.match(css, /padding:\s*var\(--erp-filter-footer-padding\)\s+var\(--erp-filter-footer-gutter\)\s+calc\(var\(--erp-filter-footer-padding\)\s*\+\s*var\(--erp-filter-footer-inset,\s*0px\)\)/);
    assert.match(css, /--erp-filter-footer-padding:\s*12px/);
});

test('filter presentation uses explicit shared role sizes without changing the application root font', () => {
    assert.ok(fs.existsSync(asset), 'Shared scoped filter presentation is missing');
    const css = fs.readFileSync(asset, 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
    for (const [name, value] of Object.entries({
        'title-size': '16px', 'label-size': '10px', 'body-size': '16px',
        'search-size': '16px', 'control-size': '16px', 'option-size': '12px',
        'date-size': '12px', 'action-size': '12px', 'action-height': '44px',
    })) {
        assert.match(css, new RegExp(`--erp-filter-${name}:\\s*${value}`));
    }
    for (const selector of ['.arus-kas-filter-sheet', '#order-book-filter-sheet', '#delivery-order-mobile-filter-sheet']) {
        assert.ok(css.includes(selector), `Missing core family ${selector}`);
    }
    assert.equal((css.match(/font-size:var\(--erp-filter-option-size\)/g) || []).length, 4);
    assert.match(css, /font-size:var\(--erp-filter-date-size\) !important/);
    assert.match(css, /@media\s*\(min-width:\s*640px\)[\s\S]*?--erp-filter-action-size:14px/);
    assert.doesNotMatch(css, /(?:^|})\s*(?:html|:root|body)\s*\{/m, 'Do not compensate by changing the global root');
    assert.doesNotMatch(css, /(?:^|})\s*(?:input|button|select|h2|h3)\s*\{/m, 'Do not leak presentation into unrelated forms');
    assert.doesNotMatch(css, /(?:^|[;{])\s*(?:height|min-height):\s*(?:88|96|100)(?:d?vh)/, 'No forced full-height filter sheets');
});
