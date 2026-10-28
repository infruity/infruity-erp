const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const viewPath = path.join(__dirname, '../../resources/views/admin/user/index.blade.php');
const controllerPath = path.join(__dirname, '../../app/Http/Controllers/UserController.php');
const staffViewPath = path.join(__dirname, '../../Modules/Master/Resources/views/staff/create.blade.php');
const staffIndexPath = path.join(__dirname, '../../Modules/Master/Resources/views/staff/index.blade.php');
const staffFieldsPath = path.join(__dirname, '../../Modules/Master/Resources/views/staff/form-fields.blade.php');
const staffOverlayPath = path.join(__dirname, '../../Modules/Master/Resources/views/staff/create-overlay.blade.php');
const staffScriptPath = path.join(__dirname, '../../Modules/Master/Resources/views/staff/js-create.blade.php');
const staffControllerPath = path.join(__dirname, '../../Modules/Master/Http/Controllers/StaffController.php');
const view = fs.readFileSync(viewPath, 'utf8');
const controller = fs.readFileSync(controllerPath, 'utf8');
const staffView = fs.readFileSync(staffViewPath, 'utf8');
const staffIndex = fs.readFileSync(staffIndexPath, 'utf8');
const staffFields = fs.readFileSync(staffFieldsPath, 'utf8');
const staffOverlay = fs.readFileSync(staffOverlayPath, 'utf8');
const staffScript = fs.readFileSync(staffScriptPath, 'utf8');
const staffController = fs.readFileSync(staffControllerPath, 'utf8');
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
    const app = context.userMaster([], '/user', groups);
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
    const app = context.userMaster([], '/user', [group]);
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
    const app = context.userMaster([], '/user', [group]);
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
    const app = context.userMaster([], '/user', []);
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
    const app = context.userMaster([], '/user', [], null, null);
    app.drawerOpen = true;
    app.closeDrawer();

    assert.equal(app.drawerOpen, false);
    assert.equal(historyCalls.length, 1);
    assert.equal(historyCalls[0][0].scroll, 40);
    assert.equal(historyCalls[0][2], '/user?role_id=2#list');
});

test('staff create overlay uses the four reference fields and keeps old data intact on update', () => {
    assert.match(staffOverlay, /data-staff-create-overlay/);
    assert.match(staffOverlay, /x-transition:enter-start="translate-y-full/);
    assert.match(staffOverlay, /@staff-create-open\.window="openDrawer\(\)"/);
    assert.match(staffFields, /name="staff_name"/);
    assert.match(staffFields, /name="date_in"/);
    assert.match(staffFields, /name="position"/);
    assert.match(staffFields, /name="branch_id"/);
    assert.doesNotMatch(staffFields, /name="(?:nickname|nik|contact|email|gender|department|description|status|avatar)"/);
    assert.match(staffFields, /<details class="border-t border-gray-200 pt-5"/);
    assert.match(staffIndex, /'branch_label'/);
    assert.match(staffView, /master::staff\.form-fields/);
    assert.match(staffController, /'branch_id' => \['required', 'integer', Rule::in\(/);
    assert.match(staffController, /\$staff->branch_id = \$request->branch_id/);
});

test('staff create overlay opens as a sheet and closes without losing the current URL context', () => {
    const start = staffScript.indexOf('window.staffCreateOverlay = function');
    const end = staffScript.indexOf('\n};\n\n(() =>', start) + 3;
    const historyCalls = [];
    const location = {
        href: 'http://127.0.0.1:8000/staff?role_id=2#list',
        get pathname() { return new URL(this.href).pathname; },
        get search() { return new URL(this.href).search; },
    };
    const context = {
        URL,
        URLSearchParams,
        document: {
            body: {classList: {toggle() {}}},
            getElementById() { return {focus() {}}; },
        },
        window: {
            location,
            history: {
                state: {scroll: 40},
                pushState(state, _title, url) {
                    historyCalls.push(['push', state, url]);
                    this.previous = {href: location.href, state: this.state};
                    location.href = new URL(url, location.href).href;
                    this.state = state;
                },
                back() {
                    historyCalls.push(['back']);
                    location.href = this.previous.href;
                    this.state = this.previous.state;
                },
                replaceState(state, _title, url) {
                    historyCalls.push(['replace', state, url]);
                    location.href = new URL(url, location.href).href;
                    this.state = state;
                },
            },
        },
    };
    vm.runInNewContext(`${staffScript.slice(start, end)}; globalThis.createOverlay = window.staffCreateOverlay;`, context);
    const app = context.createOverlay(false);
    app.$nextTick = callback => callback();

    app.openDrawer();
    assert.equal(app.drawerOpen, true);
    assert.equal(new URL(location.href).searchParams.get('create'), '1');
    app.closeDrawer();

    assert.equal(app.drawerOpen, false);
    assert.equal(location.href, 'http://127.0.0.1:8000/staff?role_id=2#list');
    assert.deepEqual(historyCalls.map(call => call[0]), ['push', 'back']);
});

test('staff account form keeps provisioning controls outside employee fields', () => {
    assert.match(staffFields, /name="account_email_type"/);
    assert.match(staffFields, /name="account_email"/);
    assert.match(staffFields, /name="account_password"/);
    assert.match(staffScript, /const autoAccountEmail = @js\(\$nextAutoEmail/);
    assert.match(staffScript, /syncAccountEmail\(true\)/);
    assert.match(staffController, /sprintf\('OPR%03d@INFRUITY\.COM'/);
});

test('add account action opens account creation on the staff form', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const navigations = [];
    const context = {window: {location: {assign: url => navigations.push(url)}}};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const app = context.userMaster([], '/user', [], null, null, [], '/staff/create');

    app.openCreate();

    assert.deepEqual(navigations, ['/staff/create']);
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
    const app = context.userMaster([], '/user', groups);

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
    assert.ok(view.includes(':disabled="isStaffLinkedElsewhere(staff)"'));
    assert.match(view, /@js\(\$staffOptions\)/);
    assert.match(controller, /'linked_user_id' => \$staff->account\?->id_user/);
    assert.match(controller, /update\(\['user_id' => \$userId\]\)/);
});

test('user edit form only displays account identity and updates the employee link', () => {
    assert.match(view, /name="link_only" value="1"/);
    assert.match(view, /form\.email \|\| 'Email tidak tersedia'/);
    assert.match(view, /Tautkan ke Karyawan/);
    assert.match(view, /name="staff_id"/);
    for (const field of ['full_name', 'password', 'role_ids[]', 'id_branch[]']) {
        assert.equal(view.includes(`name="${field}"`), false, `${field} is not submitted from the account page`);
    }
});

test('account link drawer retains scroll behavior without account provisioning inputs', () => {
    assert.match(view, /x-ref="scrollArea"/);
    assert.match(view, /@scroll="updateScroll\(\); isScrolling = true; clearTimeout\(scrollTimeout\); scrollTimeout = setTimeout\(\(\) => isScrolling = false, 800\)"/);
    assert.match(view, /@resize\.window="updateScroll\(\)"/);
    assert.match(view, /:style="`height: \$\{thumbHeight\}px; transform: translateY\(\$\{scrollPos\}px\);`"/);
    assert.match(view, /Alamat Email Akun/);
    assert.match(view, /Tautkan ke Karyawan/);
    assert.match(view, /focus:ring-1 focus:ring-emerald-500/);
    for (const field of ['full_name', 'password', 'role_ids[]', 'id_branch[]']) {
        assert.equal(view.includes(`name="${field}"`), false, `${field} remains on the staff form`);
    }
});

test('role picker is moved to the staff account form and removed from the user page', () => {
    assert.doesNotMatch(view, /name="role_ids\[\]"/);
    assert.match(staffFields, /name="role_ids\[\]"/);
});

test('mobile account link drawer keeps the reference height without create-only fields', () => {
    assert.match(view, /user-account-drawer-panel/);
    assert.ok(view.includes(':class="editing ? \'is-editing\' : \'\'"'));
    assert.ok(view.includes('@media (max-width: 1023px)') && view.includes('.user-account-drawer-panel.is-editing') && view.includes('max-height: 71.5dvh;'));
    assert.match(view, /x-ref="scrollArea"/);
    assert.doesNotMatch(view, /name="role_ids\[\]"/);
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
    const app = context.userMaster(users, '/user', [], null, null, staffOptions);

    app.openEdit(8);
    assert.equal(app.form.staffId, '10');
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
    ], '/user', [], null, null, []);
    app.openEdit(8);

    assert.equal(app.isStaffLinkedElsewhere({id: 10, linked_user_id: 8}), false);
    assert.equal(app.isStaffLinkedElsewhere({id: 11, linked_user_id: 9}), true);
    assert.equal(app.isStaffLinkedElsewhere({id: 12, linked_user_id: null}), false);
    assert.ok(view.includes('ph-lock-key'));
    assert.ok(view.includes('x-show="isStaffLinkedElsewhere(staff)"'));
    assert.ok(view.includes('x-show="!isStaffLinkedElsewhere(staff)"'));
});

test('inactive accounts remain viewable but do not open the edit drawer', () => {
    const start = view.indexOf('function userMaster(');
    const end = view.indexOf('\n}\n</script>', start) + 2;
    const context = {};
    vm.runInNewContext(`${view.slice(start, end)}; globalThis.userMaster = userMaster;`, context);
    const app = context.userMaster([
        {id: 4, name: 'Former Account', email: 'former@example.test', is_aktif: false, staff_id: null},
    ], '/user', [], null, null, []);

    app.openEdit(4);
    assert.equal(app.drawerOpen, false);
    assert.match(view, /x-show="selected\?\.is_aktif"/);
});

test('account creation lives on the staff form while user drawer only links existing accounts', () => {
    for (const field of ['name="full_name"', 'name="password"', 'name="role_ids[]"', 'name="id_branch[]"']) {
        assert.equal(view.includes(field), false, `${field} is no longer part of the user page form`);
    }
    assert.match(view, /name="staff_id"/);
    assert.match(staffFields, /name="create_account"/);
    assert.match(staffFields, /name="account_email"/);
    assert.match(staffFields, /name="account_password"/);
    assert.match(staffFields, /name="role_ids\[\]"/);
    assert.doesNotMatch(staffFields, /name="id_branch\[\]"/);
    assert.match(staffFields, /Nama akun akan mengikuti nama karyawan/i);
});
