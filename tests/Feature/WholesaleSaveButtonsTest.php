<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Transaction\Http\Controllers\WholesaleController;
use Tests\TestCase;

class WholesaleSaveButtonsTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // A dedicated disposable database: never migrate or modify the working database.
        $this->testDatabase = tempnam(sys_get_temp_dir(), 'wholesale-buttons-');
        config(['database.default' => 'wholesale_buttons_test', 'database.connections.wholesale_buttons_test' => [
            'driver' => 'sqlite', 'database' => $this->testDatabase, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        Schema::create('role', function (Blueprint $t) { $t->integer('id_role')->primary(); });
        Schema::create('role_user', function (Blueprint $t) { $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role'); $t->timestamps(); });
        Schema::create('role_menu', function (Blueprint $t) { $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission'); });
        Schema::create('branch', function (Blueprint $t) { $t->increments('id'); });
        Schema::create('user_branch', function (Blueprint $t) { $t->integer('user_id'); $t->integer('branch_id'); });
        Schema::create('wholesale', function (Blueprint $t) {
            $t->increments('id'); $t->string('order_number')->unique(); $t->integer('branch_id')->nullable();
            $t->date('order_date'); $t->string('status'); $t->integer('created_by'); $t->timestamps();
        });
        Schema::create('wholesale_product', function (Blueprint $t) {
            $t->increments('id'); $t->integer('wholesale_id'); $t->integer('product_id');
            $t->decimal('price', 15, 2); $t->decimal('quantity', 15, 2); $t->decimal('total_price', 15, 2);
            $t->integer('created_by'); $t->timestamps();
        });
        Schema::create('product_branch', function (Blueprint $t) {
            $t->increments('id'); $t->integer('product_id'); $t->integer('branch_id'); $t->decimal('price', 15, 2); $t->timestamps();
        });
        Schema::create('product_hpp', function (Blueprint $t) { $t->increments('id'); $t->integer('product_id'); $t->timestamps(); });
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'wholesale.save-transaction']);
        DB::table('branch')->insert([['id' => 1], ['id' => 2]]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        $user = new User(); $user->id_user = 7;
        $this->actingAs($user);
        Route::post('/_test/wholesale-save', [WholesaleController::class, 'saveTransaction']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('wholesale_buttons_test');
        if (isset($this->testDatabase)) unlink($this->testDatabase);
        parent::tearDown();
    }

    private function payload(string $status): array
    {
        return [
            'branch_id' => 1, 'date' => '2026-10-23', 'invoice_number' => 'BUTTON-TEST',
            'status' => $status, 'subtotal' => 25000, 'total' => 25000,
            'items' => [['id' => 11, 'qty' => 2.5, 'price' => 10000, 'sell' => 12500, 'total_input' => 25000]],
        ];
    }

    public function test_draft_and_final_buttons_persist_their_respective_status_and_items(): void
    {
        foreach (['draft', 'posting'] as $status) {
            $this->postJson('/_test/wholesale-save', $this->payload($status))->assertOk()->assertJson(['success' => true, 'status' => $status]);
            $this->assertDatabaseHas('wholesale', ['order_number' => 'BUTTON-TEST', 'status' => $status]);
            $this->assertDatabaseCount('wholesale', 1);
            $this->assertDatabaseCount('wholesale_product', 1);
            $this->assertDatabaseHas('wholesale_product', ['product_id' => 11, 'quantity' => 2.5, 'total_price' => 25000]);
            $this->assertDatabaseHas('product_branch', ['branch_id' => 1, 'product_id' => 11, 'price' => 12500]);
        }
    }

    public function test_deleting_last_item_can_clear_temp_but_cannot_save_empty_draft_or_final(): void
    {
        $this->postJson('/_test/wholesale-save', $this->payload('temp'))->assertOk();
        $payload = $this->payload('temp'); $payload['items'] = []; $payload['total'] = $payload['subtotal'] = 0;
        $this->postJson('/_test/wholesale-save', $payload)->assertOk();
        $this->assertDatabaseCount('wholesale_product', 0);
        foreach (['draft', 'posting'] as $status) {
            $payload['status'] = $status;
            $this->postJson('/_test/wholesale-save', $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
        }
    }

    public function test_unassigned_branch_and_completed_order_are_rejected(): void
    {
        $payload = $this->payload('draft'); $payload['branch_id'] = 2;
        $this->postJson('/_test/wholesale-save', $payload)->assertForbidden();
        $this->postJson('/_test/wholesale-save', $this->payload('draft'))->assertOk();
        DB::table('wholesale')->update(['status' => 'complete']);
        $this->postJson('/_test/wholesale-save', $this->payload('posting'))->assertStatus(409);
        $this->assertDatabaseHas('wholesale', ['status' => 'complete']);
    }
}
