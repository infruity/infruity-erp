<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * stock_opname_history.note was VARCHAR(255), but the adjustment reason is validated up to 1000 characters and the
 * count note has no limit at all. MySQL silently cut anything longer (one cut note exists in the legacy data) and, in
 * strict SQL mode, would answer with a 500 instead. The column must hold what the form accepts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE stock_opname_history MODIFY `note` TEXT NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('stock_opname_history')->whereRaw('CHAR_LENGTH(note) > 255')->exists()) {
            throw new RuntimeException('Refusing to shorten stock_opname_history.note: longer notes exist.');
        }

        DB::statement('ALTER TABLE stock_opname_history MODIFY `note` VARCHAR(255) NOT NULL');
    }
};
