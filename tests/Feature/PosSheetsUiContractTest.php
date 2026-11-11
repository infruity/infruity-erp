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

    public function test_sheet_close_button_keeps_its_x_icon(): void
    {
        // .erp-sheet-header button sets a flat `background`, which wipes Bootstrap's .btn-close X image.
        $css = file_get_contents(base_path('public/assets/css/pos-form.css'));

        $this->assertMatchesRegularExpression('/\.pos-sheet-header\s+\.btn-close\s*\{[^}]*url\(/', $css);
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

    public function test_customer_modal_is_a_small_popup_with_a_visible_close_icon(): void
    {
        $modal = $this->segment('modal-customer');
        $css = file_get_contents(base_path('public/assets/css/pos-form.css'));

        // Three short fields do not need a full-screen page: a small centred popup on every screen size.
        $this->assertStringContainsString('pos-sheet--compact', $modal);
        $this->assertStringContainsString('modal-dialog-centered', $modal);
        $this->assertStringNotContainsString('modal-fullscreen-sm-down', $modal);
        // The round header button hides Bootstrap's background-image cross, so it carries its own icon.
        $this->assertStringNotContainsString('btn-close', $modal);
        $this->assertStringContainsString('<i class="ph-bold ph-x" aria-hidden="true"></i>', $modal);

        foreach ([
            '.modal .pos-sheet--compact { max-width:420px; }',
            '.modal .pos-sheet--compact .modal-content { height:auto; max-height:90dvh; border-radius:20px; }',
            '.pos-sheet--compact .erp-sheet-body { flex:none;',
            // The shared sheet rules pin dialogs to the bottom edge on phones; this one stays a popup.
            '.modal .pos-sheet--compact.modal-dialog { position:relative; inset:auto; width:calc(100% - 32px); max-width:420px; margin:0 auto; padding:0; }',
        ] as $rule) {
            $this->assertStringContainsString($rule, $css, "CSS hilang: {$rule}");
        }
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
            $referenceBranch = explode('@else', $source, 2)[0];
            $this->assertStringContainsString('@if (! empty($reference))', $source, "{$name} harus memakai flag reference");
            $this->assertStringContainsString('class="pos-add-page', $referenceBranch, "{$name} harus dirender sebagai sibling page, bukan modal");
            $this->assertStringNotContainsString('class="modal fade"', $referenceBranch, "{$name} reference tidak boleh membuat Bootstrap modal");
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
            'PosAddItem.priceUnit', 'PosAddItem.quantityUnit', 'PosAddItem.discountPrefix', 'Kembali', 'Tambah',
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
        foreach (['id="select_jus"', 'id="receiptContainer"', 'saveJusToCart()', 'Komposisi Produk', 'Pilih Produk Jus di Atas', 'Kembali', 'aria-labelledby="jusModalLabel"', 'id="jusModalLabel"'] as $contract) {
            $this->assertStringContainsString($contract, $jus, "Hilang: {$contract}");
        }
        $parcel = $this->segment('modal-parcel');
        foreach (['saveParcelToCart()', 'id="select_kemasan"', 'id="parcel_budget"', 'Kembali', 'aria-labelledby="parcelModalLabel"', 'id="parcelModalLabel"'] as $contract) {
            $this->assertStringContainsString($contract, $parcel, "Hilang: {$contract}");
        }
    }

    public function test_reference_parcel_add_keeps_the_page_open_without_requiring_hidden_service_fee(): void
    {
        $js = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-parcel.blade.php'));
        $start = strpos($js, 'saveParcelToCart() {');
        $end = strpos($js, 'editParcelToCart(parcelId)', $start);
        $save = substr($js, $start, $end - $start);

        $this->assertStringContainsString("const feeInput = document.getElementById('parcel_jasa');", $save);
        $this->assertStringContainsString("const fee = feeInput ? feeInput.value : '';", $save);
        $this->assertStringContainsString("const referenceAddPage = !!document.querySelector('.pos-add-page--parcel');", $save);
        $this->assertStringContainsString('if (feeInput) feeInput.value =', $save);
        $this->assertStringContainsString('if (!referenceAddPage)', $save);
        $this->assertStringNotContainsString("document.getElementById('parcel_jasa').value", $save);
    }

    public function test_add_item_script_is_loaded_with_the_form_and_wires_the_list(): void
    {
        $this->assertStringContainsString('pos-add-item.js', file_get_contents(base_path('Modules/Pos/Resources/views/pos/js-create2.blade.php')));
        $js = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-buah.blade.php'));
        foreach (['loadProductList()', 'pickProduct(row)', 'posSwitchAddTab(', "document.getElementById('pos-product-list')", 'PosAddItem.listQuery'] as $contract) {
            $this->assertStringContainsString($contract, $js, "Hilang: {$contract}");
        }
    }

    public function test_reference_pos_select2_dropdowns_match_the_mobile_reference(): void
    {
        $header = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-header.blade.php'));
        $css = file_get_contents(base_path('public/assets/css/pos-form.css'));

        foreach (['minimumResultsForSearch', 'pos-form--reference', 'pos-ref-customer-option', 'pos-ref-customer-group', 'select2:open', 'requestAnimationFrame', 'Cari pelanggan / nomor HP...'] as $contract) {
            $this->assertStringContainsString($contract, $header, "Dropdown reference belum: {$contract}");
        }
        foreach (['#select2-branch_id-results', '#select2-customer_id-results', '.select2-dropdown:has(#select2-branch_id-results) .select2-search--dropdown', '.pos-ref-customer-group', '.pos-ref-customer-option', 'border-radius:12px !important', 'border:2px solid #10b981 !important', 'background:#ecfdf5', 'color:#98a2b3', 'body:has(.pos-form--reference) #select2-branch_id-results .select2-results__option--selected { color:#10b981; background:#ecfdf5; }', '.pos-form--reference .pos-customer:has(.select2-container--focus),', '.pos-form--reference .pos-customer:focus-within { border-color:#10b981; box-shadow:0 0 0 1px #10b981; }', '.pos-form--reference .pos-field:has(#branch_id) .select2-selection[aria-expanded="true"],', '.pos-form--reference .pos-field:has(#branch_id) .select2-selection:focus,', '.pos-form--reference .pos-field:has(#branch_id):focus-within .select2-selection,', '.pos-form--reference .pos-field:has(#branch_id) .select2-container--open .select2-selection,', '.pos-form--reference .pos-field:has(#branch_id) .select2-container--focus .select2-selection { border:1px solid #10b981 !important; box-shadow:0 0 0 1px #10b981 !important; }'] as $contract) {
            $this->assertStringContainsString($contract, $css, "Gaya dropdown belum: {$contract}");
        }
        // The existing controls still own the submitted values; this is only a visual adapter.
        $form = file_get_contents(base_path('Modules/Pos/Resources/views/pos/create2.blade.php'));
        $this->assertStringContainsString('id="branch_id" name="branch_id"', $form);
        $this->assertStringContainsString('id="customer_id" name="customer_id"', $form);
    }

    public function test_jus_and_parcel_reference_composition_panels_have_an_empty_state(): void
    {
        $jus = $this->segment('modal-jus');
        $parcel = $this->segment('modal-parcel');
        $fruit = $this->segment('modal-product');
        $css = file_get_contents(base_path('public/assets/css/pos-form.css'));

        $this->assertStringContainsString('pos-ref-card--jus', $jus);
        $this->assertStringContainsString('pos-ref-card__hint', $jus);
        $this->assertStringContainsString('placeholder="0"', $jus);
        $this->assertStringContainsString('x-model="addProduct.formattedAddTotalInput" @input="updateQtyFromAddTotal" placeholder="0"', $fruit);
        $this->assertStringContainsString('class="erp-sheet-body {{ ! empty($reference) ? \'pos-ref-body\' : \'\' }}"', $parcel);
        $this->assertStringContainsString('Tambah Parcel', $parcel);
        $this->assertStringContainsString('pos-ref-parcel-composition', $parcel);
        $this->assertStringContainsString('Tambahkan produk parcel untuk mulai mengisi komposisi', $parcel);
        $this->assertStringContainsString('id="parcel_qty" name="parcel_qty"', $parcel);
        $this->assertStringContainsString('id="select_kemasan"', $parcel);
        $this->assertStringContainsString('.pos-ref-card--jus .pos-ref-card__empty { display:flex; flex:1; flex-direction:column;', $css);
        $this->assertStringContainsString('body:has(.modal.show .pos-sheet--ref) .modal.show .pos-sheet--ref { top:60px !important; bottom:0; height:auto !important; }', $css);
        $this->assertStringContainsString('body:has(.modal.show .pos-sheet--ref) .modal-backdrop { top:60px; bottom:0; height:auto; }', $css);
        $this->assertStringContainsString('.pos-ref-parcel-composition', $css);
    }
}
