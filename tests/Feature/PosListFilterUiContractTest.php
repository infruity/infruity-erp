<?php

namespace Tests\Feature;

use Tests\TestCase;

final class PosListFilterUiContractTest extends TestCase
{
    private function posListView(): string
    {
        return file_get_contents(base_path('Modules/Pos/Resources/views/pos/index2.blade.php'));
    }

    public function test_pos_filter_uses_the_reference_date_cards_and_inline_calendar(): void
    {
        $view = $this->posListView();

        foreach ([
            'id="pos-start-date"',
            'id="pos-end-date"',
            'aria-controls="pos-filter-calendar"',
            'data-pos-calendar',
            'openCalendar(',
            'calendarDays',
            'calendarMonths',
            'calendarYears',
            'Minggu Ini',
            'Hari Ini',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*type="date"/', $view);
    }

    public function test_pos_filter_sheet_and_branch_picker_are_responsive_like_stock_opname(): void
    {
        $view = $this->posListView();

        foreach ([
            'class="pos-filter-overlay fixed',
            'class="pos-filter-panel relative',
            'width:calc(100% - 32px)',
            'max-width:512px',
            'max-height:92dvh',
            'class="pos-filter-branch-picker"',
            'class="pos-filter-branch-options"',
            'appearance:none',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
    }
}
