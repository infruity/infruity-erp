<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branch', function (Blueprint $table): void {
            $table->char('province', 2)->nullable()->after('address');
            $table->char('city', 4)->nullable()->after('province');
            $table->char('district', 6)->nullable()->after('city');
            $table->char('village', 10)->nullable()->after('district');
            $table->string('map_link', 2048)->nullable()->after('village');
        });
    }

    public function down(): void
    {
        Schema::table('branch', function (Blueprint $table): void {
            $table->dropColumn(['province', 'city', 'district', 'village', 'map_link']);
        });
    }
};
