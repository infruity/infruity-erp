<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameFormUiContractTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
    }

    private function form(): string
    {
        $source = $this->source();
        $start = strpos($source, 'id="kt_modal_add_customer"');
        $end = strpos($source, 'id="kt_modal_stock_detail"');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($source, $start, $end - $start);
    }

    public function test_form_shows_system_stock_hpp_and_a_live_difference_preview(): void
    {
        $form = $this->form();

        foreach ([
            'so-form-panel',
            'Stok Sistem',
            'HPP / Satuan',
            'id="stock-hpp-value"',
            'so-form-diff',
            'id="stock-difference-label"',
            'id="stock-difference-text"',
            'id="stock-difference-value"',
            'Dampak Finansial',
        ] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
    }

    public function test_existing_fields_ids_and_submit_flow_are_kept(): void
    {
        $form = $this->form();

        foreach ([
            'id="kt_modal_add_customer_form"',
            'name="date"',
            'name="branch_id" id="branch_id"',
            'name="product_id"',
            'name="quantity"',
            'readonly',
            'name="real_stock"',
            'id="detail-qty-container"',
            'onclick="addDetailRow()"',
            'name="note"',
            'id="kt_modal_add_customer_submit"',
            'indicator-label',
        ] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
    }

    public function test_summary_function_fills_the_new_indicator_and_hpp(): void
    {
        $source = $this->source();

        $this->assertStringContainsString("\$('#stock-hpp-value')", $source);
        $this->assertStringContainsString("\$('#stock-difference-label')", $source);
        $this->assertStringContainsString("\$('.so-form-diff')", $source);
        // The server contract for saving is untouched.
        $this->assertStringContainsString("route('stock-opname.data')", $source);
    }
}
