<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Transaction\Http\Controllers\StockOpnameController;
use Tests\TestCase;

final class StockOpnameListFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Every assertion runs against an in-memory SQLite database, never business data.
        config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('sqlite');
        $this->createSchema();
        $this->seedRecords();

        $user = new User();
        $user->id_user = 7;
        $user->nm_user = 'QA User';
        $this->actingAs($user);
        Route::get('/_test/stock-opname-data', [StockOpnameController::class, 'get_data']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        DB::purge('sqlite');
        parent::tearDown();
    }

    private function createSchema(): void
    {
        Schema::create('role', fn (Blueprint $t) => $t->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $t) {
            $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role');
        });
        Schema::create('role_menu', function (Blueprint $t) {
            $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission');
        });
        Schema::create('user_permissions', function (Blueprint $t) {
            $t->increments('id'); $t->integer('id_user'); $t->string('permission'); $t->boolean('is_allowed');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->integer('id_user')->primary(); $t->string('nm_user');
        });
        Schema::create('branch', function (Blueprint $t) {
            $t->increments('id'); $t->string('name');
        });
        Schema::create('user_branch', function (Blueprint $t) {
            $t->integer('user_id'); $t->integer('branch_id');
        });
        Schema::create('product_units', function (Blueprint $t) {
            $t->increments('id'); $t->string('abbreviation');
        });
        Schema::create('products', function (Blueprint $t) {
            $t->increments('id'); $t->string('name'); $t->integer('product_unit')->nullable();
        });
        Schema::create('product_stock', function (Blueprint $t) {
            $t->integer('id'); $t->integer('branch_id'); $t->decimal('stock_available', 15, 3); $t->decimal('avg_hpp', 15, 2);
        });
        Schema::create('stock_opname', function (Blueprint $t) {
            $t->increments('id'); $t->string('code'); $t->date('date'); $t->integer('branch_id'); $t->integer('product_id');
            $t->decimal('stock', 10, 2); $t->decimal('real_stock', 10, 2); $t->decimal('difference', 10, 2);
            $t->integer('avg_price')->default(0); $t->text('details')->nullable(); $t->string('status');
            $t->integer('created_by')->nullable(); $t->integer('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('stock_opname_discussions', function (Blueprint $t) {
            $t->increments('id'); $t->integer('stock_opname_id');
        });
        Schema::create('stock_opname_history', function (Blueprint $t) {
            $t->increments('id'); $t->integer('stock_opname_id');
        });
    }

    private function seedRecords(): void
    {
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'stock-opname.index']);
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'QA User']);
        DB::table('branch')->insert([['id' => 1, 'name' => 'GKB'], ['id' => 2, 'name' => 'GBA']]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('product_units')->insert(['id' => 1, 'abbreviation' => 'Kg']);
        DB::table('products')->insert([
            ['id' => 11, 'name' => 'Product QA', 'product_unit' => 1],
            ['id' => 12, 'name' => 'Product Sub-Decimal', 'product_unit' => 1],
            ['id' => 13, 'name' => 'Product Half-Up', 'product_unit' => 1],
            ['id' => 14, 'name' => 'Product Half-Down', 'product_unit' => 1],
        ]);
        DB::table('product_stock')->insert([
            ['id' => 11, 'branch_id' => 1, 'stock_available' => 10, 'avg_hpp' => 5000],
            ['id' => 11, 'branch_id' => 2, 'stock_available' => 10, 'avg_hpp' => 5000],
            ['id' => 12, 'branch_id' => 1, 'stock_available' => 9.996, 'avg_hpp' => 5000],
            ['id' => 13, 'branch_id' => 1, 'stock_available' => 12.345, 'avg_hpp' => 5000],
            ['id' => 14, 'branch_id' => 1, 'stock_available' => 12.345, 'avg_hpp' => 5000],
        ]);

        $this->insertOpname(1, 1, 'pending', '2026-11-03', 8, 12, 4);   // live: +2
        $this->insertOpname(2, 1, 'pending', '2026-11-03', 4, 5, 1);    // live: -5
        $this->insertOpname(3, 1, 'pending', '2026-11-03', 9, 10, 1);   // live: 0
        $this->insertOpname(4, 1, 'selesai', '2026-11-03', 10, 12, 2); // saved: +2
        $this->insertOpname(5, 1, 'selesai', '2026-11-03', 10, 5, -5); // saved: -5
        $this->insertOpname(6, 1, 'selesai', '2026-11-03', 10, 10, 0);// saved: 0
        $this->insertOpname(7, 2, 'selesai', '2026-11-03', 10, 13, 3);// outside user's branch
        $this->insertOpname(8, 1, 'selesai', '2026-12-01', 10, 13, 3);// outside date range
        // 10.00 - 9.996 = 0.004 renders as "0", so it belongs to "Sesuai", not "Lebih". Every
        // stock_opname column stays at its 2-decimal scale; the 3-decimal value lives in live stock.
        $this->insertOpname(9, 1, 'pending', '2026-11-03', 10, 10, 0.00, 12);
        // Exact half-cent ties in both directions. `stock` stays at the column's 2 decimals so the
        // fixture means the same thing on MySQL; pending rows read live stock from product_stock.
        $this->insertOpname(10, 1, 'pending', '2026-11-03', 12.35, 12.35, 0.01, 13);
        $this->insertOpname(11, 1, 'pending', '2026-11-03', 12.35, 12.34, -0.01, 14);
    }

    private function insertOpname(int $id, int $branchId, string $status, string $date, float $stock, float $real, float $difference, int $productId = 11): void
    {
        DB::table('stock_opname')->insert([
            'id' => $id, 'code' => 'SO-QA-' . $id, 'date' => $date, 'branch_id' => $branchId, 'product_id' => $productId,
            'stock' => $stock, 'real_stock' => $real, 'difference' => $difference, 'status' => $status,
            'created_by' => 7, 'created_at' => $date . ' 12:00:00', 'updated_at' => $date . ' 12:00:00',
        ]);
    }

    private function fetchRows(array $filters = []): array
    {
        $query = http_build_query(array_merge(['draw' => 1, 'start' => 0, 'length' => 100], $filters));
        $response = $this->getJson('/_test/stock-opname-data?' . $query);
        $response->assertOk();

        return collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    public function test_result_filters_match_the_displayed_pending_and_completed_differences(): void
    {
        $this->assertSame([1, 4, 8, 10], $this->fetchRows(['result_filter' => 'lebih']));
        $this->assertSame([2, 5, 11], $this->fetchRows(['result_filter' => 'kurang']));
        // Row 9 differs by 0.004 on a 3-decimal live stock; the row renders "0", so must match here.
        $this->assertSame([3, 6, 9], $this->fetchRows(['result_filter' => 'sesuai']));
    }

    public function test_status_filters_combine_with_result_branch_and_date_filters(): void
    {
        $this->assertSame([1, 2, 3, 9, 10, 11], $this->fetchRows(['status_filter' => 'draft']));
        $this->assertSame([4, 5, 6, 8], $this->fetchRows(['status_filter' => 'selesai']));
        $this->assertSame([1, 10], $this->fetchRows([
            'status_filter' => 'draft', 'result_filter' => 'lebih', 'cabang_filter' => '1',
            'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
        ]));
    }

    /**
     * The invariant the user actually sees: whatever bucket a rendered difference implies, the
     * filter must return exactly those rows. This is the assertion the first patch failed.
     */
    public function test_filtered_buckets_match_each_row_rendered_difference(): void
    {
        $query = http_build_query(['draw' => 1, 'start' => 0, 'length' => 100]);
        $rows = $this->getJson('/_test/stock-opname-data?' . $query)->assertOk()->json('data');

        $bucket = ['lebih' => [], 'kurang' => [], 'sesuai' => []];
        foreach ($rows as $row) {
            $diff = (float) $row['display_difference'];
            $bucket[$diff > 0 ? 'lebih' : ($diff < 0 ? 'kurang' : 'sesuai')][] = (int) $row['id'];
        }

        // Half-cent ties round away from zero on both sides, matching the server-side filter.
        $this->assertContains(10, $bucket['lebih']);
        $this->assertContains(11, $bucket['kurang']);

        foreach ($bucket as $key => $expected) {
            sort($expected);
            $this->assertSame($expected, $this->fetchRows(['result_filter' => $key]), "Bucket {$key} contradicts the rendered difference");
        }
    }

    public function test_unknown_result_or_status_filter_values_are_rejected(): void
    {
        $this->getJson('/_test/stock-opname-data?draw=1&start=0&length=100&result_filter=unknown')->assertUnprocessable();
        $this->getJson('/_test/stock-opname-data?draw=1&start=0&length=100&status_filter=unknown')->assertUnprocessable();
    }
}
