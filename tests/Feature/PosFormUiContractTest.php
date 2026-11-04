<?php

namespace Tests\Feature;

use Tests\TestCase;

final class PosFormUiContractTest extends TestCase
{
    private function read(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function form(): string
    {
        return $this->read('Modules/Pos/Resources/views/pos/create2.blade.php');
    }

    private function cart(): string
    {
        return $this->read('Modules/Pos/Resources/views/pos/segment/cart.blade.php');
    }

    private function summary(): string
    {
        return $this->read('Modules/Pos/Resources/views/pos/segment/ringkasan.blade.php');
    }

    public function test_form_is_a_transaction_card_plus_a_shopping_list_card(): void
    {
        $form = $this->form();

        $this->assertStringContainsString('Informasi Transaksi', $form);
        $this->assertStringContainsString('Daftar Belanja', $form);
        $this->assertStringContainsString('class="pos-form"', $form);
        $this->assertStringNotContainsString('erp-editor-summary', $form);
    }

    public function test_existing_fields_and_item_entry_points_are_kept(): void
    {
        $form = $this->form();

        foreach ([
            'id="branch_id" name="branch_id"',
            'id="branch_process_id" name="branch_process_id"',
            'id="customer_id" name="customer_id"',
            'id="tier_id"',
            'name="date"',
            'name="invoice_number"',
            'readonly',
            'addCustomer()',
            'openAddModal()',
            'openParcelModal()',
            'openJusModal()',
            "@include('pos::pos.segment.cart')",
            "@include('pos::pos.segment.ringkasan')",
            "@include('pos::pos.segment.modal-product')",
            "@include('pos::pos.segment.modal-customer')",
            "@include('pos::pos.segment.modal-gift')",
            "@include('pos::pos.segment.modal-parcel')",
            "@include('pos::pos.segment.modal-jus')",
        ] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
    }

    public function test_cart_rows_show_discount_and_keep_every_edit_handler_and_total_rule(): void
    {
        $cart = $this->cart();

        foreach ([
            'pos-cart-row',
            'openEditParcelModal(item)',
            'openEditJusModal(item)',
            'openEditModal(item)',
            'item.discount > 0',
            'item.total_input',
            'id="cart-items-container"',
        ] as $contract) {
            $this->assertStringContainsString($contract, $cart, "Hilang: {$contract}");
        }
        // Row total keeps the same rule as before (total_input wins, else price*qty-discount).
        $this->assertStringContainsString('(item.total_input || ((item.price * item.qty) - item.discount))', $cart);
    }

    public function test_summary_keeps_all_fields_and_a_docked_footer_owns_total_and_actions(): void
    {
        $summary = $this->summary();

        foreach ([
            'id="collapseTotal"',
            'updateDiskonGlobal',
            'name="note"',
            'id="address_id"',
            'id="courier_id"',
            'name="ongkir_date"',
            'updateOngkirGlobal',
            'updateDiskonOngkir',
            'saveTransaction(',
            'goToPayment(',
            'x-text="totalProduk"',
            'formatRupiah(totalHargaKeseluruhan)',
            'pos-form-footer',
            'id="modal_tambah_alamat"',
        ] as $contract) {
            $this->assertStringContainsString($contract, $summary, "Hilang: {$contract}");
        }

        // The address modal must not live inside the sticky footer.
        $footerEnd = strpos($summary, '<!-- /pos-form-footer -->');
        $this->assertNotFalse($footerEnd);
        $this->assertGreaterThan($footerEnd, strpos($summary, 'id="modal_tambah_alamat"'));
    }

    public function test_summary_accordion_stays_visible_when_open_under_the_tailwind_layout(): void
    {
        // Tailwind's `.collapse` utility is `visibility: collapse`; it hides an open Bootstrap collapse body.
        $css = $this->read('public/assets/css/pos-form.css');

        $this->assertMatchesRegularExpression('/\.pos-card\s+\.accordion-collapse\.collapse\.show\s*\{[^}]*visibility:\s*visible/', $css);
    }

    public function test_dock_is_hidden_on_the_form_routes(): void
    {
        $root = $this->read('resources/views/template/root.blade.php');

        $this->assertStringContainsString("'pos.create'", $root);
        $this->assertStringContainsString("'pos.edit'", $root);
    }
}
