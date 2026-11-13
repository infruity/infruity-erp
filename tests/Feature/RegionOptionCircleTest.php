<?php

namespace Tests\Feature;

use Tests\TestCase;

class RegionOptionCircleTest extends TestCase
{
    public function test_region_options_show_a_radio_circle_and_mark_the_chosen_one(): void
    {
        $drawer = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));
        $customer = file_get_contents(base_path('resources/views/components/erp/region-pickers.blade.php'));

        $this->assertStringContainsString("w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0 transition-colors\" :class=\"String(form[field.key]) === String(option.id) ? '!border-emerald-500' : ''\"", $drawer);
        $this->assertStringContainsString("String(form[field.key]) === String(option.id) ? 'font-bold text-gray-900' : 'font-medium text-gray-700'", $drawer);
        $this->assertStringContainsString("w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0 transition-colors", $customer);
        $this->assertStringContainsString("selected('{{ \$key }}', option) ? 'font-bold text-gray-900' : 'font-medium text-gray-700'", $customer);
        $this->assertStringContainsString(':aria-selected="selected(', $customer);
    }
}
