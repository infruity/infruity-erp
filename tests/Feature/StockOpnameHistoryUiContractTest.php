<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameHistoryUiContractTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
    }

    public function test_history_has_a_kredit_and_alasan_toggle(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('data-so-history-toggle', $source);
        $this->assertStringContainsString("state.tab === 'reason'", $source);
        $this->assertStringContainsString('function renderStockOpnameHistory(', $source);
    }

    public function test_credit_mode_shows_awal_mutasi_saldo_and_reason_mode_shows_the_note(): void
    {
        $source = $this->source();

        foreach (['Awal', 'Kredit', 'Saldo', 'Stok Awal', 'Kredit Lebih', 'Kredit Kurang', 'Alasan Pergerakan'] as $label) {
            $this->assertStringContainsString($label, $source, "Label hilang: {$label}");
        }
        $this->assertStringContainsString('so-hist-row', $source);
        // The old dashed timeline renderer is replaced.
        $this->assertStringNotContainsString('<div class="timeline-item">', $source);
    }

    public function test_history_endpoint_and_drawer_are_still_used(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('/stock-opname/${id}/history', $source);
        $this->assertStringContainsString('id="kt_stock_opname_history"', $source);
        $this->assertStringContainsString("erp.dialog.open('kt_stock_opname_history')", $source);
        $this->assertStringNotContainsString('KTDrawer', $source);
        $this->assertStringContainsString('id="history-transaction-code"', $source);
    }
}
