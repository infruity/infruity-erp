<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_nota', function (Blueprint $table) {
            $table->boolean('is_using_address')->default(true)->after('is_using_invoice_number');
            $table->boolean('is_using_payment_method')->default(true)->after('is_using_address');
            $table->boolean('is_using_phone')->default(true)->after('is_using_payment_method');
            $table->boolean('is_using_social')->default(true)->after('is_using_phone');
        });

        // The receipt always printed these lines, so rows that were never configured keep showing them.
        DB::table('setting_nota')->update([
            'is_using_cashier' => true,
            'is_using_customer' => true,
            'is_using_date' => true,
            'is_using_invoice_number' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('setting_nota', function (Blueprint $table) {
            $table->dropColumn(['is_using_address', 'is_using_payment_method', 'is_using_phone', 'is_using_social']);
        });
    }
};
