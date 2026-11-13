<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * "Menampilkan x dari y" is one component (x-erp.list-count): a grey bar under the toolbar on desktop and a pill
 * at the top of the list while it scrolls on mobile. Every list page uses it.
 */
class ListCountPositionTest extends TestCase
{
    public static function pagesWithTheComponent(): array
    {
        return [
            'units'          => ['Modules/Master/Resources/views/unit/index.blade.php'],
            'master pages'   => ['Modules/Master/Resources/views/partials/mobile-index.blade.php'],
            'products'       => ['Modules/Master/Resources/views/products/index.blade.php'],
            'stock'          => ['Modules/Master/Resources/views/products/stock.blade.php'],
            'recipes'        => ['Modules/Transaction/Resources/views/receipt/index.blade.php'],
            'wholesale'      => ['Modules/Transaction/Resources/views/wholesale/index.blade.php'],
            'transfer'       => ['Modules/Transaction/Resources/views/transfer/index2.blade.php'],
            'sortir'         => ['Modules/Transaction/Resources/views/sortir/index2.blade.php'],
            'cash flow'      => ['Modules/Pos/Resources/views/arus-kas/index.blade.php'],
            'roles'          => ['resources/views/admin/role/index.blade.php'],
            'users'          => ['resources/views/admin/user/index.blade.php'],
        ];
    }

    public static function workspacePages(): array
    {
        return [
            'orders'      => ['Modules/Pos/Resources/views/order-book/index.blade.php'],
            'deliveries'  => ['Modules/Pos/Resources/views/delivery-order/index.blade.php'],
            'expenditure' => ['Modules/Pos/Resources/views/expenditure/index.blade.php'],
            'income'      => ['Modules/Pos/Resources/views/otherbook/index.blade.php'],
            'production'  => ['Modules/Transaction/Resources/views/production/index.blade.php'],
            'opname'      => ['Modules/Transaction/Resources/views/stock-opname/index.blade.php'],
            'campaign'    => ['Modules/Crm/Resources/views/campaign/index.blade.php'],
            'tier'        => ['Modules/Crm/Resources/views/tier/index.blade.php'],
            'deposito'    => ['Modules/Crm/Resources/views/deposito/index.blade.php'],
            'customers'   => ['Modules/Crm/Resources/views/report/customer.blade.php'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pagesWithTheComponent')]
    public function test_page_uses_the_shared_count_component(string $path): void
    {
        $view = file_get_contents(base_path($path));

        $this->assertStringContainsString('<x-erp.list-count', $view);
        $this->assertStringNotContainsString('hidden md:flex px-4 py-3 bg-gray-50/50', $view, 'no hand-made copy of the bar');
        $this->assertStringNotContainsString('Menampilkan <strong', $view, 'no hand-made copy of the pill');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('workspacePages')]
    public function test_workspace_page_asks_for_the_count(string $path): void
    {
        $view = file_get_contents(base_path($path));

        $this->assertMatchesRegularExpression('/<x-erp\.workspace [^>]*count-table="[a-z-]+" count-noun="[^"]+"/', $view);
    }

    public function test_the_component_renders_the_bar_and_the_mobile_line_in_the_units_place(): void
    {
        $html = Blade::render('<x-erp.list-count noun="role" :shown="3" :total="5" server />');

        $this->assertStringContainsString('hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium', $html);
        $this->assertStringContainsString('erp-list-count-mobile md:hidden', $html);
        $this->assertStringNotContainsString('absolute', $html, 'the mobile count is a line under the search, not a floating pill');
        $this->assertStringContainsString('Menampilkan', $html);
        $this->assertMatchesRegularExpression('/>3<\/span> dari\s*<span[^>]*>5<\/span>&nbsp;role/', $html);
    }

    public function test_pages_that_scroll_as_a_whole_keep_search_and_count_sticky(): void
    {
        $html = Blade::render('<x-erp.list-count noun="pesanan" shown-id="a" total-id="b" window-scroll />');

        $this->assertStringContainsString('id="a-mobile"', $html);
        $this->assertStringNotContainsString('fixed top-', $html);
        $this->assertStringContainsString('erp-workspace-sticky', file_get_contents(base_path('resources/views/components/erp/workspace.blade.php')));
        $this->assertStringContainsString('.erp-workspace-sticky { position:sticky', file_get_contents(public_path('assets/css/erp-workspace.css')));
    }
}
