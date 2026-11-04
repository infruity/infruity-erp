<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionListUiContractTest extends TestCase
{
    private function listView(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/production/index.blade.php'));
    }

    private function controller(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Http/Controllers/ProductionController.php'));
    }

    public function test_list_is_a_flat_row_list_instead_of_the_generic_accordion(): void
    {
        $view = $this->listView();

        $this->assertStringNotContainsString('data-erp-collection', $view);
        $this->assertStringContainsString('@media (max-width:1279px)', $view);
        $this->assertStringContainsString('#production-table thead { display:none; }', $view);
        $this->assertStringContainsString('production-empty-state', $view);
    }

    public function test_rows_open_the_existing_detail_page_and_keep_row_actions_reachable(): void
    {
        $view = $this->listView();

        $this->assertStringContainsString('createdRow', $view);
        $this->assertStringContainsString('is-openable', $view);
        $this->assertStringContainsString("closest('a, button, .dropdown, .dropdown-menu')", $view);
        // The action menu stays visible on small screens so delete/edit are not lost.
        $this->assertStringNotContainsString('#production-table tbody td:nth-child(4) { display:none', $view);
    }

    public function test_existing_filters_and_server_contract_are_unchanged(): void
    {
        $view = $this->listView();

        foreach (['status_filter', 'cabang_filter', 'start_date', 'end_date', "route('production-data')", 'deleteProduct('] as $contract) {
            $this->assertStringContainsString($contract, $view . $this->controller());
        }
        $this->assertStringContainsString("data-kt-ecommerce-product-filter=\"status\"", $view);
        $this->assertStringContainsString("data-kt-ecommerce-product-filter=\"cabang\"", $view);
    }

    public function test_cell_markup_exposes_product_status_and_quantity_without_new_queries(): void
    {
        $controller = $this->controller();

        foreach (['production-row', 'production-product', 'production-sub', 'production-status-cell', 'production-qty'] as $class) {
            $this->assertStringContainsString($class, $controller);
        }
        $this->assertStringContainsString("route('production.detail', \$item->id)", $controller);
        // Cell markup must not trigger extra relation loads beyond what get_data already eager loads.
        $this->assertStringContainsString("Production::with(['products', 'branch', 'staff', 'productionDetails', 'productionDetails.products', 'pos.customer'])", $controller);
    }
}
