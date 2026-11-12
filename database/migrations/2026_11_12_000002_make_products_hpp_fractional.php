<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * products.hpp holds the cost per unit that the HPP ledger and the production / recipe / sortir math multiply by
 * quantity (valuation of production rows without hpp_per_unit reads it). It was created as INT, but the application
 * writes the fractional moving average (ProductHpp::syncCatalog writes the ledger value as a float) and the legacy
 * database keeps it as DOUBLE. As INT every write is rounded to the whole rupiah, which silently shifts those
 * calculations. Keep the fraction in the database; screens round only for display.
 * Found while rehearsing the legacy import (docs/qa/phase2-import-rehearsal.md, D4).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE products MODIFY `hpp` DOUBLE NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (DB::table('products')->whereRaw('hpp <> ROUND(hpp)')->exists()) {
            throw new RuntimeException('Refusing to turn products.hpp back into INT: fractional costs exist and would be rounded.');
        }

        DB::statement('ALTER TABLE products MODIFY `hpp` INT NULL');
    }
};
