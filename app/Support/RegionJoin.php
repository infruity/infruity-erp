<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * The reg_* region tables are utf8mb3 while the tables that point at them (supplier, branch, ...) are utf8mb4.
 * Comparing the two columns directly makes MySQL convert the reg_* side, so its PRIMARY key is unusable and every
 * join scans the 84k villages. Converting the pointing side down instead keeps the key in use.
 */
final class RegionJoin
{
    /** A column of the pointing table, converted so it can use the reg_* primary key. */
    public static function column(string $qualifiedColumn): Expression
    {
        // $qualifiedColumn is always a literal column name from the calling code, never user input.
        $wrapped = implode('.', array_map(fn ($part) => '`' . str_replace('`', '', $part) . '`', explode('.', $qualifiedColumn)));

        return DB::raw("CONVERT({$wrapped} USING utf8mb3) COLLATE utf8mb3_unicode_ci");
    }
}
