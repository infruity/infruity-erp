<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameReferencePresentationTest extends TestCase
{
    public function test_requested_states_are_wired_without_replacing_server_contracts(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
        foreach (['stock-opname-reference.css', 'stock-opname-reference.js', 'x-erp.mobile-search',
            'so-filter-sheet', 'so-history-filter', 'so-detail-correction', 'data-so-history-toggle',
            'data-so-field="created"', 'data-so-field="creator"'] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
        $this->assertStringContainsString('name="real_stock"', $source);
        $this->assertStringContainsString("route('stock-opname.data')", $source);
        $this->assertStringContainsString('/stock-opname/${id}/finalize', $source);
    }

    private function partial(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/partials/reference-filters.blade.php'));
    }

    public function test_main_filter_is_the_shared_sheet_and_the_history_sheet_is_untouched(): void
    {
        $source = $this->partial();
        $css = file_get_contents(public_path('assets/css/stock-opname-reference.css'));

        foreach ([
            '<x-erp.filter-sheet',
            'id="so"',
            'title="Filter Stok Opname"',
            'status-label="Status Opname"',
            "['all' => 'Semua Status', 'selesai' => 'Selesai', 'draft' => 'Draf']",
            "'key' => 'result'",
            "'lebih' => 'Lebih', 'kurang' => 'Kurang', 'sesuai' => 'Sesuai'",
            ':timezone="config(\'app.timezone\')"',
            'desktop="modal"',
            'reset-applies',
        ] as $contract) {
            $this->assertStringContainsString($contract, $source, "Hilang: {$contract}");
        }
        // The history filter keeps its own sheet.
        $this->assertStringContainsString('id="so-history-filter"', $source);
        $this->assertStringContainsString('data-so-direction="plus"', $source);
        // The old sheet, calendar and their styles are gone.
        foreach (['so-filter-draft-branch', 'so-calendar-host', 'data-so-period', 'data-so-reset'] as $gone) {
            $this->assertStringNotContainsString($gone, $source, "Sisa implementasi lama: {$gone}");
        }
        foreach (['#so-filter-sheet', '#so-calendar-host', '.so-branch-options'] as $gone) {
            $this->assertStringNotContainsString($gone, $css, "CSS lama masih ada: {$gone}");
        }
    }

    public function test_page_script_talks_to_the_sheet_through_events(): void
    {
        $script = file_get_contents(public_path('assets/js/stock-opname-reference-page.js'));

        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('erp-filter-open',{detail:{id:'so',values:ui.sheetValues(readApplied())}}))", $script);
        $this->assertStringContainsString("window.addEventListener('erp-filter-apply'", $script);
        $this->assertStringContainsString("window.addEventListener('erp-filter-close'", $script);
        $this->assertStringNotContainsString('bootstrap.Modal.getOrCreateInstance(filterEl)', $script);
        $this->assertStringNotContainsString('renderCalendar', $script);
    }

    public function test_result_and_status_filters_are_wired_to_the_existing_data_endpoint(): void
    {
        $source = $this->partial();

        $this->assertStringContainsString('Hasil Opname', $source);
        $this->assertStringContainsString('Status Opname', $source);
        $this->assertStringContainsString('id="so-filter-applied-status"', $source);
        $this->assertStringContainsString('id="so-filter-applied-result"', $source);
        $this->assertStringNotContainsString('so-filter-limit', $source);
        $this->assertStringNotContainsString('data-kt-ecommerce-product-filter="cabang"', $source);
    }

    public function test_list_request_carries_the_result_and_status_filters(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));

        $this->assertStringContainsString("d.status_filter = \$('#so-filter-applied-status').val()", $source);
        $this->assertStringContainsString("d.result_filter = \$('#so-filter-applied-result').val()", $source);
    }
}
