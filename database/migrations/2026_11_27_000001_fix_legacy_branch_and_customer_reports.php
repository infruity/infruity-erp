<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * The old branch and customer reports counted a sale once per payment, counted drafts, and added the unit HPP without
 * the quantity. Their definitions live in database/sql (loaded by the last migration on a fresh install); this
 * re-creates them on databases that already ran it. No table or row is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $functions = File::get(database_path('sql/function.sql'));
        foreach (['get_branch_report', 'get_customer_report'] as $name) {
            if (! preg_match("/DROP PROCEDURE IF EXISTS {$name};.*?(?=DROP PROCEDURE IF EXISTS|\z)/s", $functions, $match)) {
                throw new RuntimeException("{$name} not found in database/sql/function.sql");
            }
            DB::unprepared($match[0]);
        }

        if (! preg_match('/CREATE OR REPLACE VIEW vw_customer_report AS.*?;/s', File::get(database_path('sql/views.sql')), $view)) {
            throw new RuntimeException('vw_customer_report not found in database/sql/views.sql');
        }
        DB::unprepared($view[0]);
    }

    public function down(): void
    {
        // The previous definitions reported wrong figures; there is nothing worth restoring.
    }
};
