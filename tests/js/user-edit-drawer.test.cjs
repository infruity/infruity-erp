const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const viewPath = path.join(__dirname, '../../resources/views/admin/user/index.blade.php');
const view = fs.readFileSync(viewPath, 'utf8');
const drawerStart = view.indexOf('x-show="drawerOpen" x-cloak data-erp-modal');
const wrapperEnd = view.indexOf('>', drawerStart) + 1;
const wrapper = view.slice(drawerStart, wrapperEnd);

test('user edit overlay fades while the drawer slides', () => {
    assert.ok(drawerStart >= 0, 'the edit drawer is rendered in the user page');
    assert.match(wrapper, /x-transition:enter="transition-opacity ease-out duration-300"/);
    assert.match(wrapper, /x-transition:enter-start="opacity-0"/);
    assert.match(wrapper, /x-transition:enter-end="opacity-100"/);
    assert.match(wrapper, /x-transition:leave="transition-opacity ease-in duration-300"/);
    assert.match(wrapper, /x-transition:leave-start="opacity-100"/);
    assert.match(wrapper, /x-transition:leave-end="opacity-0"/);
});
