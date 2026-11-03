<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the stock views so a transfer with sent_quantity takes stock out of
 * the origin branch as soon as it is sent (pending / proses). The destination
 * still receives on "selesai" with the received quantity, the difference is
 * shrinkage. Transfers without sent_quantity keep the old rule, so current
 * stock (and past stock opname adjustments) do not change.
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
        // To undo the rule, restore the previous database/sql files from git
        // and run them. Rolling back the sent_quantity column alone is not
        // possible while these views read it.
    }
};
