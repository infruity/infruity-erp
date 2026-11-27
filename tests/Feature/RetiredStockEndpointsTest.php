<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The line-by-line endpoints of the old purchase and production screens wrote stock rows with no branch, status, or
 * quantity check. No screen calls them any more, so they are gone.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class RetiredStockEndpointsTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $otherBranch;
    private int $fruit;
    private int $extra;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->superAdminRoleId();
        $role = $this->makeRole('Gudang');
        $mine = $this->makeBranch('SAYA');
        $this->otherBranch = $this->makeBranch('ORANG');
        $this->fruit = $this->makeProduct('Apel', 40000);
        $this->extra = $this->makeProduct('Gula', 5000);

        $user = $this->makeUser($role, [], [$mine]);
        foreach (['wholesale.update-product', 'wholesale.save-product', 'wholesale.edit-product', 'wholesale.delete_product',
            'production.save-ajax', 'production.edit-ajax', 'production.delete-ajax', 'production.update'] as $permission) {
            DB::table('user_permissions')->insert(['id_user' => $user->id_user, 'permission' => $permission, 'is_allowed' => 1]);
        }
        $this->actAsRole($user, $role);
    }

    private function stock(int $product): float
    {
        return InventorySnapshot::stockAvailable([$product], $this->otherBranch)[$product] ?? 0.0;
    }

    public function test_a_posted_purchase_cannot_be_changed_line_by_line(): void
    {
        $purchase = DB::table('wholesale')->insertGetId([
            'uuid' => (string) Str::uuid(), 'branch_id' => $this->otherBranch, 'order_number' => 'WQA' . Str::upper(Str::random(8)),
            'order_date' => date('Y-m-d'), 'status' => 'posting', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $line = DB::table('wholesale_product')->insertGetId([
            'wholesale_id' => $purchase, 'product_id' => $this->fruit, 'quantity' => 10, 'price' => 10000, 'total_price' => 100000,
            'status' => 'complete', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $responses = [
            $this->putJson("/wholesale/update-product/{$line}", ['supplier_id' => null, 'price' => 1, 'qty' => -50]),
            $this->postJson('/wholesale/save-product', ['wholesale_id' => $purchase, 'id' => $this->extra, 'supplier_id' => null,
                'qty' => 500, 'price' => 1, 'sell_price' => 1]),
            $this->deleteJson("/wholesale/delete-product/{$line}"),
            $this->getJson("/wholesale/edit-product/{$line}"),
        ];

        foreach ($responses as $response) {
            $this->assertContains($response->getStatusCode(), [404, 405]);
        }
        $this->assertSame(10.0, $this->stock($this->fruit));
        $this->assertSame(0.0, $this->stock($this->extra));
        $this->assertEquals(10000, DB::table('wholesale_product')->where('id', $line)->value('price'));
        $this->assertEquals(5000, DB::table('products')->where('id', $this->extra)->value('price'), 'catalog price untouched');
    }

    public function test_a_finished_productions_ingredients_cannot_be_changed(): void
    {
        $this->giveStock($this->fruit, $this->otherBranch, 10);
        $juice = $this->makeProduct('Jus', 15000);
        $production = DB::table('production')->insertGetId([
            'branch_id' => $this->otherBranch, 'product_id' => $juice, 'quantity' => 1, 'status' => 'complete',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $detail = DB::table('production_detail')->insertGetId([
            'production_id' => $production, 'product_id' => $this->fruit, 'quantity' => 4, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame(6.0, $this->stock($this->fruit));

        $responses = [
            $this->putJson("/production/update-product/{$detail}", ['qty' => -50]),
            $this->postJson('/production/save-ajax', ['production_id' => $production, 'product_id' => $this->fruit, 'qty' => -100]),
            $this->deleteJson("/production/delete-product/{$detail}"),
            $this->getJson("/production/edit-product/{$detail}"),
        ];

        foreach ($responses as $response) {
            $this->assertContains($response->getStatusCode(), [404, 405]);
        }
        $this->assertSame(6.0, $this->stock($this->fruit));
        $this->assertSame(1, DB::table('production_detail')->where('production_id', $production)->count());
    }

    /** The edit screen saves through POST /production (guarded; see ProductionBranchAccessTest). */
    public function test_the_old_update_route_cannot_post_another_branchs_draft(): void
    {
        $this->giveStock($this->fruit, $this->otherBranch, 10);
        $juice = $this->makeProduct('Jus', 15000);
        $recipe = DB::table('receipt')->insertGetId(['code' => 'RQA' . Str::upper(Str::random(6)), 'product_id' => $juice,
            'created_at' => now(), 'updated_at' => now()]);
        $draft = DB::table('production')->insertGetId([
            'branch_id' => $this->otherBranch, 'product_id' => $juice, 'quantity' => 1, 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('production_detail')->insert([
            'production_id' => $draft, 'product_id' => $this->fruit, 'quantity' => 4, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->put("/production/{$draft}", ['product_id' => $recipe, 'submit_type' => 'posting',
            'production_date' => date('Y-m-d'), 'quantity' => 500, 'branch_id' => $this->otherBranch, 'service_cost' => 0]);

        $this->assertContains($response->getStatusCode(), [404, 405]);
        $this->assertDatabaseHas('production', ['id' => $draft, 'status' => 'draft', 'quantity' => 1]);
        $this->assertSame(0.0, $this->stock($juice));
    }
}
