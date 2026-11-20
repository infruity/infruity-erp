<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductStockMobileUiTest extends TestCase
{
    public function test_mobile_stock_list_matches_reference_dimensions_and_retains_live_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/products/stock.blade.php'));
        $this->assertStringContainsString('.stock-search { position:relative; display:flex; flex:1; min-width:0; max-width:720px; align-items:center; height:44px;', $view);
        $this->assertStringContainsString('.stock-icon { width:36px; height:36px;', $view);
        $this->assertStringContainsString('.stock-row { gap:12px; min-height:64px; padding:14px 20px;', $view);
        $this->assertStringContainsString("row.href = product.detail_url", $view);
        $this->assertStringContainsString("route('product-stock-data')", $view);
        $this->assertStringContainsString("branch: el.branch.value, stock_kategori: el.category.value", $view);
        $this->assertStringContainsString("stock_filter: el.status.value", $view);
    }

    public function test_filter_is_the_reference_sheet_and_the_hidden_selects_keep_the_list_state(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/products/stock.blade.php'));

        foreach ([
            'stockFilterSheet(',
            'id="stock-filter-sheet"',
            'aria-label="Filter dan urutkan stok"',
            'Cari cabang...',
            'sfs__chip',
            "'stock_available'",
            '>Batal<',
            '>Terapkan<',
            'class="stock-tune"',
            // The list is still driven by these four selects.
            'id="stock-branch"', 'id="stock-status"', 'id="stock-category"', 'id="stock-sort"',
            "window.addEventListener('erp-filter-apply'",
            "new CustomEvent('erp-filter-open'",
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }

        foreach (['id="stock-filters"', 'stock-backdrop', 'stock-branch-choice', 'stock-pending-status', 'setFilterOpen', 'pendingFilters', '<x-erp.filter-sheet'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
    }
}
