<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameReferencePresentationTest extends TestCase
{
    public function test_requested_states_are_wired_without_replacing_server_contracts(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
        foreach (['stock-opname-reference.css', 'stock-opname-reference.js', 'x-erp.mobile-search',
            'so-filter-sheet', 'so-history-filter', 'so-detail-correction', 'data-so-history-toggle',
            'data-so-field="created"', 'data-so-field="creator"'] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
        $this->assertStringContainsString('name="real_stock"', $source);
        $this->assertStringContainsString("route('stock-opname.data')", $source);
        $this->assertStringContainsString('/stock-opname/${id}/finalize', $source);
    }

    public function test_main_filter_matches_reference_controls_without_changing_other_sheets(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/partials/reference-filters.blade.php'));
        $css = file_get_contents(public_path('assets/css/stock-opname-reference.css'));
        $this->assertStringContainsString('data-so-reset="branch"', $source);
        $this->assertStringContainsString('data-so-reset="period"', $source);
        $this->assertStringContainsString('ph-bold ph-arrow-down-left', $source);
        $this->assertStringContainsString('ph-bold ph-arrow-up-right', $source);
        $this->assertStringContainsString('#so-filter-sheet .so-filter-section', $css);
        $this->assertStringContainsString('#so-filter-sheet .modal-content', $css);
        $this->assertStringContainsString('max-height:88dvh', $css);
    }

    public function test_main_filter_uses_the_reference_calendar_screen_without_changing_filter_fields(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/partials/reference-filters.blade.php'));
        $script = file_get_contents(public_path('assets/js/stock-opname-reference-page.js'));
        $styles = file_get_contents(public_path('assets/css/stock-opname-reference.css'));

        foreach (['id="so-calendar-back"', 'id="so-calendar-host"', 'id="so-calendar-heading"', 'id="so-calendar-days"', 'id="so-calendar-months"', 'id="so-calendar-years"'] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
        $this->assertStringNotContainsString('so-draft-calendar', $source);
        $this->assertStringContainsString('calendar.open(draft[dateSide]', $script);
        $this->assertStringContainsString('data-so-calendar-day', $script);
        $this->assertStringContainsString('ui.selectFilterDate(draft,dateSide,day.dataset.soCalendarDay)', $script);
        $this->assertStringContainsString('syncDates(); showCalendar(false)', $script);
        $this->assertStringContainsString('#so-calendar-host .so-calendar-card', $styles);
        $this->assertStringContainsString('#so-calendar-host .so-calendar-day button.is-selected', $styles);
        $this->assertStringContainsString('#so-calendar-host .so-calendar-day button.is-in-range', $styles);
        $this->assertStringContainsString('#so-filter-calendar:not([hidden])', $styles);
        $this->assertStringContainsString('height:36px; align-items:center; justify-content:center; color:#9ca3af;', $styles);
    }

    public function test_branch_radio_uses_a_green_checked_stroke(): void
    {
        $css = file_get_contents(public_path('assets/css/stock-opname-reference.css'));

        $this->assertStringContainsString('#so-filter-sheet .so-branch-options input[type=radio]', $css);
        $this->assertStringContainsString('#so-filter-sheet .so-branch-options input[type=radio]:checked', $css);
        $this->assertStringContainsString('appearance:none', $css);
        $this->assertStringContainsString('border-color:#10b981', $css);
        $this->assertStringContainsString('background-image:radial-gradient(circle,#10b981 0 4px,transparent 4px)', $css);
        $this->assertStringContainsString('#so-filter-sheet .so-branch-options input[type=radio]:focus-visible { outline:2px solid #10b981', $css);
    }

    public function test_result_and_status_filters_are_wired_to_the_existing_data_endpoint(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/partials/reference-filters.blade.php'));

        $this->assertStringContainsString('Hasil Opname', $source);
        $this->assertStringContainsString('Status Opname', $source);
        $this->assertStringContainsString('data-so-result="lebih"', $source);
        $this->assertStringContainsString('data-so-status="draft"', $source);
        $this->assertStringContainsString('id="so-filter-applied-status"', $source);
        $this->assertStringContainsString('id="so-filter-applied-result"', $source);
        $this->assertStringNotContainsString('disabled aria-describedby="so-filter-limit"', $source);
        $this->assertStringNotContainsString('so-filter-limit', $source);
        // Both fieldsets must stay interactive; a bare `disabled` would silently re-block them.
        $this->assertStringNotContainsString('<fieldset class="so-filter-section" disabled', $source);
        $this->assertStringContainsString('so-filter-draft-branch', $source);
        $this->assertStringNotContainsString('data-kt-ecommerce-product-filter="cabang"', $source);
    }

    public function test_list_request_carries_the_result_and_status_filters(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));

        $this->assertStringContainsString("d.status_filter = \$('#so-filter-applied-status').val()", $source);
        $this->assertStringContainsString("d.result_filter = \$('#so-filter-applied-result').val()", $source);
    }
}
