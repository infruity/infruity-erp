<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parcels of one budget share one product, so ingredients tied only to the product could not be told apart: reopening
 * a draft gave every such line all of them, and saving it again took that stock twice. Adds a nullable column; no row
 * changes. Must run BEFORE the new code is uploaded, which writes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('production_parcel_detail', 'pos_detail_id')) {
            Schema::table('production_parcel_detail', fn (Blueprint $table) => $table->unsignedBigInteger('pos_detail_id')->nullable()->after('pos_id'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_parcel_detail', 'pos_detail_id')) {
            Schema::table('production_parcel_detail', fn (Blueprint $table) => $table->dropColumn('pos_detail_id'));
        }
    }
};
