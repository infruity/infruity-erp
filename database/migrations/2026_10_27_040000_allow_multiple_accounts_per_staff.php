<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_staff_id_unique');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->index('staff_id');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropUnique('staff_user_id_unique');
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        $hasSharedStaffLinks = DB::table('users')
            ->select('staff_id')
            ->whereNotNull('staff_id')
            ->groupBy('staff_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasSharedStaffLinks) {
            throw new RuntimeException('Cannot restore one-to-one account–staff links while multiple accounts share a staff record.');
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->unique();
        });

        DB::table('users')
            ->whereNotNull('staff_id')
            ->orderBy('id_user')
            ->get(['id_user', 'staff_id'])
            ->each(function ($user) {
                DB::table('staff')
                    ->where('id', $user->staff_id)
                    ->update(['user_id' => $user->id_user]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_staff_id_index');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unique('staff_id');
        });
    }
};
