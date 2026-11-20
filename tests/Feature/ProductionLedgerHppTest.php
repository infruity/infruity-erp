<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The production form must price ingredients with the ledger's moving HPP (what posting freezes),
 * not the products.hpp cache. Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class ProductionLedgerHppTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // saveTransaction() calls DB::disconnect(), so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('PRODHPP');
        $this->productId = $this->makeProduct('Mangga', 25000);
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);

        $this->postJson('/wholesale/save-transaction', [
            'branch_id'      => $this->branch,
            'date'           => date('Y-m-d'),
            'invoice_number' => null,
            'items'          => [['id' => $this->productId, 'price' => 10000, 'qty' => 10, 'total_input' => 100000]],
            'subtotal'       => 100000,
            'total'          => 100000,
            'status'         => 'posting',
        ])->assertOk();

        // The catalog copy lags behind the ledger.
        DB::table('products')->where('id', $this->productId)->update(['hpp' => 99999]);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function listedHpp(array $query): float
    {
        $rows = $this->getJson('/ajax/getProduct?' . http_build_query($query + ['search' => 'Mangga']))->assertOk()->json();
        $row = collect($rows)->firstWhere('id', $this->productId);
        $this->assertNotNull($row, 'the test product must be listed');

        return (float) $row['hpp'];
    }

    public function test_ledger_flag_returns_the_moving_hpp_instead_of_the_stale_cache(): void
    {
        $this->assertEqualsWithDelta(10000.0, $this->listedHpp(['ledger_hpp' => 1]), 0.01);
    }

    public function test_without_the_flag_other_screens_keep_the_catalog_value(): void
    {
        $this->assertEqualsWithDelta(99999.0, $this->listedHpp([]), 0.01);
    }
}
