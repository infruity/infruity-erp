<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerRegionLockTest extends TestCase
{
    public function test_each_region_level_is_locked_until_the_level_above_has_a_value(): void
    {
        $script = file_get_contents(base_path('Modules/Master/Resources/views/customer/js-create.blade.php'));
        $form = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));

        $this->assertStringContainsString('function syncLocks()', $script);
        $this->assertStringContainsString("const locked = !pickers[parent]?.querySelector('input[type=\"hidden\"]').value;", $script);
        $this->assertStringContainsString('input.disabled = locked;', $script);
        // locks are refreshed on load, after a choice, when a level is typed over and when a level is reset
        $this->assertGreaterThanOrEqual(4, substr_count($script, 'syncLocks();'));
        $this->assertStringContainsString('disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed', $form);
    }
}
