<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Pos\Http\Controllers\PosController;
use Tests\TestCase;

class PosPaymentIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'payment_test', 'database.connections.payment_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('users', function (Blueprint $t) { $t->integer('id_user')->primary(); $t->string('nm_user'); });
        Schema::create('role', function (Blueprint $t) { $t->integer('id_role')->primary(); });
        Schema::create('role_user', function (Blueprint $t) { $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role'); $t->timestamps(); });
        Schema::create('role_menu', function (Blueprint $t) { $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $t) { $t->increments('id'); $t->integer('id_user'); $t->string('permission', 160); $t->boolean('is_allowed'); $t->timestamps(); $t->unique(['id_user', 'permission']); });
        Schema::create('user_branch', function (Blueprint $t) { $t->integer('user_id'); $t->integer('branch_id'); });
        Schema::create('branch', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('customer', function (Blueprint $t) { $t->integer('id')->primary(); });
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
        Schema::create('production', function (Blueprint $t) { $t->increments('id'); $t->integer('pos_id'); $t->string('status'); $t->timestamps(); });
        (require database_path('migrations/2026_10_25_150000_create_pos_payment_submissions_table.php'))->up();
        DB::table('users')->insert([['id_user' => 7, 'nm_user' => 'Kasir Fixture'], ['id_user' => 8, 'nm_user' => 'Kasir Lain']]);
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert([['id_user' => 7, 'id_role' => 2], ['id_user' => 8, 'id_role' => 2]]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'pos.savePayment']);
        DB::table('user_branch')->insert([['user_id' => 7, 'branch_id' => 1], ['user_id' => 8, 'branch_id' => 1]]);
        DB::table('branch')->insert([['id' => 1, 'name' => 'Cabang Fixture'], ['id' => 2, 'name' => 'Cabang Terlarang']]);
        DB::table('payment_method')->insert(['id' => 1, 'name' => 'Cash']);
        DB::table('pos_transaction')->insert(['id' => 1, 'branch_id' => 1, 'total' => 500000, 'status' => 'debt']);
        Route::post('/_payment-fixture', [PosController::class, 'savePayment']);
        $this->actingAs(User::findOrFail(7));
    }

    private function payload(string $key = 'fa15bf32-3484-4a12-8856-e20d74606d11', int $amount = 100000): array
    {
        return ['idempotency_key' => $key, 'date' => '2026-10-25', 'transaction_id' => 1, 'customer_id' => null,
            'total_payment' => $amount, 'payments' => [['payment_id' => 1, 'amount' => number_format($amount, 0, ',', '.')]]];
    }

    public function test_retried_partial_payment_returns_original_result_without_adding_to_ledger(): void
    {
        $first = $this->postJson('/_payment-fixture', $this->payload())->assertOk()->assertJsonPath('success', true);
        $this->postJson('/_payment-fixture', $this->payload())->assertOk()->assertExactJson($first->json());
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertSame(100000.0, (float) DB::table('pos_transaction')->value('paid'));
        $this->assertSame(1, DB::table('pos_payment_submissions')->count());
    }

    public function test_retry_after_full_payment_succeeds_but_new_intent_is_rejected(): void
    {
        $payload = $this->payload(amount: 500000);
        $first = $this->postJson('/_payment-fixture', $payload)->assertOk();
        $this->postJson('/_payment-fixture', $payload)->assertOk()->assertJsonPath('payment.id', $first->json('payment.id'));
        $this->postJson('/_payment-fixture', $this->payload('53c09376-55a8-472e-9400-5c821688487a'))->assertStatus(422);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public function test_reusing_key_for_changed_payload_or_another_actor_is_conflict(): void
    {
        $this->postJson('/_payment-fixture', $this->payload())->assertOk();
        $this->postJson('/_payment-fixture', $this->payload(amount: 200000))->assertStatus(409);
        $this->actingAs(User::findOrFail(8));
        $this->postJson('/_payment-fixture', $this->payload())->assertStatus(409);
        $this->assertSame(1, DB::table('pos_payment')->count());
    }

    public function test_a_distinct_partial_payment_intent_can_be_recorded(): void
    {
        $this->postJson('/_payment-fixture', $this->payload())->assertOk();
        $this->postJson('/_payment-fixture', $this->payload('53c09376-55a8-472e-9400-5c821688487a'))->assertOk();
        $this->assertSame(2, DB::table('pos_payment')->count());
        $this->assertSame(200000.0, (float) DB::table('pos_transaction')->value('paid'));
    }

    public function test_authorization_is_checked_before_replay_and_ledger_and_key_rollback_together(): void
    {
        $this->postJson('/_payment-fixture', $this->payload())->assertOk();
        DB::table('user_branch')->where('user_id', 7)->delete();
        $this->postJson('/_payment-fixture', $this->payload())->assertNotFound();
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::statement("CREATE TRIGGER fail_submission BEFORE INSERT ON pos_payment_submissions BEGIN SELECT RAISE(ABORT, 'fixture'); END");
        $this->postJson('/_payment-fixture', $this->payload('53c09376-55a8-472e-9400-5c821688487a'))->assertStatus(500);
        $this->assertSame(1, DB::table('pos_payment')->count());
        $this->assertSame(100000.0, (float) DB::table('pos_transaction')->value('paid'));
    }

    public function test_missing_migration_fails_closed_without_recording_payment(): void
    {
        Schema::drop('pos_payment_submissions');
        $this->postJson('/_payment-fixture', $this->payload())->assertStatus(503);
        $this->assertSame(0, DB::table('pos_payment')->count());
    }
}
