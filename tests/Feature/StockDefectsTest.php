<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Stock rules found by the 2026-11-27 defect hunt (H-20, H-21, H-22, B9, J1, variant counts) and orders saved with a
 * number somebody else's form already used.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class StockDefectsTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $other;
    private int $fruit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // The order save disconnects the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();
        $this->branch = $this->makeBranch('STOK');
        $this->other = $this->makeBranch('LAIN');
        $this->fruit = $this->makeProduct('Apel');
        $this->giveStock($this->fruit, $this->branch, 10);
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function stock(int $product): float
    {
        return InventorySnapshot::stockAvailable([$product], $this->branch)[$product] ?? 0.0;
    }

    private function parcel(array $override = []): int
    {
        return DB::table('production_parcel')->insertGetId($override + [
            'production_number' => 'PRCQA' . Str::upper(Str::random(5)), 'production_date' => date('Y-m-d'), 'status' => 'posting',
            'budget' => 50000, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function recipeRow(int $parcel, float $quantity, ?int $pos = null): int
    {
        return DB::table('production_parcel_detail')->insertGetId([
            'production_id' => $parcel, 'pos_id' => $pos, 'product_id' => $this->fruit, 'quantity' => $quantity,
            'quantity_kemasan' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** POS parcels share the table: their production_id is a product id, which can equal a parcel menu id. */
    public function test_the_parcel_menu_never_touches_the_rows_of_a_sold_parcel(): void
    {
        $menu = $this->parcel();
        $sold = $this->recipeRow($menu, 7, 999999);
        $own = $this->recipeRow($menu, 1);
        $staff = DB::table('staff')->insertGetId(['name' => 'QA', 'date_in' => date('Y-m-d'), 'branch_id' => $this->branch, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()]);

        $this->put("/parcel/{$menu}", ['budget' => 60000, 'quantity' => 1, 'production_date' => date('Y-m-d'), 'submit_type' => 'posting',
            'staff_id' => $staff, 'product_receipt_id' => [$this->fruit], 'ingredients_quantity' => [2]]);
        $this->putJson("/parcel/update-product/{$sold}", ['qty' => 99]);
        $this->deleteJson("/parcel/delete-product/{$sold}");

        $this->assertDatabaseHas('production_parcel_detail', ['id' => $sold, 'quantity' => 7, 'pos_id' => 999999]);
        $this->assertDatabaseMissing('production_parcel_detail', ['id' => $own]);
    }

    public function test_a_finished_parcel_takes_its_ingredients_from_the_workers_branch(): void
    {
        $staff = DB::table('staff')->insertGetId(['name' => 'QA', 'date_in' => date('Y-m-d'), 'branch_id' => $this->branch, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()]);
        $short = $this->parcel(['staff_id' => $staff]);
        $this->recipeRow($short, 11);
        $enough = $this->parcel(['staff_id' => $staff]);
        $this->recipeRow($enough, 4);

        $this->postJson("/parcel/set-selesai/{$short}")->assertStatus(422);
        $this->assertSame('posting', DB::table('production_parcel')->where('id', $short)->value('status'));
        $this->postJson("/parcel/set-selesai/{$enough}")->assertCreated();

        $this->assertSame(6.0, $this->stock($this->fruit));
        $this->assertSame(1, DB::table('production')->where('branch_id', $this->branch)->count());
    }

    public function test_legacy_stock_endpoints_are_gone(): void
    {
        foreach (['/sortir/save-stock', '/transfer/save-stock'] as $url) {
            $this->assertContains($this->postJson($url, ['product_id' => $this->fruit, 'quantity' => [1]])->getStatusCode(), [404, 405], $url);
        }
    }

    public function test_a_service_cannot_be_counted_sorted_or_transferred(): void
    {
        $service = $this->makeProduct('Jasa Kupas');
        DB::table('products')->where('id', $service)->update(['is_service' => 1]);
        $line = ['id' => $service, 'qty' => 1, 'price' => 1000, 'total_input' => 1000];

        $this->postJson('/stock-opname', ['product_id' => $service, 'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'real_stock' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('product_id');
        $this->postJson('/sortir/save-transaction', ['branch_id' => $this->branch, 'date' => date('Y-m-d'), 'items' => [$line], 'status' => 'draft'])
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson('/transfer/save-transaction', ['branch_id' => $this->branch, 'branch_destination_id' => $this->other,
            'date' => date('Y-m-d'), 'items' => [$line], 'total' => 1000, 'subtotal' => 1000, 'status' => 'draft'])
            ->assertStatus(422)->assertJsonValidationErrors('items');
    }

    /** Reproduced before the fix: a surplus of +2 on a variant lowered the parent's stock from 10 to 8. */
    public function test_a_variant_cannot_be_counted(): void
    {
        $variant = $this->makeProduct('Apel Kupas');
        DB::table('product_child')->insert(['product_id' => $variant, 'parent_id' => $this->fruit, 'created_at' => now(), 'updated_at' => now()]);

        $this->postJson('/stock-opname', ['product_id' => $variant, 'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'real_stock' => 12])
            ->assertStatus(422)->assertJsonValidationErrors('product_id');

        $this->assertSame(10.0, $this->stock($this->fruit));
    }

    public function test_two_new_orders_with_the_same_form_number_are_both_kept(): void
    {
        $number = 'ORDQA' . Str::upper(Str::random(6));
        $save = fn (string $note) => $this->postJson('/order-book/save-transaction', ['branch_id' => $this->branch,
            'invoice_number' => $number, 'order_book_id' => null, 'date' => date('Y-m-d'), 'status' => 'draft', 'note' => $note]);

        $first = $save('pesanan orang pertama')->assertOk()->json('order_book_id');
        $second = $save('pesanan orang kedua')->assertOk()->json('order_book_id');

        $this->assertDatabaseHas('order_book', ['id' => $first, 'invoice_number' => $number, 'note' => 'pesanan orang pertama']);
        $this->assertDatabaseHas('order_book', ['id' => $second, 'note' => 'pesanan orang kedua']);
        $this->assertNotSame($number, DB::table('order_book')->where('id', $second)->value('invoice_number'));

        $this->postJson('/order-book/save-transaction', ['branch_id' => $this->branch, 'invoice_number' => $number,
            'order_book_id' => $first, 'date' => date('Y-m-d'), 'status' => 'process', 'note' => 'diedit'])->assertOk();
        $this->assertSame(['diedit'], DB::table('order_book')->where('invoice_number', $number)->pluck('note')->all());
        $this->assertDatabaseHas('order_book', ['id' => $second]);
    }
}
