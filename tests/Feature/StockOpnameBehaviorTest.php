<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Pre-deploy QA, phase 1 — stock opname: a finished count resets the system stock to the physical
 * count; pending counts change nothing. Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class StockOpnameBehaviorTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branch;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->branch = $this->makeBranch('OPNAME');
        $this->productId = $this->makeProduct('Pisang', 12000);
        $this->giveStock($this->productId, $this->branch, 10);

        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    private function stock(?int $branch = null): float
    {
        return InventorySnapshot::stockAvailable([$this->productId], $branch ?? $this->branch)[$this->productId];
    }

    private function submitCount(float $real, array $override = [])
    {
        return $this->postJson('/stock-opname', array_merge([
            'product_id' => $this->productId,
            'branch_id'  => $this->branch,
            'date'       => date('Y-m-d'),
            'real_stock' => $real,
        ], $override));
    }

    private function createCount(float $real): int
    {
        return (int) $this->submitCount($real)->assertCreated()->json('data.id');
    }

    private function finalize(int $id)
    {
        return $this->postJson("/stock-opname/{$id}/finalize");
    }

    // ---- creating a count --------------------------------------------------------------------

    public function test_a_new_count_records_system_stock_and_difference_but_changes_nothing_yet(): void
    {
        $response = $this->submitCount(7)->assertCreated();

        $this->assertDatabaseHas('stock_opname', [
            'id' => $response->json('data.id'), 'status' => 'pending', 'stock' => 10, 'real_stock' => 7, 'difference' => -3,
        ]);
        $this->assertDatabaseHas('stock_opname_history', ['stock_opname_id' => $response->json('data.id'), 'action' => 'INITIAL']);
        $this->assertSame(10.0, $this->stock(), 'a pending count must not move stock');
    }

    public function test_the_same_product_cannot_have_two_pending_counts_on_one_day(): void
    {
        $this->submitCount(7)->assertCreated();

        $this->submitCount(8)->assertStatus(422);
        $this->assertSame(1, DB::table('stock_opname')->where('product_id', $this->productId)->where('branch_id', $this->branch)->where('status', 'pending')->count());
    }

    public function test_negative_physical_stock_is_refused(): void
    {
        $this->submitCount(-1)->assertStatus(422)->assertJsonValidationErrors('real_stock');
    }

    public function test_counting_a_product_with_no_stock_history_in_the_branch_is_refused(): void
    {
        $this->submitCount(5, ['branch_id' => $this->makeBranch('KOSONG')])->assertStatus(404);
    }

    // ---- finishing a count -------------------------------------------------------------------

    public function test_finalizing_a_shortage_sets_stock_to_the_physical_count(): void
    {
        $id = $this->createCount(7);

        $this->finalize($id)->assertOk()->assertJson(['success' => true]);

        $this->assertSame(7.0, $this->stock());
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'status' => 'selesai', 'stock' => 10, 'difference' => -3]);
    }

    public function test_finalizing_a_surplus_raises_stock_to_the_physical_count(): void
    {
        $id = $this->createCount(15);

        $this->finalize($id)->assertOk();

        $this->assertSame(15.0, $this->stock());
    }

    public function test_finalizing_a_zero_count_empties_the_stock(): void
    {
        $id = $this->createCount(0);

        $this->finalize($id)->assertOk();

        $this->assertSame(0.0, $this->stock());
    }

    public function test_a_finished_count_cannot_be_finalized_again(): void
    {
        $id = $this->createCount(7);
        $this->finalize($id)->assertOk();

        $this->finalize($id)->assertStatus(422);
        $this->assertSame(7.0, $this->stock());
    }

    public function test_a_finished_count_cannot_be_edited_adjusted_or_deleted(): void
    {
        $id = $this->createCount(7);
        $this->finalize($id)->assertOk();

        $this->putJson("/stock-opname/{$id}", [
            'product_id' => $this->productId, 'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'real_stock' => 1,
        ])->assertStatus(422);
        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 1, 'type' => 'up', 'reason' => 'x'])->assertStatus(422);
        // Known quirk (Low): destroy() catches \Exception, so abort(422) comes back as a 500 JSON.
        // The delete is still refused, which is what matters.
        $this->deleteJson("/stock-opname/{$id}")->assertJson(['success' => false]);

        $this->assertSame(7.0, $this->stock());
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'real_stock' => 7, 'status' => 'selesai']);
    }

    public function test_the_physical_count_wins_over_a_sale_made_between_counting_and_finalizing(): void
    {
        $id = $this->createCount(7);   // system said 10, shelf had 7

        // A sale of 2 is paid after the count was entered.
        $posId = DB::table('pos_transaction')->insertGetId([
            'uuid' => (string) Str::uuid(), 'invoice_number' => 'INVOP' . strtoupper(Str::random(6)), 'date' => date('Y-m-d'),
            'total' => 24000, 'discount' => 0, 'paid' => 24000, 'status' => 'paid', 'branch_id' => $this->branch,
            'created_by' => 1, 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => $posId, 'product_id' => $this->productId, 'quantity' => 2, 'price' => 12000, 'discount' => 0,
            'subtotal' => 24000, 'exp' => 0, 'exp_value' => 0, 'type' => 'product', 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        $this->assertSame(8.0, $this->stock());

        $this->finalize($id)->assertOk();

        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'stock' => 8, 'difference' => -1]);
        $this->assertSame(7.0, $this->stock(), 'after finalizing, system stock must equal the counted 7');
    }

    // ---- editing a pending count -------------------------------------------------------------

    public function test_pending_count_can_be_updated_and_history_is_kept(): void
    {
        $id = $this->createCount(7);

        $this->putJson("/stock-opname/{$id}", [
            'product_id' => $this->productId, 'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'real_stock' => 9, 'note' => 'hitung ulang',
        ])->assertOk();

        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'real_stock' => 9, 'difference' => -1, 'status' => 'pending']);
        $this->assertDatabaseHas('stock_opname_history', ['stock_opname_id' => $id, 'action' => 'UPDATE', 'note' => 'hitung ulang']);
        $this->assertSame(10.0, $this->stock());
    }

    public function test_product_and_branch_of_a_count_cannot_be_changed(): void
    {
        $id = $this->createCount(7);
        $otherProduct = $this->makeProduct('Lain');

        $this->putJson("/stock-opname/{$id}", [
            'product_id' => $otherProduct, 'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'real_stock' => 7,
        ])->assertStatus(422)->assertJsonValidationErrors('product_id');
    }

    public function test_adjust_up_and_down_changes_the_counted_stock_with_a_history_entry(): void
    {
        $id = $this->createCount(7);

        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 2, 'type' => 'up', 'reason' => 'ketemu 2 di gudang'])->assertOk();
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'real_stock' => 9, 'difference' => -1]);

        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 4, 'type' => 'down', 'reason' => 'busuk'])->assertOk();
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'real_stock' => 5, 'difference' => -5]);

        $this->assertSame(2, DB::table('stock_opname_history')->where('stock_opname_id', $id)->where('note', '!=', 'Input Stock Fisik Awal')->count());
        $this->assertSame(10.0, $this->stock(), 'adjusting a pending count must not move stock');
    }

    public function test_adjust_cannot_take_the_count_below_zero_or_use_zero_quantity(): void
    {
        $id = $this->createCount(3);

        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 4, 'type' => 'down', 'reason' => 'x'])->assertStatus(422);
        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 0, 'type' => 'up', 'reason' => 'x'])->assertStatus(422);
        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 1, 'type' => 'up'])->assertStatus(422);
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'real_stock' => 3]);
    }

    public function test_pending_count_can_be_deleted_without_touching_stock(): void
    {
        $id = $this->createCount(7);

        $this->deleteJson("/stock-opname/{$id}")->assertOk()->assertJson(['success' => true]);

        $this->assertSame(10.0, $this->stock());
        $this->assertDatabaseMissing('stock_opname', ['id' => $id]);
    }

    // ---- access ------------------------------------------------------------------------------

    public function test_user_cannot_count_in_a_branch_they_are_not_assigned_to(): void
    {
        $roleId = $this->makeRole('Gudang');
        $gudang = $this->makeUser($roleId, [], [$this->makeBranch('LAIN')]);
        DB::table('user_permissions')->insert(['id_user' => $gudang->id_user, 'permission' => 'stock-opname.store', 'is_allowed' => 1]);
        $this->actAsRole($gudang, $roleId);

        $this->submitCount(7)->assertForbidden();
        $this->assertSame(0, DB::table('stock_opname')->where('status', 'pending')->where('branch_id', $this->branch)->count());
    }

    public function test_user_of_another_branch_cannot_finalize_adjust_or_delete(): void
    {
        $id = $this->createCount(7);

        $roleId = $this->makeRole('Gudang');
        $outsider = $this->makeUser($roleId, [], [$this->makeBranch('LAIN')]);
        foreach (['stock-opname.update', 'stock-opname.destroy'] as $permission) {
            DB::table('user_permissions')->insert(['id_user' => $outsider->id_user, 'permission' => $permission, 'is_allowed' => 1]);
        }
        $this->actAsRole($outsider, $roleId);

        $this->finalize($id)->assertNotFound();
        $this->postJson("/stock-opname/{$id}/adjust", ['qty' => 1, 'type' => 'up', 'reason' => 'x'])->assertNotFound();
        // Known quirk (Low): destroy() turns the not-found into a 500 JSON; the count is still untouched.
        $this->deleteJson("/stock-opname/{$id}")->assertJson(['success' => false]);
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'status' => 'pending', 'real_stock' => 7]);
    }

    public function test_user_without_permission_cannot_finalize(): void
    {
        $id = $this->createCount(7);

        $roleId = $this->makeRole('Sales');
        $sales = $this->makeUser($roleId, [], [$this->branch]);
        $this->actAsRole($sales, $roleId);

        $this->finalize($id)->assertRedirect();
        $this->assertDatabaseHas('stock_opname', ['id' => $id, 'status' => 'pending']);
        $this->assertSame(10.0, $this->stock());
    }
}
