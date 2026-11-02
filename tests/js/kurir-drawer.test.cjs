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
