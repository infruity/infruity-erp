<?php

namespace Tests\Feature;

use Tests\TestCase;

final class SortirFilterUiContractTest extends TestCase
{
    public function test_filter_uses_searchable_branch_ids_with_draft_reset(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/sortir/index2.blade.php'));

        foreach ([
            'branches: @js($sortirFilterBranches)',
            'Cabang Tujuan',
            'x-model.debounce.150ms="branchSearch"',
            'role="radiogroup" aria-labelledby="sortir-filter-branch-label"',
            'x-for="branch in visibleBranches"',
            ':value="String(branch.id)"',
            'x-model="pending.branch"',
            'value="all"',
            "resetPendingFilter('branch')",
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }

        $this->assertStringNotContainsString('id="sortir-filter-branch"', $view);
    }

    public function test_filter_matches_reference_sections_and_keeps_existing_date_query(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/sortir/index2.blade.php'));
        $script = file_get_contents(base_path('public/assets/js/sortir-list.js'));

        $branchPosition = strpos($view, 'Cabang Tujuan');
        $statusPosition = strpos($view, 'Status Sortir');
        $datePosition = strpos($view, 'Periode Tanggal');
        $this->assertNotFalse($branchPosition);
        $this->assertNotFalse($statusPosition);
        $this->assertNotFalse($datePosition);
        $this->assertLessThan($statusPosition, $branchPosition);
        $this->assertLessThan($datePosition, $statusPosition);

        foreach ([
            "setPendingDateRange('week')",
            "setPendingDateRange('today')",
            "value:'draft',label:'Draf'",
            "value:'paid',label:'Selesai'",
            'x-model="pending.start"',
            'x-model="pending.end"',
            ':min="pending.start"',
            "resetPendingFilter('status')",
            "resetPendingFilter('date')",
            'applyFilter()',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }

        $this->assertStringContainsString('status_filter:this.filters.status,cabang_filter:this.filters.branch,start_date:this.filters.start,end_date:this.filters.end', $script);
    }

    public function test_filter_sheet_keeps_actions_outside_the_scrollable_body(): void
    {
        $styles = file_get_contents(base_path('public/assets/css/sortir-mobile.css'));

        $this->assertStringContainsString('.sortir-dialog.is-filter-dialog { display:flex;', $styles);
        $this->assertStringContainsString('.sortir-dialog.is-filter-dialog .sortir-filter-body { flex:1;', $styles);
        $this->assertStringContainsString('overflow-y:auto;', $styles);
        $this->assertStringContainsString('.sortir-dialog.is-filter-dialog .sortir-filter-foot { flex:none;', $styles);
    }

    public function test_branch_names_are_serialized_as_data_and_rendered_as_text(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/sortir/index2.blade.php'));
        $sortirFilterBranches = collect([['id' => '1', 'name' => 'GKB"><img src=x>']]);
        $html = \Illuminate\Support\Facades\Blade::render(
            '<div x-data="sortirList({ branches: @js($sortirFilterBranches) })"><span x-text="branch.name"></span></div>',
            compact('sortirFilterBranches')
        );

        $this->assertStringContainsString('branches: @js($sortirFilterBranches)', $view);
        $this->assertStringContainsString('x-text="branch.name"', $view);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_filter_presets_use_the_application_timezone(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/sortir/index2.blade.php'));

        $this->assertStringContainsString("timezone: @js(config('app.timezone'))", $view);
    }

    public function test_pointer_focus_keeps_branch_and_date_controls_free_of_native_strokes(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/sortir/index2.blade.php'));
        $styles = file_get_contents(base_path('public/assets/css/sortir-mobile.css'));

        foreach ([
            'id="sortir-date-start" type="date" x-model="pending.start"',
            'id="sortir-date-end" type="date" x-model="pending.end" :min="pending.start"',
        ] as $dateControl) {
            $this->assertStringContainsString($dateControl, $view);
        }

        $this->assertStringContainsString('#sortir-filter-dialog .sortir-branch-option:has(input[type=radio]:focus-visible) { outline:2px solid #10b981; outline-offset:-2px; }', $styles);
        $this->assertStringNotContainsString('#sortir-filter-dialog .sortir-branch-option:focus-within { outline:2px solid', $styles);
        $this->assertStringContainsString('#sortir-filter-dialog .sortir-filter-date-card input[type=date] {', $styles);
        $this->assertStringContainsString('outline:0; box-shadow:none;', $styles);
        $this->assertStringContainsString('#sortir-filter-dialog .sortir-filter-date-card input[type=date]:focus { border:0; outline:0; box-shadow:none; }', $styles);
    }
}
