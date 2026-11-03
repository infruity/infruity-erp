<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HPP per unit of a production run, frozen when it is posted:
 * (sum of ingredient HPP x qty) / qty produced + service cost per unit.
 *
 * Dated right after create_production_table on purpose: older migrations
 * build the stock views from the current database/sql files, and
 * product_hpp.sql reads this column, so a fresh install needs it first.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('production', 'hpp_per_unit')) {
            Schema::table('production', function (Blueprint $table) {
                $table->decimal('hpp_per_unit', 15, 3)->nullable()->after('service_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production', 'hpp_per_unit')) {
            Schema::table('production', function (Blueprint $table) {
                $table->dropColumn('hpp_per_unit');
            });
        }
    }
};
