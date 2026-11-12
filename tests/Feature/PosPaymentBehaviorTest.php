<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Pre-deploy QA, phase 1 — POS: save a sale, pay it, stock and money must come out right.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PosPaymentBehaviorTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branchId;
    private int $productId;
    private int $cashId;
    private int $transferId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->branchId = $this->makeBranch('POS');
        $this->productId = $this->makeProduct('Apel', 20000);
        $this->giveStock($this->productId, $this->branchId, 10);
        $this->cashId = $this->paymentMethodId('Cash');
        $this->transferId = $this->paymentMethodId('QA Transfer');

        $admin = $this->makeUser($this->superAdminRoleId());
        $this->actAsRole($admin, 1);
    }

    private function stock(): float
    {
        return InventorySnapshot::stockAvailable([$this->productId], $this->branchId)[$this->productId];
    }

    private function salePayload(array $override = []): array
    {
        return array_merge([
            'customer_id'     => null,
            'date'            => date('Y-m-d'),
            'invoice_number'  => 'INVQA' . strtoupper(Str::random(8)),
            'items'           => [[
                'id' => $this->productId, 'price' => 20000, 'qty' => 3, 'discount' => 0, 'total_input' => 60000,
            ]],
            'subtotal'        => 60000,
            'discount'        => 0,
            'ongkir'          => 0,
            'discount_ongkir' => 0,
            'total'           => 60000,
            'status'          => 'draft',
            'branch_id'       => $this->branchId,
        ], $override);
    }

    private function saveSale(array $override = []): int
    {
        $response = $this->postJson('/pos/save-transaction', $this->salePayload($override));
        $response->assertOk()->assertJson(['success' => true]);

        return (int) $response->json('transaksi_id');
    }

    private function pay(int $posId, array $payments, ?string $key = null, ?float $total = null)
    {
        $total ??= array_sum(array_map(fn ($p) => (float) str_replace('.', '', $p['amount']), $payments));

        return $this->postJson('/pos/savePayment', [
            'idempotency_key' => $key ?? (string) Str::uuid(),
            'date'            => date('Y-m-d'),
            'transaction_id'  => $posId,
            'payments'        => $payments,
            'total_payment'   => $total,
        ]);
    }

    // ---- saving a sale -----------------------------------------------------------------------

    public function test_saved_sale_is_a_draft_that_does_not_touch_stock_yet(): void
    {
        $posId = $this->saveSale();

        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'draft', 'total' => 60000, 'branch_id' => $this->branchId]);
        $this->assertDatabaseHas('pos_transaction_detail', ['pos_id' => $posId, 'product_id' => $this->productId, 'quantity' => 3]);
        $this->assertSame(10.0, $this->stock());
    }

    public function test_header_total_is_recomputed_from_items_with_percent_discount(): void
    {
        $posId = $this->saveSale(['discount' => 10, 'total' => 54000]);

        $this->assertEquals(54000, DB::table('pos_transaction')->where('id', $posId)->value('total'));
    }

    public function test_tampered_subtotal_is_rejected_and_nothing_is_saved(): void
    {
        $payload = $this->salePayload(['subtotal' => 1000, 'total' => 1000]);

        $this->postJson('/pos/save-transaction', $payload)->assertStatus(422)->assertJsonValidationErrors('subtotal');
        $this->assertDatabaseMissing('pos_transaction', ['invoice_number' => $payload['invoice_number']]);
    }

    public function test_selling_more_than_available_stock_fails_and_rolls_back(): void
    {
        $payload = $this->salePayload([
            'items'    => [['id' => $this->productId, 'price' => 20000, 'qty' => 11, 'discount' => 0, 'total_input' => 220000]],
            'subtotal' => 220000,
            'total'    => 220000,
        ]);

        $this->postJson('/pos/save-transaction', $payload)
            ->assertStatus(500)
            ->assertJson(['success' => false]);
        $this->assertDatabaseMissing('pos_transaction', ['invoice_number' => $payload['invoice_number']]);
    }

    public function test_user_cannot_sell_from_a_branch_they_are_not_assigned_to(): void
    {
        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId, [], [$this->makeBranch('LAIN')]);
        DB::table('user_permissions')->insert(['id_user' => $kasir->id_user, 'permission' => 'pos.receipt', 'is_allowed' => 1]);
        $this->actAsRole($kasir, $roleId);

        $payload = $this->salePayload();
        $this->postJson('/pos/save-transaction', $payload)->assertForbidden();
        $this->assertDatabaseMissing('pos_transaction', ['invoice_number' => $payload['invoice_number']]);
    }

    public function test_resaving_the_same_invoice_updates_instead_of_duplicating(): void
    {
        $invoice = 'INVQA' . strtoupper(Str::random(8));
        $first = $this->saveSale(['invoice_number' => $invoice]);
        $second = $this->saveSale(['invoice_number' => $invoice, 'items' => [[
            'id' => $this->productId, 'price' => 20000, 'qty' => 2, 'discount' => 0, 'total_input' => 40000,
        ]], 'subtotal' => 40000, 'total' => 40000]);

        $this->assertSame($first, $second);
        $this->assertSame(1, DB::table('pos_transaction')->where('invoice_number', $invoice)->count());
        $this->assertEquals(40000, DB::table('pos_transaction')->where('id', $first)->value('total'));
    }

    // ---- paying ------------------------------------------------------------------------------

    public function test_exact_cash_payment_marks_paid_and_deducts_stock(): void
    {
        $posId = $this->saveSale();

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'paid', 'paid' => 60000]);
        $this->assertSame(1, DB::table('pos_payment')->where('pos_id', $posId)->count());
        $this->assertSame(7.0, $this->stock(), 'selling 3 of 10 must leave 7');
    }

    public function test_cash_overpayment_returns_change_and_paid_equals_total(): void
    {
        $posId = $this->saveSale();

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '100.000']])->assertOk();

        $payment = DB::table('pos_payment')->where('pos_id', $posId)->first();
        $this->assertEquals(40000, $payment->return);
        $this->assertEquals(0, $payment->remaining);
        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'paid', 'paid' => 60000]);
    }

    public function test_partial_payment_becomes_debt_with_correct_remaining(): void
    {
        $posId = $this->saveSale();

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '25.000']])->assertOk();

        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'debt', 'paid' => 25000]);
        $this->assertEquals(35000, DB::table('pos_payment')->where('pos_id', $posId)->value('remaining'));
    }

    public function test_debt_can_be_settled_by_a_second_payment(): void
    {
        $posId = $this->saveSale();
        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '25.000']])->assertOk();
        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '35.000']])->assertOk();

        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'paid', 'paid' => 60000]);
        $this->assertSame(7.0, $this->stock());
    }

    public function test_split_payment_across_two_methods_is_summed(): void
    {
        $posId = $this->saveSale();

        $this->pay($posId, [
            ['payment_id' => $this->cashId, 'amount' => '20.000'],
            ['payment_id' => $this->transferId, 'amount' => '40.000'],
        ])->assertOk();

        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'paid', 'paid' => 60000]);
    }

    public function test_change_is_refused_when_paying_with_a_non_cash_method(): void
    {
        $posId = $this->saveSale();

        $this->pay($posId, [['payment_id' => $this->transferId, 'amount' => '100.000']])
            ->assertStatus(422)->assertJsonValidationErrors('payments');
        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $posId)->count());
        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'draft']);
    }

    public function test_same_idempotency_key_never_charges_twice(): void
    {
        $posId = $this->saveSale();
        $key = (string) Str::uuid();

        $first = $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']], $key)->assertOk();
        $second = $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']], $key)->assertOk();

        $second->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($first->json('payment.id'), $second->json('payment.id'));
        $this->assertSame(1, DB::table('pos_payment')->where('pos_id', $posId)->count());
    }

    public function test_fully_paid_sale_cannot_be_paid_again_with_a_new_key(): void
    {
        $posId = $this->saveSale();
        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])->assertOk();

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])->assertStatus(422);
        $this->assertSame(1, DB::table('pos_payment')->where('pos_id', $posId)->count());
    }

    public function test_paid_sale_can_no_longer_be_edited(): void
    {
        $invoice = 'INVQA' . strtoupper(Str::random(8));
        $posId = $this->saveSale(['invoice_number' => $invoice]);
        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])->assertOk();

        // Known quirk (Low): saveTransaction's catch (\Throwable) turns this validation error into a
        // 500 JSON response instead of 422. The edit is still refused, which is what matters here.
        $response = $this->postJson('/pos/save-transaction', $this->salePayload([
            'invoice_number' => $invoice,
            'items'          => [['id' => $this->productId, 'price' => 20000, 'qty' => 1, 'discount' => 0, 'total_input' => 20000]],
            'subtotal'       => 20000,
            'total'          => 20000,
        ]));
        $response->assertJson(['success' => false, 'message' => 'Transaksi yang sudah dibayar tidak dapat diubah.']);
        $this->assertEquals(60000, DB::table('pos_transaction')->where('id', $posId)->value('total'));
        $this->assertDatabaseHas('pos_transaction_detail', ['pos_id' => $posId, 'quantity' => 3]);
    }

    public function test_payment_totals_must_match_the_breakdown(): void
    {
        $posId = $this->saveSale();

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']], null, 50000)
            ->assertStatus(422)->assertJsonValidationErrors('payments');
    }

    public function test_malformed_or_zero_amounts_are_rejected(): void
    {
        $posId = $this->saveSale();

        foreach (['60,5', '10.00', '-5', 'abc', '0'] as $bad) {
            $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => $bad]], null, 60000)->assertStatus(422);
        }
        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $posId)->count());
    }

    public function test_payment_fails_and_rolls_back_when_stock_vanished_after_the_draft(): void
    {
        $posId = $this->saveSale();
        // Another sale / opname took the stock away between saving the draft and paying.
        $this->giveStock($this->productId, $this->branchId, -9);

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])
            ->assertStatus(500)->assertJson(['success' => false]);

        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $posId)->count());
        $this->assertDatabaseHas('pos_transaction', ['id' => $posId, 'status' => 'draft']);
    }

    public function test_cashier_cannot_pay_a_sale_of_another_branch(): void
    {
        $posId = $this->saveSale();

        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId, [], [$this->makeBranch('LAIN')]);
        DB::table('user_permissions')->insert(['id_user' => $kasir->id_user, 'permission' => 'pos.savePayment', 'is_allowed' => 1]);
        $this->actAsRole($kasir, $roleId);

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])->assertNotFound();
        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $posId)->count());
    }

    public function test_user_without_pos_permission_cannot_pay(): void
    {
        $posId = $this->saveSale();

        $roleId = $this->makeRole('Gudang');
        $gudang = $this->makeUser($roleId, [], [$this->branchId]);
        $this->actAsRole($gudang, $roleId);

        $this->pay($posId, [['payment_id' => $this->cashId, 'amount' => '60.000']])->assertRedirect();
        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $posId)->count());
    }
}
