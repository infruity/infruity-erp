<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds product_hpp as a plain moving average on top of the stock ledger
 * (transaction_stock_base) and report_total_belanja on top of it. Recovered
 * COGS is gone: stock may not go below zero.
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
        // To undo, restore the previous database/sql files from git and run them.
    }
};
