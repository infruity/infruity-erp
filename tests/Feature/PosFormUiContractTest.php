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

    public function test_form_is_a_reference_transaction_card_plus_a_shopping_list_card(): void
    {
        $form = $this->form();

        $this->assertStringContainsString('Daftar Belanja', $form);
        $this->assertStringContainsString('class="pos-form pos-form--reference"', $form);
        $this->assertStringNotContainsString('erp-editor-summary', $form);
        // The first card only asks for branch and customer, like the reference.
        $this->assertStringNotContainsString('Informasi Transaksi', $form);
        foreach (['ph-storefront', 'Cabang Transaksi', 'ph-user', 'Pelanggan', '+ Tambah'] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
        $this->assertStringNotContainsString('<span>Baru</span>', $form, 'Tombol tambah pelanggan hanya ikon +');
    }

    public function test_add_item_button_enters_a_same_page_add_view_without_a_dropdown_or_modal(): void
    {
        $form = $this->form();
        $button = substr($form, strpos($form, '<section class="pos-card pos-card--list"'), 800);
        $css = $this->read('public/assets/css/pos-form.css');
        $fruit = $this->read('Modules/Pos/Resources/views/pos/segment/modal-product.blade.php');
        $referenceBranch = explode('@else', $fruit, 2)[0];

        $this->assertStringContainsString('class="pos-add" @click="openAddModal()"', $button);
        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $button);
        $this->assertStringNotContainsString('<ul class="dropdown-menu dropdown-menu-end">', $button);
        $this->assertStringContainsString('class="pos-add-page', $referenceBranch);
        $this->assertStringNotContainsString('class="modal fade"', $referenceBranch);
        $this->assertStringContainsString('.pos-add-page {', $css);
        $this->assertStringContainsString('position:fixed;', $css);
    }

    public function test_all_add_category_controls_remain_inside_the_pos_alpine_scope(): void
    {
        $html = '<!doctype html><html><body><div id="pos-test-root" x-data="posApp()">'
            . view('pos::pos.segment.modal-product', ['reference' => true])->render()
            . view('pos::pos.segment.modal-parcel', ['reference' => true])->render()
            . view('pos::pos.segment.modal-jus', ['reference' => true])->render()
            . '</div></body></html>';
        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        foreach (['addModal', 'parcelModal', 'jusModal'] as $id) {
            $this->assertSame(
                1,
                $xpath->query("//*[@id='{$id}' and ancestor::*[@id='pos-test-root']]")->length,
                "{$id} must be a descendant of posApp(), otherwise its Alpine state and buttons cannot work."
            );
        }
    }

    public function test_reference_add_page_back_returns_to_form_while_normal_back_keeps_route(): void
    {
        $topbar = $this->read('resources/views/components/erp/topbar.blade.php');
        $js = $this->read('Modules/Pos/Resources/views/pos/js/js-buah.blade.php');

        $this->assertStringContainsString('data-pos-create-back', $topbar);
        $this->assertStringContainsString('closeAddPage()', $js);
        $this->assertStringContainsString('showAddPage: false', $js);
        $this->assertStringContainsString('activeAddCategory', $js);
    }

    public function test_reference_category_tabs_switch_the_same_page_without_modal_lifecycle(): void
    {
        $js = $this->read('Modules/Pos/Resources/views/pos/js/js-buah.blade.php');
        $css = $this->read('public/assets/css/pos-form.css');

        $this->assertMatchesRegularExpression(
            "/if \\(document\\.querySelector\\('\\.pos-add-page'\\)\\) \\{\\s*app\\.switchAddPageTab\\(target\\);\\s*return;\\s*\\}/",
            $js
        );
        $this->assertStringContainsString('setTimeout(() => app[openers[target]](), 350)', $js, 'Legacy modal flow remains for sibling pages.');
        $this->assertStringContainsString('.pos-add-page[x-cloak] { display:none !important; }', $css);
    }

    public function test_focused_branch_picker_has_the_same_green_outline_as_customer(): void
    {
        $css = $this->read('public/assets/css/pos-form.css');
        $this->assertStringContainsString(
            '.pos-form--reference .pos-field:has(#branch_id):focus-within .select2-selection',
            $css
        );
        $this->assertStringContainsString('border:1px solid var(--erp-active-border) !important; box-shadow:var(--erp-active-ring) !important;', $css);
    }

    public function test_existing_fields_and_item_entry_points_are_kept(): void
    {
        $form = $this->form();
        $both = $form . $this->summary();

        foreach ([
            'id="branch_id" name="branch_id"',
            'id="customer_id" name="customer_id"',
            'id="tier_id"',
            'addCustomer()',
            'openAddModal()',
            "@include('pos::pos.segment.cart')",
            "@include('pos::pos.segment.ringkasan')",
            "@include('pos::pos.segment.modal-product', ['reference' => true])",
            "@include('pos::pos.segment.modal-customer')",
            "@include('pos::pos.segment.modal-gift')",
            "@include('pos::pos.segment.modal-parcel', ['reference' => true])",
            "@include('pos::pos.segment.modal-jus', ['reference' => true])",
        ] as $contract) {
            $this->assertStringContainsString($contract, $form, "Hilang: {$contract}");
        }
        // Process branch, date and invoice number are still submitted; they live in the summary sheet.
        foreach (['id="branch_process_id" name="branch_process_id"', 'name="date"', 'name="invoice_number"', 'readonly'] as $contract) {
            $this->assertStringContainsString($contract, $both, "Hilang: {$contract}");
            $this->assertStringNotContainsString($contract, $form, "Harus pindah ke ringkasan: {$contract}");
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
            'Belum ada produk didaftar belanja',
            'Klik tombol',
            'Diskon -',
            'ph-trash',
            'deleteFromCart()',
        ] as $contract) {
            $this->assertStringContainsString($contract, $cart, "Hilang: {$contract}");
        }
        $this->assertStringNotContainsString('ph-shopping-cart', $cart, 'Empty state reference has no icon');
        $this->assertStringNotContainsString('<button type="button" class="pos-cart-row', $cart, 'Row holds a delete button, so it cannot be a button itself');
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
            'Total Belanja',
            "route('pos.index')",
            'ph-bookmark-simple',
            'sweepOpen',
            'Diskon Nota',
            'Ongkos Kirim',
            'Subtotal Item',
            'Diskon Transaksi',
            ':disabled="loading || cart.length === 0"',
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

    public function test_pos_create_mobile_topbar_returns_to_the_pos_list(): void
    {
        $topbar = $this->read('resources/views/components/erp/topbar.blade.php');

        $this->assertStringContainsString(<<<'BLADE'
$posCreateHeader = request()->routeIs('pos.create');
BLADE, $topbar);
        $this->assertStringContainsString(<<<'BLADE'
$transactionBackRoute = $posCreateHeader ? 'pos.index' :
BLADE, $topbar);
        $this->assertStringContainsString(<<<'BLADE'
$transactionBackLabel = $posCreateHeader ? 'Kembali ke Kasir / POS' :
BLADE, $topbar);
        $this->assertStringContainsString('href="{{ route($mobileBackRoute) }}" aria-label="{{ $mobileBackLabel }}"', $topbar);
        $this->assertStringContainsString('<i class="ph-bold ph-caret-left" aria-hidden="true"></i>', $topbar);
        $this->assertStringContainsString('<button type="button" data-erp-sidebar-toggle aria-label="Buka navigasi"', $topbar);
    }
}
