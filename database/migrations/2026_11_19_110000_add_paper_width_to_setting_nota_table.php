<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_nota', function (Blueprint $table) {
            $table->string('paper_width', 2)->default('80')->after('is_using_social');
        });
    }

    public function down(): void
    {
        Schema::table('setting_nota', function (Blueprint $table) {
            $table->dropColumn('paper_width');
        });
    }
};
