<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * "Menampilkan x dari y" sits like on Satuan Produk: a grey bar under the toolbar on desktop and a
 * pill at the top of the list while it scrolls on mobile.
 */
class ListCountPositionTest extends TestCase
{
    public static function pages(): array
    {
        return [
            'units'   => ['Modules/Master/Resources/views/unit/index.blade.php'],
            'recipes' => ['Modules/Transaction/Resources/views/receipt/index.blade.php'],
            'roles'   => ['resources/views/admin/role/index.blade.php'],
            'users'   => ['resources/views/admin/user/index.blade.php'],
        ];
    }

    /** @dataProvider pages */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_count_bar_and_scroll_pill_match_the_units_page(string $path): void
    {
        $view = file_get_contents(base_path($path));

        $this->assertStringContainsString('hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium', $view, 'desktop bar');
        $this->assertStringContainsString('md:hidden absolute top-2 left-0 right-0 z-20 flex justify-center pointer-events-none', $view, 'mobile pill');
        $this->assertStringContainsString('scrolled = true; clearTimeout(scrollTimer)', $view, 'pill shows while scrolling');
    }
}
