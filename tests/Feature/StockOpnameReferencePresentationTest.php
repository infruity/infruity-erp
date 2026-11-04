<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameReferencePresentationTest extends TestCase
{
    public function test_requested_states_are_wired_without_replacing_server_contracts(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
        foreach (['stock-opname-reference.css', 'stock-opname-reference.js', 'x-erp.mobile-search',
            'so-filter-sheet', 'so-history-filter', 'so-detail-correction', 'data-so-history-toggle',
            'data-so-field="created"', 'data-so-field="creator"'] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
        $this->assertStringContainsString('name="real_stock"', $source);
        $this->assertStringContainsString("route('stock-opname.data')", $source);
        $this->assertStringContainsString('/stock-opname/${id}/finalize', $source);
    }

    public function test_filter_does_not_offer_unsupported_server_filters_as_working_controls(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/partials/reference-filters.blade.php'));
        $this->assertStringContainsString('Hasil Opname', $source);
        $this->assertStringContainsString('Status Opname', $source);
        $this->assertStringContainsString('disabled aria-describedby="so-filter-limit"', $source);
        $this->assertStringContainsString('so-filter-draft-branch', $source);
        $this->assertStringNotContainsString('data-kt-ecommerce-product-filter="cabang"', $source);
    }
}
