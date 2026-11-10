<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Kasir "edit item" and the card layout of the add/edit sheets, matched to the reference.
 * Presentation only: every binding the edit flow already reads stays (editQty, editTotalFormatted, saveEditToCart...).
 */
final class PosEditItemUiContractTest extends TestCase
{
    private function segment(string $name): string
    {
        return file_get_contents(base_path("Modules/Pos/Resources/views/pos/segment/{$name}.blade.php"));
    }

    /** The markup between the n-th reference branch and its @else (or @endif). */
    private function referenceBranch(string $source, int $nth): string
    {
        $parts = explode('@if (! empty($reference))', $source);
        $this->assertArrayHasKey($nth, $parts, 'cabang reference ke-' . $nth . ' tidak ada');

        return explode('@else', $parts[$nth])[0];
    }

    private function legacyBranch(string $source, int $nth): string
    {
        $parts = explode('@if (! empty($reference))', $source);
        $branch = explode('@else', $parts[$nth], 2)[1] ?? '';

        return explode('@endif', $branch)[0];
    }

    public function test_tabs_can_be_locked_in_edit_mode_like_the_reference(): void
    {
        $tabs = $this->segment('add-tabs');

        $this->assertStringContainsString('pos-tabs__track', $tabs);
        $this->assertStringContainsString('$locked', $tabs);
        $this->assertStringContainsString('Kategori terkunci saat mode edit', $tabs);
        $this->assertStringContainsString('disabled', $tabs);
    }

    public function test_add_sheets_use_the_card_layout_with_title_subtitle_and_label(): void
    {
        $buah = $this->referenceBranch($this->segment('modal-product'), 1);
        foreach (['pos-ref-panel', 'Tambah Buah &amp; Olahan', 'Pilih produk dan tentukan kuantitas belanja', 'PILIH PRODUK', 'pos-ref-listbox'] as $contract) {
            $this->assertStringContainsString($contract, str_replace('Tambah Buah & Olahan', 'Tambah Buah &amp; Olahan', $buah), "Hilang: {$contract}");
        }
        // The search row now lives inside the list box.
        $this->assertLessThan(strpos($buah, 'id="pos-product-list"'), strpos($buah, 'productSearch'));

        $jus = $this->referenceBranch($this->segment('modal-jus'), 1);
        foreach (['pos-ref-panel', 'Tambah Jus', 'PILIH PRODUK', 'id="select_jus"', 'id="receiptContainer"'] as $contract) {
            $this->assertStringContainsString($contract, $jus, "Hilang: {$contract}");
        }
    }

    public function test_edit_buah_sheet_reuses_the_edit_bindings_in_the_reference_layout(): void
    {
        $source = $this->segment('modal-product');
        $edit = $this->referenceBranch($source, 2);

        foreach ([
            'id="editModal"', "'active' => 'buah'", "'locked' => true", 'Edit Buah', 'PILIH PRODUK',
            'x-model="editQty"', '@input="updateTotalFromEditQty"', 'updateEditDiscountValue', 'x-model="editTotalFormatted"',
            '@input="updateQtyFromEditTotalFormatted"', 'editProductName', 'editProductUnit', 'formatRupiah(editPrice)',
            '@click="closeEditModal()"', '@click="saveEditToCart()"', 'Kembali', 'Simpan', 'pos-ref-actions-bar',
        ] as $contract) {
            $this->assertStringContainsString($contract, $edit, "Hilang: {$contract}");
        }
        // Deleting happens from the cart row (with its own confirmation), like the reference.
        $this->assertStringNotContainsString('Hapus Produk', $edit);
        // The Pesanan page keeps its plain edit sheet.
        $this->assertStringContainsString('Hapus Produk', $this->legacyBranch($source, 2));
    }

    public function test_edit_jus_sheet_keeps_the_composition_container_the_script_fills(): void
    {
        $source = $this->segment('modal-jus');
        $edit = $this->referenceBranch($source, 2);

        foreach ([
            'id="editJusModal"', "'active' => 'jus'", "'locked' => true", 'Edit Jus', 'Komposisi Produk', 'id="receiptEditContainer"',
            'x-model="editQty"', '@input="updateTotalFromEditQty"', 'updateEditDiscountValue', 'x-model="editTotalFormatted"',
            '@click="closeEditJusModal()"', '@click="saveEditJusToCart()"', 'Kembali', 'Simpan',
        ] as $contract) {
            $this->assertStringContainsString($contract, $edit, "Hilang: {$contract}");
        }
        $this->assertStringNotContainsString('Hapus Produk', $edit);
        $this->assertStringContainsString('Hapus Produk', $this->legacyBranch($source, 2));
    }

    public function test_edit_sheet_only_shows_stock_when_the_item_knows_it(): void
    {
        $edit = $this->referenceBranch($this->segment('modal-product'), 2);

        // Items loaded from a saved draft may lack original_stock; never print "Habis" for them.
        $this->assertStringContainsString('editItem?.original_stock !== undefined', $edit);
        $this->assertStringContainsString('PosAddItem.stockLabel', $edit);
    }

    public function test_card_layout_styles_are_scoped_to_the_reference_sheets(): void
    {
        $css = file_get_contents(base_path('public/assets/css/pos-form.css'));

        foreach (['.pos-ref-panel', '.pos-ref-listbox', '.pos-tabs__track', '.pos-tabs__tab.is-locked', '.pos-ref-product__stock'] as $selector) {
            $this->assertStringContainsString($selector, $css, "CSS hilang: {$selector}");
        }
        // Gray page behind the cards applies to the reference sheets on phones only.
        $this->assertMatchesRegularExpression('/@media \(max-width:575\.98px\)\s*\{[^}]*\.pos-sheet--ref/', $css);
    }
}
