<?php
namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameCommentCorrectionsTest extends TestCase
{
    public function test_detail_has_a_real_done_button_without_secondary_actions_or_finished_warning(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
        $this->assertStringContainsString('id="so-detail-done"', $source);
        $this->assertMatchesRegularExpression('/id="so-detail-done"[^>]*data-erp-close[^>]*>Selesaikan opname<\/button>/', $source);
        $this->assertStringContainsString('Simpan Koreksi', $source);
        $this->assertStringContainsString('[data-so-action="finalize"]', $source);
        $this->assertStringContainsString('/stock-opname/${id}/finalize', $source);
        $this->assertStringNotContainsString('so-detail-extra', $source);
        $this->assertStringNotContainsString('Aksi lainnya', $source);
        $this->assertStringNotContainsString('Opname selesai — koreksi dikunci.', $source);
        $this->assertStringContainsString('id="so-correction-error"', $source); // Keep real error feedback.
        $this->assertStringContainsString(".prop('disabled', !canCorrect)", $source);
    }

    public function test_page_is_full_bleed_on_mobile_without_touching_other_pages(): void
    {
        $css = file_get_contents(public_path('assets/css/stock-opname-reference.css'));
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
        // layouts.erp drops the gutter for pages that ask for it; no global override of the shell.
        $this->assertStringContainsString("@section('dashboard-fullscreen', '1')", $view);
        $this->assertStringNotContainsString('#kt_content', $css);
        $this->assertStringContainsString('overflow-wrap:anywhere', $css);
        $this->assertStringContainsString('.so-row__side', $css);
        $this->assertStringContainsString('.so-correction-error:empty', $css);
    }
}
