<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'is_service')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_service')->default(false)->after('tipe');
            });
        }

        // Existing "Jasa / Layanan" products become services; a service costs nothing.
        $categoryIds = DB::table('products_category')->where('name', 'Jasa / Layanan')->pluck('id');
        if ($categoryIds->isNotEmpty()) {
            DB::table('products')->whereIn('category_id', $categoryIds)->update(['is_service' => true, 'hpp' => 0]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'is_service')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_service');
            });
        }
    }
};
