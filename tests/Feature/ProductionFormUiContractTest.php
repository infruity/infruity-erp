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

    public function test_save_validates_product_then_branch_then_quantity_then_ingredients(): void
    {
        $js = file_get_contents(base_path('Modules/Transaction/Resources/views/production/js-create.blade.php'));
        $start = strpos($js, 'async saveProduction(');
        $body = substr($js, $start, 2600);

        $order = ['Pilih produk terlebih dahulu', 'Pilih cabang terlebih dahulu', 'Jumlah produksi harus lebih dari 0', 'Jumlah produksi maksimal 9.999', 'Minimal harus ada 1 bahan baku'];
        $last = -1;
        foreach ($order as $message) {
            $pos = strpos($body, $message);
            $this->assertNotFalse($pos, $message);
            $this->assertGreaterThan($last, $pos, $message . ' is out of order');
            $last = $pos;
        }
        // Branch is only required when the select actually offers a branch to pick.
        $this->assertStringContainsString("branchSelect.options.length > 1", $body);
    }

    public function test_quantity_input_declares_the_same_upper_bound(): void
    {
        $this->assertStringContainsString('max="9999"', file_get_contents(base_path('Modules/Transaction/Resources/views/production/create.blade.php')));
    }

    public function test_resuming_a_temp_draft_preselects_its_product_and_says_so(): void
    {
        $controller = file_get_contents(base_path('Modules/Transaction/Http/Controllers/ProductionController.php'));
        $create = substr($controller, strpos($controller, 'public function create()'), 1700);

        // The draft's quantity, branch and ingredients were restored but not its product, so re-picking it
        // reloaded the recipe over the draft. Restore the product too.
        foreach (['edit_product_id', 'edit_product_name', 'edit_sell_price', 'edit_service_cost'] as $key) {
            $this->assertStringContainsString("\$data['{$key}']", $create, $key);
        }

        $form = file_get_contents(base_path('Modules/Transaction/Resources/views/production/create.blade.php'));
        $this->assertStringContainsString('production-resume-note', $form);
        $this->assertStringContainsString('Melanjutkan draf sementara', $form);
        $css = file_get_contents(public_path('assets/css/production-form-reference.css'));
        $this->assertStringContainsString('production-resume-note', $css);
        $this->assertMatchesRegularExpression('/\.production-create-reference \.production-resume-note\\s*\\{[^}]*position:\s*fixed;[^}]*z-index:\s*1040;/s', $css);
    }
}
