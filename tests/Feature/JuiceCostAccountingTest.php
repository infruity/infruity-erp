<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Pos\Entities\PosDetailModel;
use Tests\TestCase;

/** HTTP/controller accounting tests, not verification of MySQL stock views or DECIMAL coercion. */
class JuiceCostAccountingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue($this->app->environment('testing'));
        $this->assertFalse($this->app->configurationIsCached());
        config(['database.default' => 'juice_cost_test', 'database.connections.juice_cost_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'cache.default' => 'array', 'session.driver' => 'array', 'mail.default' => 'array', 'queue.default' => 'sync']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 10:00:00'));

        Schema::create('users', function (Blueprint $t) { $t->integer('id_user')->primary(); $t->string('nm_user'); });
        Schema::create('role', fn (Blueprint $t) => $t->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $t) { $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role'); $t->timestamps(); });
        Schema::create('role_menu', function (Blueprint $t) { $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $t) { $t->increments('id'); $t->integer('id_user'); $t->string('permission'); $t->boolean('is_allowed'); });
        Schema::create('user_branch', function (Blueprint $t) { $t->integer('user_id'); $t->integer('branch_id'); });
        Schema::create('branch', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('name'); });
        Schema::create('customer', fn (Blueprint $t) => $t->integer('id')->primary());
        Schema::create('products', function (Blueprint $t) {
            $t->integer('id')->primary(); $t->string('name'); $t->integer('product_unit')->default(1);
            $t->decimal('hpp', 15, 2)->default(0); $t->timestamps();
        });
        Schema::create('receipt', function (Blueprint $t) {
            $t->increments('id'); $t->integer('product_id'); $t->string('code'); $t->timestamps();
        });
        Schema::create('product_receipt', function (Blueprint $t) {
            $t->increments('id'); $t->integer('receipt_id'); $t->integer('product_id');
            $t->integer('product_receipt_id'); $t->decimal('quantity', 10, 2); $t->timestamps();
        });
        Schema::create('product_units', function (Blueprint $t) { $t->integer('id')->primary(); $t->string('abbreviation'); });
        Schema::create('product_child', function (Blueprint $t) { $t->increments('id'); $t->integer('product_id'); $t->integer('parent_id'); $t->timestamps(); });
        // Disposable stand-ins for the read-only reporting views; no live inventory writes.
        Schema::create('product_stock', function (Blueprint $t) {
            $t->integer('id'); $t->integer('branch_id'); $t->decimal('stock_available', 15, 3); $t->unique(['id', 'branch_id']);
        });
        Schema::create('product_hpp', function (Blueprint $t) {
            $t->increments('id'); $t->integer('product_id'); $t->integer('trx_id');
            $t->decimal('hpp_berjalan', 15, 6); $t->decimal('qty_berjalan', 15, 3); $t->timestamps();
        });
        Schema::create('setting_exp', function (Blueprint $t) { $t->increments('id'); $t->decimal('value_exp', 10, 2); });
        // Only the tracked baseline migration: never migrate the application or payment submissions.
        (require database_path('migrations/2025_06_09_083356_create_pos_transaction_table.php'))->up();
        Schema::table('pos_transaction', fn (Blueprint $t) => $t->string('courier_type')->nullable());
        Schema::table('pos_transaction_detail', fn (Blueprint $t) => $t->decimal('diskon_global', 15, 2)->nullable());
        // Baseline enum omits jus although the controller already writes it. Model the juice-capable
        // deployment explicitly; repairing historical migration/schema drift is outside A13.
        Schema::table('pos_transaction_detail', fn (Blueprint $t) => $t->dropColumn('type'));
        Schema::table('pos_transaction_detail', fn (Blueprint $t) => $t->enum('type', ['product', 'parcel', 'jus'])->default('product'));
        Schema::create('production', function (Blueprint $t) {
            $t->increments('id'); $t->string('production_number'); $t->integer('pos_id'); $t->integer('product_id');
            $t->decimal('quantity', 15, 3); $t->dateTime('production_date'); $t->string('status');
            $t->integer('created_by'); $t->integer('staff_id'); $t->integer('branch_id'); $t->timestamps();
        });
        Schema::create('production_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('production_id'); $t->integer('product_id'); $t->decimal('quantity', 15, 3); $t->timestamps();
        });
        Schema::create('production_parcel_detail', function (Blueprint $t) { $t->increments('id'); $t->integer('pos_id'); });
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Juice accounting fixture']);
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert([
            ['id_role' => 2, 'permission' => 'pos.receipt'],
            ['id_role' => 2, 'permission' => 'report.profit.revenue'],
        ]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('branch')->insert(['id' => 1, 'name' => 'Fixture branch']);
        DB::table('product_units')->insert(['id' => 1, 'abbreviation' => 'unit']);
        DB::table('setting_exp')->insert(['value_exp' => 0.1]);
        $this->assertFalse(Schema::hasTable('pos_payment_submissions'));
        $this->actingAs(User::findOrFail(7));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('juice_cost_test');
        parent::tearDown();
    }

    private function product(int $id, float $stock, float $hpp): void
    {
        DB::table('products')->insert(['id' => $id, 'name' => 'Fixture product '.$id, 'hpp' => $hpp]);
        DB::table('product_stock')->insert(['id' => $id, 'branch_id' => 1, 'stock_available' => $stock]);
        DB::table('product_hpp')->insert(['product_id' => $id, 'trx_id' => $id, 'hpp_berjalan' => $hpp,
            'qty_berjalan' => $stock, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function juice(float $quantity, array $ingredients = [], array $ingredientQuantities = [], int $productId = 30): array
    {
        return ['productId' => $productId, 'qty' => $quantity, 'price' => 100, 'discount' => 0, 'hpp' => 999999,
            'product_receipt_id' => $ingredients, 'product_receipt_qty' => $ingredientQuantities];
    }

    private function variant(int $id, int $parentId, float $stock, float $staleHpp): void
    {
        DB::table('products')->insert(['id' => $id, 'name' => 'Fixture variant '.$id, 'hpp' => $staleHpp]);
        DB::table('product_child')->insert(['product_id' => $id, 'parent_id' => $parentId]);
        DB::table('product_stock')->insert(['id' => $id, 'branch_id' => 1, 'stock_available' => $stock]);
    }

    private function configureRecipe(int $productId, array $ingredients): void
    {
        $receiptId = DB::table('receipt')->insertGetId([
            'product_id' => $productId, 'code' => 'RCP-FIXTURE-'.$productId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($ingredients as [$ingredientId, $quantity]) {
            DB::table('product_receipt')->insert([
                'receipt_id' => $receiptId, 'product_id' => $productId,
                'product_receipt_id' => $ingredientId, 'quantity' => $quantity,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function payload(array $juices, array $products = []): array
    {
        // Real UI includes string-ID juice placeholders in items; the numeric product loop skips them.
        $items = $products;
        foreach ($juices as $index => $juice) {
            $items[] = ['id' => 'jus-'.$index, 'price' => $juice['price'], 'qty' => $juice['qty'], 'discount' => $juice['discount']];
        }
        $subtotal = collect($items)->sum(fn ($item) => $item['price'] * $item['qty'] - $item['discount']);
        return ['customer_id' => null, 'date' => '2026-10-29', 'invoice_number' => 'INV-HPP-FIXTURE',
            'items' => $items, 'jus' => $juices, 'subtotal' => $subtotal, 'discount' => 10,
            'ongkir' => 0, 'discount_ongkir' => 0, 'total' => $subtotal * 0.9, 'status' => 'draft', 'branch_id' => 1];
    }

    private function save(array $payload): int
    {
        $response = $this->postJson('/pos/save-transaction', $payload);
        $this->assertSame(200, $response->status(), $response->getContent());
        $response->assertJsonPath('success', true);
        return (int) $response->json('transaksi_id');
    }

    public function test_new_production_juice_cost_includes_every_ingredient_and_fractional_quantity(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);
        $this->product(11, 10, 4);
        $this->configureRecipe(30, [[10, 0.5], [11, 0.25]]);
        $id = $this->save($this->payload([$this->juice(2.5, [10, 11], [0.5, 0.25])]));
        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertEqualsWithDelta(4.34, (float) $detail->hpp, 0.000001);
        $this->assertEqualsWithDelta(10.85, (float) $detail->subtotal_hpp, 0.000001);
        $this->assertDatabaseHas('production', ['pos_id' => $id, 'quantity' => 2.5, 'status' => 'draft']);
        $this->assertSame(1, DB::table('production')->count());
        $this->assertDatabaseHas('production_detail', ['product_id' => 10, 'quantity' => 1.25]);
        $this->assertDatabaseHas('production_detail', ['product_id' => 11, 'quantity' => 0.625]);
        $this->assertSame(2, DB::table('production_detail')->count());
    }

    public function test_mixed_source_juice_cost_weights_ready_stock_and_new_production(): void
    {
        $this->product(30, 0.75, 12.34);
        $this->product(10, 10, 6.68);
        $this->product(11, 10, 4);
        $this->configureRecipe(30, [[10, 0.5], [11, 0.25]]);
        $id = $this->save($this->payload([$this->juice(2.5, [10, 11], [0.5, 0.25])]));
        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertEqualsWithDelta(6.74, (float) $detail->hpp, 0.000001);
        $this->assertEqualsWithDelta(16.85, (float) $detail->subtotal_hpp, 0.000001);
        $this->assertDatabaseHas('production', ['pos_id' => $id, 'quantity' => 1.75]);
        $this->assertDatabaseHas('production_detail', ['product_id' => 10, 'quantity' => 0.875]);
        $this->assertDatabaseHas('production_detail', ['product_id' => 11, 'quantity' => 0.4375]);
    }

    public function test_repeated_juice_rows_do_not_reuse_ready_stock_for_costing(): void
    {
        $this->product(30, 0.5, 12.34);
        $this->product(10, 10, 6.68);
        $this->configureRecipe(30, [[10, 0.5]]);
        $id = $this->save($this->payload([
            $this->juice(0.75, [10], [0.5]), $this->juice(1.25, [10], [0.5]),
        ]));
        $details = PosDetailModel::where('pos_id', $id)->orderBy('id')->get();
        $this->assertCount(2, $details);
        $this->assertEqualsWithDelta(9.34, (float) $details[0]->hpp, 0.000001);
        $this->assertEqualsWithDelta(7.005, (float) $details[0]->subtotal_hpp, 0.000001);
        $this->assertEqualsWithDelta(3.34, (float) $details[1]->hpp, 0.000001);
        $this->assertEqualsWithDelta(4.175, (float) $details[1]->subtotal_hpp, 0.000001);
        $this->assertEqualsWithDelta(11.18, (float) $details->sum('subtotal_hpp'), 0.000001);
        $this->assertSame(2, DB::table('production')->count());
        $this->assertEqualsWithDelta(1.5, (float) DB::table('production')->sum('quantity'), 0.000001);
    }

    public function test_fractional_juice_uses_the_same_unrounded_multiplication_as_nonjuice(): void
    {
        $this->product(30, 10, 12.34);
        $this->product(20, 10, 12.34);
        $id = $this->save($this->payload([$this->juice(0.37)], [
            ['id' => 20, 'qty' => 0.37, 'price' => 100, 'discount' => 0],
        ]));
        $details = PosDetailModel::where('pos_id', $id)->get()->keyBy('type');
        $this->assertCount(2, $details);
        // Like the existing product path, multiply at full precision and leave scale to DECIMAL.
        // SQLite retains 4.5658; deployed DECIMAL(15,3) storage coercion is not tested here.
        $this->assertEqualsWithDelta(4.5658, (float) $details['product']->subtotal_hpp, 0.000001);
        $this->assertEqualsWithDelta((float) $details['product']->subtotal_hpp, (float) $details['jus']->subtotal_hpp, 0.000001);
        $this->assertEqualsWithDelta((float) $details['product']->hpp, (float) $details['jus']->hpp, 0.000001);
        $this->assertEqualsWithDelta(9.1316, (float) $details->sum('subtotal_hpp'), 0.000001);
    }

    public function test_mixed_unit_cost_is_not_rounded_before_computing_subtotal_cost(): void
    {
        $this->product(30, 0.25, 10.01);
        $this->product(10, 10, 6.68);
        $this->configureRecipe(30, [[10, 0.5]]);
        $id = $this->save($this->payload([$this->juice(1.5, [10], [0.5])]));
        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertEqualsWithDelta(4.451666666667, (float) $detail->hpp, 0.000001);
        $this->assertEqualsWithDelta(6.6775, (float) $detail->subtotal_hpp, 0.000001);
        // Prematurely rounding unit HPP to the unit column's 2 decimals would produce 6.675.
        $this->assertGreaterThan(6.675, (float) $detail->subtotal_hpp);
    }

    public function test_desktop_and_mobile_profit_consume_all_saved_juice_costs_and_nonjuice_costs(): void
    {
        $this->product(30, 10, 12.34);
        $this->product(10, 10, 6.68);
        $this->product(20, 10, 8.76);
        $first = $this->payload([$this->juice(1)], [['id' => 20, 'qty' => 0.5, 'price' => 20, 'discount' => 0]]);
        $id1 = $this->save($first);
        DB::table('product_stock')->where('id', 30)->update(['stock_available' => 0]);
        $this->configureRecipe(30, [[10, 0.5]]);
        $second = $this->payload([$this->juice(2, [10], [0.5])]);
        $second['invoice_number'] = 'INV-HPP-PRODUCED';
        $id2 = $this->save($second);
        DB::table('product_stock')->where('id', 30)->update(['stock_available' => 0.5]);
        $third = $this->payload([$this->juice(2, [10], [0.5])]);
        $third['invoice_number'] = 'INV-HPP-MIXED';
        $id3 = $this->save($third);
        // Report status is fixture setup, not payment completion or a simulated payment ledger.
        DB::table('pos_transaction')->whereIn('id', [$id1, $id2, $id3])->update(['status' => 'paid']);
        $this->assertSame(0, DB::table('pos_payment')->count());
        $this->assertEqualsWithDelta(34.58, (float) PosDetailModel::sum('subtotal_hpp'), 0.000001);
        $query = '?'.http_build_query(['start_date' => '2026-10-29', 'end_date' => '2026-10-29',
            'branch_id' => 1, 'start' => 0, 'length' => 50]);
        $desktop = $this->getJson('/report-profit-revenue/data'.$query)->assertOk();
        $mobile = $this->getJson('/reports/mobile/profit/dates'.$query)->assertOk();
        $this->assertCount(2, $desktop->json('data'));
        $this->assertCount(1, $mobile->json('data'));
        foreach (['grand_total_pendapatan' => 'Rp 459', 'grand_total_hpp' => '- Rp 35', 'grand_total_laba' => 'Rp 424'] as $key => $expected) {
            $desktop->assertJsonPath($key, $expected);
            $mobile->assertJsonPath($key, $expected);
        }
        $rows = collect($desktop->json('data'))->keyBy('product_id');
        $this->assertEqualsWithDelta(30.2, (float) $rows[30]['total_hpp'], 0.000001);
        $this->assertEqualsWithDelta(419.8, (float) $rows[30]['laba_kotor'], 0.000001);
        $this->assertEqualsWithDelta(34.58, (float) $mobile->json('data.0.hpp'), 0.000001);
        $this->assertEqualsWithDelta(459, (float) $mobile->json('data.0.revenue'), 0.000001);
        $this->assertEqualsWithDelta(424.42, (float) $mobile->json('data.0.amount'), 0.000001);
    }

    public function test_late_juice_insert_failure_rolls_back_new_invoice_production_and_previous_line_cost(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);
        $this->product(20, 10, 8.76);
        $this->configureRecipe(30, [[10, 0.5]]);
        DB::unprepared("CREATE TRIGGER fail_juice_cost BEFORE INSERT ON pos_transaction_detail WHEN NEW.type = 'jus' BEGIN SELECT RAISE(ABORT, 'fixture juice cost insert failed'); END");
        $payload = $this->payload([$this->juice(2, [10], [0.5])], [
            ['id' => 20, 'qty' => 0.5, 'price' => 20, 'discount' => 0],
        ]);
        $failure = $this->postJson('/pos/save-transaction', $payload)->assertStatus(500)->assertJsonPath('success', false);
        $this->assertStringContainsString('fixture juice cost insert failed', $failure->json('message'));
        foreach (['pos_transaction', 'pos_transaction_detail', 'production', 'production_detail', 'pos_payment'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' must roll back');
        }
        $this->assertSame(0, DB::transactionLevel());
        DB::unprepared('DROP TRIGGER fail_juice_cost');
        $id = $this->save($payload);
        $this->assertSame(2, PosDetailModel::where('pos_id', $id)->count());
        $this->assertEqualsWithDelta(11.06, (float) PosDetailModel::where('pos_id', $id)->sum('subtotal_hpp'), 0.000001);
    }

    public function test_late_failure_restores_existing_draft_header_lines_costs_and_production(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);
        $this->configureRecipe(30, [[10, 0.5]]);
        $id = $this->save($this->payload([$this->juice(2, [10], [0.5])]));
        $tables = ['pos_transaction', 'pos_transaction_detail', 'production', 'production_detail'];
        $before = [];
        foreach ($tables as $table) $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        DB::unprepared("CREATE TRIGGER fail_juice_edit BEFORE INSERT ON pos_transaction_detail WHEN NEW.type = 'jus' BEGIN SELECT RAISE(ABORT, 'fixture juice edit failed'); END");
        $failure = $this->postJson('/pos/save-transaction', $this->payload([$this->juice(3, [10], [0.5])]))
            ->assertStatus(500)->assertJsonPath('success', false);
        $this->assertStringContainsString('fixture juice edit failed', $failure->json('message'));
        foreach ($tables as $table) $this->assertSame($before[$table], DB::table($table)->orderBy('id')->get()->toJson(), $table);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(0, DB::table('pos_payment')->count());
        $this->assertEqualsWithDelta(6.68, (float) PosDetailModel::where('pos_id', $id)->sum('subtotal_hpp'), 0.000001);
    }

    public function test_saving_new_juice_does_not_backfill_historical_zero_cost_lines(): void
    {
        $this->product(30, 10, 12.34);
        DB::table('pos_transaction')->insert(['id' => 100, 'uuid' => 'historical-fixture', 'invoice_number' => 'INV-HISTORICAL',
            'date' => '2025-01-01', 'status' => 'paid', 'branch_id' => 1, 'created_by' => 7]);
        DB::table('pos_transaction_detail')->insert(['id' => 100, 'pos_id' => 100, 'product_id' => 30, 'type' => 'jus',
            'quantity' => 3, 'price' => 100, 'discount' => 0, 'subtotal' => 300, 'hpp' => 9, 'exp' => 91, 'exp_value' => 9.1]);
        $before = (array) DB::table('pos_transaction_detail')->where('id', 100)->first();
        $id = $this->save($this->payload([$this->juice(2)]));
        $this->assertSame($before, (array) DB::table('pos_transaction_detail')->where('id', 100)->first());
        $this->assertEqualsWithDelta(0, (float) $before['subtotal_hpp'], 0.000001);
        $this->assertEqualsWithDelta(24.68, (float) PosDetailModel::where('pos_id', $id)->sum('subtotal_hpp'), 0.000001);
    }

    public function test_ready_stock_juice_persists_line_cost_used_by_profit_aggregation(): void
    {
        $this->product(30, 10, 12.34);
        $id = $this->save($this->payload([$this->juice(2)]));
        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertSame('jus', $detail->type);
        $this->assertEqualsWithDelta(12.34, (float) $detail->hpp, 0.000001);
        $this->assertEqualsWithDelta(24.68, (float) $detail->subtotal_hpp, 0.000001);
        $this->assertSame(0, DB::table('production')->count());
        $this->assertSame(0, DB::table('pos_payment')->count());
        $this->assertSame('draft', DB::table('pos_transaction')->where('id', $id)->value('status'));
        $this->assertEqualsWithDelta(24.68, (float) PosDetailModel::where('pos_id', $id)->sum('subtotal_hpp'), 0.000001);
    }

    public function test_production_juice_without_recipe_is_rejected_without_partial_writes(): void
    {
        $this->product(30, 0, 12.34);
        $this->postJson('/pos/save-transaction', $this->payload([$this->juice(2)]))
            ->assertUnprocessable()->assertJsonValidationErrors('jus');

        foreach (['pos_transaction', 'pos_transaction_detail', 'production', 'production_detail'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' must remain unchanged');
        }
    }

    public function test_juice_hpp_uses_the_persisted_recipe_not_client_submitted_ingredients(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);
        $this->product(11, 10, 4);
        $this->configureRecipe(30, [[10, 0.5]]);

        $id = $this->save($this->payload([$this->juice(2, [11], [0.01])]));

        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertEqualsWithDelta(3.34, (float) $detail->hpp, 0.000001);
        $this->assertDatabaseHas('production_detail', ['product_id' => 10, 'quantity' => 1]);
        $this->assertDatabaseMissing('production_detail', ['product_id' => 11]);
    }

    public function test_ready_stock_juice_variant_uses_parent_moving_hpp(): void
    {
        $this->product(30, 2, 12.34);
        $this->variant(31, 30, 2, 999);

        $id = $this->save($this->payload([$this->juice(1, [], [], 31)]));

        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertEqualsWithDelta(12.34, (float) $detail->hpp, 0.000001);
        $this->assertEqualsWithDelta(12.34, (float) $detail->subtotal_hpp, 0.000001);
        $this->assertSame(0, DB::table('production')->count());
    }

    public function test_juice_hpp_rejects_variant_with_multiple_parent_links(): void
    {
        $this->product(10, 10, 6.68);
        $this->product(20, 10, 8.76);
        $this->variant(31, 10, 2, 999);
        DB::table('product_child')->insert(['product_id' => 31, 'parent_id' => 20]);

        $this->postJson('/pos/save-transaction', $this->payload([$this->juice(1, [], [], 31)]))
            ->assertUnprocessable()->assertJsonValidationErrors('jus');

        foreach (['pos_transaction', 'pos_transaction_detail', 'production', 'production_detail'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' must remain unchanged');
        }
    }

    public function test_juice_recipe_ingredient_variant_uses_parent_moving_hpp(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);
        $this->variant(11, 10, 10, 999);
        $this->configureRecipe(30, [[11, 0.5]]);

        $id = $this->save($this->payload([$this->juice(2)]));

        $detail = PosDetailModel::where('pos_id', $id)->sole();
        $this->assertEqualsWithDelta(3.34, (float) $detail->hpp, 0.000001);
        $this->assertDatabaseHas('production_detail', ['product_id' => 11, 'quantity' => 1]);
    }

    public function test_production_juice_rejects_nonpositive_recipe_quantities(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);

        $this->postJson('/pos/save-transaction', $this->payload([$this->juice(2, [10], [-0.5])]))
            ->assertUnprocessable()->assertJsonValidationErrors('jus.0.product_receipt_qty.0');
        $this->assertSame(0, DB::table('pos_transaction')->count());
        $this->assertSame(0, DB::table('production')->count());
    }

    public function test_production_juice_rejects_non_finite_recipe_quantities(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);

        $this->postJson('/pos/save-transaction', $this->payload([$this->juice(2, [10], ['1e309'])]))
            ->assertUnprocessable()->assertJsonValidationErrors('jus.0.product_receipt_qty.0');
        $this->assertSame(0, DB::table('pos_transaction')->count());
        $this->assertSame(0, DB::table('production')->count());
    }

    public function test_production_juice_requires_a_quantity_for_each_recipe_ingredient(): void
    {
        $this->product(30, 0, 12.34);
        $this->product(10, 10, 6.68);

        $this->postJson('/pos/save-transaction', $this->payload([$this->juice(2, [10], [])]))
            ->assertUnprocessable()->assertJsonValidationErrors('jus');
        $this->assertSame(0, DB::table('pos_transaction')->count());
        $this->assertSame(0, DB::table('production')->count());
    }
}
