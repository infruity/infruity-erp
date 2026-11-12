<?php

namespace Tests\Feature;

use App\Support\NegativeStockWarning;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The negative-stock banner of the POS, purchase and stock-opname pages reads the heavy product_stock view, so the
 * result is kept until the ledger changes (LedgerCache).
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class NegativeStockWarningTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        Cache::flush();
    }

    public function test_it_lists_products_below_zero_in_the_asked_branches_only(): void
    {
        $branch = $this->makeBranch('NEG1');
        $other = $this->makeBranch('NEG2');
        $short = $this->makeProduct('Apel Minus');
        $fine = $this->makeProduct('Jeruk Cukup');
        $this->giveStock($short, $branch, -4);
        $this->giveStock($fine, $branch, 9);
        $this->giveStock($short, $other, -1);

        $rows = NegativeStockWarning::forBranches([$branch]);

        $this->assertSame([$short], $rows->pluck('id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame((int) $branch, (int) $rows->first()->branch_id);
        $this->assertNotNull($rows->first()->branch_name);
    }

    public function test_the_answer_is_kept_until_the_ledger_changes_and_is_never_stale(): void
    {
        $branch = $this->makeBranch('NEG3');
        $first = $this->makeProduct('Mangga Minus');
        $this->giveStock($first, $branch, -2);

        $reads = 0;
        DB::listen(function ($query) use (&$reads) {
            if (str_contains($query->sql, 'from `product_stock`')) {
                $reads++;
            }
        });

        $this->assertCount(1, NegativeStockWarning::forBranches([$branch]));
        $this->assertCount(1, NegativeStockWarning::forBranches([(string) $branch, $branch]), 'same branch set, same key');
        $this->assertSame(1, $reads, 'the second answer came from the cache');

        // A new stock movement changes the answer at once, with no waiting time.
        $second = $this->makeProduct('Pir Minus');
        $this->giveStock($second, $branch, -3);
        $this->assertCount(2, NegativeStockWarning::forBranches([$branch]));

        // Covering the shortage shows at once too.
        $this->giveStock($first, $branch, 5);
        $this->assertSame([$second], NegativeStockWarning::forBranches([$branch])->pluck('id')->map(fn ($id) => (int) $id)->all());
    }
}
