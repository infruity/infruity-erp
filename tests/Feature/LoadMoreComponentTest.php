<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Every "load more" control of the ERP is the same component (x-erp.load-more): product list, customers, POS, transfer,
 * recipes, units, production, stock. Pages only choose the wrapper and the label.
 */
final class LoadMoreComponentTest extends TestCase
{
    private const BASE = 'h-[44px] w-full items-center justify-center gap-[8px] rounded-[12px] border-2 border-dashed border-[#e5e7eb]';

    public function test_a_plain_page_gets_the_dashed_button_without_alpine_bindings(): void
    {
        $html = Blade::render('<x-erp.load-more>Muat Produk Lagi</x-erp.load-more>');

        $this->assertStringContainsString(self::BASE, $html);
        $this->assertStringContainsString('ph-arrow-circle-down', $html);
        $this->assertStringContainsString('>Muat Produk Lagi</span>', $html);
        $this->assertStringNotContainsString(':disabled', $html);
        $this->assertStringNotContainsString(':class', $html);
    }

    public function test_an_alpine_page_disables_the_button_and_spins_the_icon_while_loading(): void
    {
        $html = Blade::render('<x-erp.load-more @click="loadMore()" loading="loadingMore">Muat Pelanggan Lagi</x-erp.load-more>');

        $this->assertStringContainsString(self::BASE, $html);
        $this->assertStringContainsString(':disabled="loadingMore"', $html);
        $this->assertStringContainsString(':aria-busy="loadingMore"', $html);
        $this->assertStringContainsString('animate-spin', $html);
        $this->assertStringContainsString('@click="loadMore()"', $html);
    }

    public function test_sizes_do_not_depend_on_the_root_font_size_of_the_layout(): void
    {
        $html = Blade::render('<x-erp.load-more>Muat</x-erp.load-more>');

        // rem based utilities (h-11, text-sm, gap-2, rounded-xl, text-base) shrink on template.root, which sets 13px.
        foreach (['h-11 ', 'text-sm ', 'gap-2 ', 'rounded-xl ', 'text-base'] as $remBased) {
            $this->assertStringNotContainsString($remBased, $html);
        }
        // template.root also redefines the gray-* palette, so the colours are fixed values too.
        $this->assertStringNotContainsString('gray-', $html);
        $this->assertStringContainsString('text-[#6b7280]', $html);
        $this->assertStringContainsString('text-[14px]', $html);
        $this->assertStringContainsString('text-[16px]', $html);
    }

    public function test_an_empty_slot_falls_back_to_the_default_label(): void
    {
        $this->assertStringContainsString('>Muat Lebih Banyak</span>', Blade::render('<x-erp.load-more />'));
    }

    public function test_no_view_draws_its_own_load_more_button(): void
    {
        $component = realpath(base_path('resources/views/components/erp/load-more.blade.php'));
        $offenders = [];

        $roots = array_merge([base_path('resources/views')], glob(base_path('Modules/*/Resources/views')));
        foreach ($roots as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $path = $file->getPathname();
                if (! str_ends_with($path, '.blade.php') || realpath($path) === $component || str_contains($path, '/views/template')) {
                    continue;
                }
                if (str_contains(file_get_contents($path), 'ph-arrow-circle-down')) {
                    $offenders[] = str_replace(base_path() . '/', '', $path);
                }
            }
        }

        $this->assertSame([], $offenders, 'use <x-erp.load-more> instead of a private button');
    }

    public function test_the_pages_that_load_more_use_the_component(): void
    {
        foreach ([
            'Modules/Master/Resources/views/products/index.blade.php',
            'Modules/Master/Resources/views/partials/mobile-index.blade.php',
            'Modules/Master/Resources/views/unit/index.blade.php',
            'Modules/Master/Resources/views/products/stock.blade.php',
            'Modules/Master/Resources/views/products/stock-show.blade.php',
            'Modules/Transaction/Resources/views/production/index.blade.php',
            'Modules/Transaction/Resources/views/receipt/index.blade.php',
            'Modules/Transaction/Resources/views/sortir/index2.blade.php',
            'Modules/Transaction/Resources/views/transfer/index2.blade.php',
            'Modules/Pos/Resources/views/pos/index2.blade.php',
        ] as $view) {
            $this->assertStringContainsString('<x-erp.load-more', file_get_contents(base_path($view)), $view);
        }
    }
}
