<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeliveryOrderPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertFalse($this->app->configurationIsCached());
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        config(['database.default' => 'delivery_payment_test', 'database.connections.delivery_payment_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('users', function (Blueprint $t) { $t->integer('id_user')->primary(); $t->string('nm_user'); $t->boolean('is_aktif')->default(true); });
        Schema::create('role', fn (Blueprint $t) => $t->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $t) { $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role'); $t->timestamps(); });
        Schema::create('role_menu', function (Blueprint $t) { $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $t) { $t->increments('id'); $t->integer('id_user'); $t->string('permission'); $t->boolean('is_allowed'); });
        Schema::create('user_branch', function (Blueprint $t) { $t->integer('user_id'); $t->integer('branch_id'); });
        Schema::create('branch', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('payment_method', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('customer', fn (Blueprint $t) => $t->integer('id')->primary());
        Schema::create('pos_transaction', function (Blueprint $t) {
            $t->increments('id'); $t->integer('branch_id')->nullable(); $t->integer('customer_id')->nullable();
            $t->integer('courier_id')->nullable(); $t->string('invoice_number'); $t->date('date');
            $t->decimal('total', 15, 2); $t->decimal('paid', 15, 2)->default(0); $t->decimal('voucher', 15, 2)->default(0);
            $t->string('status'); $t->string('ongkir_status')->default('draft'); $t->decimal('ongkir', 15, 2)->default(10000);
            $t->date('ongkir_date')->nullable(); $t->time('ongkir_time')->nullable(); $t->text('ongkir_address')->nullable();
            $t->timestamp('delivered_at')->nullable(); $t->integer('updated_by')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('pos_transaction_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->decimal('price', 15, 2)->default(0); $t->decimal('quantity', 10, 2)->default(0); $t->softDeletes();
        });
        Schema::create('pos_payment', function (Blueprint $t) {
            $t->increments('id'); $t->string('uuid'); $t->date('date'); $t->string('nota_number');
            $t->integer('pos_id'); $t->integer('branch_id'); $t->text('payment_method'); $t->text('payment_method_id'); $t->text('payment_amount');
            $t->decimal('total', 15, 2); $t->decimal('return', 15, 2)->default(0); $t->decimal('remaining', 15, 2)->default(0);
            $t->integer('created_by'); $t->softDeletes(); $t->timestamps();
        });
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Courier Fixture']);
        Schema::create('production', function (Blueprint $t) { $t->increments('id'); $t->integer('pos_id'); $t->string('status'); $t->timestamps(); });
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'delivery-order.set-selesai']);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 2]);
        DB::table('branch')->insert([['id' => 1, 'name' => 'Foreign'], ['id' => 2, 'name' => 'Assigned']]);
        DB::table('payment_method')->insert([['id' => 1, 'name' => 'Transfer'], ['id' => 7, 'name' => 'Cash']]);
        foreach ([1 => 2, 2 => 1] as $id => $branch) {
            DB::table('pos_transaction')->insert(['id' => $id, 'branch_id' => $branch, 'invoice_number' => 'INV-fixture-' . $id,
                'date' => '2026-10-29', 'total' => 100000, 'paid' => 20000, 'voucher' => 10000, 'status' => 'debt', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('pos_payment')->insert(['id' => 1, 'uuid' => 'fixture-payment', 'date' => '2026-10-28', 'nota_number' => 'fixture-nota',
            'pos_id' => 1, 'branch_id' => 2, 'payment_method' => '["Cash"]', 'payment_method_id' => '[7]', 'payment_amount' => '[30000]',
            'total' => 30000, 'return' => 10000, 'remaining' => 70000, 'created_by' => 7]);
        $this->actingAs(User::findOrFail(7));
        session(['role' => ['id_role' => 2]]);
    }

    private function paymentSubmissionsSchema(): void
    {
        Schema::create('pos_payment_submissions', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->string('request_key'); $t->integer('user_id');
            $t->string('request_hash'); $t->integer('payment_id'); $t->text('response'); $t->timestamps(); $t->unique(['pos_id', 'request_key']);
        });
    }

    private function voucherInvoice(int $face, int $historicalPaid = 0): void
    {
        Schema::table('pos_transaction', function (Blueprint $t) { $t->integer('deposito_id')->nullable(); $t->integer('voucher_qty')->default(0); });
        Schema::table('pos_transaction_detail', function (Blueprint $t) { $t->string('type')->default('product'); $t->integer('product_id')->nullable(); $t->integer('parcel_id')->nullable(); });
        Schema::table('production', function (Blueprint $t) { $t->integer('product_id')->nullable(); $t->decimal('quantity', 12, 3)->default(0); });
        Schema::create('deposito', function (Blueprint $t) { $t->increments('id'); $t->integer('customer_id'); $t->decimal('voucher', 15, 2); $t->integer('voucher_qty'); $t->timestamps(); });
        Schema::create('products', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); $t->timestamps(); });
        Schema::create('product_child', function (Blueprint $t) { $t->increments('id'); $t->integer('product_id'); $t->integer('parent_id'); });
        // Availability is a fixture table here; deployed stock deduction is provided by SQL views.
        Schema::create('product_stock', function (Blueprint $t) { $t->integer('id'); $t->integer('branch_id'); $t->decimal('stock_available', 12, 3); });
        Schema::create('production_detail', function (Blueprint $t) { $t->increments('id'); $t->integer('production_id'); $t->integer('product_id'); $t->decimal('quantity', 12, 3); });
        Schema::create('production_parcel_detail', function (Blueprint $t) { $t->increments('id'); $t->integer('pos_id'); $t->integer('product_id'); $t->decimal('quantity', 12, 3); });
        $this->paymentSubmissionsSchema();
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'pos.savePayment']);
        DB::table('customer')->insert(['id' => 40]);
        DB::table('deposito')->insert(['id' => 4, 'customer_id' => 40, 'voucher' => $face, 'voucher_qty' => 1]);
        DB::table('pos_transaction')->where('id', 1)->update(['total' => 100, 'paid' => 999, 'voucher' => 0, 'customer_id' => 40,
            'status' => $historicalPaid > 0 ? 'debt' : 'pending']);
        DB::table('pos_payment')->delete();
        if ($historicalPaid > 0) {
            DB::table('pos_payment')->insert(['id' => 1, 'uuid' => 'old-cash', 'date' => '2026-10-28', 'nota_number' => 'old-cash',
                'pos_id' => 1, 'branch_id' => 2, 'payment_method' => '["Cash"]', 'payment_method_id' => '[7]', 'payment_amount' => '[30]',
                'total' => $historicalPaid + 10, 'return' => 10, 'remaining' => 100 - $historicalPaid, 'created_by' => 7]);
        }
        DB::table('products')->insert(['id' => 10, 'name' => 'Voucher stock']);
        DB::table('product_stock')->insert(['id' => 10, 'branch_id' => 2, 'stock_available' => 2]);
        DB::table('pos_transaction_detail')->insert(['pos_id' => 1, 'product_id' => 10, 'type' => 'product', 'quantity' => 1]);
        DB::table('production')->insert(['id' => 1, 'pos_id' => 1, 'status' => 'draft']);
        DB::table('production_detail')->insert(['production_id' => 1, 'product_id' => 10, 'quantity' => 1]);
    }

    private function voucherPayment(string $adapter, int $cash, string $key = '66cf8eb1-c48d-4f52-a6e7-a667a5b3a6bb')
    {
        if ($adapter === 'cod') {
            return $this->putJson('/delivery-order/set-selesai/1', ['nominal' => (string) $cash]);
        }
        return $this->postJson('/pos/savePayment', ['date' => '2026-10-29', 'transaction_id' => 1, 'customer_id' => 40,
            'idempotency_key' => $key, 'total_payment' => $cash,
            'payments' => [['payment_id' => 7, 'payment_name' => 'Cash', 'amount' => (string) $cash]]]);
    }

    public static function paymentAdapters(): array
    {
        return ['pos' => ['pos'], 'cod' => ['cod']];
    }

    #[DataProvider('paymentAdapters')]
    public function test_a_negative_unused_deposito_voucher_cannot_increase_cash_due(string $adapter): void
    {
        $this->voucherInvoice(-10);
        $this->voucherPayment($adapter, 110)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 100, 'voucher' => 0, 'deposito_id' => null, 'status' => 'paid']);
        $this->assertDatabaseHas('pos_payment', ['pos_id' => 1, 'total' => 110, 'return' => 10, 'remaining' => 0]);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 1]);
    }

    #[DataProvider('paymentAdapters')]
    public function test_existing_invoice_voucher_is_retained_without_consuming_another_deposito_voucher(string $adapter): void
    {
        $this->voucherInvoice(10);
        DB::table('pos_transaction')->where('id', 1)->update(['voucher' => 20, 'paid' => 0]);

        $this->voucherPayment($adapter, 80)->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'voucher' => 20, 'voucher_qty' => 0,
            'deposito_id' => null, 'paid' => 80, 'status' => 'paid',
            'ongkir_status' => $adapter === 'cod' ? 'delivered' : 'draft']);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'customer_id' => 40, 'voucher' => 10, 'voucher_qty' => 1]);
        $this->assertDatabaseHas('pos_payment', ['pos_id' => 1, 'total' => 80, 'return' => 0, 'remaining' => 0]);
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
    }

    #[DataProvider('paymentAdapters')]
    public function test_first_existing_voucher_can_cover_invoice_with_zero_cash_without_consuming_another_voucher(string $adapter): void
    {
        $this->voucherInvoice(10);
        DB::table('pos_transaction')->where('id', 1)->update(['voucher' => 100, 'paid' => 0]);

        $this->voucherPayment($adapter, 0)->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'voucher' => 100, 'voucher_qty' => 0,
            'deposito_id' => null, 'paid' => 0, 'status' => 'paid',
            'ongkir_status' => $adapter === 'cod' ? 'delivered' : 'draft']);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'customer_id' => 40, 'voucher' => 10, 'voucher_qty' => 1]);
        $this->assertDatabaseHas('pos_payment', ['pos_id' => 1, 'total' => 0, 'return' => 0, 'remaining' => 0]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
        if ($adapter === 'pos') {
            $this->voucherPayment($adapter, 0, '2f5969d2-9dda-4ee8-9ed7-6c048172f184')->assertUnprocessable();
        } else {
            $this->voucherPayment($adapter, 0)->assertStatus(409);
        }
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 1]);
    }

    public static function linkedVoucherFirstSettlements(): array
    {
        return [
            'pos-zero-cash' => ['pos', 0],
            'pos-positive-cash' => ['pos', 30],
            'cod-zero-cash' => ['cod', 0],
            'cod-positive-cash' => ['cod', 30],
        ];
    }

    #[DataProvider('linkedVoucherFirstSettlements')]
    public function test_first_linked_voucher_settlement_preserves_already_applied_voucher(string $adapter, int $cash): void
    {
        $this->voucherInvoice(100);
        DB::table('pos_transaction')->where('id', 1)->update(['voucher' => 100, 'voucher_qty' => 1,
            'deposito_id' => 4, 'paid' => 0]);
        DB::table('deposito')->where('id', 4)->update(['voucher_qty' => 0]);
        $depositoBefore = (array) DB::table('deposito')->where('id', 4)->first();
        $this->assertSame(0, DB::table('pos_payment')->count());

        $this->voucherPayment($adapter, $cash)->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'voucher' => 100, 'voucher_qty' => 1,
            'deposito_id' => 4, 'paid' => 0, 'status' => 'paid',
            'ongkir_status' => $adapter === 'cod' ? 'delivered' : 'draft']);
        $this->assertSame($depositoBefore, (array) DB::table('deposito')->where('id', 4)->first());
        $this->assertSame(1, DB::table('deposito')->count());
        $this->assertDatabaseHas('pos_payment', ['pos_id' => 1, 'total' => $cash, 'return' => $cash, 'remaining' => 0]);
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertSame(0.0, (float) DB::table('pos_payment')->sum('total') - (float) DB::table('pos_payment')->sum('return'));
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
    }

    public static function settledLedgers(): array
    {
        $cases = [];
        foreach (['pos', 'cod'] as $adapter) {
            foreach ([0, 20] as $voucher) {
                foreach ([0, 30] as $cash) {
                    $cases[$adapter . '-voucher-' . $voucher . '-cash-' . $cash] = [$adapter, $voucher, $cash];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('settledLedgers')]
    public function test_settled_net_ledger_cannot_accept_new_payments_or_consume_another_voucher(string $adapter, int $voucher, int $cash): void
    {
        $this->voucherInvoice(10, 100 - $voucher);
        DB::table('pos_transaction')->where('id', 1)->update(['voucher' => $voucher]);
        $invoiceBefore = (array) DB::table('pos_transaction')->where('id', 1)->first();
        $paymentBefore = (array) DB::table('pos_payment')->where('id', 1)->first();

        $this->voucherPayment($adapter, $cash)->assertUnprocessable()->assertJsonValidationErrors('payments');

        $this->assertSame($invoiceBefore, (array) DB::table('pos_transaction')->where('id', 1)->first());
        $this->assertSame($paymentBefore, (array) DB::table('pos_payment')->where('id', 1)->first());
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertSame(0, DB::table('pos_payment_submissions')->count());
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 1]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'draft']);
    }

    public static function coveredVouchers(): array
    {
        $cases = [];
        foreach (['pos', 'cod'] as $adapter) {
            foreach (['equal' => [100, 0], 'greater' => [150, 0], 'residual' => [100, 20]] as $name => [$face, $paid]) {
                foreach ([0, 30] as $cash) {
                    $cases[$adapter . '-' . $name . '-cash-' . $cash] = [$adapter, $face, $paid, $cash];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('coveredVouchers')]
    public function test_new_voucher_can_atomically_cover_invoice_or_residual_debt_through_real_adapter(string $adapter, int $face, int $historicalPaid, int $cash): void
    {
        $this->voucherInvoice($face, $historicalPaid);
        $first = $this->voucherPayment($adapter, $cash)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => 'paid', 'paid' => $historicalPaid,
            'voucher' => 100 - $historicalPaid, 'voucher_qty' => 1, 'deposito_id' => 4,
            'ongkir_status' => $adapter === 'cod' ? 'delivered' : 'draft']);
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 0]);
        $this->assertDatabaseHas('production', ['id' => 1, 'status' => 'complete']);
        $payment = DB::table('pos_payment')->orderByDesc('id')->first();
        $this->assertSame((float) $cash, (float) $payment->total);
        $this->assertSame((float) $cash, (float) $payment->return);
        $this->assertSame(0.0, (float) $payment->remaining);
        $this->assertSame((float) $historicalPaid, (float) DB::table('pos_payment')->sum('total') - (float) DB::table('pos_payment')->sum('return'));
        if ($historicalPaid > 0) {
            $this->assertDatabaseHas('pos_payment', ['id' => 1, 'total' => 30, 'return' => 10, 'remaining' => 80]);
        }
        if ($adapter === 'pos') {
            // Tracked adapter is legacy; the local request-key adapter is exercised with an explicit 0.
            $legacyAdapter = getenv('INFRUITY_TEST_LEGACY_POS_ADAPTER') !== '0';
            if ($legacyAdapter) {
                $this->voucherPayment($adapter, $cash)->assertUnprocessable()->assertJsonValidationErrors('transaction_id');
            } else {
                $this->voucherPayment($adapter, $cash)->assertOk()->assertExactJson($first->json());
            }
            $this->voucherPayment($adapter, $cash, '2f5969d2-9dda-4ee8-9ed7-6c048172f184')->assertUnprocessable();
            $this->assertSame($legacyAdapter ? 0 : 1, DB::table('pos_payment_submissions')->count());
        } else {
            $this->voucherPayment($adapter, $cash)->assertStatus(409);
        }
        $this->assertSame($historicalPaid > 0 ? 2 : 1, DB::table('pos_payment')->count());
        $this->assertDatabaseHas('deposito', ['id' => 4, 'voucher_qty' => 0]);
    }

    public static function invalidAmounts(): array
    {
        return array_map(fn ($amount) => [$amount], [null, '', '0', '-1', '1e3', '12.50', '10,50', 'Rp 10.000', '1.00.000', [], '100000000000000']);
    }

    #[DataProvider('invalidAmounts')]
    public function test_unpaid_delivery_rejects_invalid_or_missing_cash_without_any_mutation(mixed $amount): void
    {
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => $amount])->assertUnprocessable();
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 20000, 'ongkir_status' => 'draft']);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    #[DataProvider('unpayableStates')]
    public function test_canceled_or_already_paid_invoice_rejects_new_cash(string $status): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['status' => $status]);
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertStatus(409);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'status' => $status, 'ongkir_status' => 'draft']);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public static function unpayableStates(): array { return [['canceled'], ['paid']]; }

    public function test_paid_invoice_can_be_received_without_collecting_cash_again(): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['status' => 'paid', 'paid' => 90000]);
        $this->putJson('/delivery-order/set-selesai/1')->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 90000, 'status' => 'paid', 'ongkir_status' => 'delivered']);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public function test_write_failure_rolls_back_delivery_header_and_ledger_without_leaking_database_error(): void
    {
        DB::statement("CREATE TRIGGER fail_fixture_payment BEFORE INSERT ON pos_payment BEGIN SELECT RAISE(ABORT, 'fixture-sensitive-sql'); END");
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])
            ->assertStatus(500)->assertJsonPath('success', false)->assertDontSee('fixture-sensitive-sql');
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'ongkir_status' => 'draft', 'paid' => 20000]);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public function test_delivery_listing_also_requires_the_page_permission(): void
    {
        $this->getJson('/delivery-order/data')->assertForbidden();
    }

    public function test_delivery_listing_all_and_foreign_filter_cannot_expose_other_branches(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'delivery-order.index']);
        $this->getJson('/delivery-order/data')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 1);
        $this->getJson('/delivery-order/data?cabang_filter=1')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_pos_and_cod_share_the_same_net_ledger_and_delivered_debt_is_paid_through_pos(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'pos.savePayment']);
        $this->paymentSubmissionsSchema();
        $payload = ['date' => '2026-10-29', 'transaction_id' => 1, 'customer_id' => null,
            'idempotency_key' => '66cf8eb1-c48d-4f52-a6e7-a667a5b3a6bb', 'total_payment' => 10000,
            'payments' => [['payment_id' => 7, 'payment_name' => 'Cash', 'amount' => '10.000']]];
        $this->postJson('/pos/savePayment', $payload)->assertOk()->assertJsonPath('success', true);
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertOk();
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 40000, 'status' => 'debt', 'ongkir_status' => 'delivered']);
        $payload['idempotency_key'] = '2f5969d2-9dda-4ee8-9ed7-6c048172f184';
        $payload['total_payment'] = 50000; $payload['payments'][0]['amount'] = '50.000';
        $this->postJson('/pos/savePayment', $payload)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 90000, 'status' => 'paid', 'ongkir_status' => 'delivered']);
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertStatus(409);
        $this->assertSame(4, DB::table('pos_payment')->count());
    }

    public function test_a_non_delivery_invoice_cannot_be_paid_through_the_delivery_endpoint(): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['ongkir' => 0]);
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertNotFound();
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 20000, 'ongkir_status' => 'draft']);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public function test_cash_settlement_uses_invoice_branch_real_cash_method_net_paid_and_only_current_change(): void
    {
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '100.000'])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'paid' => 90000, 'status' => 'paid', 'ongkir_status' => 'delivered']);
        $this->assertDatabaseHas('pos_payment', ['id' => 2, 'pos_id' => 1, 'branch_id' => 2, 'total' => 100000, 'return' => 30000, 'remaining' => 0, 'payment_method_id' => '[7]']);
        $this->assertDatabaseHas('pos_payment', ['id' => 1, 'return' => 10000, 'total' => 30000]);
    }

    public function test_delivery_is_one_terminal_operation_and_retry_cannot_append_another_payment(): void
    {
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertOk()->assertJsonPath('success', true);
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertStatus(409);
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'ongkir_status' => 'delivered']);
        $this->assertSame(2, DB::table('pos_payment')->count());
    }

    public function test_permission_denial_is_forbidden_before_payment_or_delivery(): void
    {
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => 'delivery-order.set-selesai', 'is_allowed' => false]);
        $this->putJson('/delivery-order/set-selesai/1', ['nominal' => '10.000'])->assertForbidden();
        $this->assertDatabaseHas('pos_transaction', ['id' => 1, 'ongkir_status' => 'draft']);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public function test_cross_branch_delivery_payment_cannot_mutate_header_or_ledger(): void
    {
        $this->putJson('/delivery-order/set-selesai/2', ['nominal' => '10.000'])->assertNotFound();
        $this->assertDatabaseHas('pos_transaction', ['id' => 2, 'paid' => 20000, 'ongkir_status' => 'draft']);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }
}
