<?php

namespace Tests\Feature;

use App\Support\ProductHpp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\ProductCategory;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Jasa / Layanan: unlimited stock, never purchased, HPP 0.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class ServiceProductTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $serviceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // saveTransaction() calls DB::disconnect(), so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('JASA');
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);

        $this->serviceId = $this->makeProduct('Kupas', 10000);
        DB::table('products')->where('id', $this->serviceId)->update(['hpp' => 10000]);
        $service = Product::findOrFail($this->serviceId);
        $service->category_id = $this->serviceCategoryId();
        $service->save();
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function serviceCategoryId(): int
    {
        $id = ProductCategory::where('name', Product::SERVICE_CATEGORY)->value('id');

        return (int) ($id ?? DB::table('products_category')->insertGetId(['name' => Product::SERVICE_CATEGORY]));
    }

    public function test_the_service_category_marks_the_product_and_clears_its_cost(): void
    {
        $this->assertDatabaseHas('products', ['id' => $this->serviceId, 'is_service' => 1, 'hpp' => 0]);
        $this->assertSame([$this->serviceId], Product::serviceIds([$this->serviceId]));
        $this->assertEquals(0.0, ProductHpp::current([$this->serviceId])[$this->serviceId]);

        $product = Product::findOrFail($this->serviceId);
        $product->category_id = null;
        $product->save();
        $this->assertDatabaseHas('products', ['id' => $this->serviceId, 'is_service' => 0]);
    }

    public function test_screens_see_a_service_as_never_running_out(): void
    {
        $rows = $this->getJson('/ajax/listProduct?term=QA+Kupas&variant=0')->assertOk()->json();
        $row = collect($rows)->firstWhere('id', $this->serviceId);

        $this->assertSame(Product::SERVICE_STOCK, $row['get_stock']['stock_available']);
    }

    public function test_procurement_neither_lists_nor_accepts_a_service(): void
    {
        $rows = $this->getJson('/ajax/listProduct?term=QA+Kupas&variant=0&no_service=1')->assertOk()->json();
        $this->assertNull(collect($rows)->firstWhere('id', $this->serviceId));

        $this->postJson('/wholesale/save-transaction', [
            'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'invoice_number' => null,
            'items' => [['id' => $this->serviceId, 'price' => 10000, 'qty' => 1, 'total_input' => 10000]],
            'subtotal' => 10000, 'total' => 10000, 'status' => 'posting',
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->assertDatabaseMissing('wholesale_product', ['product_id' => $this->serviceId]);
    }

    public function test_production_uses_a_service_without_stock_and_adds_no_cost(): void
    {
        $fruit = $this->makeProduct('Buah', 20000);
        $this->giveStock($fruit, $this->branch, 10, 10000);
        $output = $this->makeProduct('Olahan', 30000);
        $number = 'PRDQA' . strtoupper(Str::random(8));

        $this->post(route('production.store'), [
            'product_id' => $output, 'production_number' => $number, 'submit_type' => 'posting',
            'production_date' => date('Y-m-d'), 'quantity' => 2, 'branch_id' => $this->branch, 'service_cost' => '0',
            'ingredients' => [
                ['id' => $fruit, 'quantity' => 4, 'hpp' => 10000],
                ['id' => $this->serviceId, 'quantity' => 2, 'hpp' => 0],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        // 4 x 10.000 of fruit over 2 portions; the service adds nothing.
        $this->assertEqualsWithDelta(20000.0, (float) DB::table('production')->where('production_number', $number)->value('hpp_per_unit'), 0.01);
    }

    public function test_a_service_without_stock_can_be_sold_at_zero_cost(): void
    {
        $this->postJson('/pos/save-transaction', [
            'customer_id' => null, 'date' => date('Y-m-d'), 'invoice_number' => 'INVQA' . strtoupper(Str::random(8)),
            'items' => [['id' => $this->serviceId, 'price' => 10000, 'qty' => 3, 'discount' => 0, 'total_input' => 30000]],
            'subtotal' => 30000, 'discount' => 0, 'ongkir' => 0, 'discount_ongkir' => 0, 'total' => 30000,
            'status' => 'draft', 'branch_id' => $this->branch,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('pos_transaction_detail', ['product_id' => $this->serviceId, 'hpp' => 0, 'subtotal_hpp' => 0]);
    }
}
