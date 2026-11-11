<?php

namespace Tests\Feature;

use Tests\TestCase;

final class TransferReceiveFilterUiContractTest extends TestCase
{
    public function test_receiver_filter_uses_expanded_dates_and_preserves_receiving_statuses(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/transfer/index2.blade.php'));
        $styles = file_get_contents(base_path('public/assets/css/transfer-mobile.css'));

        $this->assertStringContainsString('Cabang Penerima', $view);
        $this->assertStringContainsString('Status {{ $receiving ? \'Penerimaan\' : \'Pengiriman\' }}', $view);
        $this->assertStringContainsString("['value' => 'proses', 'label' => 'Sedang Diperiksa']", $view);
        $this->assertStringContainsString('transfer-filter-date-cards', $view);
        $this->assertStringContainsString('Minggu Ini', $view);
        $this->assertStringContainsString('Hari Ini', $view);
        $this->assertStringContainsString("resetPendingFilter('branch')", $view);
        $this->assertStringContainsString("resetPendingFilter('status')", $view);
        $this->assertStringContainsString("resetPendingFilter('date')", $view);
        $this->assertStringNotContainsString('<details class="transfer-filter-period"', $view);
        $this->assertDoesNotMatchRegularExpression('~@if\s*\(!\$receiving\)\s*\R\s*<button[^>]*class="transfer-filter-reset"~', $view);
        $this->assertStringContainsString('.transfer-filter-date-cards { display:flex; flex-direction:column; gap:8px; }', $styles);
    }
}
