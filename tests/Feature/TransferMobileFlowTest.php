<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Transaction\Http\Controllers\TransferController;
use Tests\TestCase;

class TransferMobileFlowTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // This fixture uses a disposable database, never the working inventory database.
        $this->testDatabase = tempnam(sys_get_temp_dir(), 'transfer-mobile-');
        config(['database.default' => 'transfer_mobile_test', 'database.connections.transfer_mobile_test' => [
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
        Schema::create('transfer', function (Blueprint $t) {
            $t->increments('id'); $t->string('uuid'); $t->string('invoice_number')->nullable(); $t->integer('branch_id')->nullable();
            $t->integer('branch_destination_id')->nullable(); $t->date('date'); $t->decimal('total', 15, 2); $t->string('status');
            $t->integer('created_by'); $t->integer('updated_by')->nullable(); $t->integer('pic_penerima')->nullable(); $t->dateTime('tanggal_diterima')->nullable(); $t->timestamps();
        });
        Schema::create('transfer_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('transfer_id'); $t->integer('product_id'); $t->decimal('quantity', 10, 2); $t->decimal('sent_quantity', 10, 2)->nullable();
            $t->decimal('price', 15, 2); $t->decimal('discount', 15, 2); $t->decimal('subtotal', 15, 2); $t->integer('created_by'); $t->timestamps();
        });
        Schema::create('transfer_detail_correction', function (Blueprint $t) {
            $t->increments('id'); $t->integer('transfer_detail_id'); $t->decimal('old_quantity', 15, 2); $t->decimal('new_quantity', 15, 2);
            $t->string('note')->nullable(); $t->integer('created_by'); $t->timestamp('created_at')->nullable();
        });
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        foreach (['transfer-pengirim.index', 'transfer-pengirim.create', 'transfer-pengirim.edit', 'transfer-pengirim.show', 'transfer-pengirim.destroy', 'transfer-penerima.index', 'transfer-penerima.show', 'transfer-penerima.set_selesai', 'transfer.save-transaction', 'transfer.save-correction'] as $permission) {
            DB::table('role_menu')->insert(['id_role' => 2, 'permission' => $permission]);
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
        Route::post('/_test/transfer-save', [TransferController::class, 'saveTransaction']);
        Route::get('/_test/transfer-data', [TransferController::class, 'get_data']);
        Route::get('/_test/transfer-products', [TransferController::class, 'products']);
        Route::post('/_test/transfer-receive/{id}', [TransferController::class, 'set_selesai']);

    }

    protected function tearDown(): void
    {
        DB::disconnect('transfer_mobile_test');
        if (isset($this->testDatabase)) unlink($this->testDatabase);
        parent::tearDown();
    }

    private function payload(string $status = 'draft'): array
    {
        return ['branch_id' => 1, 'branch_destination_id' => 2, 'date' => '2026-10-24', 'status' => $status,
            'subtotal' => 12500, 'total' => 12500, 'type' => 'transfer-pengirim',
            'items' => [['id' => 11, 'qty' => 2.5, 'price' => 5000, 'discount' => 0, 'total_input' => 12500]]];
    }

    private function send(): int
    {
        $response = $this->postJson('/_test/transfer-save', $this->payload('pending'))->assertOk()->assertJson(['success' => true]);
        return $response->json('transaksi_id');
    }

    private function receivingBranch(): void
    {
        DB::table('user_branch')->delete();
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 2]);
    }

    private function receipt(float $quantity = 2.5): array
    {
        return ['items' => [['detail_id' => DB::table('transfer_detail')->value('id'), 'quantity' => $quantity, 'expected_quantity' => 2.5, 'note' => $quantity < 2.5 ? 'Selisih fisik' : '']]];
    }

    public function test_draft_then_send_keeps_header_identity_and_computed_values(): void
    {
        $first = $this->postJson('/_test/transfer-save', $this->payload())->assertOk()->json('transaksi_id');
        $payload = $this->payload('pending'); $payload['transfer_id'] = $first;
        $this->postJson('/_test/transfer-save', $payload)->assertOk()->assertJson(['transaksi_id' => $first]);
        $this->assertDatabaseCount('transfer', 1);
        $this->assertDatabaseCount('transfer_detail', 1);
        $this->assertDatabaseHas('transfer', ['status' => 'pending', 'branch_id' => 1, 'branch_destination_id' => 2, 'total' => 12500]);
    }

    public function test_send_rejects_same_branch_unauthorized_source_bad_totals_duplicates_and_insufficient_stock(): void
    {
        $payload = $this->payload(); $payload['branch_destination_id'] = 1;
        $this->postJson('/_test/transfer-save', $payload)->assertUnprocessable();
        $payload = $this->payload(); $payload['branch_id'] = 2;
        $this->postJson('/_test/transfer-save', $payload)->assertForbidden();
        $payload = $this->payload(); $payload['items'][0]['total_input'] = 1; $payload['total'] = $payload['subtotal'] = 1;
        $this->postJson('/_test/transfer-save', $payload)->assertUnprocessable();
        $payload = $this->payload(); $payload['items'][] = $payload['items'][0];
        $this->postJson('/_test/transfer-save', $payload)->assertUnprocessable();
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 1]);
        $this->postJson('/_test/transfer-save', $this->payload('pending'))->assertUnprocessable();
        $this->assertDatabaseCount('transfer', 0);
    }

    public function test_exact_receipt_records_receiver_and_cannot_be_finalized_twice(): void
    {
        $id = $this->send(); $this->receivingBranch();
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt())->assertOk();
        $this->assertDatabaseHas('transfer', ['id' => $id, 'status' => 'selesai', 'pic_penerima' => 7, 'total' => 12500]);
        $this->assertDatabaseCount('transfer_detail_correction', 0);
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt())->assertUnprocessable();
    }

    public function test_shortage_and_zero_receipt_are_saved_atomically_with_correction_history(): void
    {
        $id = $this->send(); $this->receivingBranch();
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt(0))->assertOk();
        $this->assertDatabaseHas('transfer_detail', ['quantity' => 0, 'subtotal' => 0]);
        $this->assertDatabaseHas('transfer_detail_correction', ['old_quantity' => 2.5, 'new_quantity' => 0, 'note' => 'Selisih fisik', 'created_by' => 7]);
        $this->assertDatabaseHas('transfer', ['status' => 'selesai', 'total' => 0]);
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-penerima')->assertOk()
            ->assertJsonPath('data.0.items.0.sent', 2.5)->assertJsonPath('data.0.items.0.quantity', 0)
            ->assertJsonPath('data.0.items.0.note', 'Selisih fisik');
    }

    public function test_receipt_rejects_wrong_branch_extra_quantity_foreign_details_and_stale_edits(): void
    {
        $id = $this->send();
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt())->assertNotFound();
        $this->receivingBranch();
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt(3))->assertUnprocessable();
        $payload = $this->receipt(); $payload['items'][0]['detail_id'] = 999;
        $this->postJson('/_test/transfer-receive/' . $id, $payload)->assertUnprocessable();
        $payload = $this->receipt(); $payload['items'][0]['expected_quantity'] = 2;
        $this->postJson('/_test/transfer-receive/' . $id, $payload)->assertUnprocessable();
        $this->assertDatabaseCount('transfer_detail_correction', 0);
        $this->assertDatabaseHas('transfer', ['status' => 'pending']);
    }

    public function test_sent_quantity_is_recorded_only_when_the_transfer_is_sent(): void
    {
        $first = $this->postJson('/_test/transfer-save', $this->payload())->assertOk()->json('transaksi_id');
        $this->assertDatabaseHas('transfer_detail', ['transfer_id' => $first, 'sent_quantity' => null]);
        $payload = $this->payload('pending'); $payload['transfer_id'] = $first;
        $this->postJson('/_test/transfer-save', $payload)->assertOk();
        $this->assertDatabaseHas('transfer_detail', ['transfer_id' => $first, 'quantity' => 2.5, 'sent_quantity' => 2.5]);
    }

    public function test_sent_transfer_is_received_without_origin_stock_check(): void
    {
        // Its items already left the origin stock when it was sent.
        $id = $this->send(); $this->receivingBranch();
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 0]);
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt(2))->assertOk();
        $this->assertDatabaseHas('transfer_detail', ['quantity' => 2, 'sent_quantity' => 2.5]);
        $this->assertDatabaseHas('transfer', ['id' => $id, 'status' => 'selesai']);
    }

    public function test_editing_a_sent_transfer_can_reuse_its_own_quantity(): void
    {
        $id = $this->send();
        // The view would now show 10 - 2.5 left at the origin.
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 7.5]);
        $payload = $this->payload('pending'); $payload['transfer_id'] = $id;
        $payload['items'][0]['qty'] = 10; $payload['items'][0]['total_input'] = $payload['subtotal'] = $payload['total'] = 50000;
        $this->postJson('/_test/transfer-save', $payload)->assertOk();
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 0]);
        $payload['items'][0]['qty'] = 10.5; $payload['items'][0]['total_input'] = $payload['subtotal'] = $payload['total'] = 52500;
        $this->postJson('/_test/transfer-save', $payload)->assertUnprocessable();
    }

    public function test_failed_final_stock_check_rolls_back_corrections_and_status_together(): void
    {
        // Transfers sent before sent_quantity existed still check origin stock on receipt.
        $id = $this->send(); $this->receivingBranch();
        DB::table('transfer_detail')->update(['sent_quantity' => null]);
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 1]);
        $this->postJson('/_test/transfer-receive/' . $id, $this->receipt(2))->assertUnprocessable();
        $this->assertDatabaseHas('transfer', ['status' => 'pending', 'total' => 12500]);
        $this->assertDatabaseHas('transfer_detail', ['quantity' => 2.5, 'subtotal' => 12500]);
        $this->assertDatabaseCount('transfer_detail_correction', 0);
    }

    public function test_mobile_lists_scope_the_two_sides_search_products_and_paginate(): void
    {
        $id = $this->send();
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-pengirim&search=Anggur&length=1')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.destination', 'GBA')->assertJsonPath('data.0.can_edit', true);
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-penerima')->assertOk()->assertJsonCount(0, 'data');
        $this->receivingBranch();
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-penerima&search=GKB')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.can_receive', true)->assertJsonPath('data.0.can_edit', false);
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-pengirim')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-penerima&start=1')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-penerima&status_filter=selesai')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/_test/transfer-data?mobile=1&url=unknown')->assertForbidden();
    }

    public function test_product_picker_uses_source_stock_and_hpp_and_requires_branch_access(): void
    {
        $this->getJson('/_test/transfer-products?branch_id=1&term=Anggur')->assertOk()->assertJsonCount(1)
            ->assertJsonFragment(['id' => 11, 'unit' => 'Kg', 'stock' => 10, 'price' => 5000]);
        $this->getJson('/_test/transfer-products?branch_id=2')->assertForbidden();
        DB::table('product_stock')->where('branch_id', 1)->update(['stock_available' => 0]);
        $this->getJson('/_test/transfer-products?branch_id=1')->assertOk()->assertJsonCount(0);
    }

    public function test_record_view_is_scoped_to_the_requested_side_and_dates_allow_open_ranges(): void
    {
        $id = $this->send();
        $this->getJson('/transfer-penerima/show/' . $id)->assertNotFound();
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-pengirim&end_date=2026-10-24')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-pengirim&start_date=2026-10-25')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-pengirim&start_date=2026-10-25&end_date=2026-10-24')->assertUnprocessable();
        $this->receivingBranch();
        $this->getJson('/transfer-pengirim/show/' . $id)->assertNotFound();
    }

    public function test_receiver_does_not_see_unsent_drafts_or_mutation_without_permission(): void
    {
        $this->postJson('/_test/transfer-save', $this->payload())->assertOk(); $this->receivingBranch();
        $this->getJson('/_test/transfer-data?mobile=1&url=transfer-penerima')->assertOk()->assertJsonCount(0, 'data');
        DB::table('role_menu')->where('permission', 'transfer-penerima.set_selesai')->delete();
        $this->postJson('/_test/transfer-receive/1', $this->receipt())->assertForbidden();
    }
}
