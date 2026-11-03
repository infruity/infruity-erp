<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quantity that left the origin branch when the transfer was sent (pending).
 * The stock views take it out of the origin stock from that moment on.
 * Rows without it (transfers sent before this column existed) keep the old
 * rule: origin stock moves on "selesai" with the received quantity.
 *
 * Dated right after the transfer tables on purpose: later migrations run
 * database/sql/*.sql, which reads this column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transfer_detail') && ! Schema::hasColumn('transfer_detail', 'sent_quantity')) {
            Schema::table('transfer_detail', function (Blueprint $table) {
                $table->decimal('sent_quantity', 10, 2)->nullable()->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transfer_detail', 'sent_quantity')) {
            Schema::table('transfer_detail', function (Blueprint $table) {
                $table->dropColumn('sent_quantity');
            });
        }
    }
};
