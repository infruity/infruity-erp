<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Master\Entities\Customer;
use Modules\Pos\Entities\Payment;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Pos\Entities\PosModel;
use Tests\TestCase;

/**
 * pos/show/{id} (receipt.blade.php) follows the reference "Nota Transaksi" screen:
 * brand header, label/value rows, ### status ###, items with qty x price, totals, footer.
 */
final class PosInvoiceNotaUiTest extends TestCase
{
    private function render(string $status, array $overrides = [], bool $withPayment = true, bool $receiptPreview = false, array $lineOverrides = []): string
    {
        $invoice = (new PosModel())->forceFill(array_merge([
            'invoice_number' => 'INV-NOTA-7', 'status' => $status, 'discount' => 0, 'ongkir' => 7000,
            'ongkir_discount' => 0, 'paid' => 193000, 'return' => 0,
        ], $overrides));
        $invoice->id = 7;
        $invoice->created_at = '2026-10-26 13:51:00';
        $cashier = new User();
        $cashier->nm_user = 'Kasir Nota';
        $invoice->setRelation('user', $cashier);
        $invoice->setRelation('customer', (new Customer())->forceFill(['name' => 'Bu Enda', 'whatsapp' => '08123456789']));
        $invoice->setRelation('paymentDetails', new Collection($withPayment ? [new Payment(['payment_method' => 'Tunai', 'payment_amount' => '193000'])] : []));

        $line = new PosDetailModel(array_merge(['quantity' => 2, 'price' => 18000, 'subtotal' => 36000, 'discount' => 0, 'type' => 'product'], $lineOverrides));

        return view('pos::pos.receipt', ['data' => $invoice, 'detail' => new Collection([$line]), 'parcelDetail' => collect(), 'setting' => null, 'receiptPreview' => $receiptPreview])->render();
    }

    public function test_payment_preview_returns_to_payment_and_shows_unpaid_status(): void
    {
        $html = $this->render('draft', ['paid' => 0], false, true);

        $this->assertStringContainsString('### Belum Lunas ###', $html);
        $this->assertStringContainsString(route('pos.payment', 7), $html);
        $this->assertStringNotContainsString('ph-printer', $html);
    }

    public function test_receipt_uses_the_stored_transaction_total_when_available(): void
    {
        $html = $this->render('draft', ['total' => 32000], false, true);

        $this->assertMatchesRegularExpression('/Total \(1 Produk\).*?Rp 32\.000/s', $html);
    }

    public function test_item_gross_matches_the_price_and_quantity_shown_on_the_receipt(): void
    {
        $html = $this->render('draft', [], false, true, ['quantity' => 10, 'price' => 16000, 'subtotal' => 160000, 'discount' => 160000]);

        $this->assertMatchesRegularExpression('/10 Pcs x Rp 16\.000\s*<\/span>\s*<span>Rp 160\.000<\/span>/', $html);
    }

    public function test_follows_the_reference_nota_layout(): void
    {
        $html = $this->render('paid');

        foreach (['nota-topbar', 'Nota Transaksi', 'nota-card', 'No. Invoice', 'INV-NOTA-7', 'Kasir Nota', '### Sudah Lunas ###', 'Bu Enda', '08123456789', 'Total (1 Produk)', 'Cetak Nota', 'Terima kasih atas kunjungan Anda'] as $part) {
            $this->assertStringContainsString($part, $html, "Hilang: {$part}");
        }
        $this->assertStringContainsString('Rp 36.000', $html);
        $this->assertStringContainsString('Rp 7.000', $html);
        $this->assertStringContainsString('26 Okt 2026, 13:51', $html);
        // The old gradient/"Paid" badge invoice is gone.
        $this->assertStringNotContainsString('erp-invoice-header', $html);
        $this->assertStringNotContainsString('receipt-header', $html);
    }

    public function test_status_wording_matches_the_reference_receipt(): void
    {
        $this->assertStringContainsString('### Belum Lunas ###', $this->render('draft'));
        $this->assertStringContainsString('### Belum Lunas ###', $this->render('debt', ['paid' => 0], false));
        $this->assertStringContainsString('### Bayar Sebagian ###', $this->render('debt'));
    }

    public function test_whatsapp_share_sends_the_nota_as_a_picture_not_as_a_link(): void
    {
        $html = $this->render('paid', ['uuid' => 'uuid-nota-7']);

        $this->assertStringContainsString('aria-label="Kirim ke WhatsApp"', $html);
        $this->assertStringContainsString('function sendReceiptToWA()', $html);
        $this->assertStringContainsString('assets/js/nota-share.js', $html);
        $this->assertStringContainsString('NotaShare.share(', $html);
        $this->assertStringContainsString('NotaShare.publish(', $html);
        $this->assertStringNotContainsString('cek-nota', $html, 'the link is built by the server, never typed into the message here');
    }

    public function test_print_and_back_actions_are_kept_off_the_printout(): void
    {
        $html = $this->render('paid');

        $this->assertStringContainsString('onclick="window.print()"', $html);
        $this->assertStringContainsString("urlParams.get('print') === 'true'", $html);
        $print = substr($html, strrpos($html, '@media print'));
        $this->assertStringContainsString('.nota-topbar, .nota-bar, .nota-desktop-actions { display:none !important; }', $print);
        $this->assertStringContainsString('height:auto; min-height:0; max-width:100%; overflow:visible', $print);
        $this->assertStringContainsString('.nota-items { overflow:visible; }', $print);
    }
}
