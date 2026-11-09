<?php

namespace Tests\Feature;

use Tests\TestCase;

final class PosSheetsUiContractTest extends TestCase
{
    private function segment(string $name): string
    {
        return file_get_contents(base_path("Modules/Pos/Resources/views/pos/segment/{$name}.blade.php"));
    }

    public function test_pos_modals_share_one_neutral_sheet_header(): void
    {
        foreach (['modal-customer', 'modal-parcel', 'modal-gift', 'modal-jus', 'modal-product'] as $name) {
            $source = $this->segment($name);

            $this->assertStringNotContainsString('#ff000d', $source, "{$name} masih memakai header merah");
            $this->assertStringContainsString('pos-sheet-header', $source, "{$name} belum memakai pos-sheet-header");
            $this->assertStringNotContainsString('bg-primary text-white', $source);
        }
    }

    public function test_customer_modal_is_retitled_but_keeps_the_bindings_the_save_flow_reads(): void
    {
        $modal = $this->segment('modal-customer');

        $this->assertStringContainsString('Tambah Pelanggan', $modal);
        $this->assertStringContainsString('Simpan &amp; Pilih', str_replace('Simpan & Pilih', 'Simpan &amp; Pilih', $modal));
        foreach (['x-model="customerName"', 'x-model="customerPhone"', 'x-model="customerAddress"', 'saveCustomer()', 'id="customerModal"'] as $contract) {
            $this->assertStringContainsString($contract, $modal, "Hilang: {$contract}");
        }
        // Phone stays required: the server flow still demands it.
        $this->assertMatchesRegularExpression('/required[^>]*>\s*No WhatsApp|No WhatsApp[^<]*<\/label>/', $modal);
    }

    public function test_new_customer_is_saved_through_the_real_endpoint_and_selected_by_its_id(): void
    {
        $js = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-buah.blade.php'));

        $this->assertStringContainsString("fetch('/pos/customers'", $js);
        $this->assertStringContainsString('new Option(c.name, c.id, true, true)', $js);
        $this->assertStringContainsString("\$('#customer_id').append(option).trigger('change')", $js);
    }

    public function test_parcel_builder_keeps_every_id_and_handler(): void
    {
        $modal = $this->segment('modal-parcel');

        foreach ([
            'id="parcelModal"', 'id="parcelEditModal"',
            'id="parcel_qty"', 'id="parcel_budget"', 'id="select_kemasan"', 'id="kemasan_price"', 'id="parcel_jasa"',
            'id="parcel_edit_qty"', 'id="parcel_edit_budget"', 'id="select_edit_kemasan"', 'id="kemasan_edit_price"', 'id="parcel_edit_jasa"',
            'parcel-select', 'parcel-select-edit',
            'saveParcelToCart()', 'editParcelToCart(parcelId)', 'deleteParcel(parcelId)',
            'addParcel()', 'removeParcel(index)', 'updateQty(index)', 'updatePrice(index)', 'formatRupiah(totalAll)',
        ] as $contract) {
            $this->assertStringContainsString($contract, $modal, "Hilang: {$contract}");
        }
        $this->assertStringContainsString('pos-sheet-total', $modal);
        $this->assertStringContainsString('pos-sheet-row', $modal);
    }

    public function test_shared_pos_styles_load_on_every_page_that_reuses_the_segments(): void
    {
        $css = file_get_contents(base_path('public/assets/css/pos-form.css'));
        foreach (['.pos-cart-row', '.pos-form-footer', '.pos-sheet-header', '.pos-sheet-total', '.pos-card'] as $selector) {
            $this->assertStringContainsString($selector, $css, "CSS hilang: {$selector}");
        }

        // Kasir form, Pesanan "Proses" page and the Pesanan form all reuse cart/summary/modals.
        foreach ([
            'Modules/Pos/Resources/views/pos/create2.blade.php',
            'Modules/Pos/Resources/views/order-book/order.blade.php',
            'Modules/Pos/Resources/views/order-book/create.blade.php',
        ] as $view) {
            $this->assertStringContainsString("assets/css/pos-form.css", file_get_contents(base_path($view)), "{$view} tidak memuat pos-form.css");
        }
    }
    public function test_reference_add_item_views_are_opt_in_so_the_pesanan_page_keeps_its_modals(): void
    {
        foreach (['modal-product', 'modal-jus', 'modal-parcel'] as $name) {
            $source = $this->segment($name);
            $this->assertStringContainsString('@if (! empty($reference))', $source, "{$name} harus memakai flag reference");
            $this->assertStringContainsString("@include('pos::pos.segment.add-tabs'", $source, "{$name} butuh tab Buah/Parcel/Jus");
        }
        $form = file_get_contents(base_path('Modules/Pos/Resources/views/pos/create2.blade.php'));
        foreach (['modal-product', 'modal-jus', 'modal-parcel'] as $name) {
            $this->assertStringContainsString("@include('pos::pos.segment.{$name}', ['reference' => true])", $form);
        }
        $order = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/order.blade.php'));
        $this->assertStringNotContainsString("'reference' => true", $order, 'Halaman Pesanan tidak boleh ikut berubah');
    }

    public function test_reference_buah_view_has_a_searchable_product_list_and_the_reference_fields(): void
    {
        $modal = $this->segment('modal-product');

        foreach ([
            'id="pos-product-list"', 'productSearch', 'loadProductList()', 'pickProduct(row)', 'PosAddItem.stockLabel',
            'PosAddItem.priceLabel', 'PosAddItem.quantityLabel', 'PosAddItem.discountPrefix', 'Kembali', 'Tambah',
            'saveAddToCart()', ':disabled="!addProduct.id"', 'Produk tidak ditemukan',
        ] as $contract) {
            $this->assertStringContainsString($contract, $modal, "Hilang: {$contract}");
        }
        // The old select2 markup stays for the Pesanan page.
        $this->assertStringContainsString('id="select_product"', $modal);
        // Discount keeps the existing "Rp above 100, % otherwise" input handler.
        $this->assertStringContainsString('updateDiscountValue', $modal);
    }

    public function test_reference_jus_and_parcel_views_keep_their_ids_and_handlers(): void
    {
        $jus = $this->segment('modal-jus');
        foreach (['id="select_jus"', 'id="receiptContainer"', 'saveJusToCart()', 'Komposisi Produk', 'Pilih Produk Jus di Atas', 'Kembali'] as $contract) {
            $this->assertStringContainsString($contract, $jus, "Hilang: {$contract}");
        }
        $parcel = $this->segment('modal-parcel');
        foreach (['saveParcelToCart()', 'id="select_kemasan"', 'id="parcel_budget"', 'Kembali'] as $contract) {
            $this->assertStringContainsString($contract, $parcel, "Hilang: {$contract}");
        }
    }

    public function test_add_item_script_is_loaded_with_the_form_and_wires_the_list(): void
    {
        $this->assertStringContainsString('pos-add-item.js', file_get_contents(base_path('Modules/Pos/Resources/views/pos/js-create2.blade.php')));
        $js = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-buah.blade.php'));
        foreach (['loadProductList()', 'pickProduct(row)', 'posSwitchAddTab(', "document.getElementById('pos-product-list')", 'PosAddItem.listQuery'] as $contract) {
            $this->assertStringContainsString($contract, $js, "Hilang: {$contract}");
        }
    }
}
