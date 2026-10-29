<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\ProductionInventoryFixture;
use Tests\TestCase;

class ParcelFinalizationIntegrityTest extends TestCase
{
    use ProductionInventoryFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useSqliteInventoryFixture();
    }

    public function test_unknown_parcel_state_cannot_be_finalized(): void
    {
        // Simulate a corrupt legacy status; supported UI states remain draft/posting.
        DB::statement('PRAGMA ignore_check_constraints = ON');
        $id = $this->seedParcel('cancelled');
        DB::statement('PRAGMA ignore_check_constraints = OFF');
        $this->postJson(route('parcel.set_selesai', $id), ['hpp' => 200])->assertConflict();
        $this->assertDatabaseHas('production_parcel', ['id' => $id, 'status' => 'cancelled', 'product_id' => null]);
        $this->assertDatabaseCount('receipt', 0);
        $this->assertDatabaseCount('production', 0);
    }

    public function test_inconsistent_complete_without_a_result_fails_closed(): void
    {
        $id = $this->seedParcel('complete');
        $productCount = DB::table('products')->count();
        $this->postJson(route('parcel.set_selesai', $id), ['hpp' => 200])->assertConflict();
        $this->assertDatabaseHas('production_parcel', ['id' => $id, 'status' => 'complete', 'product_id' => null]);
        $this->assertDatabaseCount('products', $productCount);
        $this->assertDatabaseCount('production', 0);
        $this->assertDatabaseCount('receipt', 0);
    }

    public function test_sequential_completion_retry_reuses_the_original_result(): void
    {
        $id = $this->seedParcel();
        $productCount = DB::table('products')->count();
        $first = $this->postJson(route('parcel.set_selesai', $id), ['hpp' => 200])->assertCreated();
        $productId = DB::table('production_parcel')->where('id', $id)->value('product_id');
        $productionId = DB::table('production')->value('id');
        $receiptId = DB::table('receipt')->value('id');
        $this->assertNotNull($productId);
        $retry = $this->postJson(route('parcel.set_selesai', $id), ['hpp' => 999])->assertOk();
        foreach ([$first, $retry] as $response) {
            $response->assertJson([
                'parcel_id' => $id, 'product_id' => $productId,
                'production_id' => $productionId, 'receipt_id' => $receiptId,
            ]);
        }
        $this->assertDatabaseCount('products', $productCount + 1);
        $this->assertDatabaseCount('production', 1);
        $this->assertDatabaseCount('production_detail', 1);
        $this->assertDatabaseCount('receipt', 1);
        $this->assertDatabaseCount('product_receipt', 1);
        $this->assertDatabaseHas('products', ['id' => $productId, 'hpp' => 200]);
    }
}
