<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductStockDetailLoadMoreTest extends TestCase
{
    public function test_load_more_follows_history_inside_the_same_scroll_container(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/products/stock-show.blade.php'));
        $this->assertMatchesRegularExpression('/<div class="stock-detail-scroll">\s*<div id="stock-mobile-list"[^>]*><\/div>\s*<div class="stock-detail-load-more" id="stock-detail-load-more"/s', $view);
        $this->assertStringContainsString('.stock-detail-scroll {', $view);
        $this->assertMatchesRegularExpression('/\.stock-detail-scroll\s*\{[^}]*overflow-y:auto/s', $view);
        $this->assertStringContainsString('list.replaceChildren()', $view);
        $this->assertStringContainsString('dataTable.page.len(Math.min(page.recordsDisplay, page.length + 50)).draw(\'page\')', $view);
    }
}
