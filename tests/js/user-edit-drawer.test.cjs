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
    assert.match(view, /'permissions' => \$user->RoleUser\?->role\?->roleMenu\?->pluck\('permission'\)->all\(\)/);
    assert.match(view, /'permission_overrides' =>/);
    assert.match(view, /x-for="group in filteredAccessGroups"/);
    assert.match(view, /\['key' => 'kurir', 'label' => 'Kurir', 'permissions' => \['kurir\.index'\]\]/);
    assert.match(view, /@click\.stop="toggleGroupAccess\(selected, group\)"/);
    assert.match(view, /@click\.stop="toggleModuleAccess\(selected, module\)"/);
    assert.doesNotMatch(view, /role="switch" aria-disabled="true"/);
    assert.match(view, /items-end[^\"]*sm:items-center/);
    assert.match(view, /rounded-t-3xl[^\"]*sm:rounded-2xl/);
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
    assert.equal(app.hasModuleAccess({role: 2, permissions: ['products.index'], permission_overrides: {}}, module), true);
    assert.equal(app.hasModuleAccess({role: 2, permissions: ['products.index'], permission_overrides: {'products.index': false}}, module), false);
    assert.equal(app.hasModuleAccess({role: 2, permissions: [], permission_overrides: {'products.create': true}}, module), true);
    assert.equal(app.hasModuleAccess({role: 1, permissions: [], permission_overrides: {}}, module), true);
    assert.equal(app.hasModuleAccess({role: 1, permissions: [], permission_overrides: {'products.index': false, 'products.create': false}}, module), false);
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
    const user = {id: 44, role: 2, permissions: ['products.index'], permission_overrides: {}, can_change_permissions: true};

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
    const user = {id: 45, role: 2, permissions: ['products.index'], permission_overrides: {}, can_change_permissions: true};

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
    assert.match(view, /x-show="!editing" x-cloak class="flex rounded-lg bg-gray-100 p-0.5"/);
    assert.match(view, /:readonly="!editing && form.emailType === 'auto'"/);
    assert.match(view, /name="full_name"/);
    assert.match(view, /name="password"/);
    assert.match(view, /name="id_role"/);
    assert.match(view, /name="id_branch\[\]"/);
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

    const editing = context.userMaster([{id: 8, name: 'Existing User', email: 'existing@example.com', role: 2, branches: [1]}], '/user', '/user', null, null, null, null, [], [], 'OPR011@INFRUITY.COM', null);
    editing.openEdit(8);
    assert.equal(editing.form.email, 'existing@example.com');
    assert.equal(editing.form.emailType, 'custom');

    const invalid = context.userMaster([], '/user', '/user', null, 'New User', 'custom@example.com', '2', [1], [], 'OPR011@INFRUITY.COM', 'custom');
    invalid.restoreOld();
    assert.equal(invalid.form.email, 'custom@example.com');
    assert.equal(invalid.form.emailType, 'custom');
});
