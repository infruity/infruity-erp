<?php

namespace Tests\Feature;

use Tests\TestCase;

class KurirDrawerPresentationTest extends TestCase
{
    public function test_kurir_uses_reference_style_picker_fields_without_changing_field_contract(): void
    {
        $view = file_get_contents(module_path('Master', 'Resources/views/kurir/index.blade.php'));
        $this->assertStringContainsString("'presentation' => 'picker'", $view);
        $this->assertStringContainsString("'searchable' => true", $view);
        $this->assertStringContainsString("'placeholder' => 'Misal: Gojek, Grab, Maxim'", $view);
        $this->assertStringContainsString("'key' => 'type'", $view);
        $this->assertStringContainsString("'key' => 'name'", $view);
        $this->assertStringContainsString("'key' => 'staff_id'", $view);
        $this->assertStringContainsString("'key' => 'description'", $view);
        $this->assertStringContainsString("'visibleWhen' => ['key' => 'type', 'value' => 'internal']", $view);
        $this->assertStringContainsString("\$formBodyClass = 'px-5 pt-5 pb-6 space-y-5';", $view);
    }

    public function test_name_input_is_only_visible_and_required_for_external_couriers(): void
    {
        $view = file_get_contents(module_path('Master', 'Resources/views/kurir/index.blade.php'));
        preg_match('/@php(.*?)@endphp/s', $view, $matches);
        $staffOptions = collect([(object) ['id' => 7, 'name' => 'Karyawan Contoh']]);
        $fields = eval($matches[1] . '; return $formFields;');
        $name = collect($fields)->firstWhere('key', 'name');
        $staff = collect($fields)->firstWhere('key', 'staff_id');

        $external = ['key' => 'type', 'value' => 'external'];
        $internal = ['key' => 'type', 'value' => 'internal'];
        $this->assertSame($external, $name['visibleWhen'] ?? null);
        $this->assertSame($external, $name['requiredWhen'] ?? null);
        $this->assertFalse($name['required'] ?? false);
        $this->assertSame($internal, $staff['visibleWhen']);
        $this->assertSame($internal, $staff['requiredWhen']);
    }

    public function test_shared_master_drawer_renders_scoped_searchable_pickers_and_preserves_native_selects(): void
    {
        $partial = file_get_contents(module_path('Master', 'Resources/views/partials/mobile-index.blade.php'));

        $this->assertStringContainsString("field.type === 'select' && field.presentation === 'picker'", $partial);
        $this->assertStringContainsString('fieldPickerOpen[field.key]', $partial);
        $this->assertStringContainsString('fieldPickerSearch[field.key]', $partial);
        $this->assertStringContainsString('filteredFieldOptions(field)', $partial);
        $this->assertStringContainsString('this.form[field.key] = option.value', $partial);
        $this->assertStringContainsString('validateRequiredPickers()', $partial);
        $this->assertStringContainsString("if (this.saving || !this.validateRequiredPickers()) return", $partial);
        $this->assertStringContainsString("field.type === 'select' && field.presentation !== 'picker'", $partial);
        $this->assertStringContainsString('x-model="form[field.key]"', $partial);
        $this->assertStringContainsString('body: JSON.stringify(payload)', $partial);
    }
}
