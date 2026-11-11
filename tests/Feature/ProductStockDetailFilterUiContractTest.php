<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductStockDetailFilterUiContractTest extends TestCase
{
    private function pageSource(): string
    {
        return file_get_contents(base_path('Modules/Master/Resources/views/products/stock-show.blade.php'));
    }

    public function test_history_filter_is_the_shared_sheet_with_search_direction_source_and_period(): void
    {
        $view = $this->pageSource();

        foreach ([
            '<x-erp.filter-sheet',
            'id="stock-detail"',
            'title="Filter Riwayat Stok"',
            'search-label="Cari aktivitas"',
            'branch-label="Cabang"',
            "'key' => 'direction'",
            "'in' => 'Masuk', 'out' => 'Keluar'",
            "'key' => 'reference'",
            "'stock-opname' => 'Stock Opname'",
            "'wholesale' => 'Pengadaan'",
            ":quick-ranges=\"['week', 'today', 'month']\"",
            ':timezone="config(\'app.timezone\')"',
            'desktop="modal"',
            'reset-applies',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }

        // The old sheet, its select2/flatpickr state holders and styles are gone.
        foreach (['stock-detail-filter-backdrop', 'stock-detail-direction', 'stock-detail-date-start', 'stockStartDatePicker', 'stock-detail-filter-mobile-controls', 'data-stock-reference', 'data-stock-date-preset'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
        $this->assertStringContainsString('id="stock-detail-filter-toggle"', $view);
        $this->assertStringContainsString('id="stock-detail-filter-dot"', $view);
    }

    public function test_the_table_requests_the_same_fields_from_the_page_filters(): void
    {
        $view = $this->pageSource();

        foreach (['d.branch = stockFilters.branch', 'd.reference = stockFilters.reference', 'd.stock_direction = stockFilters.direction', 'd.start_date = stockFilters.start', 'd.end_date = stockFilters.end', 'search:{search:stockFilters.search}'] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }
        $this->assertStringContainsString("route('product-stock-data-show')", $view);
        $this->assertStringContainsString("route('product-transaction-data')", $view);
    }

    public function test_the_page_opens_the_sheet_per_tab_and_listens_for_apply_and_close(): void
    {
        $view = $this->pageSource();

        $this->assertStringContainsString("new CustomEvent('erp-filter-open', { detail: {", $view);
        $this->assertStringContainsString("id: 'stock-detail'", $view);
        $this->assertStringContainsString("'Filter Riwayat HPP' : 'Filter Riwayat Stok'", $view);
        $this->assertStringContainsString("window.addEventListener('erp-filter-apply'", $view);
        $this->assertStringContainsString("window.addEventListener('erp-filter-close'", $view);
        $this->assertStringContainsString("\$('#stock-detail-filter-toggle').on('click', openStockDetailFilters)", $view);
    }
}
