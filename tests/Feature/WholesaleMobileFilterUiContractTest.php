<?php

namespace Tests\Feature;

use Tests\TestCase;

class WholesaleMobileFilterUiContractTest extends TestCase
{
    public function test_mobile_date_changes_stay_temporary_until_apply(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/index.blade.php'));

        $this->assertStringNotContainsString('data-wholesale-filter=', $this->inputTag($view, 'wholesale-mobile-start-date'));
        $this->assertStringNotContainsString('data-wholesale-filter=', $this->inputTag($view, 'wholesale-mobile-end-date'));
        $startApply = <<<'JAVASCRIPT'
$('[data-wholesale-filter="start"]').val(start || '');
JAVASCRIPT;
        $endApply = <<<'JAVASCRIPT'
$('[data-wholesale-filter="end"]').val(end || '');
JAVASCRIPT;

        $this->assertStringContainsString($startApply, $view);
        $this->assertStringContainsString($endApply, $view);
    }

    public function test_mobile_filter_renders_the_reference_calendar_state(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/index.blade.php'));

        $this->assertStringContainsString('id="wholesale-mobile-calendar"', $view);
        $this->assertStringContainsString('data-wholesale-calendar-day', $view);
        $this->assertStringContainsString('openMobileCalendar($event.currentTarget)', $view);
        $this->assertStringContainsString('backToMobileFilters()', $view);
        $this->assertStringContainsString('moveMobileCalendar(-1)', $view);
        $this->assertStringContainsString('moveMobileCalendar(1)', $view);
    }

    private function inputTag(string $view, string $id): string
    {
        $idPosition = strpos($view, 'id="' . $id . '"');
        $this->assertNotFalse($idPosition);
        $tagStart = strrpos(substr($view, 0, $idPosition), '<input');
        $tagEnd = strpos($view, '>', $idPosition);

        return substr($view, $tagStart, $tagEnd - $tagStart + 1);
    }

    public function test_mobile_filter_exposes_the_reference_date_controls_without_replacing_wholesale_filter_contracts(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/index.blade.php'));

        $this->assertStringContainsString('id="wholesale-mobile-start-date"', $view);
        $this->assertStringContainsString('x-model="mobileStartDate"', $view);
        $this->assertStringContainsString('id="wholesale-mobile-end-date"', $view);
        $this->assertStringContainsString('x-model="mobileEndDate"', $view);
        $this->assertStringContainsString('Periode Tanggal', $view);
        $this->assertStringContainsString('Pilihan Cepat', $view);
        $this->assertStringContainsString("setMobileDateRange('week')", $view);
        $this->assertStringContainsString("setMobileDateRange('today')", $view);
        $this->assertStringContainsString('applyWholesaleMobileFilters(mobileStatus, mobileBranch, mobileStartDate, mobileEndDate)', $view);
        $this->assertStringContainsString("data.start_date = getWholesaleFilter('start') || null;", $view);
        $this->assertStringContainsString("data.end_date = getWholesaleFilter('end') || null;", $view);
    }
}
