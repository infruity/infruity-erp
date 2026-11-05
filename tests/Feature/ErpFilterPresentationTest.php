<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErpFilterPresentationTest extends TestCase
{
    public function test_shared_filter_presentation_asset_exists(): void
    {
        $this->assertFileExists(public_path('assets/css/erp-filters.css'));
    }

    public function test_tailwind_layout_loads_the_same_cache_busted_filter_stylesheet(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/erp.blade.php'));
        $include = "{{ asset('assets/css/erp-filters.css') }}?v={{ filemtime(public_path('assets/css/erp-filters.css')) }}";

        $this->assertSame(1, substr_count($layout, $include));
        $this->assertLessThan(strpos($layout, '</head>'), strpos($layout, $include));
    }

    public function test_root_loads_one_cache_busted_filter_stylesheet_after_page_overrides(): void
    {
        $root = file_get_contents(resource_path('views/template/root.blade.php'));
        $include = "{{ asset('assets/css/erp-filters.css') }}?v={{ filemtime(public_path('assets/css/erp-filters.css')) }}";

        $this->assertSame(1, substr_count($root, $include));
        $this->assertGreaterThan(strpos($root, "assets/css/arus-kas-filter.css"), strpos($root, $include));
    }
}
