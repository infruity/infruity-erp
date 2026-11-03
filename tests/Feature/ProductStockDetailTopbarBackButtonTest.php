<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductStockDetailTopbarBackButtonTest extends TestCase
{
    public function test_product_stock_detail_topbar_links_back_to_the_stock_list(): void
    {
        $request = Request::create('/product-stock/773/show', 'GET');
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(static fn () => $route);
        $this->app->instance('request', $request);

        $this->assertSame('product-stock.show', $route->getName());

        $html = Blade::render('<x-erp.topbar mobile-only="true" />');

        $this->assertStringContainsString('href="' . route('product-stock') . '"', $html);
        $this->assertStringContainsString('aria-label="Kembali ke Stok Barang"', $html);
        $this->assertStringContainsString('ph-caret-left', $html);
        $this->assertDoesNotMatchRegularExpression('/<button\b[^>]*data-erp-sidebar-toggle/', $html);
    }
}
