<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer', function (Blueprint $table): void {
            $table->string('birth_place')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('customer', function (Blueprint $table): void {
            $table->dropColumn('birth_place');
        });
    }
};
