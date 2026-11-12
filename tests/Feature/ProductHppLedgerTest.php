<?php

namespace Tests\Feature;

use App\Support\LedgerSql;
use App\Support\ProductHpp;
use App\Support\ProductionCost;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Transaction\Entities\Production;
use Tests\TestCase;

/**
 * Moving-average HPP ledger (database/sql/product_hpp.sql), checked against
 * the "Copy of Prototipe V.2" sheet.
 *
 * product_hpp is a MySQL view, so this test needs a real MySQL database:
 *   HPP_LEDGER_TEST_DATABASE=infruity_hpp_test php -d memory_limit=512M vendor/bin/phpunit --filter=ProductHppLedgerTest
 * The database is rebuilt with migrate:fresh, so its name must end in _test.
 * The fresh migration loads the large wilayah SQL file, hence the memory limit.
 */
class ProductHppLedgerTest extends TestCase
{
    private const APEL = 9001;
    private const SALAD = 9002;
    private const JERUK = 9003;
    private const APEL_GRADE_A = 9004;

    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) env('HPP_LEDGER_TEST_DATABASE', '');
        if ($database === '') {
            $this->markTestSkipped('Set HPP_LEDGER_TEST_DATABASE to a MySQL database ending in _test.');
        }
        if (! str_ends_with($database, '_test')) {
            $this->fail("Refusing to rebuild {$database}: the name must end in _test.");
        }

        config([
            'database.connections.hpp_ledger' => array_merge(config('database.connections.mysql'), ['database' => $database]),
            'database.default' => 'hpp_ledger',
        ]);
        DB::purge('hpp_ledger');

        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true, '--database' => 'hpp_ledger']);
            self::$migrated = true;
        }

        DB::beginTransaction();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('products')->insert([
            ['id' => self::APEL, 'name' => 'Apel', 'hpp' => 0],
            ['id' => self::SALAD, 'name' => 'Salad', 'hpp' => 0],
            ['id' => self::JERUK, 'name' => 'Jeruk', 'hpp' => 0],
            ['id' => self::APEL_GRADE_A, 'name' => 'Apel Grade A', 'hpp' => 0],
        ]);
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_sheet_example_matches_moving_average(): void
    {
        $this->pengadaan(1, self::APEL, 50, 20000, '10:00');
        $this->penjualan(1, self::APEL, 25, '10:01');
        $this->pengadaan(2, self::APEL, 50, 25000, '10:02');
        $this->produksi(1, self::SALAD, 1, [self::APEL => 25], '10:03', hppPerUnit: 583333.333);
        $this->pengadaan(3, self::APEL, 50, 25000, '10:04');
        $this->penjualan(2, self::APEL, 99, '10:05');
        $this->opname(1, self::APEL, -1, '10:06');
        $this->opname(2, self::APEL, 1, '10:07');

        $rows = $this->ledger(self::APEL);

        $this->assertSame(
            ['pengadaan', 'penjualan', 'pengadaan', 'bahan_produksi', 'pengadaan', 'penjualan', 'opname', 'opname'],
            array_column($rows, 'trx_type')
        );
        $this->assertEqualsWithDelta([20000, 20000, 23333.33, 23333.33, 24166.67, 24166.67, 24166.67, 24166.67],
            array_map(fn ($r) => (float) $r['hpp_berjalan'], $rows), 0.01);
        $this->assertEqualsWithDelta([50, 25, 75, 50, 100, 1, 0, 1],
            array_map(fn ($r) => (float) $r['qty_berjalan'], $rows), 0.0001);

        // COGS only on sales, valued at the moving HPP.
        $this->assertEqualsWithDelta(500000, (float) $rows[1]['cogs'], 0.01);
        $this->assertEqualsWithDelta(2392500, (float) $rows[5]['cogs'], 0.01);
        $this->assertEqualsWithDelta(0, (float) $rows[3]['cogs'], 0.01);
        $this->assertEqualsWithDelta(583333.33, (float) $rows[3]['nilai_keluar'], 0.01);

        // Saldo 0 keeps the last HPP, opname + is valued at it.
        $this->assertEqualsWithDelta(0, (float) $rows[6]['total_aset_berjalan'], 0.0001);
        $this->assertEqualsWithDelta(24166.67, (float) $rows[7]['total_aset_berjalan'], 0.01);

        $report = DB::table('report_total_belanja')->where('product_id', self::APEL)->first();
        $this->assertEqualsWithDelta(3500000, (float) $report->total_belanja, 0.01);
        $this->assertEqualsWithDelta(2892500, (float) $report->total_cogs, 0.01);
        $this->assertEqualsWithDelta(583333.33, (float) $report->total_bahan_produksi, 0.01);
        $this->assertEqualsWithDelta(0, (float) $report->total_opname, 0.01);
        $this->assertEqualsWithDelta(0, (float) $report->selisih_control, 0.0001);
    }

    public function test_purchase_after_stock_hits_zero_takes_the_purchase_price(): void
    {
        $this->pengadaan(1, self::APEL, 10, 1000, '10:00');
        $this->penjualan(1, self::APEL, 10, '10:01');
        $this->pengadaan(2, self::APEL, 5, 3000, '10:02');

        $rows = $this->ledger(self::APEL);

        $this->assertEqualsWithDelta(1000, (float) $rows[1]['hpp_berjalan'], 0.0001);
        $this->assertEqualsWithDelta(3000, (float) $rows[2]['hpp_berjalan'], 0.0001);
        $this->assertEqualsWithDelta(15000, (float) $rows[2]['total_aset_berjalan'], 0.0001);
    }

    public function test_purchase_goes_first_when_timestamps_match(): void
    {
        $this->penjualan(1, self::JERUK, 4, '12:00');
        $this->pengadaan(5, self::JERUK, 10, 1000, '12:00');

        $rows = $this->ledger(self::JERUK);

        $this->assertSame(['pengadaan', 'penjualan'], array_column($rows, 'trx_type'));
        $this->assertEqualsWithDelta(4000, (float) $rows[1]['cogs'], 0.0001);
        $this->assertEqualsWithDelta(6, (float) $rows[1]['qty_berjalan'], 0.0001);
    }

    public function test_draft_documents_are_not_in_the_ledger(): void
    {
        $this->pengadaan(1, self::APEL, 10, 1000, '10:00');
        $this->pengadaan(2, self::APEL, 999, 1, '10:01', status: 'draft');
        $this->produksi(1, self::SALAD, 1, [self::APEL => 5], '10:02', status: 'draft', hppPerUnit: 5000);

        $this->assertCount(1, $this->ledger(self::APEL));
        $this->assertCount(0, $this->ledger(self::SALAD));
    }

    public function test_variants_roll_up_to_their_parent(): void
    {
        DB::table('product_child')->insert(['parent_id' => self::APEL, 'product_id' => self::APEL_GRADE_A]);

        $this->pengadaan(1, self::APEL, 10, 1000, '10:00');
        $this->pengadaan(2, self::APEL_GRADE_A, 10, 3000, '10:01');

        $rows = $this->ledger(self::APEL);
        $this->assertCount(2, $rows);
        $this->assertEqualsWithDelta(2000, (float) $rows[1]['hpp_berjalan'], 0.0001);
        $this->assertSame(2000.0, ProductHpp::current([self::APEL_GRADE_A])[self::APEL_GRADE_A]);
    }

    public function test_production_hpp_is_frozen_with_service_cost(): void
    {
        $this->pengadaan(1, self::APEL, 10, 1000, '10:00');
        $production = $this->produksi(1, self::SALAD, 2, [self::APEL => 4], '10:01', serviceCost: 500);

        // (4 x 1.000) / 2 + 500 jasa per unit
        $this->assertEqualsWithDelta(2500, ProductionCost::freeze($production, 500), 0.0001);

        // A pricier purchase of the ingredient later does not touch the posted run.
        $this->pengadaan(2, self::APEL, 6, 9000, '10:02');

        $salad = $this->ledger(self::SALAD);
        $this->assertCount(1, $salad);
        $this->assertEqualsWithDelta(2500, (float) $salad[0]['hpp_berjalan'], 0.0001);
        $this->assertEqualsWithDelta(5000, (float) $salad[0]['total_aset_berjalan'], 0.0001);
        $this->assertEqualsWithDelta(2500, (float) DB::table('products')->where('id', self::SALAD)->value('hpp'), 0.0001);
    }

    public function test_variants_of_one_note_keep_a_fixed_order(): void
    {
        DB::table('product_child')->insert(['parent_id' => self::APEL, 'product_id' => self::APEL_GRADE_A]);

        $this->pengadaan(1, self::APEL, 10, 1000, '10:00');
        // One note, same time: the parent and its variant share every other sort key.
        $this->penjualan(1, self::APEL_GRADE_A, 3, '10:01');
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => 1, 'product_id' => self::APEL, 'quantity' => 2, 'price' => 0, 'discount' => 0,
            'subtotal' => 0, 'exp' => 0, 'exp_value' => 0,
        ]);

        $rows = $this->ledger(self::APEL);
        $this->assertEqualsWithDelta([10, 8, 5], array_map(fn ($r) => (float) $r['qty_berjalan'], $rows), 0.0001);
    }

    public function test_per_product_queries_match_the_views(): void
    {
        DB::table('product_child')->insert(['parent_id' => self::APEL, 'product_id' => self::APEL_GRADE_A]);
        DB::table('products')->whereIn('id', [self::APEL, self::SALAD, self::JERUK, self::APEL_GRADE_A])->update(['tipe' => 'product']);

        $this->pengadaan(1, self::APEL, 50, 20000, '10:00');
        $this->pengadaan(2, self::APEL_GRADE_A, 10, 30000, '10:01');
        $this->penjualan(1, self::APEL_GRADE_A, 4, '10:02');
        $this->produksi(1, self::SALAD, 1, [self::APEL => 5], '10:03', hppPerUnit: 100000);
        $this->opname(1, self::APEL, -1, '10:04');

        // More than 20 products: MySQL 8.4 returned wrong rows for bound ids here.
        $ids = [self::APEL, self::SALAD, self::JERUK, self::APEL_GRADE_A];
        for ($i = 0; $i < 24; $i++) {
            $id = 9100 + $i;
            $ids[] = $id;
            DB::table('products')->insert(['id' => $id, 'name' => 'Buah ' . $i, 'hpp' => 0, 'tipe' => 'product']);
            $this->pengadaan(100 + $i, $id, 10 + $i, 1000 + $i, '11:00');
            $this->penjualan(100 + $i, $id, 1 + $i % 5, '11:01');
        }

        $normalize = fn ($rows) => array_map(fn ($row) => array_map(
            fn ($v) => is_numeric($v) ? round((float) $v, 6) : $v, (array) $row
        ), $rows);

        $view = DB::table('product_hpp')->whereIn('product_id', $ids)->orderBy('product_id')->orderBy('seq')->get()->all();
        $ledger = LedgerSql::hpp($ids)->orderBy('product_id')->orderBy('seq')->get()->map->getAttributes()->all();
        $this->assertGreaterThan(50, count($view));
        $this->assertSame($normalize($view), $normalize($ledger));

        $single = LedgerSql::hpp([self::APEL])->orderBy('seq')->get()->map->getAttributes()->all();
        $this->assertSame($normalize($this->ledger(self::APEL)), $normalize($single));

        $columns = ['id', 'branch_id', 'parent_stock', 'child_consumed', 'stock_available', 'avg_hpp', 'child', 'stock_status'];
        $stockView = DB::table('product_stock')->whereIn('id', $ids)->orderBy('id')->orderBy('branch_id')->get($columns)->all();
        $stockLedger = LedgerSql::stock($ids)->orderBy('id')->orderBy('branch_id')->get($columns)->map->getAttributes()->all();
        $this->assertNotEmpty($stockView);
        $this->assertSame($normalize($stockView), $normalize($stockLedger));
    }

    private function ledger(int $productId): array
    {
        return DB::table('product_hpp')->where('product_id', $productId)->orderBy('seq')->get()
            ->map(fn ($row) => (array) $row)->all();
    }

    private function at(string $time): string
    {
        return '2026-11-01 ' . $time . ':00';
    }

    private function pengadaan(int $id, int $productId, float $qty, float $price, string $time, string $status = 'posting'): void
    {
        DB::table('wholesale')->insert([
            'id' => $id, 'uuid' => 'w' . $id, 'branch_id' => 1, 'order_number' => 'W' . $id, 'supplier_id' => 1,
            'order_date' => '2026-11-01', 'status' => $status, 'created_at' => $this->at($time),
        ]);
        DB::table('wholesale_product')->insert([
            'wholesale_id' => $id, 'category_id' => 1, 'product_id' => $productId, 'quantity' => $qty, 'price' => $price,
        ]);
    }

    private function penjualan(int $id, int $productId, float $qty, string $time): void
    {
        DB::table('pos_transaction')->insert([
            'id' => $id, 'uuid' => 'p' . $id, 'branch_id' => 1, 'status' => 'paid', 'created_at' => $this->at($time),
        ]);
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => $id, 'product_id' => $productId, 'quantity' => $qty, 'price' => 0, 'discount' => 0,
            'subtotal' => 0, 'exp' => 0, 'exp_value' => 0,
        ]);
    }

    private function produksi(
        int $id,
        int $productId,
        float $qty,
        array $ingredients,
        string $time,
        string $status = 'posting',
        ?float $hppPerUnit = null,
        float $serviceCost = 0,
    ): Production {
        DB::table('production')->insert([
            'id' => $id, 'production_number' => 'PRO' . $id, 'product_id' => $productId, 'quantity' => $qty,
            'status' => $status, 'branch_id' => 1, 'service_cost' => $serviceCost, 'hpp_per_unit' => $hppPerUnit,
            'created_at' => $this->at($time),
        ]);
        foreach ($ingredients as $ingredientId => $ingredientQty) {
            DB::table('production_detail')->insert([
                'production_id' => $id, 'product_id' => $ingredientId, 'quantity' => $ingredientQty,
            ]);
        }

        return Production::findOrFail($id);
    }

    private function opname(int $id, int $productId, float $difference, string $time): void
    {
        DB::table('stock_opname')->insert([
            'id' => $id, 'code' => 'O' . $id, 'date' => '2026-11-01', 'branch_id' => 1, 'product_id' => $productId,
            'stock' => 0, 'real_stock' => 0, 'difference' => $difference, 'avg_price' => 99999,
            'status' => 'selesai', 'created_at' => $this->at($time),
        ]);
    }
}
