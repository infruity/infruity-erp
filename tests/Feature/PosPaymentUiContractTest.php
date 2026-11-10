<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The payment screen already follows the new visual language. These tests lock the money-safety
 * behaviour so later UI work cannot silently weaken it.
 */
final class PosPaymentUiContractTest extends TestCase
{
    private function read(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_payment_form_locks_every_input_while_sending_or_waiting_for_a_retry(): void
    {
        $view = $this->read('Modules/Pos/Resources/views/pos/payment.blade.php');

        $this->assertStringContainsString('x-show="retryPending"', $view);
        $this->assertGreaterThanOrEqual(5, substr_count($view, ':disabled="loading || retryPending"'));
        $this->assertStringContainsString(':disabled="loading"', $view);
        $this->assertStringContainsString(':aria-busy="loading"', $view);
        $this->assertStringContainsString('@click="submitPayment()"', $view);
        $this->assertStringContainsString("'Coba ulang pembayaran'", $view);
    }

    public function test_payment_submit_uses_the_idempotent_attempt_and_the_existing_endpoint(): void
    {
        $js = $this->read('Modules/Pos/Resources/views/pos/js-payment.blade.php');

        $this->assertStringContainsString('assets/js/pos-payment-attempt.js', $js);
        $this->assertStringContainsString("route('pos.savePayment')", $js);
        $this->assertStringContainsString("'Idempotency-Key': key", $js);
        $this->assertStringContainsString("'X-CSRF-TOKEN'", $js);
        $this->assertStringContainsString('this.retryPending = !!this.paymentAttempt.pending', $js);
        // Validation failures must release the loading lock instead of leaving the button stuck.
        $this->assertGreaterThanOrEqual(2, substr_count($js, 'this.loading = false'));
    }

    public function test_the_page_never_offers_a_way_to_change_status_without_paying(): void
    {
        $view = $this->read('Modules/Pos/Resources/views/pos/payment.blade.php');

        $this->assertStringNotContainsString('markAsPaid', $view);
        $this->assertStringNotContainsString('setStatus', $view);
        $this->assertStringNotContainsString('statusLunas', $view);
    }

    public function test_payment_bill_and_exit_links_stay_available(): void
    {
        $view = $this->read('Modules/Pos/Resources/views/pos/payment.blade.php');

        foreach (['Total Belanja', 'TOTAL TERBAYAR', 'paymentStatus.status', "route('pos.printDraftPayment', \$data->id)", "route('pos.edit', \$data->id)", "route('pos.index')"] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }
    }

    public function test_payment_page_follows_the_reference_cards_and_footer(): void
    {
        $view = $this->read('Modules/Pos/Resources/views/pos/payment.blade.php');

        foreach ([
            'class="pos-form pos-form--reference"', 'METODE PEMBAYARAN', 'Split Pembayaran', 'Komposisi Pembayaran',
            'PILIH KURIR', 'pos-ref-footer', 'pos-ref-pay__btn', 'aria-label="Kembali"', 'aria-label="Cetak draf"',
            'assets/css/pos-form.css',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }
        // Address and courier are read from the saved transaction; choosing them writes, so they stay read-only here.
        // Whole rupiah in the footer, like the list (toNumber() prints ",00").
        $this->assertStringContainsString("number_format(floor(\$data->total - \$data->paid), 0, ',', '.')", $view);
        $this->assertStringContainsString('$data->ongkir_address', $view);
        $this->assertStringContainsString('$data->courier', $view);
        $this->assertStringNotContainsString('name="courier_id"', $view);
        // Every money-affecting control keeps the lock while sending or waiting for a retry.
        $this->assertGreaterThanOrEqual(6, substr_count($view, ':disabled="loading || retryPending"'));
        $this->assertStringContainsString('@click="submitPayment()"', $view);
    }

    public function test_split_toggle_only_adds_or_drops_extra_rows_and_never_changes_the_payload_shape(): void
    {
        $js = $this->read('Modules/Pos/Resources/views/pos/js-payment.blade.php');

        $this->assertStringContainsString('split: false', $js);
        $this->assertStringContainsString('setSplit(', $js);
        $this->assertStringContainsString('this.payments.splice(1)', $js);
        $this->assertStringContainsString('this.split = this.payments.length > 1', $js);
    }

    public function test_receipt_and_confirmation_pages_keep_print_and_share_actions(): void
    {
        $receipt = $this->read('Modules/Pos/Resources/views/pos/receipt2.blade.php');
        $success = $this->read('Modules/Pos/Resources/views/pos/payment-success2.blade.php');

        $this->assertStringContainsString('thermal-receipt', $receipt);
        $this->assertStringContainsString('@media print', $receipt);
        $this->assertStringContainsString("url('pos/printNota')", $success);
        $this->assertStringContainsString('cek-nota', $success);
    }
    public function test_pay_asks_for_confirmation_before_anything_is_sent(): void
    {
        $view = $this->read('Modules/Pos/Resources/views/pos/payment.blade.php');
        $js = $this->read('Modules/Pos/Resources/views/pos/js-payment.blade.php');

        // The dialog from the reference: title, copy, summary, Batal / Selesaikan Transaksi.
        foreach ([
            'Selesaikan Transaksi?', 'Transaksi ini akan disimpan dan diproses', 'role="dialog"', 'aria-modal="true"',
            'x-show="confirmOpen"', '@click="confirmOpen = false"', '@click="confirmPayment()"', '@keydown.escape.window="confirmOpen = false"',
            'Cabang', 'Pelanggan', 'Item Belanja', 'Total Belanja', 'Dibayar', 'x-ref="confirmCancel"',
        ] as $contract) {
            $this->assertStringContainsString($contract, $view, "Hilang: {$contract}");
        }
        // The confirm button locks while the request is in flight, like every other money control.
        $this->assertStringContainsString(':disabled="loading"', substr($view, strpos($view, 'confirmPayment()') - 200, 400));

        // Bayar only opens the dialog; the request is built and sent from confirmPayment() alone.
        $this->assertStringContainsString('confirmOpen: false', $js);
        $this->assertStringContainsString('this.confirmOpen = true', $js);
        $this->assertStringContainsString('confirmPayment() {', $js);
        $submit = substr($js, strpos($js, 'submitPayment() {'), strpos($js, 'confirmPayment() {') - strpos($js, 'submitPayment() {'));
        $this->assertStringNotContainsString('this.doSavePayment(payload);\n                },', $submit);
        $confirm = substr($js, strpos($js, 'confirmPayment() {'), 400);
        $this->assertStringContainsString('this.doSavePayment(this.buildPayload())', $confirm);
        $this->assertStringContainsString('if (this.loading) return;', $confirm);
        // A retry of an attempt the cashier already confirmed goes straight out with the same key.
        $this->assertMatchesRegularExpression('/pending\)\s*\{\s*(?:\/\/[^\n]*\s*)?this\.loading = true;\s*this\.doSavePayment\(this\.paymentAttempt\.pending\)/', $js);
    }

    public function test_confirmation_summary_is_display_only_and_uses_the_stored_totals(): void
    {
        $view = $this->read('Modules/Pos/Resources/views/pos/payment.blade.php');

        // Same rule as saveTransaction: 100 or less is a percentage, above is rupiah.
        $this->assertStringContainsString('$discountAmount', $view);
        $this->assertStringContainsString('<= 100', $view);
        $this->assertStringContainsString('$data->branch?->name', $view);
        $this->assertStringContainsString("\$detail->sum('subtotal')", $view);
        $this->assertStringNotContainsString('$data->subtotal', $view);
        $this->assertStringContainsString('$data->ongkir', $view);
        $this->assertStringContainsString('$detail->count()', $view);
        // The branch loads lazily from the model (one small query); the controller stays untouched.
    }
}
