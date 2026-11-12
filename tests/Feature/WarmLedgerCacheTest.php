<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * `ledger:warm` recomputes the cached ledger reads once the ledger changed, so the next page does not wait; it costs
 * (almost) nothing while nothing changed, and what it stores equals what the views return.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class WarmLedgerCacheTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        Cache::flush();
    }

    private function viewReads(callable $fn): int
    {
        $reads = 0;
        DB::listen(function ($query) use (&$reads) {
            if (preg_match('/from `product_(hpp|stock)`|from `transaction_stock`/', $query->sql)) {
                $reads++;
            }
        });
        $fn();

        return $reads;
    }

    public function test_it_warms_after_a_change_and_does_nothing_when_nothing_changed(): void
    {
        $branch = $this->makeBranch('WRM');
        $product = $this->makeProduct('Apel Warm');
        $this->giveStock($product, $branch, -2);

        $first = $this->viewReads(fn () => Artisan::call('ledger:warm'));
        $this->assertGreaterThan(0, $first, 'the first run computes');

        $this->assertSame(0, $this->viewReads(fn () => Artisan::call('ledger:warm')), 'nothing changed: only the fingerprint is read');

        // What the pages ask for is now served from the cache.
        $this->assertSame(0, $this->viewReads(function () {
            \App\Support\TotalAsetLedger::latestHpp();
            \App\Support\TotalAsetLedger::stockRows(\App\Support\TotalAsetLedger::stockQuery());
        }));

        // A change is seen by the next run.
        $this->giveStock($product, $branch, 10);
        $this->assertGreaterThan(0, $this->viewReads(fn () => Artisan::call('ledger:warm')));
    }

    public function test_the_warmed_values_equal_the_views(): void
    {
        $branch = $this->makeBranch('WRM2');
        $product = $this->makeProduct('Jeruk Warm');
        $this->giveStock($product, $branch, 6, 4000);
        Artisan::call('ledger:warm');

        $warmed = \App\Support\TotalAsetLedger::latestHpp()->get($product);
        $view = DB::table('product_hpp')->where('product_id', $product)->orderByDesc('seq')->first();
        $this->assertEquals((string) $view->hpp_berjalan, (string) $warmed->hpp_berjalan);
        $this->assertEquals((string) $view->total_aset_berjalan, (string) $warmed->total_aset_berjalan);
        $this->assertEquals((int) $view->seq, (int) $warmed->seq);
    }
}
