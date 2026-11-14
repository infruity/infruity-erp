<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An employee can hold several positions. staff.position_id stays as the first (main) position so the
 * places that still read one position keep working; this table holds the complete list.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_position')) {
            Schema::create('staff_position', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('staff_id');
                $table->unsignedBigInteger('position_id');
                $table->timestamps();
                $table->unique(['staff_id', 'position_id'], 'staff_position_unique');
            });
        }

        if (! Schema::hasTable('staff') || ! Schema::hasColumn('staff', 'position_id')) {
            return;
        }

        $now = now();
        foreach (DB::table('staff')->whereNotNull('position_id')->get(['id', 'position_id']) as $staff) {
            DB::table('staff_position')->insertOrIgnore([
                'staff_id' => $staff->id,
                'position_id' => $staff->position_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_position');
    }
};
