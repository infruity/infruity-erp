<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class StaffSubmitLockTest extends TestCase
{
    public function test_the_staff_save_button_stays_locked_until_every_required_field_is_filled(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/Modules/Master/Resources/views/staff/form-fields.blade.php');

        self::assertStringContainsString(':disabled="!ready"', $view);
        foreach (['staff_name', 'date_in', "input[name='positions[]']:checked", "input[name='branch_id']:checked", "input[name='role_ids[]']:checked"] as $needle) {
            self::assertStringContainsString($needle, $view, $needle);
        }
        self::assertStringContainsString('disabled:bg-gray-200 disabled:text-gray-400', $view);
    }
}
