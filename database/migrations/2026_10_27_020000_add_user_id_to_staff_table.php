<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->id_user]);
            });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
