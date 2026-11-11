<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Nota page (print2: printDraftPayment, printNota and the public cek-nota link), matched to the reference
 * "Nota Transaksi" screen. Screen chrome only: the receipt card, print output, PNG export and the
 * WhatsApp link keep working exactly as before.
 */
final class PosNotaUiContractTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Pos/Resources/views/pos/print2.blade.php'));
    }

    public function test_top_bar_and_bottom_action_bar_follow_the_reference_and_stay_off_the_public_link(): void
    {
        $view = $this->source();

        foreach (['nota-topbar', 'Nota Transaksi', 'nota-bar', 'aria-label="Kembali"', 'aria-label="Unduh PNG"', 'aria-label="Kirim ke WhatsApp"', 'aria-label="Unduh PNG"', 'Cetak Nota'] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }
        // Customers opening the shared cek-nota link must not get staff controls.
        $topbar = strpos($view, 'nota-topbar"');
        $guard = strrpos(substr($view, 0, $topbar), "Request::segment(1) != 'cek-nota'");
        $this->assertNotFalse($guard, 'top bar harus di dalam guard cek-nota');
        $this->assertLessThan(strpos($view, '<div class="container">'), $topbar, 'top bar harus di luar .container (PNG memotret .container)');
        $bar = strpos($view, '<nav class="nota-bar"');
        $this->assertNotFalse($bar);
        $this->assertGreaterThan(strpos($view, '<div class="container">'), $bar);
        $this->assertSame(3, substr_count($view, "Request::segment(1) != 'cek-nota'"), 'topbar dan action bar hanya untuk layar kasir internal');
    }

    public function test_every_old_action_is_still_wired(): void
    {
        $view = $this->source();

        $this->assertStringContainsString('onclick="window.print()"', $view);
        $this->assertStringContainsString('onclick="downloadReceiptAsPNG()"', $view);
        $this->assertStringContainsString('onclick="sendReceiptToWA()"', $view);
        $this->assertStringContainsString("route('pos.index')", $view);
        $this->assertStringContainsString('function downloadReceiptAsPNG()', $view);
        $this->assertStringContainsString('function sendReceiptToWA()', $view);
        $this->assertStringContainsString("document.querySelector('.container')", $view);
        // Back returns to where the cashier came from, falling back to the list.
        $this->assertStringContainsString('function notaBack()', $view);
        $this->assertStringContainsString('history.back()', $view);
    }

    public function test_bars_are_screen_only_and_leave_room_so_the_card_is_not_covered(): void
    {
        $view = $this->source();
        $print = substr($view, strrpos($view, '@media print'));

        $this->assertStringContainsString('.nota-bar', $print);
        $this->assertStringContainsString('.nota-topbar', $print);
        $this->assertMatchesRegularExpression('/\.nota-bar[^{]*\{[^}]*position:\s*fixed/s', $view);
        $this->assertMatchesRegularExpression('/body\.has-nota-bar\s*\{[^}]*padding:64px 0 calc/s', $view);
        $this->assertStringContainsString('has-nota-bar', $view);
    }

    public function test_item_totals_are_bold_like_the_reference_card(): void
    {
        $this->assertMatchesRegularExpression('/\.item-total\s*\{[^}]*font-weight:\s*(600|700)/s', $this->source());
    }
}
