<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The stock endpoints no longer read the whole product_stock view for every request: they compute only the products
 * they show (LedgerSql::stockFor) or skip the stock for counting. Each must still agree with the view.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class StockEndpointsLedgerReadTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branch;

    private array $products = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->branch = $this->makeBranch('LED');
        $other = $this->makeBranch('LED2');
        $this->actAsRole($this->makeUser($this->superAdminRoleId(), [], [$this->branch, $other]), $this->superAdminRoleId());

        $this->products = [
            'apel' => $this->makeProduct('Apel Ledger'),
            'jeruk' => $this->makeProduct('Jeruk Ledger'),
            'mangga' => $this->makeProduct('Mangga Ledger'),
            'pir' => $this->makeProduct('Pir Ledger'), // never stocked
        ];
        $this->giveStock($this->products['apel'], $this->branch, 12);
        $this->giveStock($this->products['jeruk'], $this->branch, -3);
        $this->giveStock($this->products['mangga'], $other, 7);
    }

    private function viewStock(array $branches): array
    {
        return DB::table('product_stock')->whereIn('branch_id', $branches)->whereNull('is_variant')
            ->whereIn('id', array_values($this->products))->orderBy('id')->get()
            ->mapWithKeys(fn ($r) => [(int) $r->id . '|' . (int) $r->branch_id => (float) $r->stock_available])->all();
    }

    public function test_available_stock_matches_the_view_for_a_branch_and_for_all_branches(): void
    {
        foreach ([[$this->branch], null] as $only) {
            $url = '/product-stock/available-stock?search=Ledger' . ($only ? '&branch_id=' . $only[0] : '');
            $rows = collect($this->getJson($url)->assertOk()->json());

            $actual = $rows->mapWithKeys(fn ($r) => [(int) $r['id'] => (float) $r['stock_available']])->all();
            $expected = collect($this->viewStock($only ?? [$this->branch, DB::table('user_branch')->where('branch_id', '!=', $this->branch)->orderByDesc('id')->value('branch_id')]))
                ->mapWithKeys(fn ($v, $k) => [(int) explode('|', $k)[0] => $v])->all();

            $this->assertEquals($expected, $actual, $only ? 'one branch' : 'all branches');
            $this->assertEqualsCanonicalizing(['id', 'name', 'stock_available', 'hpp'], array_keys($rows->first()));
        }
    }

    public function test_available_stock_honours_the_search_and_the_limit_of_20(): void
    {
        $names = collect($this->getJson('/product-stock/available-stock?branch_id=' . $this->branch . '&search=Apel')->assertOk()->json())->pluck('name')->all();
        $this->assertCount(1, $names);
        $this->assertStringContainsString('Apel Ledger', $names[0]);

        foreach (range(1, 25) as $i) {
            $id = $this->makeProduct("Bulk {$i}");
            $this->giveStock($id, $this->branch, $i);
        }
        $this->assertCount(20, $this->getJson('/product-stock/available-stock?branch_id=' . $this->branch . '&search=Bulk')->assertOk()->json());
    }

    public function test_stock_list_counts_products_and_carries_the_stock_of_the_view(): void
    {
        $base = '/products/stock-data?draw=1&start=0&length=100&order[0][column]=0&order[0][dir]=asc';
        $total = DB::table('products')->whereNull('is_variant')->where('tipe', '!=', 'parcel')->count();

        $all = $this->getJson($base)->assertOk()->json();
        $this->assertSame($total, $all['recordsTotal']);
        $this->assertSame($total, $all['recordsFiltered']);

        $apel = collect($all['data'])->firstWhere('id', $this->products['apel']);
        $this->assertEquals(12, $apel['stock_raw']);

        $searched = $this->getJson($base . '&search[value]=Ledger')->assertOk()->json();
        $this->assertSame($total, $searched['recordsTotal']);
        $this->assertSame(4, $searched['recordsFiltered']);

        $inStock = $this->getJson($base . '&stock_filter=ada&search[value]=Ledger')->assertOk()->json();
        $this->assertSame(2, $inStock['recordsFiltered'], 'apel (12) and mangga (7, other branch); jeruk is negative, pir has none');
        $empty = $this->getJson($base . '&stock_filter=kosong&search[value]=Ledger')->assertOk()->json();
        $this->assertSame(2, $empty['recordsFiltered']);
    }

    public function test_product_list_attaches_the_stock_of_the_asked_branch(): void
    {
        $rows = collect($this->getJson('/ajax/listProduct?term=Ledger')->assertOk()->json())->keyBy('id');

        $this->assertEquals(12, $rows[$this->products['apel']]['get_stock']['stock_available']);
        $this->assertEquals(-3, $rows[$this->products['jeruk']]['get_stock']['stock_available']);
        $pir = $rows[$this->products['pir']]['get_stock'];
        $this->assertNull($pir['branch_id'], 'the view lists an unstocked product once, without a branch');
        $this->assertEquals(0, $pir['stock_available']);

        DB::table('product_branch')->insert(['product_id' => $this->products['apel'], 'branch_id' => $this->branch, 'price' => 1000, 'created_at' => now(), 'updated_at' => now()]);
        $byBranch = collect($this->getJson('/ajax/listProduct?term=Ledger&branch=' . $this->branch)->assertOk()->json())->keyBy('id');
        $this->assertEquals(12, $byBranch[$this->products['apel']]['get_stock']['stock_available']);
        $this->assertSame((int) $this->branch, (int) $byBranch[$this->products['apel']]['get_stock']['branch_id']);
    }

    public function test_a_long_product_list_shows_a_stock_change_at_once_despite_the_cache(): void
    {
        foreach (range(1, 45) as $i) {
            $this->makeProduct("Panjang {$i}");
        }
        $apel = $this->products['apel'];
        $stockOf = fn () => (float) collect($this->getJson('/ajax/listProduct?term=')->assertOk()->json())
            ->firstWhere('id', $apel)['get_stock']['stock_available'];

        $this->assertEquals(12.0, $stockOf());
        $this->assertEquals(12.0, $stockOf(), 'second read, from the cache');

        $this->giveStock($apel, $this->branch, 5);
        $this->assertEquals(17.0, $stockOf(), 'the new movement is there on the very next read');
    }

    public function test_the_stock_list_shows_a_stock_change_at_once_despite_the_cache(): void
    {
        $url = '/products/stock-data?draw=1&start=0&length=100&stock_filter=ada&search[value]=Apel%20Ledger';
        $stockOf = fn () => (float) collect($this->getJson($url)->assertOk()->json('data'))->firstWhere('id', $this->products['apel'])['stock_raw'];

        $this->assertEquals(12.0, $stockOf());
        $this->assertEquals(12.0, $stockOf(), 'second read, from the cache');

        $this->giveStock($this->products['apel'], $this->branch, 8);
        $this->assertEquals(20.0, $stockOf(), 'the new movement is there on the very next read');
    }
}
