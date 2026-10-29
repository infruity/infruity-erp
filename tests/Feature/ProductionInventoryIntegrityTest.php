<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\ProductionInventoryFixture;
use Tests\TestCase;

class ProductionInventoryIntegrityTest extends TestCase
{
    use ProductionInventoryFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useSqliteInventoryFixture();
    }

    public function test_complete_production_cannot_be_rewritten_through_store(): void
    {
        $this->postJson(route('production.store'), $this->productionPayload())->assertOk();
        $id = DB::table('production')->value('id');
        DB::table('production')->where('id', $id)->update(['status' => 'complete']);
        $this->postJson(route('production.store'), $this->productionPayload([
            'id' => $id, 'submit_type' => 'temp', 'quantity' => 2,
        ]))->assertUnprocessable()->assertJson(['success' => false]);
        $this->assertDatabaseHas('production', ['id' => $id, 'status' => 'complete', 'quantity' => 1]);
        $this->assertDatabaseHas('production_detail', ['production_id' => $id, 'quantity' => 6]);
    }

    public function test_posting_retry_without_id_reuses_the_same_production_number(): void
    {
        $payload = $this->productionPayload();
        $this->postJson(route('production.store'), $payload)->assertOk();
        $id = DB::table('production')->value('id');
        $this->postJson(route('production.store'), $payload)->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('production', 1);
        $this->assertDatabaseHas('production', ['id' => $id, 'status' => 'posting']);
        $this->assertDatabaseCount('production_detail', 1);
    }

    public function test_posted_edit_credits_only_its_own_previous_consumption(): void
    {
        $this->postJson(route('production.store'), $this->productionPayload())->assertOk();
        $id = DB::table('production')->value('id');
        $this->postJson(route('production.store'), $this->productionPayload([
            'id' => $id, 'ingredients' => [['id' => 1, 'quantity' => 10, 'hpp' => 100]],
        ]))->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('production', 1);
        $this->assertDatabaseHas('production_detail', ['production_id' => $id, 'quantity' => 10]);
        $this->postJson(route('production.store'), $this->productionPayload([
            'id' => $id, 'ingredients' => [['id' => 1, 'quantity' => 10.01, 'hpp' => 100]],
        ]))->assertUnprocessable();
        $this->assertDatabaseHas('production_detail', ['production_id' => $id, 'quantity' => 10]);
    }

    public function test_exact_decimal_availability_is_not_rejected_by_float_roundoff(): void
    {
        DB::table('inventory_opening')->update(['quantity' => '0.30']);
        $this->postJson(route('production.store'), $this->productionPayload(['ingredients' => [
            ['id' => 1, 'quantity' => '0.10', 'hpp' => 100],
            ['id' => 1, 'quantity' => '0.20', 'hpp' => 100],
        ]]))->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('production_detail', 2);
        $this->assertEqualsWithDelta(0, DB::table('product_stock')->where('id', 1)->value('stock_available'), 0.00001);
    }

    public function test_repeated_ingredients_cannot_exceed_combined_available_stock(): void
    {
        $this->postJson(route('production.store'), $this->productionPayload(['ingredients' => [
            ['id' => 1, 'quantity' => 6, 'hpp' => 100],
            ['id' => 1, 'quantity' => 6, 'hpp' => 100],
        ]]))->assertUnprocessable()->assertJson(['success' => false]);

        $this->assertDatabaseCount('production', 0);
        $this->assertDatabaseCount('production_detail', 0);
        $this->assertDatabaseCount('product_branch', 0);
    }

    public function test_parent_and_variant_ingredients_cannot_double_spend_the_shared_stock_pool(): void
    {
        $variantId = DB::table('products')->insertGetId([
            'name' => 'Ingredient variant', 'status' => 'no-receipt', 'tipe' => 'product',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_child')->insert(['product_id' => $variantId, 'parent_id' => 1]);

        $this->assertSame(10.0, (float) DB::table('product_stock')->where('id', 1)->where('branch_id', 1)->value('stock_available'));
        $this->assertSame(10.0, (float) DB::table('product_stock')->where('id', $variantId)->where('branch_id', 1)->value('stock_available'));
        $this->postJson(route('production.store'), $this->productionPayload(['ingredients' => [
            ['id' => 1, 'quantity' => 6, 'hpp' => 100],
            ['id' => $variantId, 'quantity' => 6, 'hpp' => 100],
        ]]))->assertUnprocessable()->assertJson(['success' => false]);

        $this->assertDatabaseCount('production', 0);
        $this->assertDatabaseCount('production_detail', 0);
        $this->assertSame(0, DB::transactionLevel());
    }
}
