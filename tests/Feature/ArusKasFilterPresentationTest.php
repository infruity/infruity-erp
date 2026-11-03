<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ArusKasFilterPresentationTest extends TestCase
{
    public function test_filter_styles_are_loaded_only_on_the_cash_flow_index(): void
    {
        $root = file_get_contents(resource_path('views/template/root.blade.php'));
        preg_match("/(@if\\s*\\(request\\(\\)->is\\('arus-kas'\\)\\)[\\s\\S]*?@endif)/", $root, $matches);
        $this->assertNotEmpty($matches, 'The scoped mobile filter stylesheet needs an index-only include.');

        foreach (['/arus-kas' => true, '/arus-kas?branch_id=15' => true, '/arus-kas/create' => false, '/pos' => false] as $uri => $expected) {
            $this->app->instance('request', Request::create($uri, 'GET'));
            $html = Blade::render($matches[1]);
            $this->assertSame($expected, str_contains($html, 'assets/css/arus-kas-filter.css'), $uri);
        }
    }
}
