<?php

namespace Tests\Unit;

use App\Support\RegionJoin;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegionJoinTest extends TestCase
{
    public function test_it_converts_the_pointing_column_down_to_the_charset_of_the_region_tables(): void
    {
        $sql = RegionJoin::column('supplier.village')->getValue(DB::connection()->getQueryGrammar());

        $this->assertSame('CONVERT(`supplier`.`village` USING utf8mb3) COLLATE utf8mb3_unicode_ci', $sql);
    }

    public function test_it_strips_backticks_so_a_column_name_cannot_break_out_of_the_expression(): void
    {
        $sql = RegionJoin::column('a.`b`; DROP TABLE x')->getValue(DB::connection()->getQueryGrammar());

        $this->assertStringNotContainsString('``', $sql);
        $this->assertSame(1, substr_count($sql, 'CONVERT('));
    }
}
