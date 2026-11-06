<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionListReferenceTest extends TestCase
{
    public function test_mobile_list_hides_the_reference_omitted_row_action_and_reclaims_its_column(): void
    {
        $css = file_get_contents(public_path('assets/css/production-list-reference.css'));

        $this->assertStringContainsString('.production-list-page #production-table tbody td:nth-child(4) { display:none!important; }', $css);
        $this->assertStringContainsString('grid-template-columns:minmax(0,1fr) minmax(0,auto);', $css);
        $this->assertLessThan(strpos($css, '.production-list-page #production-table tbody td:nth-child(4) { display:none!important; }'), strpos($css, '@media(max-width:767px) {'));
    }

    public function test_list_reuses_mobile_search_and_preserves_server_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/production/index.blade.php'));

        foreach ([
            'class="production-list-page"',
            'x-erp.mobile-search',
            'data-production-search',
            'data-production-filter-toggle',
            '.select2-container, .flatpickr-calendar',
            'production-list-reference.css',
            'production-list-reference.js',
            'ProductionListUi.enhanceRow',
            'status_filter',
            'cabang_filter',
            "route('production-data')",
            "data: 'action'",
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
    }
}
