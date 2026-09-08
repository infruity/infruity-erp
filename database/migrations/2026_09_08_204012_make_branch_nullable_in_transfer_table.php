<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfer', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->change();
            $table->unsignedBigInteger('branch_destination_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            $table->unsignedBigInteger('branch_destination_id')->nullable(false)->change();
        });
    }
};
