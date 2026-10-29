<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Entities\Payment;
use Modules\Pos\Entities\PosModel;
use Modules\Pos\Services\CompletePayment;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Action-level parity tests; stock view rows are fixtures, not MySQL view verification. */
class CompletePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertFalse($this->app->configurationIsCached());
        config(['database.default' => 'complete_payment_test', 'database.connections.complete_payment_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('payment_method', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('pos_transaction', function (Blueprint $t) {
            $t->increments('id'); $t->integer('branch_id'); $t->integer('customer_id')->nullable(); $t->integer('deposito_id')->nullable();
            $t->decimal('total', 15, 2); $t->decimal('paid', 15, 2)->default(0); $t->decimal('voucher', 15, 2)->default(0);
            $t->integer('voucher_qty')->default(0); $t->string('status'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('pos_payment', function (Blueprint $t) {
            $t->increments('id'); $t->string('uuid'); $t->date('date'); $t->string('nota_number'); $t->integer('pos_id'); $t->integer('branch_id');
            $t->text('payment_method'); $t->text('payment_method_id'); $t->text('payment_amount'); $t->decimal('total', 15, 2);
            $t->decimal('return', 15, 2)->default(0); $t->decimal('remaining', 15, 2)->default(0); $t->integer('created_by'); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('pos_transaction_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->string('type'); $t->integer('product_id')->nullable(); $t->integer('parcel_id')->nullable();
            $t->decimal('quantity', 12, 3); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); $t->timestamps(); });
        // Product::getParentId() uses product_child, not a products.parent_id column.
        Schema::create('product_child', function (Blueprint $t) { $t->increments('id'); $t->integer('product_id'); $t->integer('parent_id'); $t->timestamps(); });
        Schema::create('product_stock', function (Blueprint $t) {
            $t->integer('id'); $t->integer('branch_id'); $t->decimal('stock_available', 12, 3); $t->unique(['id', 'branch_id']);
        });
        Schema::create('production', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->integer('product_id')->nullable(); $t->decimal('quantity', 12, 3)->default(0); $t->string('status'); $t->timestamps();
        });
        Schema::create('production_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('production_id'); $t->integer('product_id'); $t->decimal('quantity', 12, 3); $t->timestamps();
        });
        Schema::create('production_parcel_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->integer('product_id'); $t->decimal('quantity', 12, 3); $t->timestamps();
        });
        Schema::create('deposito', function (Blueprint $t) {
            $t->increments('id'); $t->integer('customer_id'); $t->decimal('voucher', 15, 2); $t->integer('voucher_qty'); $t->timestamps();
        });
        DB::table('payment_method')->insert([['id' => 7, 'name' => 'Cash'], ['id' => 8, 'name' => 'Transfer']]);
        DB::table('pos_transaction')->insert(['id' => 1, 'branch_id' => 2, 'total' => 100, 'status' => 'draft']);
    }

    protected function tearDown(): void
    {
        DB::purge('complete_payment_test');
        parent::tearDown();
    }

    private function settle(array $payments = [['payment_id' => 7, 'amount' => 100]]): Payment
    {
        return DB::transaction(function () use ($payments) {
            $pos = PosModel::whereKey(1)->lockForUpdate()->firstOrFail();
            return app(CompletePayment::class)->handle($pos, $payments, '2026-10-29', 17);
        });
    }

    private function product(int $id, float $stock, ?int $parentId = null): void
    {
        DB::table('products')->insert(['id' => $id, 'name' => 'Fixture product ' . $id]);
        DB::table('product_stock')->insert(['id' => $id, 'branch_id' => 2, 'stock_available' => $stock]);
        if ($parentId !== null) {
            DB::table('product_child')->insert(['product_id' => $id, 'parent_id' => $parentId]);
        }
    }

    private function detail(string $type, int $productId, float $quantity, ?int $parcelId = null): void
    {
        DB::table('pos_transaction_detail')->insert(['pos_id' => 1, 'type' => $type, 'product_id' => $productId, 'parcel_id' => $parcelId, 'quantity' => $quantity]);
    }

    private function assertStockRejected(string $expectedMessage): void
    {
        try {
            $this->settle();
            $this->fail('Insufficient cumulative stock must reject settlement.');
        } catch (\Exception $e) {
            $this->assertSame($expectedMessage, $e->getMessage());
        }
        $this->assertSame(0, DB::table('pos_payment')->count());
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 0]);
    }

    public static function firstSettlementStates(): array
    {
        return [['draft'], ['temp'], ['pending']];
    }

    #[DataProvider('firstSettlementStates')]
    public function test_parity_first_settlement_accumulates_duplicate_and_parent_variant_stock(string $status): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['status' => $status]);
        $this->product(10, 5.5);
        $this->product(11, 999, 10);
        $this->product(12, 999, 10);
        // Neither per-line checks nor variant-local availability may admit 6 units against 5.5.
        $this->detail('product', 10, 2);
        $this->detail('product', 10, 1);
        $this->detail('product', 11, 1.5);
        $this->detail('product', 12, 1.5);
        DB::table('product_stock')->insert(['id' => 10, 'branch_id' => 99, 'stock_available' => 999]);
        $this->detail('product', 10, 500);
        DB::table('pos_transaction_detail')->where('quantity', 500)->update(['deleted_at' => now()]);
        DB::table('production')->insert(['id' => 1, 'pos_id' => 1, 'status' => 'draft']);

        $this->assertStockRejected('Stok "Fixture product 10" tidak mencukupi. Stok tersedia: 5.5, dibutuhkan: 6');
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => $status]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'draft']);

        DB::table('product_stock')->where('id', 10)->where('branch_id', 2)->update(['stock_available' => 6]);
        $payment = $this->settle();
        $this->assertSame(100.0, (float) $payment->total);
        $this->assertSame(0.0, (float) $payment->remaining);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => 'paid', 'paid' => 100]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
        // Physical stock changes belong to SQL views; this fixture table is not that implementation.
    }

    public function test_parity_parcel_packaging_juice_remainder_and_production_ingredients_are_all_checked(): void
    {
        $this->product(10, 8.5);
        $this->product(11, 999, 10);
        $this->product(20, 2); // Packaging, stored in parcel_id (not the parcel recipe product).
        $this->product(30, 2); // Five juices sold, three produced, two taken from ready stock.
        $this->detail('product', 10, 2);
        $this->detail('parcel', 99, 2, 20);
        $this->detail('jus', 30, 5);
        DB::table('production_parcel_detail')->insert(['pos_id' => 1, 'product_id' => 11, 'quantity' => 3]);
        DB::table('production')->insert(['id' => 1, 'pos_id' => 1, 'product_id' => 30, 'quantity' => 3, 'status' => 'draft']);
        DB::table('production_detail')->insert(['production_id' => 1, 'product_id' => 11, 'quantity' => 4]);
        // Unrelated invoice ingredients must not be included in this invoice's demand.
        DB::table('production_parcel_detail')->insert(['pos_id' => 2, 'product_id' => 10, 'quantity' => 1000]);
        DB::table('production')->insert(['id' => 2, 'pos_id' => 2, 'product_id' => 30, 'quantity' => 1000, 'status' => 'draft']);
        DB::table('production_detail')->insert(['production_id' => 2, 'product_id' => 10, 'quantity' => 1000]);

        $this->assertStockRejected('Stok "Fixture product 10" tidak mencukupi. Stok tersedia: 8.5, dibutuhkan: 9');
        DB::table('product_stock')->where('id', 10)->update(['stock_available' => 9]);
        DB::table('product_stock')->where('id', 20)->update(['stock_available' => 1]);
        $this->assertStockRejected('Stok "Fixture product 20" tidak mencukupi. Stok tersedia: 1, dibutuhkan: 2');
        DB::table('product_stock')->where('id', 20)->update(['stock_available' => 2]);
        DB::table('product_stock')->where('id', 30)->delete();
        $this->assertStockRejected('Stok "Fixture product 30" tidak mencukupi. Stok tersedia: 0, dibutuhkan: 2');
        DB::table('product_stock')->insert(['id' => 30, 'branch_id' => 2, 'stock_available' => 2]);

        $this->settle();
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 100, 'status' => 'paid']);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
        $this->assertDatabaseHas('production', ['id' => 2, 'status' => 'draft']);
    }

    public function test_parity_debt_skips_first_stock_check_and_consumes_voucher_once_using_net_ledger(): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['status' => 'debt', 'customer_id' => 40, 'paid' => 999]);
        DB::table('deposito')->insert(['id' => 4, 'customer_id' => 40, 'voucher' => 20, 'voucher_qty' => 2]);
        $this->product(10, 0);
        $this->detail('product', 10, 500); // Already committed stock is not checked again for debt collection.
        DB::table('production')->insert([
            ['id' => 1, 'pos_id' => 1, 'status' => 'draft'],
            ['id' => 2, 'pos_id' => 1, 'status' => 'posting'],
            ['id' => 3, 'pos_id' => 2, 'status' => 'draft'],
        ]);
        $old = ['uuid' => 'old-payment', 'date' => '2026-10-28', 'nota_number' => 'old-nota', 'pos_id' => 1, 'branch_id' => 2,
            'payment_method' => '["Cash"]', 'payment_method_id' => '[7]', 'payment_amount' => '[30]', 'total' => 30,
            'return' => 10, 'remaining' => 80, 'created_by' => 17];
        DB::table('pos_payment')->insert($old + ['id' => 1]);
        DB::table('pos_payment')->insert(array_replace($old, ['id' => 2, 'uuid' => 'deleted-payment', 'total' => 1000, 'deleted_at' => now()]));

        $partial = $this->settle([['payment_id' => 8, 'amount' => 10]]);
        $this->assertSame(50.0, (float) $partial->remaining);
        $this->assertSame(0.0, (float) $partial->return);
        $this->assertSame('["Transfer"]', $partial->payment_method);
        $this->assertSame('[8]', $partial->payment_method_id);
        $this->assertSame([10], json_decode($partial->payment_amount, true));
        $this->assertSame('2026-10-29', $partial->date);
        $this->assertSame(17, $partial->created_by);
        $this->assertSame(2, $partial->branch_id);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => 'debt', 'paid' => 30, 'voucher' => 20, 'voucher_qty' => 1, 'deposito_id' => 4]);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 1]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
        $this->assertDatabaseHas('production', ['id' => 2, 'status' => 'posting']);
        $this->assertDatabaseHas('production', ['id' => 3, 'status' => 'draft']);

        $final = $this->settle([['payment_id' => 7, 'amount' => 70]]);
        $this->assertSame(20.0, (float) $final->return);
        $this->assertSame(0.0, (float) $final->remaining);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => 'paid', 'paid' => 80, 'voucher' => 20, 'deposito_id' => 4]);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 1]);
        $this->assertDatabaseHas('pos_payment', ['id' => 1, 'total' => 30, 'return' => 10, 'remaining' => 80]);
        $this->assertDatabaseHas('pos_payment', ['id' => $partial->id, 'return' => 0, 'remaining' => 50]);
        $netPaid = (float) Payment::sum('total') - (float) Payment::sum('return');
        $this->assertSame(80.0, $netPaid);
        $this->assertSame($netPaid, (float) PosModel::findOrFail(1)->paid);
        $this->assertSame(100.0, $netPaid + (float) PosModel::findOrFail(1)->voucher);
        $this->assertSame(3, Payment::count());
    }

    public function test_parity_late_production_failure_rolls_back_voucher_header_and_ledger_together(): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['customer_id' => 40]);
        DB::table('deposito')->insert(['id' => 4, 'customer_id' => 40, 'voucher' => 20, 'voucher_qty' => 2]);
        $this->product(30, 0);
        $this->product(10, 2);
        $this->detail('jus', 30, 3);
        DB::table('production')->insert(['id' => 1, 'pos_id' => 1, 'product_id' => 30, 'quantity' => 3, 'status' => 'draft']);
        DB::table('production_detail')->insert(['production_id' => 1, 'product_id' => 10, 'quantity' => 2]);
        $headerBefore = (array) DB::table('pos_transaction')->where('id', 1)->first();
        $voucherBefore = (array) DB::table('deposito')->where('id', 4)->first();
        $productionBefore = (array) DB::table('production')->where('id', 1)->first();
        // A real SQLite failure after voucher decrement AND header save exercises caller transaction atomicity.
        DB::unprepared("CREATE TRIGGER fail_complete_payment BEFORE UPDATE ON production WHEN NEW.status = 'complete' BEGIN SELECT RAISE(ABORT, 'fixture production completion failed'); END");

        try {
            $this->settle();
            $this->fail('The injected production write failure must escape the action.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('fixture production completion failed', $e->getMessage());
        }
        $this->assertSame(0, DB::table('pos_payment')->count());
        $this->assertSame($headerBefore, (array) DB::table('pos_transaction')->where('id', 1)->first());
        $this->assertSame($voucherBefore, (array) DB::table('deposito')->where('id', 4)->first());
        $this->assertSame($productionBefore, (array) DB::table('production')->where('id', 1)->first());
        $this->assertSame(0, DB::transactionLevel());

        DB::unprepared('DROP TRIGGER fail_complete_payment');
        $payment = $this->settle();
        $this->assertSame(1, Payment::count());
        $this->assertSame(20.0, (float) $payment->return);
        $this->assertSame(0.0, (float) $payment->remaining);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 80, 'voucher' => 20, 'voucher_qty' => 1, 'deposito_id' => 4, 'status' => 'paid']);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 1]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
    }

    public function test_parity_mixed_non_cash_overpayment_is_rejected_but_exact_split_settles(): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['status' => 'debt']);
        try {
            $this->settle([['payment_id' => 7, 'amount' => 80], ['payment_id' => 8, 'amount' => 30]]);
            $this->fail('A non-cash component must not fund cash change.');
        } catch (ValidationException $e) {
            $this->assertSame(['payments' => ['Kembalian hanya dapat diberikan untuk pembayaran tunai.']], $e->errors());
        }
        $this->assertSame(0, Payment::count());
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 0, 'status' => 'debt']);

        $payment = $this->settle([['payment_id' => 7, 'amount' => 70], ['payment_id' => 8, 'amount' => 30]]);
        $this->assertSame('["Cash","Transfer"]', $payment->payment_method);
        $this->assertSame('[7,8]', $payment->payment_method_id);
        $this->assertSame([70, 30], json_decode($payment->payment_amount, true));
        $this->assertSame(100.0, (float) $payment->total);
        $this->assertSame(0.0, (float) $payment->return);
        $this->assertSame(0.0, (float) $payment->remaining);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 100, 'status' => 'paid']);
        $this->assertSame(1, Payment::count());
    }

    public function test_regression_juice_production_is_not_reused_for_duplicate_sale_rows(): void
    {
        $this->product(30, 0);
        $this->product(10, 2);
        $this->detail('jus', 30, 3);
        $this->detail('jus', 30, 3);
        DB::table('production')->insert(['id' => 1, 'pos_id' => 1, 'product_id' => 30, 'quantity' => 3, 'status' => 'draft']);
        DB::table('production_detail')->insert(['production_id' => 1, 'product_id' => 10, 'quantity' => 2]);

        // Six sold minus three produced still requires three ready-stock units, not zero.
        $rejection = null;
        try {
            $this->settle();
        } catch (\Exception $e) {
            $rejection = $e;
        }
        $this->assertNotNull($rejection, sprintf(
            'Six juices sold, three produced, zero ready stock: settlement accepted; payments=%d, invoice=%s, production=%s.',
            Payment::count(), PosModel::findOrFail(1)->status, DB::table('production')->where('id', 1)->value('status')
        ));
        $this->assertSame('Stok "Fixture product 30" tidak mencukupi. Stok tersedia: 0, dibutuhkan: 3', $rejection->getMessage());
        $this->assertSame(0, Payment::count());
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => 'draft', 'paid' => 0]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'draft']);
    }

    public function test_production_rows_are_summed_without_reusing_output_across_sale_rows(): void
    {
        $this->product(30, 0);
        $this->detail('jus', 30, 3);
        $this->detail('jus', 30, 3);
        DB::table('production')->insert([
            ['id' => 1, 'pos_id' => 1, 'product_id' => 30, 'quantity' => 3, 'status' => 'draft'],
            ['id' => 2, 'pos_id' => 1, 'product_id' => 30, 'quantity' => 3, 'status' => 'draft'],
        ]);
        $this->settle();
        $this->assertSame(1, Payment::count());
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 100, 'status' => 'paid']);
        $this->assertSame(2, DB::table('production')->where('status', 'complete')->count());
    }
}
