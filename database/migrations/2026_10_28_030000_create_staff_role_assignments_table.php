<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_role')) {
            Schema::create('staff_role', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('staff_id');
                $table->integer('id_role');
                $table->timestamps();
                $table->unique(['staff_id', 'id_role'], 'staff_role_employee_role_unique');
            });
        }

        if (!Schema::hasTable('staff')
            || !Schema::hasColumn('staff', 'user_id')
            || !Schema::hasTable('users')
            || !Schema::hasTable('role_user')) {
            return;
        }

        $now = now();
        $assignments = DB::table('staff')
            ->join('users', 'users.id_user', '=', 'staff.user_id')
            ->join('role_user', 'role_user.id_user', '=', 'users.id_user')
            ->whereNotNull('staff.user_id')
            ->distinct()
            ->get(['staff.id as staff_id', 'role_user.id_role']);

        foreach ($assignments as $assignment) {
            DB::table('staff_role')->insertOrIgnore([
                'staff_id' => $assignment->staff_id,
                'id_role' => $assignment->id_role,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_role');
    }
};
