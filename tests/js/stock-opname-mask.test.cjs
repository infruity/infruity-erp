const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

// Runs the real maskPhysical() from the stock opname form against plain input objects.
const source = fs.readFileSync(path.join(__dirname, '../../Modules/Transaction/Resources/views/stock-opname/index.blade.php'), 'utf8');
const start = source.indexOf('function maskPhysical(input)');
const end = source.indexOf('function updateStockSummary');
assert.ok(start > -1 && end > start, 'maskPhysical must exist in the opname form');
const maskPhysical = new Function(`${source.slice(start, end)}; return maskPhysical;`)();

const masked = value => {
    const input = {value};
    maskPhysical(input);
    return input.value;
};

test('a pasted decimal with a dot keeps its decimals', () => {
    assert.equal(masked('78.33'), '78,33');
    assert.equal(masked('0.5'), '0,5');
    assert.equal(masked('12.5'), '12,5');
});

test('thousands grouping with dots stays a whole number', () => {
    assert.equal(masked('1.234'), '1.234');
    assert.equal(masked('12.345.678'), '12.345.678');
});

test('typing keeps working: trailing dot becomes the decimal comma', () => {
    assert.equal(masked('78.'), '78,');
    assert.equal(masked('78,33'), '78,33');
    assert.equal(masked('1000'), '1.000');
});
