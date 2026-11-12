<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * products.hpp keeps the fractional cost (production / recipe / sortir math multiplies it by quantity); only the
 * screens round. Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class ProductHppFractionTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
    }

    public function test_the_column_is_a_floating_point_type_like_the_legacy_data(): void
    {
        $type = DB::selectOne("SELECT data_type AS t FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'hpp'")->t;

        $this->assertSame('double', $type);
    }

    public function test_a_fractional_cost_is_stored_without_rounding(): void
    {
        $id = $this->makeProduct('Es Buah');

        DB::table('products')->where('id', $id)->update(['hpp' => 5083.3928718449]);

        $this->assertEqualsWithDelta(5083.3928718449, (float) DB::table('products')->where('id', $id)->value('hpp'), 1e-9);
    }

    public function test_quantity_times_cost_is_computed_from_the_fraction(): void
    {
        $id = $this->makeProduct('Salad');
        DB::table('products')->where('id', $id)->update(['hpp' => 6131.2041796034]);

        $total = (float) DB::selectOne('SELECT hpp * 8 AS t FROM products WHERE id = ?', [$id])->t;

        $this->assertEqualsWithDelta(49049.6334368272, $total, 1e-6, 'asset value must come from the unrounded unit cost (a whole-rupiah INT would give 49048)');
    }
}
