<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * get_customer_product_transaction joins the customer's numeric region ids to the CHAR ids of the reg_* tables. Without a
 * cast the region keys cannot be used and the report takes minutes on the real data (see the migration). The rows must
 * stay the same, and the procedure must keep casting.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class CustomerProductReportRegionJoinTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
    }

    public function test_the_report_resolves_the_region_names(): void
    {
        $village = DB::table('reg_villages')->orderBy('id')->first();
        $district = DB::table('reg_districts')->where('id', $village->district_id)->first();
        $regency = DB::table('reg_regencies')->where('id', $district->regency_id)->first();

        $branch = $this->makeBranch('RPT');
        $product = $this->makeProduct('Apel');
        $customer = DB::table('customer')->insertGetId([
            'name' => 'Pelanggan Laporan', 'city' => (int) $regency->id, 'district' => (int) $district->id, 'village' => (int) $village->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $pos = DB::table('pos_transaction')->insertGetId([
            'uuid' => 'rpt-' . uniqid(), 'customer_id' => $customer, 'date' => '2026-10-27', 'invoice_number' => 'INV-RPT-1',
            'status' => 'paid', 'branch_id' => $branch, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => $pos, 'product_id' => $product, 'quantity' => 2, 'price' => 1000, 'discount' => 0, 'subtotal' => 2000,
            'exp' => 0, 'exp_value' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $rows = DB::select('CALL get_customer_product_transaction(?, ?)', ['2026-10-27', '2026-10-27']);

        $this->assertCount(1, $rows);
        $this->assertSame('Pelanggan Laporan', $rows[0]->nama);
        $this->assertSame($regency->name, $rows[0]->city_name);
        $this->assertSame($district->name, $rows[0]->district_name);
        $this->assertSame($village->name, $rows[0]->village_name);
    }

    public function test_a_sale_paid_in_several_payments_is_reported_once(): void
    {
        $branch = $this->makeBranch('RPT2');
        $product = $this->makeProduct('Jeruk');
        $pos = DB::table('pos_transaction')->insertGetId([
            'uuid' => 'rpt-' . uniqid(), 'customer_id' => null, 'date' => '2026-10-28', 'invoice_number' => 'INV-RPT-2',
            'status' => 'paid', 'branch_id' => $branch, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => $pos, 'product_id' => $product, 'quantity' => 3, 'price' => 1000, 'discount' => 0, 'subtotal' => 3000,
            'exp' => 0, 'exp_value' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([1000, 2000] as $installment) {
            DB::table('pos_payment')->insert([
                'uuid' => 'pay-' . uniqid(), 'pos_id' => $pos, 'total' => $installment, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $rows = DB::select('CALL get_customer_product_transaction(?, ?)', ['2026-10-28', '2026-10-28']);

        $this->assertCount(1, $rows, 'one sold item must give one row, however many payments it has');
        $this->assertEquals(3, $rows[0]->quantity);
    }

    public function test_the_procedure_casts_the_customer_side_so_the_region_keys_are_used(): void
    {
        $definition = (string) DB::selectOne(
            'SELECT routine_definition d FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = ?',
            ['get_customer_product_transaction']
        )->d;

        foreach (['C.city', 'C.district', 'C.village'] as $column) {
            $this->assertMatchesRegularExpression('/CAST\(\s*' . preg_quote($column, '/') . '\s+AS\s+CHAR/i', $definition, $column);
        }
    }
}
