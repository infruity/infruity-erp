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
        $this->assertStringContainsString('Total Tagihan (Setelah Voucher)', $html);
        $this->assertStringContainsString('aria-label="Nominal pembayaran yang diterima"', $html);
        $this->assertStringContainsString('data-delivery-order-payment-balance', $html);
        $this->assertStringContainsString('data-delivery-order-payment-back', $html);
        $this->assertStringContainsString('data-delivery-order-payment-submit>Bayar</button>', $html);

        $source = file_get_contents(base_path('Modules/Pos/Resources/views/delivery-order/index.blade.php'));
        $this->assertIsString($source);
        $this->assertStringContainsString('window.DeliveryOrderMobileCards?.openPaymentReceiptStep?.(id)', $source);
        $this->assertStringContainsString("function setSelesai(id)", $source);
        $this->assertStringNotContainsString("title: 'Barang diterima?'", $source);
        $this->assertStringNotContainsString('delivery-order-receipt-status', $html);
        $this->assertStringNotContainsString('delivery-order-receipt-outcome', $html);
        $detail = explode('data-delivery-order-detail-body>', $html)[1];
        $detail = explode('data-delivery-order-detail-footer', $detail)[0];
        $this->assertStringNotContainsString('delivery-order-billing-summary', $detail);
        $this->assertSame(1, substr_count($html, '<dl class="delivery-order-billing-summary"'));
        $this->assertStringContainsString('data-delivery-order-payment-amount-label', $html);
        $this->assertStringContainsString('data-delivery-order-payment-balance hidden', $html);
    }
}
