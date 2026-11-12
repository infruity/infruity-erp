<?php

namespace Tests\Feature;

use App\Support\LedgerCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * LedgerCache keeps ledger-derived results until a ledger row changes. The fingerprint must move for every kind of
 * change (insert, edit, delete) in the tables the stock and HPP views read, so a cached result is never older than
 * the ledger.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class LedgerCacheTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        Cache::flush();
    }

    public function test_the_fingerprint_is_stable_while_nothing_changes(): void
    {
        $this->assertNotNull(LedgerCache::fingerprint());
        $this->assertSame(LedgerCache::fingerprint(), LedgerCache::fingerprint());
    }

    public function test_the_fingerprint_moves_for_an_insert_an_edit_and_a_delete(): void
    {
        $branch = $this->makeBranch('FP');
        $product = $this->makeProduct('Apel FP');
        $before = LedgerCache::fingerprint();

        $this->giveStock($product, $branch, 5); // insert into stock_opname
        $afterInsert = LedgerCache::fingerprint();
        $this->assertNotSame($before, $afterInsert);

        DB::table('stock_opname')->where('product_id', $product)->update(['real_stock' => 6, 'difference' => 6]); // edit
        $afterEdit = LedgerCache::fingerprint();
        $this->assertNotSame($afterInsert, $afterEdit);

        DB::table('products')->where('id', $product)->update(['name' => 'Apel FP renamed']); // the view shows the name
        $afterRename = LedgerCache::fingerprint();
        $this->assertNotSame($afterEdit, $afterRename);

        DB::table('stock_opname')->where('product_id', $product)->delete(); // hard delete
        $this->assertNotSame($afterRename, LedgerCache::fingerprint());
    }

    public function test_remember_computes_once_until_the_ledger_changes(): void
    {
        $branch = $this->makeBranch('RM');
        $product = $this->makeProduct('Jeruk RM');
        $this->giveStock($product, $branch, 4);

        $calls = 0;
        $read = function () use (&$calls, $product, $branch) {
            return LedgerCache::remember('test_stock', function () use (&$calls, $product, $branch) {
                $calls++;

                return (float) DB::table('product_stock')->where('id', $product)->where('branch_id', $branch)->value('stock_available');
            });
        };

        $this->assertEquals(4.0, $read());
        $this->assertEquals(4.0, $read());
        $this->assertSame(1, $calls, 'second read came from the cache');

        $this->giveStock($product, $branch, 3);
        $this->assertEquals(7.0, $read(), 'a ledger change is visible immediately');
        $this->assertSame(2, $calls);
    }

    public function test_the_hpp_read_follows_the_view_after_every_change(): void
    {
        $branch = $this->makeBranch('HP');
        $product = $this->makeProduct('Mangga HP');
        $latest = fn () => LedgerCache::remember('test_latest_hpp', fn () => DB::table('product_hpp')->where('product_id', $product)->orderByDesc('seq')->first());

        $this->giveStock($product, $branch, 10, 8000);
        $first = $latest();
        $this->assertEquals(10, (float) $first->qty_berjalan);

        $this->giveStock($product, $branch, 5, 8000);
        $second = $latest();
        $this->assertEquals(15, (float) $second->qty_berjalan, 'the second movement shows at once');

        $view = DB::table('product_hpp')->where('product_id', $product)->orderByDesc('seq')->first();
        $this->assertEquals($view, $second, 'identical to reading the view directly');
    }
}
