<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExpenditureAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(8, 0));
        config(['database.default' => 'expenditure_authorization_test', 'database.connections.expenditure_authorization_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        // Emulate the existing invoice helper's MySQL scalar functions, not business data.
        $pdo = DB::connection()->getPdo();
        $pdo->createFunction('RIGHT', static fn ($value, $length) => substr((string) $value, -(int) $length), 2);
        $pdo->createFunction('MONTH', static fn ($value) => (int) date('n', strtotime($value)), 1);
        $pdo->createFunction('YEAR', static fn ($value) => (int) date('Y', strtotime($value)), 1);
        $pdo->createFunction('NOW', static fn () => now()->toDateTimeString(), 0);

        Schema::create('users', function (Blueprint $t) { $t->integer('id_user')->primary(); $t->string('nm_user'); $t->boolean('is_aktif')->default(true); });
        Schema::create('role', function (Blueprint $t) { $t->integer('id_role')->primary(); });
        Schema::create('role_user', function (Blueprint $t) { $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role'); $t->timestamps(); });
        Schema::create('role_menu', function (Blueprint $t) { $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $t) { $t->increments('id'); $t->integer('id_user'); $t->string('permission'); $t->boolean('is_allowed'); });
        Schema::create('user_branch', function (Blueprint $t) { $t->integer('user_id'); $t->integer('branch_id'); });
        Schema::create('branch', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('payment_method', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('expenditure', function (Blueprint $t) {
            $t->increments('id'); $t->string('uuid'); $t->string('type'); $t->integer('branch_id')->nullable();
            $t->date('date')->nullable(); $t->string('invoice_number')->nullable(); $t->decimal('total', 15, 2)->nullable();
            $t->decimal('discount', 15, 2)->nullable(); $t->decimal('paid', 15, 2)->nullable(); $t->decimal('return', 15, 2)->nullable();
            $t->integer('payment_method')->nullable(); $t->string('status'); $t->integer('created_by')->nullable();
            $t->integer('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('expenditure_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('expenditure_id'); $t->integer('product_id')->nullable();
            $t->string('product_name')->nullable(); $t->string('product_unit')->nullable(); $t->decimal('quantity', 10, 2);
            $t->decimal('price', 15, 2); $t->decimal('discount', 15, 2); $t->decimal('subtotal', 15, 2);
            $t->string('type'); $t->integer('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('expenditure_payment', function (Blueprint $t) {
            $t->increments('id'); $t->string('uuid'); $t->string('nota_number')->nullable(); $t->integer('expenditure_id');
            $t->decimal('total', 15, 2); $t->decimal('remaining', 15, 2)->default(0); $t->decimal('return', 15, 2)->default(0);
            $t->string('payment_method')->nullable(); $t->string('payment_method_id')->nullable(); $t->string('payment_amount')->nullable();
            $t->integer('branch_id')->nullable(); $t->date('date')->nullable(); $t->integer('created_by')->nullable();
            $t->integer('updated_by')->nullable(); $t->timestamps();
        });
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Fixture Operator']);
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert([
            ['id_role' => 2, 'permission' => 'expenditure.save-transaction'],
            ['id_role' => 2, 'permission' => 'expenditure.edit'],
            ['id_role' => 2, 'permission' => 'expenditure.update'],
        ]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('branch')->insert([['id' => 1, 'name' => 'Allowed Branch'], ['id' => 2, 'name' => 'Foreign Branch']]);
        DB::table('payment_method')->insert(['id' => 1, 'name' => 'Cash']);
        foreach ([1, 2] as $id) {
            DB::table('expenditure')->insert([
                'id' => $id, 'uuid' => 'fixture-expenditure-' . $id, 'branch_id' => $id,
                'invoice_number' => 'EXP20261000' . $id, 'date' => '2026-10-29', 'type' => 'pengeluaran',
                'total' => 10000, 'paid' => 5000, 'payment_method' => 1, 'status' => 'draft', 'created_by' => 7,
                'created_at' => now()->subDay(), 'updated_at' => now(),
            ]);
            DB::table('expenditure_detail')->insert([
                'id' => $id, 'expenditure_id' => $id, 'product_id' => null, 'product_name' => 'Fixture Expense',
                'product_unit' => 'Item', 'price' => 10000, 'quantity' => 1, 'discount' => 0, 'subtotal' => 10000,
                'type' => 'product', 'created_by' => 7,
            ]);
            DB::table('expenditure_payment')->insert([
                'id' => $id, 'uuid' => 'fixture-payment-' . $id, 'expenditure_id' => $id, 'branch_id' => $id,
                'date' => '2026-10-29', 'total' => 5000, 'payment_method_id' => '1', 'created_by' => 7,
            ]);
        }
        $this->actingAs(User::findOrFail(7));
        session(['role' => ['id_role' => 2]]);
    }

    private function payload(int $id = 1): array
    {
        return [
            'expenditure_id' => $id, 'invoice_number' => 'EXP20261000' . $id, 'branch_id' => 1,
            'date' => '2026-10-29', 'type' => 'pengeluaran', 'status' => 'draft', 'total' => 10000, 'subtotal' => 10000,
            'payment' => 5000, 'payment_method' => 1,
            'items' => [['id' => null, 'name' => 'Edited Expense', 'unit' => 'Item', 'price' => 10000, 'qty' => 1, 'discount' => 0, 'total_input' => 10000]],
        ];
    }

    private function save(array $payload): TestResponse
    {
        return $this->postJson('/expenditure/save-transaction', $payload);
    }

    public function test_an_accessible_draft_edit_page_still_renders_its_real_record(): void
    {
        $this->get('/expenditure/1/edit')->assertOk()
            ->assertViewHas('data', fn ($transaction) => $transaction->id === 1 && $transaction->invoice_number === 'EXP202610001');
    }

    public function test_delete_fails_closed_when_paid_header_has_no_matching_payment_history(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.destroy']);
        DB::table('expenditure_payment')->where('expenditure_id', 1)->delete();
        $this->deleteJson('/expenditure/1')->assertStatus(409);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'paid' => 5000]);
    }

    public function test_missing_identity_key_is_rejected_instead_of_turning_a_legacy_edit_into_a_new_transaction(): void
    {
        $payload = $this->payload(); unset($payload['expenditure_id']);
        $this->save($payload)->assertUnprocessable()->assertJsonValidationErrors('expenditure_id');
        $this->assertSame(2, DB::table('expenditure')->count());
    }

    public function test_create_rejects_a_branch_the_actor_cannot_access(): void
    {
        $payload = $this->payload(); $payload['expenditure_id'] = null; $payload['branch_id'] = 2;
        $this->save($payload)->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->assertSame(2, DB::table('expenditure')->count());
    }

    public function test_a_preview_invoice_cannot_overwrite_an_existing_record_without_its_id(): void
    {
        $payload = $this->payload(2); $payload['expenditure_id'] = null;
        $response = $this->save($payload)->assertOk();
        $this->assertNotSame(2, $response->json('transaksi_id'));
        $this->assertSame(3, DB::table('expenditure')->count());
        $this->assertDatabaseHas('expenditure', ['id' => 2, 'branch_id' => 2, 'uuid' => 'fixture-expenditure-2']);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 2, 'expenditure_id' => 2]);
    }

    public function test_an_invoice_label_cannot_redirect_an_id_based_edit_to_another_record(): void
    {
        $payload = $this->payload(); $payload['invoice_number'] = 'EXP202610002';
        $this->save($payload)->assertOk()->assertJsonPath('transaksi_id', 1);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'invoice_number' => 'EXP202610001']);
        $this->assertDatabaseHas('expenditure', ['id' => 2, 'branch_id' => 2]);
    }

    public function test_a_recent_final_transaction_remains_editable_without_duplication(): void
    {
        DB::table('expenditure')->where('id', 1)->update(['status' => 'paid', 'updated_at' => now()->subMinutes(5)]);
        $payload = $this->payload(); $payload['status'] = 'paid';
        $this->save($payload)->assertOk()->assertJsonPath('transaksi_id', 1);
        $this->save($payload)->assertOk()->assertJsonPath('transaksi_id', 1);
        $this->assertSame(2, DB::table('expenditure_payment')->count());
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'status' => 'paid', 'paid' => 5000]);
    }

    public function test_edit_page_also_enforces_the_final_edit_deadline(): void
    {
        DB::table('expenditure')->where('id', 1)->update(['status' => 'paid', 'updated_at' => now()->subMinutes(31)]);
        $this->getJson('/expenditure/1/edit')->assertStatus(409);
    }

    public function test_canceled_transaction_cannot_be_reopened_by_saving(): void
    {
        DB::table('expenditure')->where('id', 1)->update(['status' => 'canceled']);
        $this->save($this->payload())->assertStatus(409);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'status' => 'canceled']);
    }

    public function test_edit_preserves_multiple_historical_payment_rows(): void
    {
        DB::table('expenditure_payment')->where('id', 1)->update(['total' => 2000]);
        DB::table('expenditure_payment')->insert(['id' => 3, 'uuid' => 'second-payment', 'expenditure_id' => 1, 'total' => 3000, 'branch_id' => 1, 'payment_method_id' => '1']);
        $this->save($this->payload())->assertOk();
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'uuid' => 'fixture-payment-1', 'total' => 2000]);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 3, 'uuid' => 'second-payment', 'total' => 3000]);
        $this->assertSame(3, DB::table('expenditure_payment')->count());
    }

    public function test_edit_cannot_replace_multi_payment_history_with_one_client_amount(): void
    {
        DB::table('expenditure_payment')->where('id', 1)->update(['total' => 2000]);
        DB::table('expenditure_payment')->insert(['id' => 3, 'uuid' => 'second-payment', 'expenditure_id' => 1, 'total' => 3000, 'branch_id' => 1, 'payment_method_id' => '1']);
        $payload = $this->payload(); $payload['payment'] = 6000;
        $this->save($payload)->assertStatus(409);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'paid' => 5000]);
        $this->assertDatabaseHas('expenditure_detail', ['id' => 1, 'product_name' => 'Fixture Expense']);
    }

    public function test_failure_updating_payment_rolls_back_header_and_details_together(): void
    {
        DB::statement("CREATE TRIGGER reject_payment_update BEFORE UPDATE ON expenditure_payment BEGIN SELECT RAISE(ABORT, 'fixture'); END");
        $payload = $this->payload(); $payload['total'] = 12000;
        $this->save($payload)->assertStatus(500)->assertJsonPath('success', false);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'total' => 10000]);
        $this->assertDatabaseHas('expenditure_detail', ['id' => 1, 'product_name' => 'Fixture Expense']);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'total' => 5000]);
    }

    public function test_draft_with_real_payments_cannot_be_deleted(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.destroy']);
        $this->deleteJson('/expenditure/1')->assertStatus(409);
        $this->assertDatabaseHas('expenditure', ['id' => 1]);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'total' => 5000]);
    }

    public function test_an_unpaid_draft_can_be_deleted_without_orphaning_zero_payment_placeholders(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.destroy']);
        DB::table('expenditure')->where('id', 1)->update(['paid' => 0]);
        DB::table('expenditure_payment')->where('id', 1)->update(['total' => 0]);
        $this->deleteJson('/expenditure/1')->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('expenditure', ['id' => 1]);
        $this->assertDatabaseMissing('expenditure_detail', ['expenditure_id' => 1]);
        $this->assertDatabaseMissing('expenditure_payment', ['expenditure_id' => 1]);
    }

    public function test_authorized_list_only_returns_accessible_branch_rows(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.index']);
        $this->getJson('/expenditure/data')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 1);
        $this->getJson('/expenditure/data?cabang_filter=2')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_explicit_save_denial_returns_forbidden_before_mutation(): void
    {
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => 'expenditure.save-transaction', 'is_allowed' => false]);
        $this->save($this->payload())->assertForbidden();
        $this->assertDatabaseHas('expenditure_detail', ['id' => 1, 'product_name' => 'Fixture Expense']);
    }

    public function test_invalid_item_does_not_delete_existing_details(): void
    {
        $payload = $this->payload(); $payload['items'][0]['qty'] = -1;
        $this->save($payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.qty');
        $this->assertDatabaseHas('expenditure_detail', ['id' => 1, 'product_name' => 'Fixture Expense']);
    }

    public function test_list_data_requires_the_index_permission(): void
    {
        $this->getJson('/expenditure/data')->assertForbidden();
    }

    public function test_edit_cannot_move_an_existing_invoice_to_another_branch_even_when_both_are_accessible(): void
    {
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 2]);
        $payload = $this->payload(); $payload['branch_id'] = 2;
        $this->save($payload)->assertStatus(422);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'branch_id' => 1]);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'branch_id' => 1]);
    }

    public function test_delete_cannot_remove_a_final_transaction_with_payment_history(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.destroy']);
        DB::table('expenditure')->where('id', 1)->update(['status' => 'paid']);
        $this->deleteJson('/expenditure/1')->assertStatus(409);
        $this->assertDatabaseHas('expenditure', ['id' => 1]);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'total' => 5000]);
    }

    public function test_delete_cannot_remove_another_branches_transaction(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.destroy']);
        $this->deleteJson('/expenditure/2')->assertNotFound();
        $this->assertDatabaseHas('expenditure', ['id' => 2]);
        $this->assertDatabaseHas('expenditure_payment', ['expenditure_id' => 2]);
    }

    public function test_the_edit_page_does_not_expose_a_foreign_transaction(): void
    {
        $this->getJson('/expenditure/2/edit')->assertNotFound();
    }

    public function test_a_recent_final_transaction_cannot_be_reverted_to_draft(): void
    {
        DB::table('expenditure')->where('id', 1)->update(['status' => 'paid']);
        $this->save($this->payload())->assertStatus(409);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'status' => 'paid']);
    }

    public function test_an_expired_final_transaction_cannot_be_edited(): void
    {
        DB::table('expenditure')->where('id', 1)->update(['status' => 'paid', 'updated_at' => now()->subMinutes(31)]);
        $payload = $this->payload(); $payload['status'] = 'paid';
        $this->save($payload)->assertStatus(409);
        $this->assertDatabaseHas('expenditure_detail', ['id' => 1, 'product_name' => 'Fixture Expense']);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'total' => 5000]);
    }

    public function test_an_authorized_edit_preserves_header_identity_and_existing_payment_links(): void
    {
        $this->save($this->payload())->assertOk()->assertJsonPath('transaksi_id', 1);
        $this->assertDatabaseHas('expenditure', ['id' => 1, 'uuid' => 'fixture-expenditure-1', 'invoice_number' => 'EXP202610001']);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 1, 'uuid' => 'fixture-payment-1', 'expenditure_id' => 1, 'total' => 5000]);
        $this->assertSame(2, DB::table('expenditure')->count());
        $this->assertSame(2, DB::table('expenditure_payment')->count());
        $this->assertDatabaseHas('expenditure_detail', ['expenditure_id' => 1, 'product_name' => 'Edited Expense']);
    }

    public function test_a_foreign_invoice_cannot_be_replaced_by_submitting_an_allowed_destination_branch(): void
    {
        $this->save($this->payload(2))->assertNotFound();
        $this->assertDatabaseHas('expenditure', ['id' => 2, 'branch_id' => 2, 'uuid' => 'fixture-expenditure-2']);
        $this->assertDatabaseHas('expenditure_detail', ['id' => 2, 'expenditure_id' => 2]);
        $this->assertDatabaseHas('expenditure_payment', ['id' => 2, 'expenditure_id' => 2, 'total' => 5000]);
    }
}
