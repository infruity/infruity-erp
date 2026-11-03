<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Transaction\Http\Controllers\SortirController;
use Tests\TestCase;

class SortirMobileFlowTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // This fixture uses a disposable database, never the working inventory database.
        $this->testDatabase = tempnam(sys_get_temp_dir(), 'sortir-mobile-');
        config(['database.default' => 'sortir_mobile_test', 'database.connections.sortir_mobile_test' => [
            'driver' => 'sqlite', 'database' => $this->testDatabase, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        Schema::create('role', fn (Blueprint $t) => $t->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $t) { $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role'); $t->timestamps(); });
        Schema::create('role_menu', function (Blueprint $t) { $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $t) { $t->increments('id'); $t->integer('id_user'); $t->string('permission', 160); $t->boolean('is_allowed'); $t->timestamps(); $t->unique(['id_user', 'permission']); });
        Schema::create('users', function (Blueprint $t) { $t->integer('id_user')->primary(); $t->string('nm_user'); });
        Schema::create('branch', function (Blueprint $t) { $t->increments('id'); $t->string('name'); });
        Schema::create('user_branch', function (Blueprint $t) { $t->integer('user_id'); $t->integer('branch_id'); });
        Schema::create('product_units', function (Blueprint $t) { $t->increments('id'); $t->string('abbreviation'); });
        Schema::create('products', function (Blueprint $t) {
            $t->increments('id'); $t->string('name'); $t->integer('product_unit'); $t->string('tipe');
            $t->integer('is_variant')->nullable(); $t->decimal('hpp', 15, 2); $t->timestamps();
        });
        Schema::create('product_stock', function (Blueprint $t) {
            $t->integer('id'); $t->integer('branch_id'); $t->decimal('stock_available', 15, 2); $t->decimal('avg_hpp', 15, 2);
        });
        Schema::create('sortir_transaction', function (Blueprint $t) {
            $t->increments('id'); $t->string('uuid'); $t->string('invoice_number'); $t->integer('branch_id');
            $t->date('date'); $t->decimal('total', 15, 2); $t->string('status'); $t->integer('created_by'); $t->timestamps();
        });
        Schema::create('sortir_transaction_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('sortir_id'); $t->integer('product_id'); $t->decimal('quantity', 10, 2);
            $t->decimal('price', 15, 2); $t->decimal('discount', 15, 2); $t->decimal('subtotal', 15, 2);
            $t->integer('created_by'); $t->timestamps();
        });
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        foreach (['index', 'create', 'save-transaction', 'show', 'edit', 'destroy'] as $action) {
            DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'sortir.' . $action]);
        }
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Dimas']);
        DB::table('branch')->insert([['id' => 1, 'name' => 'GKB'], ['id' => 2, 'name' => 'GBA']]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('product_units')->insert(['id' => 1, 'abbreviation' => 'Kg']);
        DB::table('products')->insert(['id' => 11, 'name' => 'Anggur', 'product_unit' => 1, 'hpp' => 5000, 'tipe' => 'buah']);
        DB::table('product_stock')->insert([
            ['id' => 11, 'branch_id' => 1, 'stock_available' => 10, 'avg_hpp' => 5000],
            ['id' => 11, 'branch_id' => 2, 'stock_available' => 100, 'avg_hpp' => 7000],
        ]);
        $user = new User(); $user->id_user = 7; $user->nm_user = 'Dimas';
        $this->actingAs($user);
        Route::post('/_test/sortir-save', [SortirController::class, 'saveTransaction']);
        Route::get('/_test/sortir-data', [SortirController::class, 'get_data']);
        Route::get('/_test/sortir-products', [SortirController::class, 'products']);
        Route::delete('/_test/sortir/{id}', [SortirController::class, 'destroy']);
        Route::get('/_test/sortir-show/{id}', [SortirController::class, 'show']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sortir_mobile_test');
        if (isset($this->testDatabase)) unlink($this->testDatabase);
        parent::tearDown();
    }

    private function payload(string $status = 'draft'): array
    {
        return ['branch_id' => 1, 'date' => '2026-10-23', 'invoice_number' => 'SOR-TEST-1',
            'status' => $status, 'subtotal' => 1, 'total' => 1,
            'items' => [['id' => 11, 'qty' => 2.5, 'price' => 5000, 'discount' => 0, 'total_input' => 1]]];
    }

    public function test_draft_and_final_compute_totals_and_keep_header_identity(): void
    {
        $this->postJson('/_test/sortir-save', $this->payload())->assertOk()->assertJson(['success' => true]);
        $uuid = DB::table('sortir_transaction')->value('uuid');
        $payload = $this->payload('paid'); $payload['sortir_id'] = DB::table('sortir_transaction')->value('id');
        $this->postJson('/_test/sortir-save', $payload)->assertOk()->assertJson(['status' => 'paid']);
        $this->assertDatabaseCount('sortir_transaction', 1);
        $this->assertDatabaseHas('sortir_transaction', ['total' => 12500, 'status' => 'paid', 'uuid' => $uuid, 'created_by' => 7]);
        $this->assertDatabaseHas('sortir_transaction_detail', ['quantity' => 2.5, 'subtotal' => 12500]);
    }

    public function test_stock_limit_is_enforced_at_finalization_and_rejection_keeps_draft(): void
    {
        $payload = $this->payload(); $payload['items'][0]['qty'] = 11;
        $this->postJson('/_test/sortir-save', $payload)->assertOk();
        $payload['status'] = 'paid'; $payload['sortir_id'] = DB::table('sortir_transaction')->value('id');
        $this->postJson('/_test/sortir-save', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.qty');
        $this->assertDatabaseHas('sortir_transaction', ['status' => 'draft', 'total' => 55000]);
    }

    public function test_invalid_quantities_duplicate_items_and_discounts_cannot_save(): void
    {
        foreach ([0, -1, 1.234] as $quantity) {
            $payload = $this->payload(); $payload['items'][0]['qty'] = $quantity;
            $this->postJson('/_test/sortir-save', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.qty');
        }
        $payload = $this->payload(); $payload['items'][] = $payload['items'][0];
        $this->postJson('/_test/sortir-save', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.id');
        $payload = $this->payload(); $payload['items'][0]['discount'] = 12501;
        $this->postJson('/_test/sortir-save', $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.discount');
        $this->assertDatabaseCount('sortir_transaction', 0);
    }

    public function test_authorized_branch_is_required_for_saves_product_lookup_and_record_access(): void
    {
        $payload = $this->payload(); $payload['branch_id'] = 2;
        $this->postJson('/_test/sortir-save', $payload)->assertForbidden();
        $this->getJson('/_test/sortir-products?branch_id=2')->assertForbidden();
        $this->postJson('/_test/sortir-save', $this->payload())->assertOk();
        $id = DB::table('sortir_transaction')->value('id');
        DB::table('sortir_transaction')->where('id', $id)->update(['branch_id' => 2]);
        $this->getJson('/_test/sortir-show/' . $id)->assertNotFound();
        $this->deleteJson('/_test/sortir/' . $id)->assertNotFound();
        $payload = $this->payload(); $payload['sortir_id'] = $id;
        $this->postJson('/_test/sortir-save', $payload)->assertNotFound();
    }

    public function test_finalized_transaction_cannot_be_reverted_or_deleted_by_non_admin(): void
    {
        $this->postJson('/_test/sortir-save', $this->payload('paid'))->assertOk();
        $id = DB::table('sortir_transaction')->value('id');
        $payload = $this->payload(); $payload['sortir_id'] = $id;
        $this->postJson('/_test/sortir-save', $payload)->assertStatus(409);
        $this->deleteJson('/_test/sortir/' . $id)->assertForbidden();
        DB::table('sortir_transaction')->where('id', $id)->update(['updated_at' => now()->subMinutes(31)]);
        $payload['status'] = 'paid';
        $this->postJson('/_test/sortir-save', $payload)->assertStatus(409);
        $this->assertDatabaseHas('sortir_transaction', ['status' => 'paid']);
    }

    public function test_product_lookup_and_final_edit_credit_existing_quantity(): void
    {
        $this->postJson('/_test/sortir-save', $this->payload('paid'))->assertOk();
        $id = DB::table('sortir_transaction')->value('id');
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 0]);
        $this->getJson('/_test/sortir-products?branch_id=1&sortir_id=' . $id)
            ->assertOk()->assertJsonFragment(['id' => 11, 'name' => 'Anggur', 'hpp' => 5000, 'stock_available' => 2.5]);
        $payload = $this->payload('paid'); $payload['sortir_id'] = $id;
        $this->postJson('/_test/sortir-save', $payload)->assertOk();
        $payload['items'][0]['qty'] = 3;
        $this->postJson('/_test/sortir-save', $payload)->assertUnprocessable();
    }

    public function test_mobile_list_search_filter_pagination_and_branch_scope(): void
    {
        foreach (range(1, 3) as $number) {
            $payload = $this->payload(); $payload['invoice_number'] = 'SOR-TEST-' . $number;
            $this->postJson('/_test/sortir-save', $payload)->assertOk();
        }
        DB::table('sortir_transaction')->where('id', 3)->update(['branch_id' => 2]);
        $this->getJson('/_test/sortir-data?mobile=1&search=Anggur&length=1&start=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 2])
            ->assertJsonPath('data.0.id', 1)->assertJsonPath('data.0.branch_name', 'GKB')
            ->assertJsonPath('data.0.can_edit', true)->assertJsonPath('data.0.can_delete', true)
            ->assertJsonPath('data.0.items.0.name', 'Anggur');
        $this->getJson('/_test/sortir-data?mobile=1&status_filter=paid')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/_test/sortir-data?mobile=1&start_date=2026-10-24&end_date=2026-10-25')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/_test/sortir-data?mobile=1&cabang_filter=2')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_create_pages_with_the_same_preview_never_overwrite_each_other(): void
    {
        $first = $this->postJson('/_test/sortir-save', $this->payload())->assertOk()->json();
        $second = $this->postJson('/_test/sortir-save', $this->payload())->assertOk()->json();
        $this->assertNotSame($first['transaksi_id'], $second['transaksi_id']);
        $this->assertNotSame($first['invoice_number'], $second['invoice_number']);
        $this->assertDatabaseCount('sortir_transaction', 2);
        $this->assertDatabaseCount('sortir_transaction_detail', 2);
        $payload = $this->payload(); $payload['sortir_id'] = $first['transaksi_id'];
        $payload['items'][0]['qty'] = 3;
        $this->postJson('/_test/sortir-save', $payload)->assertOk()
            ->assertJson(['transaksi_id' => $first['transaksi_id'], 'invoice_number' => $first['invoice_number']]);
        $this->assertDatabaseHas('sortir_transaction_detail', ['sortir_id' => $first['transaksi_id'], 'quantity' => 3]);
        $this->assertDatabaseHas('sortir_transaction_detail', ['sortir_id' => $second['transaksi_id'], 'quantity' => 2.5]);
    }

    public function test_draft_delete_removes_header_and_items(): void
    {
        $this->postJson('/_test/sortir-save', $this->payload())->assertOk();
        $id = DB::table('sortir_transaction')->value('id');
        $this->deleteJson('/_test/sortir/' . $id)->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('sortir_transaction', 0);
        $this->assertDatabaseCount('sortir_transaction_detail', 0);
    }
}
