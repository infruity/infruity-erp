<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * get_customer_product_transaction joined customer.city/district/village (BIGINT) to reg_*.id (CHAR). MySQL and MariaDB
 * then compare numerically and cannot use the primary key of the region tables: one month of the report took 104 s on
 * the real data (83,761 villages scanned per customer row) and a year hit the 120 s statement limit of the shared
 * hosting. The procedure now casts the customer side to CHAR, so the keys are used. Same rows, same columns.
 *
 * The procedure lives in database/sql/function.sql (which the last migration loads on a fresh install); this re-creates
 * just this one on databases that already ran it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(database_path('sql/function.sql'));

        if (! preg_match('/DROP PROCEDURE IF EXISTS get_customer_product_transaction;.*?(?=DROP PROCEDURE IF EXISTS|\z)/s', $sql, $m)) {
            throw new RuntimeException('get_customer_product_transaction not found in database/sql/function.sql');
        }

        DB::unprepared($m[0]);
    }

    public function down(): void
    {
        // The previous definition produced the same rows; nothing to restore.
    }
};
