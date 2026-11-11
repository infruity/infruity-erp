<?php

namespace Tests\Feature;

use Tests\TestCase;

final class StockOpnameSubmitGuardTest extends TestCase
{
    public function test_form_asks_for_a_product_before_posting(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/stock-opname/index.blade.php'));
        $start = strpos($view, "$('#kt_modal_add_customer_form').on('submit'");
        $this->assertNotFalse($start);
        $handler = substr($view, $start, 1400);

        $guard = strpos($handler, "if (!$('#product_id').val())");
        $this->assertNotFalse($guard, 'The submit handler must check the product before it sends anything.');
        $this->assertLessThan(strpos($handler, '$.ajax('), $guard);
        $this->assertStringContainsString('Pilih produk', $handler);
    }
}
