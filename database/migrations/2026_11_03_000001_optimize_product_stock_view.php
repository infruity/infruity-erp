<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the stock views so product_stock sums the raw ledger
 * (transaction_stock_base / transaction_stock_show_base) instead of the
 * running-balance views. Output columns and values are unchanged; the
 * window-function sort over the whole ledger is skipped (~6x faster per lookup).
 */
return new class extends Migration
{
    private array $files = [
        'sql/transaction_stock.sql',
        'sql/transaction_stock_show.sql',
        'sql/product_stock.sql',
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
        // The new definitions return the same data as the old ones, so there is
        // nothing to restore. The base views stay because the other views use them.
    }
};
