<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class SortirFilterUiContractTest extends TestCase
{
    private function pageSource(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/sortir/index2.blade.php'));
    }

    public function test_filter_is_the_shared_sheet_with_the_sortir_labels_and_statuses(): void
    {
        $view = $this->pageSource();

        foreach ([
            '<x-erp.filter-sheet',
            'id="sortir"',
            'title="Filter Sortir Barang"',
            'branch-label="Cabang Tujuan"',
            'status-label="Status Sortir"',
            ':branches="$sortirFilterBranches"',
            "'draft' => 'Draf'",
            "'paid' => 'Selesai'",
            ':timezone="config(\'app.timezone\')"',
            'desktop="modal"',
            'reset-applies',
            'apply-label="Terapkan Filter"',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }

        // The sheet markup, calendar and draft state live in the component now.
        foreach (['pending.', 'openFilterCalendar', 'filterCalendar', 'resetPendingFilter', 'x-teleport="body">' . "\n" . '            <div x-show="filterOpen"'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
    }

    public function test_the_page_opens_the_sheet_and_listens_for_apply_and_close(): void
    {
        $view = $this->pageSource();
        $script = file_get_contents(base_path('public/assets/js/sortir-list.js'));

        $this->assertStringContainsString('@click="openFilter($event)"', $view);
        $this->assertStringContainsString("@erp-filter-apply.window=\"if (\$event.detail.id === 'sortir') applyFilters(\$event.detail.values)\"", $view);
        $this->assertStringContainsString("@erp-filter-close.window=\"if (\$event.detail.id === 'sortir') closeFilter()\"", $view);
        $this->assertStringContainsString("\$dispatch('erp-filter-open', {id: 'sortir'", $script);
        // The date query keeps the same request fields.
        $this->assertStringContainsString('status_filter:this.filters.status,cabang_filter:this.filters.branch,start_date:this.filters.start,end_date:this.filters.end', $script);
    }

    public function test_sheet_renders_sections_in_reference_order(): void
    {
        $html = Blade::render(
            '<x-erp.filter-sheet id="sortir" title="Filter Sortir Barang" branch-label="Cabang Tujuan" status-label="Status Sortir" :branches="$branches" :statuses="$statuses" />',
            ['branches' => [(object) ['id' => 1, 'name' => 'toko gkb']], 'statuses' => ['all' => 'Semua Status', 'draft' => 'Draf', 'paid' => 'Selesai']]
        );

        $branch = strpos($html, 'Cabang Tujuan');
        $status = strpos($html, 'Status Sortir');
        $date = strpos($html, 'Periode Tanggal');
        $this->assertNotFalse($branch);
        $this->assertLessThan($status, $branch);
        $this->assertLessThan($date, $status);
        foreach (['Minggu Ini', 'Hari Ini', 'Draf', 'Selesai', 'Toko Gkb'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_branch_names_are_serialized_as_data_and_rendered_as_text(): void
    {
        $html = Blade::render(
            '<x-erp.filter-sheet id="sortir" title="Filter" :branches="$branches" />',
            ['branches' => [(object) ['id' => '1', 'name' => 'GKB"><img src=x>']]]
        );

        $this->assertStringContainsString('x-text="row.name"', $html);
        $this->assertStringNotContainsString('<img', $html);
    }
}
