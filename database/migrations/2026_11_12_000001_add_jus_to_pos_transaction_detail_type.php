<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * POS writes juice lines with type = 'jus' (PosController::saveTransaction, savePayment's stock check), but the
 * table was created with ENUM('product','parcel'). Databases that were altered by hand accept 'jus'; a database
 * built from the migrations stored it as '' (the connection is non-strict), so juice sales lost their marker.
 * Found while rehearsing the legacy import (docs/qa/phase2-import-rehearsal.md). Idempotent: MODIFY to the same
 * definition is a no-op on databases that already have it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE pos_transaction_detail MODIFY `type` ENUM('product','parcel','jus') NOT NULL DEFAULT 'product'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('pos_transaction_detail')->where('type', 'jus')->exists()) {
            throw new RuntimeException("Refusing to drop 'jus' from pos_transaction_detail.type: juice lines exist.");
        }

        DB::statement("ALTER TABLE pos_transaction_detail MODIFY `type` ENUM('product','parcel') NOT NULL DEFAULT 'product'");
    }
};
