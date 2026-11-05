<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ProductionFormUiContractTest extends TestCase
{
    private function form(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/production/create.blade.php'))
            . file_get_contents(base_path('Modules/Transaction/Resources/views/production/segment/summary.blade.php'));
    }

    public function test_form_is_two_cards_with_a_docked_cost_footer(): void
    {
        $form = $this->form();

        $this->assertStringContainsString('Informasi Hasil Produksi', $form);
        $this->assertStringContainsString('Bahan Baku Terpakai', $form);
        $this->assertStringContainsString('production-form-footer', $form);
        $this->assertStringContainsString('Total Biaya', $form);
        $this->assertStringContainsString('Estimasi HPP', $form);
        $this->assertStringNotContainsString('erp-editor-summary', $form);
    }

    public function test_card_one_follows_the_reference_target_unit_and_folds_price_fields(): void
    {
        $form = $this->form();

        $this->assertStringContainsString('Target Hasil Produksi', $form);
        $this->assertStringContainsString('id="production-unit"', $form);
        $this->assertStringContainsString('<details class="production-extra"', $form);
        $this->assertStringContainsString('Harga &amp; Biaya', $form);
        // Folded fields keep every id and binding the save payload reads.
        foreach (['id="sell_price"', 'id="service_cost"', 'x-model="sellPrice"', 'x-model="serviceCost"'] as $kept) {
            $this->assertStringContainsString($kept, $form);
        }
        $this->assertStringContainsString('x-model="productionQuantity"', $form);
    }

    public function test_product_picker_shows_pilih_or_ganti_and_feeds_the_unit(): void
    {
        $js = file_get_contents(base_path('Modules/Transaction/Resources/views/production/js-create.blade.php'));

        $this->assertStringContainsString('actionLabels', $js);
        $this->assertStringContainsString("unit: item.products?.unit?.abbreviation", $js);
        $this->assertStringContainsString("production-unit", $js);
        $this->assertStringContainsString("loadRecipeIngredients(selectedProductId)", $js);
    }

    public function test_receipt_endpoint_returns_the_product_unit(): void
    {
        $controller = file_get_contents(base_path('Modules/Transaction/Http/Controllers/ProductReceiptController.php'));

        $this->assertStringContainsString("Receipt::with('products.unit')", $controller);
    }

    public function test_existing_fields_and_save_handlers_are_kept(): void
    {
        $form = $this->form();

        foreach ([
            'id="branch_id" name="branch_id"',
            'name="production_date"',
            'id="product_id"',
            'id="sell_price"',
            'id="quantity"',
            'id="service_cost"',
            'name="production_number"',
            'name="id"',
            'name="notes"',
            'x-model="notes"',
            "saveProduction('temp'",
            "saveProduction('posting'",
            'openAddIngredientModal()',
            "@include('transaction::production.segment.ingredients-cart')",
            "@include('transaction::production.segment.modal-ingredient')",
            "@include('transaction::production.segment.modal-recipe')",
        ] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
    }

    public function test_dock_is_hidden_on_the_form_because_the_footer_owns_the_bottom_edge(): void
    {
        $root = file_get_contents(base_path('resources/views/template/root.blade.php'));

        $this->assertStringContainsString("'production.create'", $root);
        $this->assertStringContainsString("'production.edit'", $root);
    }
}
