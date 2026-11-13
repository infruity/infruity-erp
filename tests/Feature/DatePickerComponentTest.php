<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DatePickerComponentTest extends TestCase
{
    public function test_the_picker_posts_the_date_through_a_hidden_input_and_shows_the_reference_calendar(): void
    {
        $html = Blade::render('<x-erp.date-picker id="d" name="birth_of_date" value="1990-01-02 00:00:00" />');

        $this->assertStringContainsString('<input type="hidden" name="birth_of_date" :value="value">', $html);
        $this->assertStringContainsString('id="d"', $html);
        $this->assertStringContainsString('1990-01-02', $html);
        $this->assertStringNotContainsString('00:00:00', $html, 'only the date part is posted');
        foreach (['Januari', 'dd\\/mm\\/yyyy', 'background-color: #c5f037;', 'navigateCalendar', "calendarView === 'year'"] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
    }

    public function test_the_customer_form_uses_it_for_the_birth_date(): void
    {
        $form = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));

        $this->assertStringContainsString('<x-erp.date-picker id="birth_of_date" name="birth_of_date"', $form);
        $this->assertStringNotContainsString('type="date"', $form);
    }
}
