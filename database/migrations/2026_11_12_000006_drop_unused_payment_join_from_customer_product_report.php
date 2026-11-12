<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * get_customer_product_transaction left-joined pos_payment although no payment column is read, so a sale paid in several
 * installments came out once per payment and its quantities were counted several times (real data, one year: 21,900 item
 * rows reported as 24,633). The join is gone; one row per sold item, as the report intends.
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
