<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Master\Entities\Customer;
use Modules\Pos\Entities\Payment;
use Modules\Pos\Entities\PosModel;
use Tests\TestCase;

class ReceiptViewDataTest extends TestCase
{
    public function test_thermal_receipt_uses_the_current_invoice_instead_of_demo_content(): void
    {
        $invoice = new PosModel(['invoice_number' => 'INV-UI-FIXTURE-42', 'date' => '2026-10-26', 'total' => 10000]);
        $invoice->id = 42;
        $cashier = new User();
        $cashier->nm_user = 'Kasir Fixture';
        $invoice->setRelation('user', $cashier);
        $invoice->setRelation('customer', new Customer(['name' => 'Pelanggan Cetak Fixture']));
        $invoice->setRelation('paymentDetails', new Collection([new Payment(['total' => 9000])]));
        $html = view('pos::pos.receipt2', ['data' => $invoice, 'detail' => collect(), 'setting' => null])->render();

        $this->assertStringContainsString('INV-UI-FIXTURE-42', $html);
        $this->assertStringContainsString('Pelanggan Cetak Fixture', $html);
        $this->assertStringContainsString('Kasir Fixture', $html);
        $this->assertStringContainsString('Tidak ada rincian produk.', $html);
        $this->assertStringNotContainsString('TOKO MAJU JAYA', $html);
        $this->assertStringNotContainsString('Budi Santoso', $html);
    }
}
