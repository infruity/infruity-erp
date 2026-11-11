<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class PosListFilterUiContractTest extends TestCase
{
    private function posListView(): string
    {
        return file_get_contents(base_path('Modules/Pos/Resources/views/pos/index2.blade.php'));
    }

    public function test_pos_filter_is_the_shared_sheet_with_the_same_ux_as_the_other_lists(): void
    {
        $view = $this->posListView();

        foreach ([
            '<x-erp.filter-sheet',
            'id="pos"',
            'title="Filter Transaksi"',
            'branch-label="Cabang"',
            'status-label="Status Transaksi"',
            ':branches="$posBranches"',
            ':default-branch="$posFilterConfig[\'defaultBranch\']"',
            ':statuses="$posStatuses"',
            ':timezone="$posFilterConfig[\'timezone\']"',
            'desktop="modal"',
            'reset-applies',
            'reset-label="Reset Filter"',
            'apply-label="Terapkan Filter"',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }

        // The page-specific sheet, calendar and its styles are gone.
        foreach (['pos-filter-', 'calendarOpen', 'draft.', 'posCalendarBack', 'data-pos-status', 'id="pos-start-date"'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
    }

    public function test_the_page_opens_the_sheet_and_listens_for_apply_and_close(): void
    {
        $view = $this->posListView();
        $script = file_get_contents(base_path('public/assets/js/pos-list-reference.js'));

        $this->assertStringContainsString('@click="open()"', $view);
        $this->assertStringContainsString("@erp-filter-apply.window=\"if (\$event.detail.id === 'pos') applyFilters(\$event.detail.values)\"", $view);
        $this->assertStringContainsString("@erp-filter-close.window=\"if (\$event.detail.id === 'pos') close()\"", $view);
        $this->assertStringContainsString("\$dispatch('erp-filter-open', {id: 'pos'", $script);
    }

    public function test_sheet_offers_the_pos_statuses_and_the_standard_sections(): void
    {
        $html = Blade::render(
            '<x-erp.filter-sheet id="pos" title="Filter Transaksi" branch-label="Cabang" status-label="Status Transaksi" :branches="$branches" :statuses="$statuses" reset-label="Reset Filter" apply-label="Terapkan Filter" />',
            [
                'branches' => [(object) ['id' => 1, 'name' => 'toko gkb']],
                'statuses' => ['all' => 'Semua Status', 'draft' => 'Pending', 'unpaid' => 'Belum Bayar', 'partial' => 'Kurang Bayar', 'paid' => 'Lunas'],
            ]
        );

        foreach (['Filter Transaksi', 'Status Transaksi', 'Belum Bayar', 'Kurang Bayar', 'Lunas', 'Periode Tanggal', 'Pilihan Cepat', 'Minggu Ini', 'Hari Ini', 'Reset Filter', 'Terapkan Filter'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_a_single_branch_user_gets_no_all_branches_row_and_a_default_branch(): void
    {
        $html = Blade::render(
            '<x-erp.filter-sheet id="pos" title="Filter" :branches="$branches" :show-branch-all="false" default-branch="1" />',
            ['branches' => [(object) ['id' => 1, 'name' => 'toko gkb']]]
        );

        $this->assertStringNotContainsString('data-pos-mobile-branch="all"', $html);
        $this->assertStringContainsString('\\u0022defaultBranch\\u0022:\\u00221\\u0022', $html);
    }
}
