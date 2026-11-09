<?php

namespace Tests\Feature;

use Tests\TestCase;

final class PosListUiContractTest extends TestCase
{
    private function listView(): string
    {
        return file_get_contents(base_path('Modules/Pos/Resources/views/pos/index2.blade.php'));
    }

    private function controller(): string
    {
        return file_get_contents(base_path('Modules/Pos/Http/Controllers/PosController.php'));
    }

    public function test_list_is_a_flat_row_list_instead_of_the_generic_accordion(): void
    {
        $view = $this->listView();

        $this->assertStringNotContainsString('<x-erp.collection', $view);
        $this->assertStringContainsString('@media (max-width:1279px)', $view);
        $this->assertStringContainsString('#pos-table thead { display:none; }', $view);
        $this->assertStringContainsString('pos-list-empty', $view);
    }

    public function test_rows_open_the_detail_page_and_keep_the_action_menu_reachable(): void
    {
        $view = $this->listView();

        $this->assertStringContainsString('createdRow', $view);
        $this->assertStringContainsString('is-openable', $view);
        $this->assertStringContainsString("closest('a, button, .dropdown, .dropdown-menu')", $view);
        $this->assertStringNotContainsString('#pos-table tbody td:nth-child(3) { display:none', $view);
    }

    public function test_filters_and_data_endpoint_contract_are_unchanged(): void
    {
        $view = $this->listView();

        foreach (['status_filter', 'cabang_filter', 'start_date', 'end_date', "route('pos-data')", 'deleteProduct(', 'showStockAlert('] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
        // The filter sheet feeds the same request fields: status, branch and a date range.
        foreach (['d.status_filter = applied.status', 'd.cabang_filter = applied.branch', 'd.start_date = start', 'd.end_date = end', 'data-pos-status="{{ $value }}"', 'id="pos-start-date"', 'id="pos-end-date"'] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
    }

    public function test_cell_markup_shows_customer_time_status_and_amounts_without_new_queries(): void
    {
        $controller = $this->controller();

        foreach (['pos-list-row', 'pos-list-customer', 'pos-list-sub', 'pos-list-status-cell', 'pos-list-status', 'pos-list-total', 'pos-list-paid'] as $class) {
            $this->assertStringContainsString($class, $controller, "Kelas hilang: {$class}");
        }
        // Same eager loading as before: no per-row relation queries are introduced.
        $this->assertStringContainsString("PosModel::with('customer', 'paymentDetails', 'details.product:id', 'branch', 'user')", $controller);
        // Status mapping and the row actions keep their rules.
        $this->assertStringContainsString("route('pos.payment', \$item->id)", $controller);
        $this->assertStringContainsString("'canceled' => ['label' => 'Dihapus'", $controller);
    }
    public function test_list_follows_the_reference_toolbar_filter_sheet_and_load_more(): void
    {
        $view = $this->listView();

        // Pill search with the filter trigger inside it, like the reference list.
        foreach (['<x-erp.mobile-search', 'erp-mobile-search__filter', 'placeholder="Cari..."', 'Tambah Transaksi'] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
        // Draft/apply sheet with the reference sections and quick ranges.
        foreach (['Filter Transaksi', 'Status Transaksi', 'Periode Tanggal', 'Pilihan Cepat', 'Minggu Ini', 'Hari Ini', 'Reset Filter', 'Terapkan Filter'] as $label) {
            $this->assertStringContainsString($label, $view);
        }
        // Load more replaces the pager; the sheet no longer posts the old Metronic selects.
        // Alpine initialises before DataTables, so the hooks must resolve lazily.
        $this->assertStringContainsString('(applied) => window.posApplyFilters(applied)', $view);
        $this->assertStringContainsString('<x-erp.load-more', $view);
        $this->assertStringContainsString('PosListUi.nextLength', $view);
        $this->assertStringNotContainsString('kt_ecommerce_sales_flatpickr', $view);
        $this->assertStringNotContainsString('lengthMenu', $view);
        // Reference columns and avatar tile.
        foreach (['Pelanggan', 'Total &amp; Status', 'PosListUi.customerInitial'] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }
    }
    public function test_rows_have_no_action_menu_like_the_reference(): void
    {
        $view = $this->listView();

        // The reference list opens a row on tap and has no per-row action button.
        foreach (["data: 'action'", 'dropstart', 'dtfc-fixed-right', 'fixedColumns', 'visually-hidden">Aksi', 'show.bs.dropdown'] as $removed) {
            $this->assertStringNotContainsString($removed, $view, "Masih ada sisa menu aksi: {$removed}");
        }
        $this->assertSame(2, substr_count($view, '<th '), 'Header hanya Pelanggan dan Total & Status');
        $this->assertStringContainsString('grid-template-columns:minmax(0,1fr) auto;', $view);
    }
}
