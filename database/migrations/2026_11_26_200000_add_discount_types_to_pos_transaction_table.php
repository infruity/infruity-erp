<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unit the cashier picked for Diskon Nota and Diskon Ongkir. Existing rows stay NULL on purpose: they are
     * read with the old rule (100 or less = percent), so no stored sale changes its figures.
     */
    public function up(): void
    {
        Schema::table('pos_transaction', function (Blueprint $table) {
            $table->string('discount_type', 10)->nullable()->after('discount');
            $table->string('ongkir_discount_type', 10)->nullable()->after('ongkir_discount');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'ongkir_discount_type']);
        });
    }
};
