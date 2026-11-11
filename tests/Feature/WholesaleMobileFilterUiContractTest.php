<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class WholesaleMobileFilterUiContractTest extends TestCase
{
    private function wholesale(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/index.blade.php'));
    }

    private function sheet(): string
    {
        return Blade::render(
            '<x-erp.filter-sheet id="wholesale" title="Filter Pengadaan" aria-label="Filter pengadaan" branch-label="Cabang Tujuan" status-label="Status Pengadaan" :branches="$branches" :show-branch-all="true" :statuses="$statuses" />',
            [
                'branches' => [(object) ['id' => 1, 'name' => 'toko gkb'], (object) ['id' => 2, 'name' => 'toko gba']],
                'statuses' => ['all' => 'Semua Status', 'draft' => 'Pending'],
            ]
        );
    }

    public function test_wholesale_uses_the_shared_sheet_instead_of_its_own_mobile_markup(): void
    {
        $view = $this->wholesale();

        $this->assertStringContainsString('<x-erp.filter-sheet', $view);
        $this->assertStringContainsString('id="wholesale"', $view);
        // The calendar, branch list and draft state now live in the component.
        foreach (['wholesale-mobile-calendar', 'mobileStartDate', 'mobileBranch', 'filterWholesaleBranches', 'toggleFilters()'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
    }

    public function test_the_page_opens_the_sheet_with_its_applied_values_and_listens_for_apply(): void
    {
        $view = $this->wholesale();

        $this->assertStringContainsString("this.\$dispatch('erp-filter-open', { id: 'wholesale'", $view);
        foreach (['branch', 'status', 'start', 'end'] as $filter) {
            $this->assertStringContainsString("getWholesaleFilter('{$filter}')", $view);
        }
        $this->assertStringContainsString('@erp-filter-apply.window', $view);
        $this->assertStringContainsString('applyWholesaleMobileFilters($event.detail.values.status, $event.detail.values.branch, $event.detail.values.start, $event.detail.values.end)', $view);
    }

    public function test_applied_dates_still_reach_the_server_through_the_page_filters(): void
    {
        $view = $this->wholesale();

        $this->assertStringContainsString("\$('[data-wholesale-filter=\"start\"]').val(start || '');", $view);
        $this->assertStringContainsString("\$('[data-wholesale-filter=\"end\"]').val(end || '');", $view);
        $this->assertStringContainsString("data.start_date = getWholesaleFilter('start') || null;", $view);
        $this->assertStringContainsString("data.end_date = getWholesaleFilter('end') || null;", $view);
    }

    public function test_sheet_renders_branch_status_and_date_sections_with_the_page_labels(): void
    {
        $html = $this->sheet();

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-label="Filter pengadaan"', $html);
        $this->assertStringContainsString('Filter Pengadaan', $html);
        $this->assertStringContainsString('Cabang Tujuan', $html);
        $this->assertStringContainsString('Status Pengadaan', $html);
        $this->assertStringContainsString('Semua Status', $html);
        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('Periode Tanggal', $html);
        $this->assertStringContainsString('Pilihan Cepat', $html);
        $this->assertStringContainsString("setDateRange('week')", $html);
        $this->assertStringContainsString("setDateRange('today')", $html);
        $this->assertStringContainsString('Terapkan', $html);
    }

    public function test_sheet_keeps_the_hooks_the_filter_stylesheet_targets(): void
    {
        $html = $this->sheet();

        foreach (['id="wholesale-mobile-branch-search"', 'id="wholesale-mobile-branches"', 'data-wholesale-mobile-branch="all"', 'id="wholesale-mobile-calendar"'] as $hook) {
            $this->assertStringContainsString($hook, $html, "Hook hilang: {$hook}");
        }
        // Dates are a draft: the hidden inputs carry no page-level filter hook.
        foreach (['wholesale-mobile-start-date', 'wholesale-mobile-end-date'] as $id) {
            $this->assertMatchesRegularExpression('/<input id="' . $id . '" type="hidden"(?![^>]*data-wholesale-filter)/', $html);
        }
    }

    public function test_sheet_state_is_seeded_with_the_branches_of_the_page(): void
    {
        $html = $this->sheet();

        $this->assertStringContainsString('erpFilterSheet(', $html);
        $this->assertStringContainsString('Toko Gkb', $html);
        $this->assertStringContainsString('Toko Gba', $html);
        $this->assertStringContainsString('assets/js/erp-filter-sheet.js', $html);
    }
}
