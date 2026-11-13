<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaffDatePickerTest extends TestCase
{
    public function test_the_staff_entry_date_uses_the_reference_calendar(): void
    {
        $form = file_get_contents(base_path('Modules/Master/Resources/views/staff/form-fields.blade.php'));

        $this->assertStringContainsString('<x-erp.date-picker id="{{ $fieldIdPrefix }}_date_in" name="date_in" :value="$selectedDate" />', $form);
        $this->assertStringNotContainsString('type="date"', $form);
    }
}
