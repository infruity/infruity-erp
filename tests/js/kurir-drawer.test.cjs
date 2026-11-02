const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const repo = path.join(__dirname, '../..');
const partial = fs.readFileSync(path.join(repo, 'Modules/Master/Resources/views/partials/mobile-index.blade.php'), 'utf8');

function pickerMethods() {
    const start = partial.indexOf('        fieldVisible(field) {');
    const end = partial.indexOf('        fieldValue(record, key) {', start);
    assert.ok(start >= 0 && end > start, 'Master drawer picker methods must be present');
    return vm.runInNewContext(`({${partial.slice(start, end)}})`);
}

const staffField = {
    key: 'staff_id',
    label: 'Pilih Karyawan',
    type: 'select',
    presentation: 'picker',
    searchable: true,
    requiredWhen: { key: 'type', value: 'internal' },
    visibleWhen: { key: 'type', value: 'internal' },
    options: [
        { value: 7, label: 'Zulki Arga Rahman' },
        { value: 12, label: 'Siti Rahmah' },
    ],
};

test('picker resolves numeric saved IDs and filters staff options case-insensitively', () => {
    const model = { form: { type: 'internal', staff_id: '7' }, fieldPickerSearch: { staff_id: 'SITI' } };
    const methods = pickerMethods();
    assert.equal(methods.selectedFieldOption.call(model, staffField).label, 'Zulki Arga Rahman');
    assert.deepEqual(Array.from(methods.filteredFieldOptions.call(model, staffField), item => item.label), ['Siti Rahmah']);
});

test('picker selection writes the unchanged request field value and closes the menu', () => {
    const model = {
        form: { type: 'internal', staff_id: '' },
        fieldPickerOpen: { staff_id: true },
        fieldPickerSearch: { staff_id: 'siti' },
        error: 'required',
    };
    pickerMethods().selectFieldOption.call(model, staffField, staffField.options[1]);
    assert.equal(model.form.staff_id, 12);
    assert.equal(model.fieldPickerOpen.staff_id, false);
    assert.equal(model.fieldPickerSearch.staff_id, '');
    assert.equal(model.error, '');
});

test('custom required picker validates only when its existing conditional requirement applies', () => {
    const methods = pickerMethods();
    const model = {
        fields: [staffField], form: { type: 'internal', staff_id: '' },
        fieldPickerOpen: {}, error: '',
        fieldRequired: methods.fieldRequired,
    };
    assert.equal(methods.validateRequiredPickers.call(model), false);
    assert.equal(model.fieldPickerOpen.staff_id, true);
    assert.equal(model.error, 'Pilih Karyawan wajib dipilih.');

    model.form.type = 'external';
    model.fieldPickerOpen = {};
    model.error = '';
    assert.equal(methods.validateRequiredPickers.call(model), true);
    assert.deepEqual(model.fieldPickerOpen, {});
});

function drawer(routeBase = 'kurir') {
    const requests = [];
    const context = {
        document: { querySelector: () => ({ content: 'fixture-csrf' }) },
        window: { location: { pathname: '/kurir', search: '', reload() {} } },
        fetch: async (url, options) => {
            requests.push({ url, ...options, body: JSON.parse(options.body) });
            return { ok: true, json: async () => ({ success: true }) };
        },
    };
    const script = partial.slice(partial.indexOf('function masterList('), partial.lastIndexOf('</script>'));
    vm.runInNewContext(script, context);
    const nameField = { key: 'name', visibleWhen: { key: 'type', value: 'external' }, requiredWhen: { key: 'type', value: 'external' } };
    const model = context.masterList([], [{ key: 'type' }, nameField, staffField, { key: 'description' }], routeBase, 'name', [], 'Kurir', 'ajax');
    return { model, requests, nameField };
}

test('name visibility and native required state follow external type while staff follows internal', () => {
    const { model, nameField } = drawer();
    for (const type of ['internal', 'external', 'internal', '']) {
        model.form = { type };
        assert.equal(model.fieldVisible(nameField), type === 'external');
        assert.equal(model.fieldRequired(nameField), type === 'external');
        assert.equal(model.fieldVisible(staffField), type === 'internal');
        assert.equal(model.fieldRequired(staffField), type === 'internal');
    }
    assert.ok(partial.includes(':required="fieldRequired(field)"'));
});

test('internal create and edit keep existing payload keys and derive hidden name from selected staff', async () => {
    for (const editing of [null, 42]) {
        const { model, requests } = drawer();
        model.editing = editing;
        model.form = { type: 'internal', name: editing ? 'Nama lama' : '', staff_id: '7', description: 'Catatan' };
        await model.save();
        assert.equal(requests.length, 1);
        assert.equal(requests[0].url, editing ? '/kurir/42' : '/kurir');
        assert.equal(requests[0].method, editing ? 'PUT' : 'POST');
        assert.deepEqual(requests[0].body, { type: 'internal', name: 'Zulki Arga Rahman', staff_id: '7', description: 'Catatan' });
    }
});

test('switching staff uses the latest selection and external manual names remain untouched', async () => {
    const { model, requests } = drawer();
    model.form = { type: 'external', name: 'Kurir eksternal', staff_id: '', description: '' };
    model.form.type = 'internal';
    model.selectFieldOption(staffField, staffField.options[0]);
    model.selectFieldOption(staffField, staffField.options[1]);
    assert.equal(model.form.name, 'Kurir eksternal', 'keep manual draft until submission');
    model.form.type = 'external';
    await model.save();
    assert.equal(requests[0].body.name, 'Kurir eksternal');
    model.saving = false;
    model.form.type = 'internal';
    await model.save();
    assert.equal(requests[1].body.name, 'Siti Rahmah');
    assert.equal(requests[1].body.staff_id, 12);
    assert.equal(model.form.name, 'Kurir eksternal', 'derived name must not overwrite the external draft');
});

test('unavailable staff cannot submit a stale hidden internal name', async () => {
    const { model, requests } = drawer();
    model.form = { type: 'internal', name: 'Nama lama', staff_id: 999, description: '' };
    await model.save();
    assert.equal(requests.length, 0);
    assert.equal(model.error, 'Pilih Karyawan wajib dipilih.');
    assert.equal(model.fieldPickerOpen.staff_id, true);
    assert.equal(model.saving, false);
});

test('missing internal staff blocks the request and other Master forms do not derive names', async () => {
    const { model, requests } = drawer();
    model.form = { type: 'internal', name: '', staff_id: '', description: '' };
    await model.save();
    assert.equal(requests.length, 0);
    assert.equal(model.error, 'Pilih Karyawan wajib dipilih.');
    const other = drawer('supplier');
    other.model.form = { type: 'internal', name: 'Supplier Asli', staff_id: 7, description: '' };
    await other.model.save();
    assert.equal(other.requests[0].body.name, 'Supplier Asli');
});
