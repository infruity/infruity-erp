<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ReportFilterUiContractTest extends TestCase
{
    private function partial(): string
    {
        return file_get_contents(base_path('Modules/Report/Resources/views/partials/mobile-detail.blade.php'));
    }

    public function test_report_filter_is_the_shared_sheet_with_branch_groups_and_a_conditional_period(): void
    {
        $view = $this->partial();

        foreach ([
            '<x-erp.filter-sheet',
            'id="report"',
            'title="Filter Laporan"',
            'branch-label="Cabang"',
            ':groups="$reportGroups"',
            ":with-dates=\"\$reportType !== 'asset'\"",
            ":dates-when=\"['timeGroup' => 'daily']\"",
            ':timezone="config(\'app.timezone\')"',
            'desktop="modal"',
            'reset-applies',
            "@erp-filter-apply.window=\"if (\$event.detail.id === 'report') applyFilters(\$event.detail.values)\"",
            "@erp-filter-close.window=\"if (\$event.detail.id === 'report') closeFilter()\"",
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }

        // The old sheet, its dropdowns, calendar and live-apply are gone; the history popover is a separate filter.
        foreach (['report-mobile-sheet--filter', 'applyDraftLive', 'draftTimeGroup', 'report-mobile-calendar__head', 'weekOptions'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
        $this->assertStringContainsString('report-mobile-history-filter-popover', $view);
    }

    public function test_groups_depend_on_the_report_type_and_the_chosen_period_mode(): void
    {
        $view = $this->partial();

        // Only reports other than Total Aset get groups; shipping adds its courier list.
        $this->assertStringContainsString("if (\$reportType !== 'asset') {", $view);
        $this->assertStringContainsString("'key' => 'groupBy'", $view);
        $this->assertStringContainsString("\$reportType === 'shipping' ? 'Kurir' : 'Produk'", $view);
        $this->assertStringContainsString("'key' => 'courier'", $view);
        $this->assertStringContainsString("'showWhen' => ['groupBy' => 'product']", $view);
        $this->assertStringContainsString("'Tanpa Kurir / Lainnya'", $view);
        // Weekly mode: a year and the weeks of that year, Saturday to Friday, future weeks disabled.
        $this->assertStringContainsString("'showWhen' => ['timeGroup' => 'weekly']", $view);
        $this->assertStringContainsString("'resets' => ['week']", $view);
        $this->assertStringContainsString("'optionsBy' => 'year'", $view);
        $this->assertStringContainsString("'disabled' => \$reportDisabledWeeks", $view);
        $this->assertStringContainsString("dayOfWeek + 1) % 7", $view);
    }

    public function test_the_script_hands_the_sheet_its_values_and_maps_them_to_the_same_requests(): void
    {
        $script = file_get_contents(base_path('public/assets/js/report-mobile.js'));

        $this->assertStringContainsString("this.\$dispatch('erp-filter-open', { id: 'report'", $script);
        $this->assertStringContainsString('applyFilters(values) {', $script);
        $this->assertStringContainsString("params.set('start_date', this.startDate || '1900-01-01')", $script);
        $this->assertStringContainsString("params.set('time_group', this.timeGroup)", $script);
        $this->assertStringContainsString("params.set('courier_id', this.courier)", $script);
        foreach (['draftStart', 'draftBranch', 'generateWeekOptions', 'applyDraftLive'] as $gone) {
            $this->assertStringNotContainsString($gone, $script, "Sisa implementasi lama: {$gone}");
        }
    }
}
