<?php

namespace Tests\Feature;

use Tests\TestCase;

final class TransferFilterUiContractTest extends TestCase
{
    public function test_sender_filter_matches_reference_without_changing_local_status_values(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/transfer/index2.blade.php'));
        $script = file_get_contents(base_path('public/assets/js/transfer-list.js'));
        $styles = file_get_contents(base_path('public/assets/css/transfer-mobile.css'));

        foreach ([
            "timezone: @js(config('app.timezone'))",
            'Cabang Pengirim',
            'Status {{ $receiving ? \'Penerimaan\' : \'Pengiriman\' }}',
            'Minggu Ini',
            'Hari Ini',
            "resetPendingFilter('branch')",
            "resetPendingFilter('status')",
            "resetPendingFilter('date')",
            'x-model="pending.start"',
            'x-model="pending.end"',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view);
        }

        $this->assertStringContainsString("['value' => 'draft', 'label' => 'Draf']", $view);
        $this->assertStringContainsString("['value' => 'proses', 'label' => 'Sedang Diperiksa']", $view);
        $this->assertStringContainsString('status_filter:this.filters.status,cabang_filter:this.filters.branch,start_date:this.filters.start,end_date:this.filters.end', $script);
        $this->assertStringContainsString('overflow-y:auto;', $styles);
        $this->assertStringContainsString('flex:none;', $styles);
    }

    public function test_mobile_transfer_dates_stack_and_quick_actions_are_styled(): void
    {
        $styles = file_get_contents(base_path('public/assets/css/transfer-mobile.css'));

        $this->assertStringContainsString('.transfer-filter-date-cards { display:grid; grid-template-columns:minmax(0,1fr);', $styles);
        $this->assertStringContainsString('.transfer-filter-quick-actions button.is-week', $styles);
    }
}
