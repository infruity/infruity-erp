<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * crm-dashboard/top-distribution grouped customers by district and joined reg_districts (CHAR id) on customer.district
 * (BIGINT): a scan of the region table per customer, 7-12 s on the real data. The join casts now; the numbers must not change.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class CrmTopDistributionTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    public function test_the_top_five_districts_carry_their_names_and_the_rest_is_summed_as_other(): void
    {
        DB::table('customer')->delete();
        $districts = DB::table('reg_districts')->orderBy('id')->limit(7)->get();
        foreach ($districts as $i => $district) {
            for ($n = 0; $n < 7 - $i; $n++) { // 7, 6, 5, 4, 3, 2, 1 customers
                DB::table('customer')->insert(['name' => "Pelanggan {$i}-{$n}", 'district' => (int) $district->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $rows = collect($this->get('/crm-dashboard/top-distribution')->assertOk()->viewData('totalCustomer'));

        $this->assertCount(6, $rows);
        foreach ($rows->take(5)->values() as $i => $row) {
            $this->assertSame($districts[$i]->name, $row->name);
            $this->assertEquals(7 - $i, $row->total);
        }
        $this->assertSame('Other', $rows[5]->name);
        $this->assertEquals(3, $rows[5]->total); // 2 + 1
    }
}
