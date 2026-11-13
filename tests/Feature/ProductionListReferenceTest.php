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
            'production-list-reference.css',
            'production-list-reference.js',
            'ProductionListUi.enhanceRow',
            "route('production-data')",
            "data: 'action'",
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
    }

    public function test_list_has_no_filter_controls_and_keeps_search_and_add(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/production/index.blade.php'));

        foreach (['production-list-filter', 'erp-operations-filter', 'production-status-segments', 'production-branch-pill',
            'data-production-filter-toggle', 'kt_ecommerce_sales_flatpickr', 'data-kt-ecommerce-product-filter',
            'status_filter', 'cabang_filter', 'start_date', 'active-branch-button-label'] as $removed) {
            $this->assertStringNotContainsString($removed, $view, $removed);
        }
        $this->assertStringContainsString('data-production-search', $view);
        $this->assertStringContainsString("url('production/create')", $view);
    }

    public function test_production_css_has_no_low_contrast_faint_grey_or_green_status_text(): void
    {
        // production-form-reference and production-detail-reference copy the HTML reference exactly, which uses gray-400 (#9ca3af) for secondary text.
        foreach (['production-list-reference'] as $name) {
            $this->assertStringNotContainsString('#9ca3af', file_get_contents(public_path("assets/css/{$name}.css")), $name);
        }
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/production/index.blade.php'));
        $this->assertStringNotContainsString('#9ca3af', $view);
        $this->assertStringNotContainsString('color:#059669; }', file_get_contents(public_path('assets/css/production-list-reference.css')));
    }

    public function test_branch_select_boundary_matches_the_other_form_fields(): void
    {
        $css = file_get_contents(public_path('assets/css/production-form-reference.css'));

        // The branch select2 rule is !important, so it must carry the 3:1 boundary itself.
        $this->assertMatchesRegularExpression('/#branch_id \+ \.select2-container \.select2-selection--single \{[^}]*border-color:#868e98 !important;/', $css);
    }
}
