<?php

namespace Tests\Feature;

use App\Support\NegativeStockWarning;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The negative-stock banner of the POS, purchase and stock-opname pages reads the heavy product_stock view, so the
 * result is kept for a minute per set of branches.
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

    public function test_the_answer_is_kept_for_the_ttl_and_refreshed_after_it(): void
    {
        $branch = $this->makeBranch('NEG3');
        $first = $this->makeProduct('Mangga Minus');
        $this->giveStock($first, $branch, -2);
        $this->assertCount(1, NegativeStockWarning::forBranches([$branch]));

        $second = $this->makeProduct('Pir Minus');
        $this->giveStock($second, $branch, -3);
        $this->assertCount(1, NegativeStockWarning::forBranches([$branch]), 'served from the cache within the TTL');
        $this->assertCount(1, NegativeStockWarning::forBranches([(string) $branch, $branch]), 'same branch set, same key');

        Cache::flush();
        $this->assertCount(2, NegativeStockWarning::forBranches([$branch]));
    }
}
