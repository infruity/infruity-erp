<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Master\Http\Controllers\ProductController;
use Tests\TestCase;

class StockHistoryTransferRowsTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Master/Http/Controllers/ProductController.php'));
    }

    public function test_hpp_history_names_the_creator_of_a_transfer(): void
    {
        $this->assertStringContainsString("'transfer' => 'transfer',", $this->source());
    }

    public function test_hpp_history_links_a_transfer_to_its_sender_or_receiver_page(): void
    {
        $source = $this->source();

        $this->assertStringContainsString("->editColumn('url', function (\$item) {", $source);
        $this->assertStringContainsString("(\$item->type === '-' ? 'transfer-pengirim' : 'transfer-penerima') . '/show/' . \$item->trx_id", $source);
        $this->assertTrue(Route::has('transfer-pengirim.show'));
        $this->assertTrue(Route::has('transfer-penerima.show'));
    }

    public function test_stock_history_never_links_to_the_missing_transfer_show_page(): void
    {
        $method = new \ReflectionMethod(ProductController::class, 'getTargetUrlFromTransaction');
        $controller = (new \ReflectionClass(ProductController::class))->newInstanceWithoutConstructor();

        $this->assertSame(url('transfer-pengirim/show/7'), $method->invoke($controller, (object) ['url' => 'transfer', 'id' => 7, 'reff' => 'mengirim']));
        $this->assertSame(url('transfer-penerima/show/7'), $method->invoke($controller, (object) ['url' => 'transfer', 'id' => 7, 'reff' => 'menerima']));
        $this->assertSame(url('pos/show/9'), $method->invoke($controller, (object) ['url' => 'pos', 'id' => 9, 'reff' => 'pos']));
        $this->assertStringNotContainsString("'sortir', 'transfer']", $this->source());
    }
}
