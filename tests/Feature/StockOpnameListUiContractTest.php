<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameListUiContractTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
    }

    public function test_list_rows_are_compact_with_product_time_difference_and_value(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('class="so-row', $source);
        $this->assertStringContainsString('so-row__diff', $source);
        $this->assertStringContainsString('so-row__value', $source);
        // The heavy per-row card (stock boxes + inline action buttons) is gone from the renderer.
        $this->assertStringNotContainsString('<div class="so-card d-flex', $source);
    }

    public function test_tapping_a_row_opens_a_detail_sheet_with_history_and_completion(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('id="kt_modal_stock_detail"', $source);
        $this->assertStringContainsString('function openStockDetail(', $source);

        foreach ([
            'editProduct(',
            'openAdjustmentModal(',
            'showHistory(',
            'showDiscussion(',
            'finalizeStockOpname(',
            'deleteProduct(',
        ] as $action) {
            $this->assertStringContainsString($action, $source, "Aksi hilang: {$action}");
        }
        foreach (['finalize', 'history'] as $action) {
            $this->assertStringContainsString('data-so-action="' . $action . '"', $source, "Tombol hilang: {$action}");
        }
    }

    public function test_detail_sheet_offers_corrections_only_while_the_record_is_pending(): void
    {
        $source = $this->source();

        // update / adjust / destroy answer 422 for a finished record. Since the reference redesign the sheet no longer has
        // separate edit/adjust/delete buttons: the correction fields and the save button follow `canCorrect`.
        $this->assertStringContainsString("const canCorrect = info.isPending && @json(check_access('stock-opname.update'));", $source);
        $this->assertStringContainsString("prop('disabled', !canCorrect)", $source);
        $this->assertStringContainsString("find('[data-so-action=\"finalize\"]').toggleClass('d-none', !info.isPending)", $source);
    }

    public function test_server_endpoints_and_filters_are_unchanged(): void
    {
        $source = $this->source();

        foreach ([
            "route('stock-opname.data')",
            "route('stock-opname.export')",
            "route('stock-opname.preview')",
            '/stock-opname/${id}/adjust',
            '/stock-opname/${id}/finalize',
            '/stock-opname/${id}/history',
            'cabang_filter',
            'start_date',
        ] as $contract) {
            $this->assertStringContainsString($contract, $source);
        }
    }
}
