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

test('user detail opens as a role-backed bottom sheet with read-only access switches', () => {
    assert.match(view, /'permissions' => \$user->RoleUser\?->role\?->roleMenu\?->pluck\('permission'\)->all\(\)/);
    assert.match(view, /x-for="group in filteredAccessGroups"/);
    assert.match(view, /\['key' => 'kurir', 'label' => 'Kurir', 'permissions' => \['kurir\.index'\]\]/);
    assert.match(view, /role="switch" aria-disabled="true"/);
    assert.match(view, /items-end[^\"]*sm:items-center/);
    assert.match(view, /rounded-t-3xl[^\"]*sm:rounded-2xl/);
});

test('detail access summary reflects the assigned role permissions', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    assert.ok(start >= 0 && end > start, 'userMaster is present in the user page');
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const groups = [{label: 'Master', modules: [{key: 'products', label: 'Produk', permissions: ['products.index', 'products.create']}]}];
    const app = context.userMaster([], '/user', '/user', null, null, null, null, [], groups);
    const module = app.accessGroups?.[0]?.modules?.[0];
    assert.ok(module, 'access groups are available to the user screen');
    assert.equal(app.hasModuleAccess({role: 2, permissions: ['products.index']}, module), true);
    assert.equal(app.hasModuleAccess({role: 2, permissions: []}, module), false);
    assert.equal(app.hasModuleAccess({role: 1, permissions: []}, module), true);
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
