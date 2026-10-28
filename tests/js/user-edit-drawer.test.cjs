const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const viewPath = path.join(__dirname, '../../resources/views/admin/user/index.blade.php');
const controllerPath = path.join(__dirname, '../../app/Http/Controllers/UserController.php');
const view = fs.readFileSync(viewPath, 'utf8');
const controller = fs.readFileSync(controllerPath, 'utf8');
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

test('user detail opens as a role-backed bottom sheet with interactive access switches', () => {
    assert.ok(view.includes("'permissions' => $user->roleAssignments->flatMap"));
    assert.match(view, /'permission_overrides' =>/);
    assert.match(view, /x-for="group in filteredAccessGroups"/);
    assert.match(view, /\['key' => 'kurir', 'label' => 'Kurir', 'permissions' => \['kurir\.index'\]\]/);
    assert.match(view, /@click\.stop="toggleGroupAccess\(selected, group\)"/);
    assert.match(view, /@click\.stop="toggleModuleAccess\(selected, module\)"/);
    assert.doesNotMatch(view, /role="switch" aria-disabled="true"/);
    assert.match(view, /items-end[^\"]*sm:items-center/);
    assert.match(view, /rounded-t-3xl[^\"]*sm:rounded-2xl/);
});

test('permission switch tracks use green only for the active state', () => {
    const switches = [...view.matchAll(/<button type="button" role="switch"[^>]*>/g)].map(match => match[0]);
    assert.equal(switches.length, 2, 'group and module switches are both rendered');

    for (const toggle of switches) {
        const classes = toggle.match(/class="([^"]+)"/)?.[1].split(/\s+/) || [];
        assert.equal(classes.includes('bg-gray-200'), false, 'a static gray class must not override the active color');
        const colorBinding = toggle.match(/:class="([^"]*bg-emerald-500[^"]*)"/)?.[1] || '';
        assert.match(colorBinding, /\? 'bg-emerald-500' : 'bg-gray-200'/);
    }
});

test('access switch disabled bindings return booleans when saving state is unset', () => {
    const bindings = [...view.matchAll(/role="switch"[^>]*:disabled="([^"]+)"/g)].map((match) => match[1]);
    assert.equal(bindings.length, 2, 'group and module switches both define a disabled state');

    for (const binding of bindings) {
        const isDisabled = new Function('selected', `return ${binding}`);
        assert.equal(isDisabled({can_change_permissions: true}), false);
        assert.equal(isDisabled({can_change_permissions: true, permission_saving: true}), true);
        assert.equal(isDisabled({can_change_permissions: false}), true);
    }
});

test('detail access summary applies account overrides over assigned role grants', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    assert.ok(start >= 0 && end > start, 'userMaster is present in the user page');
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const groups = [{label: 'Master', modules: [{key: 'products', label: 'Produk', permissions: ['products.index', 'products.create']}]}];
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], groups);
    const module = app.accessGroups?.[0]?.modules?.[0];
    assert.ok(module, 'access groups are available to the user screen');
    assert.equal(app.hasModuleAccess({roles: [{id: 2}], permissions: ['products.index'], permission_overrides: {}}, module), true);
    assert.equal(app.hasModuleAccess({roles: [{id: 2}], permissions: ['products.index'], permission_overrides: {'products.index': false}}, module), false);
    assert.equal(app.hasModuleAccess({roles: [{id: 2}], permissions: [], permission_overrides: {'products.create': true}}, module), true);
    assert.equal(app.hasModuleAccess({roles: [{id: 1}], permissions: [], permission_overrides: {}}, module), true);
    assert.equal(app.hasModuleAccess({roles: [{id: 1}], permissions: [], permission_overrides: {'products.index': false, 'products.create': false}}, module), false);
});

test('module and group switches persist user-specific access changes', async () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const calls = [];
    const context = {
        document: {querySelector: () => ({content: 'csrf-token'})},
        fetch: async (url, options) => {
            calls.push({url, options});
            return {ok: true, json: async () => ({success: true, overrides: JSON.parse(options.body).overrides})};
        },
    };
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const productModule = {key: 'products', label: 'Produk', permissions: ['products.index', 'products.create']};
    const stockModule = {key: 'stock-out', label: 'Stok Keluar', permissions: ['stock-out.index']};
    const group = {label: 'Master', modules: [productModule, stockModule]};
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], [group]);
    const user = {id: 44, roles: [{id: 2}], permissions: ['products.index'], permission_overrides: {}, can_change_permissions: true};

    await app.toggleModuleAccess(user, productModule);
    assert.equal(app.hasModuleAccess(user, productModule), false);
    assert.equal(calls[0].url, '/user/44/permissions');
    assert.equal(calls[0].options.method, 'PUT');
    assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.deepEqual(JSON.parse(calls[0].options.body).overrides, {'products.index': false, 'products.create': false});

    await app.toggleGroupAccess(user, group);
    assert.equal(app.hasFullGroupAccess(user, group), true);
    assert.deepEqual(JSON.parse(calls[1].options.body).overrides, {'products.index': true, 'products.create': true, 'stock-out.index': true});

    await app.toggleGroupAccess(user, group);
    assert.equal(app.hasAnyGroupAccess(user, group), false);
    assert.deepEqual(JSON.parse(calls[2].options.body).overrides, {'products.index': false, 'products.create': false, 'stock-out.index': false});
});

test('a partially granted group switch clears all module permissions when turned off', async () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const calls = [];
    const context = {
        document: {querySelector: () => ({content: 'csrf-token'})},
        fetch: async (url, options) => {
            calls.push({url, options});
            return {ok: true, json: async () => ({success: true, overrides: JSON.parse(options.body).overrides})};
        },
    };
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const productModule = {key: 'products', label: 'Produk', permissions: ['products.index', 'products.create']};
    const stockModule = {key: 'stock-out', label: 'Stok Keluar', permissions: ['stock-out.index']};
    const group = {label: 'Master', modules: [productModule, stockModule]};
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], [group]);
    const user = {id: 45, roles: [{id: 2}], permissions: ['products.index'], permission_overrides: {}, can_change_permissions: true};

    assert.equal(app.hasAnyGroupAccess(user, group), true);
    assert.equal(app.hasFullGroupAccess(user, group), false);
    await app.toggleGroupAccess(user, group);
    assert.equal(app.hasAnyGroupAccess(user, group), false);
    assert.deepEqual(JSON.parse(calls[0].options.body).overrides, {'products.index': false, 'products.create': false, 'stock-out.index': false});
});

test('failed access saves revert switch state and self accounts cannot issue updates', async () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const calls = [];
    const alerts = [];
    const context = {
        document: {querySelector: () => ({content: 'csrf-token'})},
        fetch: async (...args) => { calls.push(args); return {ok: false, json: async () => ({message: 'Denied'})}; },
        Swal: {fire: async (...args) => { alerts.push(args); }},
    };
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const module = {key: 'products', label: 'Produk', permissions: ['products.index']};
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], []);
    const user = {id: 44, permissions: ['products.index'], permission_overrides: {}, can_change_permissions: true};

    await app.toggleModuleAccess(user, module);
    assert.equal(app.hasModuleAccess(user, module), true);
    assert.equal(user.permission_saving, false);
    assert.equal(alerts.length, 1);

    const self = {...user, id: 45, can_change_permissions: false};
    await app.toggleModuleAccess(self, module);
    assert.equal(calls.length, 1);
});

test('closing the create sheet removes its transient URL flag without losing other query state', () => {
    assert.match(view, /@keydown\.escape\.window="closeDrawer\(\)"/);
    assert.equal((view.match(/@click="closeDrawer\(\)"/g) || []).length, 3);

    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const historyCalls = [];
    const context = {
        URL,
        window: {
            location: {href: 'http://127.0.0.1:8000/user?create=1&role_id=2#list'},
            history: {state: {scroll: 40}, replaceState(...args) { historyCalls.push(args); }},
        },
    };
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], [], 'OPR001@INFRUITY.COM', null);
    app.drawerOpen = true;
    app.closeDrawer();

    assert.equal(app.drawerOpen, false);
    assert.equal(historyCalls.length, 1);
    assert.equal(historyCalls[0][0].scroll, 40);
    assert.equal(historyCalls[0][2], '/user?role_id=2#list');
});

test('create user email mode defaults to the next OPR address and can switch to custom', () => {
    assert.match(view, /Bawaan/);
    assert.match(view, /Kustom/);
    assert.match(view, /name="email" x-model="form.email"/);
    assert.match(view, /name="email_type"/);
    assert.doesNotMatch(view, /<div x-show="!editing"[^>]*role="radiogroup"/);
    assert.ok(view.includes('role="radiogroup" aria-label="Jenis email" :aria-disabled="Boolean(editing)"'));
    assert.match(view, /:readonly="editing \|\| form\.emailType === 'auto'"/);
    assert.match(view, /name="password"/);
    assert.doesNotMatch(view, /name="full_name"/);
    assert.doesNotMatch(view, /name="role_ids/);
    assert.ok(!view.includes('name="id_branch[]"'));
    assert.match(view, /@js\(\$nextAutoEmail\)/);
    assert.match(controller, /\$data\['nextAutoEmail'\] = sprintf\('OPR%03d@INFRUITY\.COM'/);

    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const users = Array.from({length: 10}, (_, index) => ({id: index + 1, email: `OPR${String(index + 1).padStart(3, '0')}@INFRUITY.COM`}));
    const app = context.userMaster(users, '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null);

    app.openCreate();
    assert.equal(app.form.emailType, 'auto');
    assert.equal(app.form.email, 'OPR011@INFRUITY.COM');
    app.setEmailType('custom');
    assert.equal(app.form.email, '');
    app.form.email = 'person@example.com';
    app.setEmailType('auto');
    assert.equal(app.form.email, 'OPR011@INFRUITY.COM');

    const editing = context.userMaster([{id: 8, name: 'Existing User', email: 'existing@example.com', roles: [{id: 2}], branches: [1]}], '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null);
    editing.openEdit(8);
    assert.equal(editing.form.email, 'existing@example.com');
    assert.equal(editing.form.emailType, 'custom');

    const invalid = context.userMaster([], '/user', '/user', null, 'New User', 'custom@example.com', '2', [1], [], 'OPR011@INFRUITY.COM', 'custom');
    invalid.restoreOld();
    assert.equal(invalid.form.email, 'custom@example.com');
    assert.equal(invalid.form.emailType, 'custom');
});

test('mobile create sheet keeps employee-owned fields out and retains the password field', () => {
    assert.match(view, /\.user-account-drawer-panel:not\(\.is-editing\)\s*\{\s*max-height:\s*71\.5dvh;/);
    assert.doesNotMatch(view, /name="role_ids/);
    const fieldOrder = ['Alamat Email', 'Tautkan ke Karyawan', 'name="password"']
        .map(field => view.indexOf(field));
    assert.ok(fieldOrder.every((position, index) => position >= 0 && (index === 0 || position > fieldOrder[index - 1])));
    assert.ok(!view.includes('name="full_name"'));
    assert.ok(!view.includes('name="role_ids[]"'));
    assert.ok(!view.includes('name="id_branch[]"'));
    assert.ok(view.includes('relative max-h-48 overflow-y-auto overscroll-none scrollbar-hide bg-white'));
    assert.ok(view.includes('rounded-full bg-gray-200 text-gray-400'));
    assert.ok(view.includes('Telah ditautkan'));
    assert.ok(view.includes('text-[10px] italic text-gray-400'));
});

test('permission search matches the reference focus ring and group/submodule filtering', () => {
    const input = view.match(/<input id="user-access-search"[^>]+>/)?.[0] || '';
    const classes = input.match(/class="([^"]+)"/)?.[1].split(/\s+/) || [];
    assert.ok(classes.includes('h-9') && classes.includes('rounded-xl') && classes.includes('bg-gray-50'));
    assert.ok(classes.includes('outline-none'), 'the shared ERP focus-visible outline is suppressed on this reference-style input');
    assert.ok(classes.includes('focus:ring-1') && classes.includes('focus:ring-emerald-500'));
    assert.match(view, /x-show="modalSearchQuery\.length > 0"[^>]*@click="modalSearchQuery = ''"/);

    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const groups = [
        {label: 'Master', modules: [{label: 'Produk'}, {label: 'Kategori'}]},
        {label: 'Transaksi', modules: [{label: 'Penjualan'}]},
    ];
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], groups);

    app.modalSearchQuery = ' kategori ';
    assert.deepEqual(Array.from(app.filteredAccessGroups, group => [group.label, Array.from(group.modules, module => module.label)]), [['Master', ['Kategori']]]);
    app.modalSearchQuery = ' MASTER ';
    assert.deepEqual(Array.from(app.filteredAccessGroups, group => [group.label, Array.from(group.modules, module => module.label)]), [['Master', ['Produk', 'Kategori']]]);
    app.modalSearchQuery = 'not found';
    assert.equal(app.filteredAccessGroups.length, 0);
});

test('account edit drawer prevents one employee from linking to multiple accounts', () => {
    assert.match(view, /name="staff_id"/);
    assert.match(view, /Tautkan ke Karyawan/);
    assert.match(view, /Cari karyawan/);
    assert.match(view, /Karyawan tidak ditemukan/);
    assert.match(view, /staff\.linked_user_id/);
    assert.ok(view.includes(':disabled="isStaffUnavailable(staff)"'));
    assert.ok(controller.includes("'branch_id' => $staff->branch_id"));
    assert.ok(controller.includes("'role_ids' => $staff->pendingRoles->pluck"));
    assert.match(view, /@js\(\$staffOptions\)/);
    assert.match(controller, /'linked_user_id' => \$staff->account\?->id_user/);
    assert.match(controller, /update\(\['user_id' => \$userId\]\)/);
});

test('edit mode exposes account identity and employee linking without create-only fields', () => {
    assert.match(view, /name="link_only" value="1" :disabled="!editing"/);
    assert.doesNotMatch(view, /<div x-show="!editing"[^>]*role="radiogroup"/);
    assert.ok(view.includes('role="radiogroup" aria-label="Jenis email" :aria-disabled="Boolean(editing)"'));
    assert.match(view, /:readonly="editing \|\| form\.emailType === 'auto'"/);
    assert.match(view, /:disabled="Boolean\(editing\)"/);
    assert.match(view, /x-show="!editing" x-cloak :disabled="Boolean\(editing\)" class="contents"/);
    assert.match(view, /Tautkan ke Karyawan/);
    assert.ok(view.includes('name="password"'), 'password remains a create-only account field');
    for (const field of ['full_name', 'role_ids[]', 'id_branch[]']) {
        assert.ok(!view.includes(`name="${field}"`), `employee-owned ${field} is absent from the account drawer`);
    }
});

test('edit account drawer matches reference scrolling and email focus styling', () => {
    assert.match(view, /x-ref="scrollArea"/);
    assert.match(view, /@scroll="updateScroll\(\); isScrolling = true; clearTimeout\(scrollTimeout\); scrollTimeout = setTimeout\(\(\) => isScrolling = false, 800\)"/);
    assert.match(view, /@resize\.window="updateScroll\(\)"/);
    assert.match(view, /:style="`height: \$\{thumbHeight\}px; transform: translateY\(\$\{scrollPos\}px\);`"/);
    assert.match(view, /placeholder="Misal: nama@infruity\.com"/);
    assert.match(view, /focus:ring-1 focus:ring-emerald-500/);
    for (const field of ['staff_id', 'password']) {
        assert.ok(view.includes(`name="${field}"`), `existing edit field ${field} stays in the form`);
    }
    for (const field of ['full_name', 'role_ids[]', 'id_branch[]']) {
        assert.ok(!view.includes(`name="${field}"`), `removed field ${field} is absent`);
    }
});

test('account role assignment is provided by the employee form, not the account drawer', () => {
    assert.doesNotMatch(view, /name="role_ids/);
});

test('mobile edit account sheet matches the reference height without the role picker', () => {
    assert.match(view, /user-account-drawer-panel/);
    assert.ok(view.includes(':class="editing ? \'is-editing\' : \'\'"'));
    assert.ok(view.includes('@media (max-width: 1023px)') && view.includes('.user-account-drawer-panel.is-editing') && view.includes('max-height: 71.5dvh;'));
    assert.ok(view.includes('@media (min-width: 1024px)') && view.includes('.user-account-email-type-toggle'));
    assert.match(view, /x-ref="scrollArea"/);
    assert.doesNotMatch(view, /id="user-roles"/);
});

test('edit form cannot select an employee already linked to another account', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const users = [
        {id: 8, name: 'Existing User', email: 'existing@example.com', roles: [{id: 2}], branches: [1], staff_id: 10},
        {id: 9, name: 'Other User', email: 'other@example.com', roles: [{id: 2}], branches: [1], staff_id: 11},
    ];
    const staffOptions = [
        {id: 10, name: 'Dwi Febbrianti', linked_user_id: 8},
        {id: 11, name: 'Achmad Noorman', linked_user_id: 9},
    ];
    const app = context.userMaster(users, '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null, null, staffOptions);

    app.openEdit(8);
    assert.equal(app.form.staffId, '10');
    assert.equal(app.form.emailType, 'custom');
    app.toggleStaff(11);
    assert.equal(app.form.staffId, '10');
    app.toggleStaff(10);
    assert.equal(app.form.staffId, '');
});

test('linked employee rows expose the lock state only for another account', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const app = context.userMaster([
        {id: 8, name: 'Current Account', email: 'current@example.test', staff_id: 10},
        {id: 9, name: 'Other Account', email: 'other@example.test', staff_id: 11},
    ], '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null, null, []);
    app.openEdit(8);

    assert.equal(app.isStaffLinkedElsewhere({id: 10, linked_user_id: 8}), false);
    assert.equal(app.isStaffLinkedElsewhere({id: 11, linked_user_id: 9}), true);
    assert.equal(app.isStaffLinkedElsewhere({id: 12, linked_user_id: null}), false);
    assert.ok(view.includes('ph-lock-key'));
    assert.ok(view.includes('x-show="isStaffLinkedElsewhere(staff)"'));
    assert.ok(view.includes('x-show="!isStaffUnavailable(staff)"'));
});

test('account creation only allows employees with configured roles and branch', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const staffOptions = [
        {id: 10, name: 'No Branch', linked_user_id: null, branch_id: null, role_ids: [2]},
        {id: 11, name: 'No Role', linked_user_id: null, branch_id: 1, role_ids: []},
        {id: 12, name: 'Ready', linked_user_id: null, branch_id: 1, role_ids: [2]},
        {id: 13, name: 'Legacy Linked', linked_user_id: 8, branch_id: null, role_ids: []},
    ];
    const app = context.userMaster([{id: 8, email: 'legacy@example.test', staff_id: 13}], '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null, null, staffOptions);

    app.openCreate();
    assert.equal(app.isStaffIncomplete(staffOptions[0]), true);
    assert.equal(app.isStaffUnavailable(staffOptions[0]), true);
    assert.equal(app.isStaffUnavailable(staffOptions[1]), true);
    assert.equal(app.isStaffUnavailable(staffOptions[2]), false);
    app.toggleStaff(10);
    assert.equal(app.form.staffId, '');
    app.toggleStaff(12);
    assert.equal(app.form.staffId, '12');

    app.openEdit(8);
    assert.equal(app.form.staffId, '13');
    assert.equal(app.isStaffLinkedElsewhere(staffOptions[3]), false);
    assert.equal(app.isStaffUnavailable(staffOptions[3]), false, 'an existing link remains editable even when legacy employee data is incomplete');
});

test('inactive accounts remain viewable but do not open the edit drawer', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const app = context.userMaster([
        {id: 4, name: 'Former Account', email: 'former@example.test', is_aktif: false, staff_id: null},
    ], '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null, null, [], {});

    app.openEdit(4);
    assert.equal(app.drawerOpen, false);
    assert.match(view, /x-show="selected\?\.is_aktif"/);
});
