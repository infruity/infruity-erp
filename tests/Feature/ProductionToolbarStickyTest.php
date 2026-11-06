<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionToolbarStickyTest extends TestCase
{
    public function test_mobile_production_search_toolbar_stays_pinned_over_list_scrolling(): void
    {
        $css = file_get_contents(public_path('assets/css/production-list-reference.css'));
        $mobileRule = '.production-list-page .erp-workspace-toolbar { position:sticky; top:0; z-index:25; display:block; padding:16px; background:#fff;';
        $filterOverride = 'body.erp-ui .production-list-page .erp-workspace-toolbar:has(details.erp-operations-filter #kt_ecommerce_sales_flatpickr) { position:sticky; top:0; z-index:25; background:#fff;';

        $this->assertStringContainsString($mobileRule, $css);
        $this->assertStringContainsString($filterOverride, $css);
        $this->assertLessThan(
            strpos($css, $mobileRule),
            strpos($css, '@media(max-width:767px) {')
        );
    }
}
