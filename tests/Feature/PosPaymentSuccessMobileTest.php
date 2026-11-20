<?php

namespace Tests\Feature;

use Modules\Pos\Entities\Payment;
use Modules\Pos\Entities\PosModel;
use Tests\TestCase;

final class PosPaymentSuccessMobileTest extends TestCase
{
    private function renderSuccess(float $remaining, float $change): string
    {
        $pos = (new PosModel())->forceFill(['id' => 26, 'total' => 28000, 'uuid' => 'pos-demo']);
        $pos->setRelation('customer', null);
        $payment = (new Payment())->forceFill([
            'id' => 17, 'uuid' => 'payment-demo', 'payment_method' => '["tunai"]',
            'total' => 28000, 'remaining' => $remaining, 'return' => $change,
            'created_at' => '2026-11-16 17:57:00',
        ]);
        $payment->setRelation('pos', $pos);

        return view('pos::pos.payment-success2', ['data' => $payment])->render();
    }

    public function test_mobile_success_shows_the_payment_and_reference_actions(): void
    {
        $html = $this->renderSuccess(0, 0);

        foreach (['Transaksi Berhasil', 'Senin, 16 Nov 2026 17:57', 'Tunai', 'Rp28.000', 'Kembalian', 'Rp0', 'Daftar Transaksi', 'Cetak nota', 'pos-success-print', 'Bagikan nota', 'pos-success-share', 'Transaksi Baru', 'preview=mobile'] as $part) {
            $this->assertStringContainsString($part, $html);
        }
    }

    public function test_mobile_success_shows_remaining_balance_for_partial_payment(): void
    {
        $html = $this->renderSuccess(5000, 0);

        $this->assertStringContainsString('<dt>Kurang</dt><dd>Rp5.000</dd>', $html);
    }
}
