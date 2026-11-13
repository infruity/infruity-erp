<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerFormPlaceholderColourTest extends TestCase
{
    public function test_gender_is_the_reference_dropdown_with_a_grey_placeholder(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));
        $component = file_get_contents(base_path('resources/views/components/erp/picker-select.blade.php'));

        $this->assertStringContainsString('<x-erp.picker-select id="gender" name="gender"', $view);
        $this->assertStringContainsString("'label' => 'Laki-laki'", $view);
        $this->assertStringContainsString("'label' => 'Perempuan'", $view);
        $this->assertStringNotContainsString('<select id="gender"', $view);
        $this->assertStringContainsString(":class=\"value ? 'text-gray-900' : 'text-gray-400'\"", $component, 'grey until a value is chosen');
        $this->assertStringContainsString('max-h-32 overflow-y-auto', $component);
    }

    public function test_the_component_posts_the_chosen_value_through_a_hidden_input(): void
    {
        $html = \Illuminate\Support\Facades\Blade::render('<x-erp.picker-select id="g" name="gender" :options="[[\'value\' => \'male\', \'label\' => \'Laki-laki\']]" value="male" placeholder="Pilih" />');

        $this->assertStringContainsString('<input type="hidden" name="gender" :value="value">', $html);
        $this->assertStringContainsString('id="g"', $html);
        $this->assertStringContainsString('male', $html);
    }
}
