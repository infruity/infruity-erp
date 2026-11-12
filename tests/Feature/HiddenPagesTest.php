<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFeatureEnabled;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class HiddenPagesTest extends TestCase
{
    private function middlewareOf(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route {$routeName} missing");

        return $route->gatherMiddleware();
    }

    public function test_pengeluaran_routes_are_behind_the_feature_flag(): void
    {
        foreach (['expenditure.index', 'expenditure.create', 'expenditure.store', 'expenditure.edit', 'expenditure.update', 'expenditure.destroy', 'expenditure.save-transaction', 'expenditure.show', 'expenditure.data'] as $name) {
            $this->assertContains(EnsureFeatureEnabled::class . ':expenditure_pages', $this->middlewareOf($name), $name);
        }
    }

    public function test_pemasukan_list_and_data_are_behind_the_flag_but_set_selesai_stays_for_the_order_list(): void
    {
        foreach (['other-book', 'other-book.data'] as $name) {
            $this->assertContains(EnsureFeatureEnabled::class . ':other_book_pages', $this->middlewareOf($name), $name);
        }
        $this->assertNotContains(EnsureFeatureEnabled::class . ':other_book_pages', $this->middlewareOf('other-book.set-selesai'));
        $this->assertStringContainsString('/other-book/set-selesai/', file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php')));
    }

    public function test_stok_keluar_routes_are_behind_the_feature_flag(): void
    {
        foreach (['stock-out.index', 'stock-out.create', 'stock-out.store', 'stock-out.edit', 'stock-out.update', 'stock-out.destroy', 'stock-out.data'] as $name) {
            $this->assertContains(EnsureFeatureEnabled::class . ':stock_out_pages', $this->middlewareOf($name), $name);
        }
    }

    public function test_jenis_stok_keluar_routes_are_behind_their_own_flag(): void
    {
        foreach (['stock-out-type.index', 'stock-out-type.create', 'stock-out-type.store', 'stock-out-type.edit', 'stock-out-type.update', 'stock-out-type.destroy', 'stock-out-type.data'] as $name) {
            $this->assertContains(EnsureFeatureEnabled::class . ':stock_out_type_pages', $this->middlewareOf($name), $name);
        }
    }

    public function test_tier_pages_are_behind_the_flag_but_the_tier_list_other_pages_read_stays(): void
    {
        foreach (['tier.index', 'tier.create', 'tier.store', 'tier.edit', 'tier.update', 'tier.destroy', 'tier.save_detail', 'tier.get-gift', 'tier.data'] as $name) {
            $this->assertContains(EnsureFeatureEnabled::class . ':tier_pages', $this->middlewareOf($name), $name);
        }
        $this->assertNotContains(EnsureFeatureEnabled::class . ':tier_pages', $this->middlewareOf('tier.list-tier'));
        $this->assertStringContainsString("/tier/list-tier", file_get_contents(base_path('Modules/Crm/Resources/views/deposito/js-deposito.blade.php')));
    }

    public function test_the_pages_are_off_by_default_and_answer_404(): void
    {
        $this->assertFalse(config('features.expenditure_pages'));
        $this->assertFalse(config('features.other_book_pages'));
        $this->assertFalse(config('features.stock_out_pages'));
        $this->assertFalse(config('features.stock_out_type_pages'));
        $this->assertFalse(config('features.tier_pages'));

        foreach (['expenditure_pages', 'other_book_pages', 'stock_out_pages', 'stock_out_type_pages', 'tier_pages'] as $feature) {
            $middleware = new EnsureFeatureEnabled();
            $request = \Illuminate\Http\Request::create('/x');

            try {
                $middleware->handle($request, fn () => response('ok'), $feature);
                $this->fail('The page should be hidden');
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
    }

    public function test_turning_a_flag_on_lets_the_request_through(): void
    {
        config(['features.expenditure_pages' => true]);

        $response = (new EnsureFeatureEnabled())->handle(\Illuminate\Http\Request::create('/x'), fn () => response('ok'), 'expenditure_pages');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_the_old_sidebar_no_longer_links_the_pages(): void
    {
        $menu = file_get_contents(resource_path('views/template/aside/menu/menus.blade.php'));

        $this->assertStringNotContainsString("url('expenditure')", $menu);
        $this->assertStringNotContainsString('Pemasukan / Pengeluaran', $menu);
        $this->assertStringNotContainsString("url('tier')", $menu);
        $this->assertStringNotContainsString('Loyalty Tier', $menu);
    }
}
