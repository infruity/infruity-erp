<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * A sale's HPP is frozen when it is first paid, not when its draft was saved, and the stock ledger places the sale at
 * that same moment, so the profit on the note and the ledger's COGS agree.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PosHppAtPaymentTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $fruit;
    private int $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // saveTransaction disconnects the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->at('08:00');
        $this->branch = $this->makeBranch('HPPBAYAR');
        $this->fruit = $this->makeProduct('Apel', 40000);
        $this->giveStock($this->fruit, $this->branch, 10, 10000);
        $this->cash = $this->paymentMethodId('Cash');
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function at(string $time): void
    {
        Carbon::setTestNow(Carbon::parse(date('Y-m-d') . ' ' . $time . ':00'));
    }

    /** 10 more at Rp20.000 on top of 10 at Rp10.000 moves the HPP to Rp15.000. */
    private function buyAtHigherPrice(int $product): void
    {
        $id = DB::table('wholesale')->insertGetId([
            'uuid' => (string) Str::uuid(), 'branch_id' => $this->branch, 'order_number' => 'WQA' . Str::upper(Str::random(8)),
            'order_date' => date('Y-m-d'), 'status' => 'posting', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('wholesale_product')->insert([
            'wholesale_id' => $id, 'product_id' => $product, 'quantity' => 10, 'price' => 20000, 'status' => 'complete',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function saveDraft(array $override): int
    {
        $payload = array_merge([
            'customer_id' => null, 'date' => date('Y-m-d'), 'invoice_number' => 'INVQA' . Str::upper(Str::random(8)),
            'items' => [], 'discount' => 0, 'ongkir' => 0, 'discount_ongkir' => 0, 'status' => 'draft', 'branch_id' => $this->branch,
        ], $override);

        return (int) $this->postJson('/pos/save-transaction', $payload)->assertOk()->json('transaksi_id');
    }

    private function saleOfFruit(): int
    {
        return $this->saveDraft([
            'items' => [['id' => $this->fruit, 'price' => 40000, 'qty' => 2, 'discount' => 0, 'total_input' => 80000]],
            'subtotal' => 80000, 'total' => 80000,
        ]);
    }

    private function pay(int $pos, int $amount): void
    {
        $this->postJson('/pos/savePayment', [
            'idempotency_key' => (string) Str::uuid(), 'date' => date('Y-m-d'), 'transaction_id' => $pos,
            'payments' => [['payment_id' => $this->cash, 'amount' => number_format($amount, 0, ',', '.')]],
            'total_payment' => $amount,
        ])->assertOk()->assertJson(['success' => true]);
    }

    private function line(int $pos): object
    {
        return DB::table('pos_transaction_detail')->where('pos_id', $pos)->whereNull('deleted_at')->first();
    }

    private function ledgerCogs(int $product, int $pos): float
    {
        return (float) DB::table('product_hpp')->where('product_id', $product)->where('trx_type', 'penjualan')
            ->where('trx_id', $pos)->sum('cogs');
    }

    public function test_hpp_is_taken_when_the_sale_is_paid_and_matches_the_ledger(): void
    {
        $this->at('09:00');
        $pos = $this->saleOfFruit();
        $this->assertEquals(10000, $this->line($pos)->hpp, 'a draft shows the HPP of the moment it was saved');

        $this->at('10:00');
        $this->buyAtHigherPrice($this->fruit);

        $this->at('11:00');
        $this->pay($pos, 80000);

        $line = $this->line($pos);
        $this->assertEquals(15000, $line->hpp);
        $this->assertEquals(30000, $line->subtotal_hpp);
        $this->assertEquals(25000, $line->exp);
        $this->assertEquals(30000, $this->ledgerCogs($this->fruit, $pos));
        $this->assertSame(18.0, InventorySnapshot::stockAvailable([$this->fruit], $this->branch)[$this->fruit]);
    }

    public function test_paying_off_a_debt_does_not_move_the_hpp_again(): void
    {
        $this->at('09:00');
        $pos = $this->saleOfFruit();
        $this->pay($pos, 30000);
        $paidAt = DB::table('pos_transaction')->where('id', $pos)->value('created_at');

        $this->at('10:00');
        $this->buyAtHigherPrice($this->fruit);
        $this->at('11:00');
        $this->pay($pos, 50000);

        $this->assertEquals(10000, $this->line($pos)->hpp);
        $this->assertSame($paidAt, DB::table('pos_transaction')->where('id', $pos)->value('created_at'));
        $this->assertEquals(20000, $this->ledgerCogs($this->fruit, $pos));
    }

    public function test_juice_made_for_the_sale_is_costed_when_paid(): void
    {
        $juice = $this->makeProduct('Jus', 30000);
        DB::table('products')->where('id', $juice)->update(['fee' => 1000]);

        $this->at('09:00');
        $pos = $this->saveDraft([
            'items' => [['id' => 'jus1', 'price' => 30000, 'qty' => 2, 'discount' => 0, 'total_input' => 60000]],
            'jus' => [[
                'productId' => $juice, 'qty' => 2, 'price' => 30000, 'discount' => 0, 'total_input' => 60000,
                'product_receipt_id' => [$this->fruit], 'product_receipt_qty' => [1],
            ]],
            'subtotal' => 60000, 'total' => 60000,
        ]);
        $this->assertEquals(11000, $this->line($pos)->hpp);

        $this->at('10:00');
        $this->buyAtHigherPrice($this->fruit);
        $this->at('11:00');
        $this->pay($pos, 60000);

        $production = DB::table('production')->where('pos_id', $pos)->first();
        $this->assertSame('complete', $production->status);
        $this->assertEquals(16000, $production->hpp_per_unit);
        $this->assertEquals(16000, $this->line($pos)->hpp);
        $this->assertEquals(32000, $this->line($pos)->subtotal_hpp);
        $this->assertEquals(32000, $this->ledgerCogs($juice, $pos));
        $this->assertEquals(16000, DB::table('products')->where('id', $juice)->value('hpp'));
    }

    public function test_parcel_ingredients_are_costed_when_paid(): void
    {
        $this->at('09:00');
        $pos = $this->saveDraft([
            'items' => [['id' => 'parcel1', 'price' => 100000, 'qty' => 1, 'discount' => 0, 'total_input' => 100000]],
            'parcel' => [[
                'qty' => 1, 'budget' => '100000', 'fee' => '0', 'kemasan' => 'Kemasan QA ' . Str::random(6),
                'data' => [['product' => $this->fruit, 'qty' => 3, 'price' => 40000]],
            ]],
            'subtotal' => 100000, 'total' => 100000,
        ]);
        $this->assertEquals(30000, $this->line($pos)->hpp);

        $this->at('10:00');
        $this->buyAtHigherPrice($this->fruit);
        $this->at('11:00');
        $this->pay($pos, 100000);

        $this->assertEquals(45000, $this->line($pos)->hpp);
        $this->assertEquals(45000, $this->line($pos)->subtotal_hpp);
    }

    public function test_settling_from_the_delivery_order_page_does_the_same(): void
    {
        $this->at('09:00');
        $pos = $this->saleOfFruit();

        $this->at('10:00');
        $this->buyAtHigherPrice($this->fruit);
        $this->at('11:00');
        $this->putJson("/delivery-order/set-selesai/{$pos}", ['nominal' => '80000'])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('paid', DB::table('pos_transaction')->where('id', $pos)->value('status'));
        $this->assertEquals(15000, $this->line($pos)->hpp);
        $this->assertEquals(30000, $this->ledgerCogs($this->fruit, $pos));
    }
}
