<?php

namespace Tests\Feature;

use App\User;
use Modules\Pos\Entities\OrderBook;
use Tests\TestCase;

final class OrderBookFormUiContractTest extends TestCase
{
    private function read(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function form(): string
    {
        return $this->read('Modules/Pos/Resources/views/order-book/create.blade.php');
    }

    public function test_form_is_an_information_card_plus_a_notes_card_with_a_docked_footer(): void
    {
        $form = $this->form();

        $this->assertStringContainsString('Informasi Pesanan', $form);
        $this->assertStringContainsString('Catatan Pesanan', $form);
        $this->assertStringContainsString('class="pos-form"', $form);
        $this->assertStringContainsString('pos-form-footer', $form);
        $this->assertStringNotContainsString('erp-editor-summary', $form);
        $this->assertStringContainsString('karakter', $form);
    }

    public function test_every_field_and_save_path_the_script_reads_is_kept(): void
    {
        $form = $this->form();

        foreach ([
            'name="branch_id" id="branch_id"',
            'id="customer_id" name="customer_id"',
            'id="tier_id"',
            'name="date"',
            'name="invoice_number"',
            'readonly',
            'name="note" id="note"',
            '{{ $data->note ?? \'\' }}',
            'addCustomer()',
            "saveDraft(() => loading = false)",
            "saveTransaction(() => loading = false)",
            "@include('pos::pos.segment.modal-customer')",
            "url('/order-book')",
        ] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
        // The script still reads the note and branch straight from the DOM.
        $this->assertStringContainsString("document.querySelector('textarea[name=\"note\"]')", $form);
        $this->assertStringContainsString("document.querySelector('select[name=\"branch_id\"]')", $form);
    }

    public function test_list_endpoint_exposes_and_searches_the_creator_without_leaking_the_user_record(): void
    {
        $controller = $this->read('Modules/Pos/Http/Controllers/OrderBookController.php');

        $this->assertStringContainsString("OrderBook::with('customer', 'pos', 'branch', 'user:id_user,nm_user')", $controller);
        $this->assertStringContainsString("orWhereHas('user'", $controller);
        $this->assertStringContainsString("->addColumn('created_by_name'", $controller);
        $this->assertStringContainsString("->removeColumn('user')", $controller);
    }

    public function test_order_book_creator_relation_resolves_the_actual_user_model_and_key(): void
    {
        $relation = (new OrderBook())->user();

        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('created_by', $relation->getForeignKeyName());
        $this->assertSame('id_user', $relation->getOwnerKeyName());
    }

    public function test_dock_is_hidden_on_the_form_routes(): void
    {
        $root = $this->read('resources/views/template/root.blade.php');

        $this->assertStringContainsString("'order-book.create'", $root);
        $this->assertStringContainsString("'order-book.edit'", $root);
    }
}
