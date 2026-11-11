<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TransferFilterUiContractTest extends TestCase
{
    private function pageSource(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/transfer/index2.blade.php'));
    }

    public function test_filter_is_the_shared_sheet_for_both_sides_without_changing_status_values(): void
    {
        $view = $this->pageSource();
        $script = file_get_contents(base_path('public/assets/js/transfer-list.js'));

        foreach ([
            '<x-erp.filter-sheet',
            'id="transfer"',
            'title="Filter Pindah Stok"',
            "'Cabang Penerima' : 'Cabang Pengirim'",
            "'Status ' . (\$receiving ? 'Penerimaan' : 'Pengiriman')",
            ':branches="$filterBranches"',
            "collect(\$statuses)->pluck('label', 'value')->all()",
            ':timezone="config(\'app.timezone\')"',
            'desktop="modal"',
            'reset-applies',
            "['value' => 'draft', 'label' => 'Draf']",
            "['value' => 'proses', 'label' => 'Sedang Diperiksa']",
            "@erp-filter-apply.window=\"if (\$event.detail.id === 'transfer') applyFilters(\$event.detail.values)\"",
            "@erp-filter-close.window=\"if (\$event.detail.id === 'transfer') closeFilter()\"",
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }

        foreach (['pending.', 'openFilterCalendar', 'resetPendingFilter', 'transfer-filter-date-cards'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
        $this->assertStringContainsString("\$dispatch('erp-filter-open', {id: 'transfer'", $script);
        $this->assertStringContainsString('status_filter:this.filters.status,cabang_filter:this.filters.branch,start_date:this.filters.start,end_date:this.filters.end', $script);
    }

    public function test_sender_side_offers_draft_and_the_receiver_does_not(): void
    {
        $statuses = fn (bool $receiving) => collect(array_merge(
            [['value' => 'all', 'label' => 'Semua Status']],
            $receiving ? [] : [['value' => 'draft', 'label' => 'Draf']],
            [['value' => 'pending', 'label' => 'Dalam Perjalanan'], ['value' => 'proses', 'label' => 'Sedang Diperiksa'], ['value' => 'selesai', 'label' => 'Sudah Diterima']]
        ))->pluck('label', 'value')->all();

        $render = fn (bool $receiving) => Blade::render(
            '<x-erp.filter-sheet id="transfer" title="Filter Pindah Stok" :branch-label="$label" :status-label="$statusLabel" :branches="$branches" :statuses="$statuses" />',
            [
                'label' => $receiving ? 'Cabang Penerima' : 'Cabang Pengirim',
                'statusLabel' => 'Status ' . ($receiving ? 'Penerimaan' : 'Pengiriman'),
                'branches' => [(object) ['id' => 1, 'name' => 'toko gkb']],
                'statuses' => $statuses($receiving),
            ]
        );

        $sender = $render(false);
        $receiver = $render(true);

        $this->assertStringContainsString('Cabang Pengirim', $sender);
        $this->assertStringContainsString('Status Pengiriman', $sender);
        $this->assertStringContainsString('>Draf<', $sender);
        $this->assertStringContainsString('Cabang Penerima', $receiver);
        $this->assertStringContainsString('Status Penerimaan', $receiver);
        $this->assertStringNotContainsString('>Draf<', $receiver);
        $this->assertStringContainsString('Sedang Diperiksa', $receiver);
    }
}
