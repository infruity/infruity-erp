<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Pemilih cabang halaman pengadaan (mode ubah/buat) memakai ErpPicker, bukan select2. */
class WholesaleEditBranchArrowTest extends TestCase
{
    private function source(string $file = 'wholesale/create2.blade.php'): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/'.$file));
    }

    public function test_mobile_edit_branch_picker_matches_the_reference_surface_and_selected_state(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode\s+#branch_id\s*~\s*\.erp-picker__trigger\s*\{[^}]*background\s*:\s*#f9fafb[^}]*font-size\s*:\s*14px[^}]*\}/s',
            $source,
            'Pemicu pemilih cabang mode ubah memakai permukaan abu-abu muda dan teks 14px.'
        );
        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode\s+#branch_id\s*~\s*\.erp-picker__trigger\s+\.erp-picker__label:not\(\.is-placeholder\)\s*\{[^}]*font-weight\s*:\s*600[^}]*\}/s',
            $source,
            'Cabang yang sudah dipilih memakai semibold.'
        );
    }

    public function test_the_page_no_longer_depends_on_select2_or_the_metronic_bundle(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString('select2', $source);
        $this->assertStringNotContainsString('plugins.bundle', $source);
        $this->assertStringNotContainsString('style.bundle', $source);
        $this->assertStringNotContainsString('select2', $this->source('pengadaan/js/js-header.blade.php'));
        $this->assertStringContainsString("ErpPicker.attach('#branch_id'", $this->source('pengadaan/js/js-header.blade.php'));
    }

    public function test_item_dialogs_are_native_dialogs(): void
    {
        $segment = $this->source('wholesale/segment/modal-product.blade.php');

        $this->assertStringContainsString('<x-erp.dialog id="addModal"', $segment);
        $this->assertStringContainsString('<x-erp.dialog id="editModal"', $segment);
        $this->assertStringNotContainsString('class="modal ', $segment);
        $this->assertStringNotContainsString('bootstrap', $this->source('pengadaan/js/js-product.blade.php'));
    }
}
