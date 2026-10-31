<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class DeliveryOrderMobilePaymentTest extends TestCase
{
    public function test_detail_modal_renders_reference_payment_receipt_step(): void
    {
        $this->app->instance('request', Request::create('/delivery-order', 'GET'));
        $html = view('pos::delivery-order.index', [
            'branches' => collect([(object) ['id' => 1, 'name' => 'Toko GKB']]),
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('data-delivery-order-payment-step', $html);
        $this->assertStringContainsString('Penerimaan Pembayaran', $html);
        $this->assertStringContainsString('data-delivery-order-payment-field="subtotal"', $html);
        $this->assertStringContainsString('data-delivery-order-payment-field="shipping-fee"', $html);
        $this->assertStringContainsString('data-delivery-order-payment-field="grand-total"', $html);
        $this->assertStringContainsString('Total Belanja + Ongkir', $html);
        $this->assertStringContainsString('aria-label="Nominal pembayaran yang diterima"', $html);
        $this->assertStringContainsString('data-delivery-order-payment-balance', $html);
        $this->assertStringContainsString('data-delivery-order-payment-back', $html);
        $this->assertStringContainsString('data-delivery-order-payment-submit>Bayar</button>', $html);
    }
}
