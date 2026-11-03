<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DeliveryOrderToolbarStickyTest extends TestCase
{
    public function test_mobile_delivery_toolbar_sticks_to_the_main_scroll_area(): void
    {
        $this->app->instance('request', Request::create('/delivery-order', 'GET'));
        $html = view('pos::delivery-order.index', [
            'branches' => collect([(object) ['id' => 1, 'name' => 'Toko GKB']]),
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString(
            '#delivery-order-page .erp-workspace-toolbar { position:sticky; top:0; z-index:25;',
            $html
        );
    }
}
