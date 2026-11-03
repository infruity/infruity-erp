<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ChangePasswordTopbarBackButtonTest extends TestCase
{
    public function test_change_password_mobile_topbar_links_back_to_settings(): void
    {
        $request = Request::create('/change-password', 'GET');
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(static fn () => $route);
        $this->app->instance('request', $request);

        $this->assertSame('change-password', $route->getName());

        $html = Blade::render('<x-erp.topbar mobile-only="true" />');

        $this->assertStringContainsString('href="' . route('settings.index') . '"', $html);
        $this->assertStringContainsString('aria-label="Kembali ke Pengaturan"', $html);
        $this->assertStringContainsString('ph-caret-left', $html);
        $this->assertDoesNotMatchRegularExpression('/<button\b[^>]*data-erp-sidebar-toggle/', $html);
    }
}
