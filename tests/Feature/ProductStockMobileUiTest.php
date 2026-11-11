<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductStockMobileUiTest extends TestCase
{
    public function test_mobile_stock_list_matches_reference_dimensions_and_retains_live_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/products/stock.blade.php'));
        $this->assertStringContainsString('.stock-search-shell .erp-mobile-search { height:44px;', $view);
        $this->assertStringContainsString('.stock-icon { width:36px; height:36px;', $view);
        $this->assertStringContainsString('.stock-row { gap:12px; min-height:64px; padding:14px 20px;', $view);
        $this->assertStringContainsString("row.href = product.detail_url", $view);
        $this->assertStringContainsString("route('product-stock-data')", $view);
        $this->assertStringContainsString("branch: el.branch.value, stock_kategori: el.category.value", $view);
        $this->assertStringContainsString("stock_filter: el.status.value", $view);
    }

    public function test_filter_slot_does_not_close_its_body_before_the_footer(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/products/stock.blade.php'));
        $start = strpos($view, '<x-slot:body>');
        $end = strpos($view, '</x-slot:body>', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $body = substr($view, $start, $end - $start);
        preg_match_all('/<div(?:\\s[^>]*)?>|<\\/div>/', $body, $matches);
        $depth = 0;
        foreach ($matches[0] as $tag) {
            $depth += $tag === '</div>' ? -1 : 1;
            $this->assertGreaterThanOrEqual(0, $depth, 'Filter body closes an ancestor outside its slot');
        }
        $this->assertSame(0, $depth);
    }
}
