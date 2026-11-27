<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Money on a sale: totals are whole rupiah (the cashier cannot type a fraction), and a delivery takes money once.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PosMoneyRoundingTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $fruit;
    private int $cash;
    private int $transfer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // saveTransaction disconnects the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('RUPIAH');
        $this->fruit = $this->makeProduct('Apel', 20699);
        $this->giveStock($this->fruit, $this->branch, 50, 10000);
        $this->cash = $this->paymentMethodId('Cash');
        $this->transfer = $this->paymentMethodId('Transfer');
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function saveSale(int $price, int $discountPercent = 10): int
    {
        return (int) $this->postJson('/pos/save-transaction', [
            'customer_id' => null, 'date' => date('Y-m-d'), 'invoice_number' => 'INVQA' . Str::upper(Str::random(8)),
            'items' => [['id' => $this->fruit, 'price' => $price, 'qty' => 1, 'discount' => 0, 'total_input' => $price]],
            'subtotal' => $price, 'total' => $price, 'discount' => $discountPercent, 'discount_type' => 'percent',
            'ongkir' => 0, 'discount_ongkir' => 0, 'status' => 'draft', 'branch_id' => $this->branch,
        ])->assertOk()->json('transaksi_id');
    }

    private function pay(int $pos, int $amount, ?int $method = null)
    {
        return $this->postJson('/pos/savePayment', [
            'idempotency_key' => (string) Str::uuid(), 'date' => date('Y-m-d'), 'transaction_id' => $pos,
            'payments' => [['payment_id' => $method ?? $this->cash, 'amount' => number_format($amount, 0, ',', '.')]],
            'total_payment' => $amount,
        ]);
    }

    private function sale(int $pos): object
    {
        return DB::table('pos_transaction')->find($pos);
    }

    // ---- whole rupiah ----------------------------------------------------------------------

    public function test_a_percent_discount_gives_a_whole_rupiah_total_that_can_be_paid_exactly(): void
    {
        foreach ([20699 => 18629, 20621 => 18559, 20625 => 18563] as $price => $total) {
            $pos = $this->saveSale($price);
            $this->assertEquals($total, $this->sale($pos)->total, "price {$price} less 10%");

            $this->pay($pos, $total)->assertOk()->assertJson(['success' => true]);
            $this->assertSame('paid', $this->sale($pos)->status);
        }
    }

    public function test_changing_the_totals_on_the_payment_page_also_gives_whole_rupiah(): void
    {
        $pos = $this->saveSale(20699, 0);

        $this->postJson("/pos/payment/{$pos}/totals", ['discount' => 10, 'discount_type' => 'percent', 'ongkir' => 0])
            ->assertOk()->assertJson(['total' => 18629]);

        $this->assertEquals(18629, $this->sale($pos)->total);
    }

    public function test_a_note_already_saved_with_a_fraction_is_settled_by_the_rounded_amount(): void
    {
        foreach (['18629.10' => 18629, '18629.90' => 18630] as $stored => $due) {
            $pos = $this->saveSale(20699);
            DB::table('pos_transaction')->where('id', $pos)->update(['total' => $stored]);

            $page = $this->get("/pos/payment/{$pos}")->assertOk()->getContent();
            $this->assertStringContainsString("totalDue: {$due},", $page);

            // A transfer has no change, so the server must ask for exactly what the page shows.
            $this->pay($pos, $due, $this->transfer)->assertOk()->assertJson(['success' => true]);
            $this->assertSame('paid', $this->sale($pos)->status);
            $this->assertEquals(0, DB::table('pos_payment')->where('pos_id', $pos)->value('return'));
        }
    }

    public function test_one_rupiah_short_is_still_a_debt(): void
    {
        $pos = $this->saveSale(20699);

        $this->pay($pos, 18628)->assertOk();

        $this->assertSame('debt', $this->sale($pos)->status);
        $this->pay($pos, 1)->assertOk();
        $this->assertSame('paid', $this->sale($pos)->status);
    }

    // ---- delivery order --------------------------------------------------------------------

    private function delivery(int $total = 50000, string $status = 'draft'): int
    {
        $pos = $this->saveSale($total, 0);
        DB::table('pos_transaction')->where('id', $pos)->update(['status' => $status, 'ongkir_status' => 'draft']);

        return $pos;
    }

    private function receive(int $pos, $nominal = null)
    {
        return $this->putJson("/delivery-order/set-selesai/{$pos}", $nominal === null ? [] : ['nominal' => $nominal]);
    }

    private function payments(int $pos): int
    {
        return DB::table('pos_payment')->where('pos_id', $pos)->count();
    }

    public function test_receiving_with_money_marks_the_delivery_received(): void
    {
        $pos = $this->delivery();

        $this->receive($pos, 50000)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('delivered', $this->sale($pos)->ongkir_status);
        $this->assertSame('paid', $this->sale($pos)->status);
        $this->assertEquals(50000, $this->sale($pos)->paid);
    }

    public function test_a_second_payment_for_the_same_delivery_is_refused(): void
    {
        foreach ([50000, 20000] as $first) {
            $pos = $this->delivery();
            $this->receive($pos, $first)->assertOk();

            $this->receive($pos, 20000)->assertStatus(422)->assertJson(['success' => false]);

            $this->assertSame(1, $this->payments($pos));
            $this->assertEquals($first, $this->sale($pos)->paid);
        }
    }

    public function test_a_paid_sale_can_be_marked_received_but_takes_no_more_money(): void
    {
        $pos = $this->delivery();
        $this->pay($pos, 50000);
        DB::table('pos_transaction')->where('id', $pos)->update(['status' => 'paid', 'paid' => 50000]);
        $before = $this->payments($pos);

        $this->receive($pos, 50000)->assertStatus(422);
        $this->assertSame('draft', $this->sale($pos)->ongkir_status);

        $this->receive($pos)->assertOk();
        $this->assertSame('delivered', $this->sale($pos)->ongkir_status);
        $this->receive($pos)->assertOk();
        $this->assertSame($before, $this->payments($pos));
    }

    public function test_money_above_the_bill_is_change_not_paid_money(): void
    {
        $pos = $this->delivery();

        $this->receive($pos, 60000)->assertOk();

        $this->assertEquals(50000, $this->sale($pos)->paid);
        $this->assertEquals(10000, DB::table('pos_payment')->where('pos_id', $pos)->value('return'));
    }

    public function test_the_cashier_form_restores_the_shipping_discount_of_a_draft(): void
    {
        $script = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-buah.blade.php'));

        $this->assertStringContainsString('this.diskonOngkir = parseFloat(transactionData.ongkir_discount || 0);', $script);
        $this->assertStringContainsString('this.diskonOngkirType = transactionData.ongkir_discount_type || legacyUnit(this.diskonOngkir);', $script);
    }
}
