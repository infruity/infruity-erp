<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * These columns were created NOT NULL without a default, but the application does not fill them:
 *  - wholesale.branch_id   : opening the purchase form saves a draft before any branch is chosen. The code treats a
 *                            draft without a branch as NULL (whereNull('branch_id')), but the column forced 0.
 *  - wholesale.supplier_id : unused (0 in every legacy row).
 *  - wholesale.uuid        : unused ('' in every legacy row).
 *  - wholesale_product.category_id : unused (0 in every legacy row).
 * The connection is non-strict, so MySQL silently stored 0 / '' for all of them. That is how a draft without a branch
 * got branch 0 and was then refused to its own creator (found in the manual QA run and the legacy import rehearsal).
 * "No value" is NULL.
 */
return new class extends Migration
{
    private const COLUMNS = [
        ['wholesale', 'branch_id', 'BIGINT UNSIGNED', 0],
        ['wholesale', 'supplier_id', 'BIGINT UNSIGNED', 0],
        ['wholesale', 'uuid', 'TEXT', ''],
        ['wholesale_product', 'category_id', 'BIGINT UNSIGNED', 0],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::COLUMNS as [$table, $column, $type]) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$type} NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::COLUMNS as [$table, $column, $type, $empty]) {
            DB::table($table)->whereNull($column)->update([$column => $empty]);
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$type} NOT NULL");
        }
    }
};
