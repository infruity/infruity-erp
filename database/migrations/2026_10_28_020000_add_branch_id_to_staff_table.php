<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('staff', 'branch_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedInteger('branch_id')->nullable()->after('position_id');
            $table->index('branch_id', 'staff_branch_id_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('staff', 'branch_id')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('staff_branch_id_index');
            $table->dropColumn('branch_id');
        });
    }
};
