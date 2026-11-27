<?php

namespace Tests\Feature;

use App\Support\PosDiscount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Diskon Nota / Diskon Ongkir: the cashier picks Rp or %. Rows saved before that choice existed keep the old
 * reading (100 or less = percent, above = rupiah).
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PosDiscountTypeTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // saveTransaction disconnects the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('DISKON');
        $this->product = $this->makeProduct('Apel', 20000);
        $this->giveStock($this->product, $this->branch, 10);
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'customer_id' => null, 'date' => date('Y-m-d'), 'invoice_number' => 'INVQA' . Str::upper(Str::random(8)),
            'items' => [['id' => $this->product, 'price' => 20000, 'qty' => 1, 'discount' => 0, 'total_input' => 20000]],
            'subtotal' => 20000, 'discount' => 0, 'ongkir' => 0, 'discount_ongkir' => 0, 'total' => 20000,
            'status' => 'draft', 'branch_id' => $this->branch,
        ], $override);
    }

    private function save(array $override = []): object
    {
        $response = $this->postJson('/pos/save-transaction', $this->payload($override))->assertOk();

        return DB::table('pos_transaction')->where('id', $response->json('transaksi_id'))->first();
    }

    public function test_a_hundred_rupiah_discount_takes_off_a_hundred_rupiah(): void
    {
        $pos = $this->save(['discount' => 100, 'discount_type' => 'amount']);

        $this->assertEquals(19900, $pos->total);
        $this->assertSame('amount', $pos->discount_type);
        $this->assertEquals(100, DB::table('pos_transaction_detail')->where('pos_id', $pos->id)->sum('diskon_global'));
    }

    public function test_a_percent_discount_is_a_percentage(): void
    {
        $pos = $this->save(['discount' => 10, 'discount_type' => 'percent']);

        $this->assertEquals(18000, $pos->total);
        $this->assertEquals(2000, DB::table('pos_transaction_detail')->where('pos_id', $pos->id)->sum('diskon_global'));
    }

    public function test_a_shipping_discount_follows_its_own_type(): void
    {
        $pos = $this->save(['ongkir' => 15000, 'discount_ongkir' => 50, 'discount_ongkir_type' => 'amount']);

        $this->assertEquals(34950, $pos->total);
        $this->assertSame('amount', $pos->ongkir_discount_type);
    }

    public function test_more_than_a_hundred_percent_is_refused(): void
    {
        $payload = $this->payload(['discount' => 150, 'discount_type' => 'percent']);

        $this->postJson('/pos/save-transaction', $payload)->assertStatus(422);
        $this->assertDatabaseMissing('pos_transaction', ['invoice_number' => $payload['invoice_number']]);
    }

    public function test_a_page_opened_before_the_update_keeps_the_old_reading(): void
    {
        $pos = $this->save(['discount' => 10]);

        $this->assertEquals(18000, $pos->total);
        $this->assertNull($pos->discount_type);
    }

    public function test_payment_page_totals_respect_the_chosen_type(): void
    {
        $pos = $this->save(['ongkir' => 15000, 'discount_ongkir' => 100, 'discount_ongkir_type' => 'amount']);

        $this->postJson("/pos/payment/{$pos->id}/totals", ['discount' => 100, 'discount_type' => 'amount', 'ongkir' => 15000])
            ->assertOk()->assertJson(['total' => 34800]);
        $this->assertDatabaseHas('pos_transaction', ['id' => $pos->id, 'discount' => 100, 'discount_type' => 'amount']);

        $this->postJson("/pos/payment/{$pos->id}/totals", ['discount' => 25, 'discount_type' => 'percent', 'ongkir' => 15000])
            ->assertOk()->assertJson(['total' => 29900]);
        $this->postJson("/pos/payment/{$pos->id}/totals", ['discount' => 101, 'discount_type' => 'percent', 'ongkir' => 15000])
            ->assertStatus(422);
    }

    public function test_receipt_and_payment_page_show_rupiah_discounts_as_rupiah(): void
    {
        $pos = $this->save([
            'discount' => 100, 'discount_type' => 'amount',
            'ongkir' => 15000, 'discount_ongkir' => 90, 'discount_ongkir_type' => 'amount',
        ]);

        $this->get("/pos/payment/{$pos->id}")->assertOk()->assertDontSee('Diskon Transaksi (100%)', false);
        $this->get("/pos/show/{$pos->id}")->assertOk()
            ->assertDontSee('Diskon (100%)', false)->assertSee('-Rp 90', false);
    }

    public function test_shipping_cost_sql_matches_the_chosen_type(): void
    {
        $rows = [
            ['amount', 50, 14950], ['percent', 50, 7500], [null, 50, 7500], [null, 5000, 10000], ['amount', 0, 15000],
        ];
        $sql = PosDiscount::sql('t.d', 't.k', 't.o');

        foreach ($rows as [$type, $value, $net]) {
            $discount = DB::selectOne("SELECT {$sql} AS x FROM (SELECT ? AS d, ? AS k, 15000 AS o) t", [$value, $type])->x;
            $this->assertEquals($net, 15000 - $discount, "type=" . var_export($type, true) . " value={$value}");
            $this->assertEquals($discount, PosDiscount::amount($value, $type, 15000));
        }
    }
}
