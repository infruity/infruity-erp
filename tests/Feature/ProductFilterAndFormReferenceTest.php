<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductFilterAndFormReferenceTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_desktop_filter_lives_inside_the_search_pill_like_the_reference(): void
    {
        $view = $this->source('Modules/Master/Resources/views/products/index.blade.php');

        $this->assertStringContainsString('<span class="icon-rounded"', $view, 'tune icon');
        $this->assertStringContainsString('>tune</span>', $view);
        $this->assertStringContainsString('absolute top-1 right-1 w-2 h-2 rounded-full bg-[#0b595b] ring-2 ring-white', $view, 'active dot');
        $this->assertStringContainsString('class="absolute right-0 top-10 w-56 bg-white rounded-2xl shadow-xl', $view);
        $this->assertStringContainsString('hidden md:block fixed inset-0 z-40 bg-transparent', $view, 'click-away layer');
        $this->assertSame(2, substr_count($view, '>Reset</button>'), 'Reset in the desktop dropdown and the mobile sheet');
        $this->assertStringContainsString('data-kt-ecommerce-product-filter="search" id="search"', $view, 'DataTables still reads #search');
    }

    public function test_product_drawer_follows_the_reference_sizes(): void
    {
        $view = $this->source('Modules/Master/Resources/views/products/index.blade.php');
        $form = $this->source('Modules/Master/Resources/views/products/partials/create-form.blade.php');

        $this->assertStringContainsString('h-[85vh]', $view);
        $this->assertStringContainsString('overscroll-none scrollbar-hide', $view, 'native scrollbar hidden');
        $this->assertStringContainsString('bg-gray-400/50', $view, 'custom scrollbar thumb');
        $this->assertStringContainsString('placeholder="Cari tipe produk..."', $form);
        $this->assertStringContainsString('Misal: Grade Super, Grade A, Grade AB', $form);
        $this->assertStringContainsString('<span class="text-gray-300">(Opsional)</span>', $form);
        $this->assertStringContainsString('line-height: 1rem; font-weight: 600;', $form, 'label 16px high');
    }
}
