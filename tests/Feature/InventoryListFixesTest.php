<?php

namespace Tests\Feature;

use App\Support\Like;
use Tests\TestCase;

final class InventoryListFixesTest extends TestCase
{
    private function src(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_like_escape_is_available_without_the_surrounding_wildcards(): void
    {
        $this->assertSame('100\\%', Like::escape('100%'));
        $this->assertSame('a\\_b\\\\c', Like::escape('a_b\\c'));
        $this->assertSame('%a\\_b%', Like::contains('a_b'));
    }

    public function test_sortir_transfer_and_wholesale_searches_treat_wildcards_literally(): void
    {
        $sortir = $this->src('Modules/Transaction/Http/Controllers/SortirController.php');
        $this->assertStringContainsString('$like = Like::contains($search);', $sortir);
        $this->assertStringNotContainsString('"%{$search}%"', $sortir);

        $transfer = $this->src('Modules/Transaction/Http/Controllers/TransferController.php');
        $this->assertStringContainsString('$like = Like::contains($search);', $transfer);
        $this->assertStringNotContainsString('"%{$search}%"', $transfer);

        // Pengadaan uses the built-in DataTables search, so the keyword is escaped before it is built.
        $wholesale = $this->src('Modules/Transaction/Http/Controllers/WholesaleController.php');
        $this->assertStringContainsString('Like::escape(', substr($wholesale, strpos($wholesale, 'public function get_data')));
    }

    public function test_wholesale_detail_popup_shows_the_same_date_as_the_list_row(): void
    {
        $controller = $this->src('Modules/Transaction/Http/Controllers/WholesaleController.php');
        $view = $this->src('Modules/Transaction/Resources/views/wholesale/index.blade.php');

        $payload = substr($controller, strpos($controller, "'detail_payload'"));
        $this->assertStringContainsString("'order_date_label' => \$mobileDate", $payload);
        $this->assertStringNotContainsString('created_at_label', $controller);
        $this->assertStringContainsString("item?.order_date_label", $view);
        $this->assertStringNotContainsString('Dibuat pada', $view);
    }

    public function test_wholesale_filtered_empty_result_does_not_claim_there_are_no_orders(): void
    {
        $view = $this->src('Modules/Transaction/Resources/views/wholesale/index.blade.php');

        $this->assertMatchesRegularExpression('/emptyTable:\s*wholesaleEmptyState/', $view);
        $this->assertMatchesRegularExpression('/zeroRecords:\s*wholesaleNoMatchState/', $view);
        $this->assertStringContainsString('Pengadaan tidak ditemukan', $view);
        $this->assertStringContainsString('Coba kata pencarian lain atau sesuaikan filter.', $view);
        // The count line under the toolbar must say the same thing as the table body.
        $this->assertStringContainsString(": (hasCriteria ? 'Pengadaan tidak ditemukan' : 'Belum ada pengadaan')", $view);
        $this->assertStringContainsString('function hasWholesaleFilterCriteria(filters)', $view);
        $this->assertStringContainsString("$('#wholesale-table td.dataTables_empty').html(hasCriteria ? wholesaleNoMatchState : wholesaleEmptyState);", $view);
    }

    public function test_wholesale_mobile_empty_state_centers_in_the_available_list_area(): void
    {
        $view = $this->src('Modules/Transaction/Resources/views/wholesale/index.blade.php');

        $this->assertStringContainsString("document.querySelector('.mobile-list-edge-fade')?.classList.toggle('has-wholesale-empty-state', !info.recordsDisplay);", $view);
        $this->assertStringContainsString("$('#wholesale-table_wrapper').toggleClass('has-wholesale-empty-state', !info.recordsDisplay);", $view);
        $this->assertStringContainsString('.mobile-list-edge-fade.has-wholesale-empty-state { display:flex; flex-direction:column; }', $view);
        $this->assertStringContainsString('#wholesale-table_wrapper.has-wholesale-empty-state #wholesale-table tbody tr { display:flex; flex:1 1 0%; min-height:0;', $view);
        $this->assertStringContainsString('align-items:center; justify-content:center;', $view);
        $this->assertStringContainsString('.wholesale-empty-state { min-height:0; }', $view);
    }

    public function test_stock_opname_length_menu_has_no_all_option(): void
    {
        $view = $this->src('Modules/Transaction/Resources/views/stock-opname/index.blade.php');

        $this->assertStringContainsString('lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]', $view);
        $this->assertStringNotContainsString('"All"', $view);
    }
}
