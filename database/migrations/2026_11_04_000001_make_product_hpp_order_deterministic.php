<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds product_hpp with a full tie-breaker in its ledger order. Rows of
 * one note (variants, same time, same transaction) were ordered arbitrarily,
 * so their running qty/aset columns could swap between queries. Final HPP and
 * totals do not change.
 */
return new class extends Migration
{
    private array $files = [
        'sql/product_hpp.sql',
        'sql/report_total_belanja.sql',
    ];

    public function up(): void
    {
        foreach ($this->files as $file) {
            $sql = file_get_contents(database_path($file));

            foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
                DB::statement($query);
            }
        }
    }

    public function down(): void
    {
        // The previous order was not deterministic, there is nothing to restore.
    }
};
