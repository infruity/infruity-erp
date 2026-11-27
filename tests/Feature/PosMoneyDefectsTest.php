<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Money and stock rules around a sale found by the 2026-11-27 defect hunt (H-04, H-07, H-08, H-10..H-13, H-31, H-32).
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PosMoneyDefectsTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $fruit;
    private int $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('UANG');
        $this->fruit = $this->makeProduct('Apel', 40000);
        $this->giveStock($this->fruit, $this->branch, 10, 10000);
        $this->cash = $this->paymentMethodId('Cash');
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function sale(array $override = [])
    {
        return $this->postJson('/pos/save-transaction', $override + [
            'customer_id' => null, 'date' => date('Y-m-d'), 'invoice_number' => 'INVQA' . Str::upper(Str::random(8)),
            'items' => [['id' => $this->fruit, 'price' => 40000, 'qty' => 1, 'discount' => 0, 'total_input' => 40000]],
            'subtotal' => 40000, 'total' => 40000, 'discount' => 0, 'ongkir' => 0, 'discount_ongkir' => 0,
            'status' => 'draft', 'branch_id' => $this->branch,
        ]);
    }

    private function saleOfQty(int $qty): int
    {
        return (int) $this->sale([
            'items' => [['id' => $this->fruit, 'price' => 40000, 'qty' => $qty, 'discount' => 0, 'total_input' => 40000 * $qty]],
            'subtotal' => 40000 * $qty, 'total' => 40000 * $qty,
        ])->assertOk()->json('transaksi_id');
    }

    private function pay(int $pos, int $amount)
    {
        return $this->postJson('/pos/savePayment', [
            'idempotency_key' => (string) Str::uuid(), 'date' => date('Y-m-d'), 'transaction_id' => $pos,
            'payments' => [['payment_id' => $this->cash, 'amount' => number_format($amount, 0, ',', '.')]],
            'total_payment' => $amount,
        ]);
    }

    private function row(int $pos): object
    {
        return DB::table('pos_transaction')->find($pos);
    }

    private function stock(int $product): float
    {
        return InventorySnapshot::stockAvailable([$product], $this->branch)[$product] ?? 0.0;
    }

    // ---- H-08 --------------------------------------------------------------------------------

    private function customerWithVoucher(int $voucher): int
    {
        $customer = DB::table('customer')->insertGetId(['name' => 'QA Voucher', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('deposito')->insert(['customer_id' => $customer, 'deposito_date' => date('Y-m-d'), 'tier_id' => 1,
            'voucher' => $voucher, 'voucher_qty' => 2, 'exp' => 0, 'deposito' => $voucher * 2, 'created_at' => now(), 'updated_at' => now()]);

        return $customer;
    }

    public function test_a_voucher_that_covers_the_bill_settles_it_with_a_zero_payment(): void
    {
        $customer = $this->customerWithVoucher(100000);
        $pos = (int) $this->sale(['customer_id' => $customer])->assertOk()->json('transaksi_id');

        $page = $this->get("/pos/payment/{$pos}")->assertOk()->getContent();
        $this->assertStringContainsString('totalDue: 0,', $page);

        $this->pay($pos, 40000)->assertStatus(422);
        $this->pay($pos, 0)->assertOk()->assertJson(['success' => true]);

        $this->assertSame('paid', $this->row($pos)->status);
        $this->assertEquals(40000, $this->row($pos)->voucher);
        $this->assertEquals(1, DB::table('deposito')->where('customer_id', $customer)->value('voucher_qty'));
        $this->assertSame(9.0, $this->stock($this->fruit));
    }

    public function test_a_zero_payment_is_refused_while_something_is_owed(): void
    {
        $pos = $this->saleOfQty(1);

        $this->pay($pos, 0)->assertStatus(422);

        $this->assertSame('draft', $this->row($pos)->status);
    }

    /** Notes left in debt by the old fraction rounding owe less than one rupiah and can now be closed. */
    public function test_a_debt_of_less_than_one_rupiah_can_be_closed(): void
    {
        $pos = $this->saleOfQty(1);
        $this->pay($pos, 40000)->assertOk();
        DB::table('pos_transaction')->where('id', $pos)->update(['total' => 40000.10, 'status' => 'debt']);

        $this->pay($pos, 0)->assertOk();

        $this->assertSame('paid', $this->row($pos)->status);
    }

    // ---- H-26 --------------------------------------------------------------------------------

    public function test_a_deposit_must_pay_the_price_of_its_package(): void
    {
        $customer = DB::table('customer')->insertGetId(['name' => 'QA Deposit', 'created_at' => now(), 'updated_at' => now()]);
        $other = DB::table('customer')->insertGetId(['name' => 'QA Lain', 'created_at' => now(), 'updated_at' => now()]);
        $tier = DB::table('crm_tier')->insertGetId(['name' => 'QA ' . Str::random(8), 'level' => 1, 'exp' => 0,
            'voucher' => 100000, 'deposito' => 1000000, 'created_at' => now(), 'updated_at' => now()]);
        if (! DB::table('crm_point_schedule')->exists()) {
            DB::table('crm_point_schedule')->insert(['start_date' => date('Y-01-01'), 'end_date' => date('Y-12-31'), 'created_at' => now(), 'updated_at' => now()]);
        }
        $body = fn (string $amount, int $who) => ['customer_id' => $who, 'tier_id' => $tier, 'date' => date('Y-m-d'), 'deposito' => $amount];

        $this->postJson('/deposito', $body('1.000', $customer))->assertStatus(422);
        $this->assertSame(0, DB::table('deposito')->where('customer_id', $customer)->count());
        $id = $this->postJson('/deposito', $body('1.000.000', $customer))->assertCreated()->json('data.id');
        $this->assertEquals(10, DB::table('deposito')->where('id', $id)->value('voucher_qty'));

        $this->putJson("/deposito/{$id}", $body('5.000', $customer))->assertStatus(422);
        $pos = $this->saleOfQty(1);
        DB::table('pos_transaction')->where('id', $pos)->update(['deposito_id' => $id]);
        $this->putJson("/deposito/{$id}", $body('1.000.000', $other))->assertStatus(422);

        $this->assertEquals([$customer, 1000000], [(int) DB::table('deposito')->where('id', $id)->value('customer_id'), (int) DB::table('deposito')->where('id', $id)->value('deposito')]);
    }

    // ---- H-10 / H-11 -------------------------------------------------------------------------

    private function delivery(int $qty): int
    {
        $pos = $this->saleOfQty($qty);
        DB::table('pos_transaction')->where('id', $pos)->update(['ongkir_status' => 'draft']);

        return $pos;
    }

    public function test_a_delivery_settlement_checks_stock_and_records_the_sales_branch(): void
    {
        $first = $this->delivery(7);
        $second = $this->delivery(7);

        $this->putJson("/delivery-order/set-selesai/{$first}", ['nominal' => 280000])->assertJson(['success' => true]);
        $this->putJson("/delivery-order/set-selesai/{$second}", ['nominal' => 280000])->assertJson(['success' => false]);

        $this->assertSame(3.0, $this->stock($this->fruit));
        $this->assertSame('draft', $this->row($second)->status);
        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $second)->count());
        $payment = DB::table('pos_payment')->where('pos_id', $first)->first();
        $this->assertEquals($this->branch, $payment->branch_id);
        $this->assertSame([$this->cash], json_decode($payment->payment_method_id));
    }

    public function test_two_juice_lines_of_one_product_are_checked_together(): void
    {
        $juice = $this->makeProduct('Jus', 15000);
        $this->giveStock($juice, $this->branch, 2);
        $line = fn (string $id, int $qty) => ['id' => $id, 'productId' => $juice, 'price' => 15000, 'qty' => $qty, 'discount' => 0,
            'total_input' => 15000 * $qty, 'product_receipt_id' => [$this->fruit], 'product_receipt_qty' => [1]];
        $jus = [$line('jusa', 2), $line('jusb', 3)];
        $pos = (int) $this->sale(['items' => $jus, 'jus' => $jus, 'subtotal' => 75000, 'total' => 75000])->assertOk()->json('transaksi_id');
        // Another sale takes the two ready juices before this one is paid.
        DB::table('stock_opname')->insert(['code' => 'SO-QA-' . Str::random(8), 'date' => date('Y-m-d'), 'branch_id' => $this->branch,
            'status' => 'selesai', 'product_id' => $juice, 'stock' => 2, 'real_stock' => 0, 'difference' => -2, 'avg_price' => 0,
            'created_at' => now(), 'updated_at' => now()]);

        $this->pay($pos, 75000)->assertStatus(500)->assertJsonFragment(['message' => 'Gagal menyimpan transaksi']);

        $this->assertSame('draft', $this->row($pos)->status);
        $this->assertSame(0.0, $this->stock($juice));
    }

    // ---- H-07 --------------------------------------------------------------------------------

    public function test_a_delivery_amount_with_a_decimal_point_is_refused_not_multiplied(): void
    {
        $pos = $this->delivery(1);

        foreach (['40000.00', '40000,5', '-1', 'abc', '99999999999'] as $bad) {
            $this->putJson("/delivery-order/set-selesai/{$pos}", ['nominal' => $bad])->assertStatus(422);
        }
        $this->putJson("/delivery-order/set-selesai/{$pos}", ['nominal' => '40.000'])->assertJson(['success' => true]);

        $this->assertEquals(40000, $this->row($pos)->paid);
    }

    // ---- H-13 --------------------------------------------------------------------------------

    public function test_stored_lines_add_up_to_the_notes_subtotal(): void
    {
        $cart = [
            ['id' => $this->fruit, 'price' => 40000, 'qty' => 1, 'discount' => 0, 'total_input' => 0],
            ['id' => 'parcel1', 'price' => 100000, 'qty' => 1, 'discount' => 0, 'total_input' => 90000],
        ];
        $parcel = [['id' => 'parcel1', 'qty' => 1, 'budget' => 100000, 'fee' => 0, 'kemasan' => '',
            'data' => [['product' => $this->fruit, 'qty' => 1, 'price' => 40000]]]];

        $pos = (int) $this->sale(['items' => $cart, 'parcel' => $parcel, 'subtotal' => 130000, 'total' => 130000])->assertOk()->json('transaksi_id');

        $this->assertEquals(130000, DB::table('pos_transaction_detail')->where('pos_id', $pos)->whereNull('deleted_at')->sum('subtotal'));
        $this->assertEquals(130000, $this->row($pos)->total);
    }

    public function test_a_cart_row_that_no_line_is_stored_for_is_refused(): void
    {
        $this->sale([
            'items' => [['id' => $this->fruit, 'price' => 40000, 'qty' => 1, 'total_input' => 40000],
                ['id' => 'jusghost', 'price' => 15000, 'qty' => 1, 'total_input' => 15000]],
            'subtotal' => 55000, 'total' => 55000,
        ])->assertStatus(422);
    }

    // ---- H-04 / H-12 / H-31 / H-32 -----------------------------------------------------------

    public function test_the_old_store_routes_are_gone(): void
    {
        $sales = DB::table('pos_transaction')->count();
        $purchases = DB::table('wholesale')->count();

        foreach (['/pos', '/pos/submitTransaction', '/wholesale'] as $url) {
            $response = $this->postJson($url, ['items' => [['product_id' => $this->fruit, 'qty' => 1, 'price' => 1000,
                'discount' => 5000, 'total_input' => 1000]], 'products' => [], 'order_date' => date('Y-m-d')]);
            $this->assertContains($response->getStatusCode(), [404, 405], $url);
        }

        $this->assertSame($sales, DB::table('pos_transaction')->count());
        $this->assertSame($purchases, DB::table('wholesale')->count());
    }

    public static function impossiblePurchaseLines(): array
    {
        return [
            'negative line total' => [['total_input' => -999999]],
            'negative sell price' => [['sell' => -5000]],
            'huge quantity' => [['qty' => 1000000000000]],
            'huge price' => [['price' => 99999999999]],
            'total that overflows' => [['qty' => 1000000, 'price' => 2000000000]],
        ];
    }

    /** @dataProvider impossiblePurchaseLines */
    #[\PHPUnit\Framework\Attributes\DataProvider('impossiblePurchaseLines')]
    public function test_a_purchase_refuses_impossible_numbers(array $line): void
    {
        $before = $this->stock($this->fruit);

        $this->postJson('/wholesale/save-transaction', [
            'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'status' => 'posting', 'subtotal' => 10000, 'total' => 10000,
            'items' => [$line + ['id' => $this->fruit, 'qty' => 10, 'price' => 1000]],
        ])->assertStatus(422);

        $this->assertSame($before, $this->stock($this->fruit));
    }

    public function test_a_sale_refuses_a_huge_quantity(): void
    {
        $this->sale(['items' => [['id' => $this->fruit, 'price' => 1, 'qty' => 1000000000000, 'total_input' => 1]],
            'subtotal' => 1, 'total' => 1])->assertStatus(422);
    }
}
