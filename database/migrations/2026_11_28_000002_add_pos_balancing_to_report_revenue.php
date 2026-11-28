<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * POS balancing counts in revenue. Re-creates the branch and customer report procedures from
 * database/sql/function.sql, which must be uploaded first. No table or row is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $functions = File::get(database_path('sql/function.sql'));
        $definitions = [];
        foreach (['get_branch_report', 'get_customer_report'] as $name) {
            if (! preg_match("/DROP PROCEDURE IF EXISTS {$name};.*?(?=DROP PROCEDURE IF EXISTS|\z)/s", $functions, $match)
                || ! str_contains($match[0], 'pos_balancing')) {
                // An older function.sql would silently put the previous definition back.
                throw new RuntimeException("The new {$name} is not in database/sql/function.sql. Upload that file first.");
            }
            $definitions[] = $match[0];
        }

        foreach ($definitions as $definition) {
            DB::unprepared($definition);
        }
    }

    public function down(): void
    {
        // Going back means uploading the previous function.sql and re-creating the two procedures from it.
    }
};
