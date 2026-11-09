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

    public function test_rows_open_the_detail_popup_and_keep_full_page_fallback_and_row_actions_reachable(): void
    {
        $view = $this->listView();

        $this->assertStringContainsString('createdRow', $view);
        $this->assertStringContainsString('is-openable', $view);
        $this->assertStringContainsString("closest('a, button, .dropdown, .dropdown-menu')", $view);
        $this->assertStringContainsString('window.openProductionDetail(this.dataset.href)', $view);
        $this->assertStringContainsString('else window.location.href = this.dataset.href', $view);
        // The action menu stays visible on small screens so delete/edit are not lost.
        $this->assertStringNotContainsString('#production-table tbody td:nth-child(4) { display:none', $view);
    }

    public function test_filters_are_off_in_the_view_but_the_server_contract_is_unchanged(): void
    {
        $view = $this->listView();

        // The list no longer shows or sends filters; the endpoint still accepts them.
        foreach (['status_filter', 'cabang_filter', 'start_date', 'end_date'] as $param) {
            $this->assertStringNotContainsString($param, $view);
            $this->assertStringContainsString($param, $this->controller());
        }
        foreach (["route('production-data')", 'deleteProduct('] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
    }

    public function test_cell_markup_exposes_product_status_and_quantity_without_new_queries(): void
    {
        $controller = $this->controller();

        foreach (['production-row', 'production-product', 'production-sub', 'production-status-cell', 'production-qty'] as $class) {
            $this->assertStringContainsString($class, $controller);
        }
        $this->assertStringContainsString("route('production.detail', \$item->id)", $controller);
        // Cell markup must not trigger extra relation loads beyond what get_data already eager loads.
        // ProductIcon::resolve() reads category.name, so the category has to be eager loaded too.
        $this->assertStringContainsString("Production::with(['products', 'products.category', 'branch', 'staff', 'productionDetails', 'productionDetails.products', 'pos.customer'])", $controller);
    }

    public function test_list_replaces_pagination_with_a_load_more_button(): void
    {
        $view = $this->listView();
        $listCss = file_get_contents(public_path('assets/css/production-list-reference.css'));

        // Same pattern as the /products reference: one dashed button instead of page numbers.
        $this->assertStringContainsString('id="production-load-more"', $view);
        $this->assertStringContainsString('Muat Produk Lagi', $view);
        $this->assertStringContainsString('ph ph-arrow-circle-down', $view);
        $this->assertStringContainsString('<button type="button" class="production-load-more__button">', $view);
        // Pagination and the page-size select are gone at every breakpoint, not only below 1280px.
        $this->assertStringContainsString('#production-table_wrapper :is(.dataTables_paginate, .dataTables_length) { display:none !important; }', $view);
        // The stylesheet used to force the pagination visible with a higher-specificity !important.
        $this->assertStringNotContainsString('#production-table_wrapper .dataTables_paginate { display:block!important; }', $listCss);
        // page.len() only sets the length in DataTables 1.13, so the draw call is required.
        $this->assertStringContainsString('dataTable.page.len(Math.max(dataTable.page.len(), 50) + 50).draw()', $view);
        // Clicking reveals the next batch; the button hides once everything is already shown.
        $this->assertStringContainsString('info.end >= info.recordsDisplay', $view);
        $this->assertStringContainsString("prop('hidden', done)", $view);
        $this->assertStringContainsString('pageLength: 50,', $view);
    }

    public function test_detail_popup_avatar_glyph_stays_allowed_by_the_sanitizer(): void
    {
        $detail = file_get_contents(base_path('Modules/Transaction/Resources/views/production/show.blade.php'));
        $script = file_get_contents(public_path('assets/js/production-list-reference.js'));

        $this->assertStringContainsString('<i class="ph ph-orange"></i>', $detail);
        // The popup sanitizer drops classes it does not know, so the glyph must stay allowlisted.
        $this->assertStringContainsString("'ph-orange'", $script);
    }

    public function test_product_avatar_uses_the_shared_product_emoji_tile(): void
    {
        $controller = $this->controller();
        $view = $this->listView();

        // Same resolver the /products list uses, so the glyph matches the product itself.
        $this->assertStringContainsString('ProductIcon::resolve($item->products)', $controller);
        $this->assertStringContainsString("e(\$iconClass)", $controller);
        $this->assertStringContainsString("e(\$emoji)", $controller);
        $this->assertStringNotContainsString('ph-cooking-pot', $controller);

        // The tile carries the per-product tint the resolver returns, not one fixed colour.
        $this->assertStringContainsString('.production-avatar.bg-lime-50', $view);
        $this->assertStringContainsString('.production-avatar.bg-gray-100', $view);
        $this->assertStringContainsString('.production-avatar.bg-emerald-50', $view);
    }
}
