<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal auth schema (role, role_user, role_menu, user_permissions, users, user_branch) for tests that run on the
 * in-memory SQLite database, plus a helper that logs in a user with a given role, permissions and branch scope.
 */
trait ActsAsBranchUser
{
    protected function createAuthTables(): void
    {
        foreach (['role', 'role_user', 'role_menu', 'user_permissions', 'users', 'user_branch'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('role', fn (Blueprint $table) => $table->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
        });
        Schema::create('role_menu', function (Blueprint $table) {
            $table->increments('id_rm');
            $table->integer('id_role');
            $table->string('permission');
        });
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_user');
            $table->string('permission', 160);
            $table->boolean('is_allowed');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_aktif')->default(true);
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });
    }

    /**
     * @param  int  $roleId  1 is the administrator (every permission, every branch)
     * @param  list<string>  $permissions  granted to a non-administrator role
     * @param  list<int>  $branchIds  branches of a non-administrator user
     */
    protected function actAsUser(int $userId, int $roleId, array $permissions = [], array $branchIds = []): User
    {
        DB::table('role')->insertOrIgnore(['id_role' => $roleId]);
        DB::table('role_user')->insert(['id_user' => $userId, 'id_role' => $roleId]);
        foreach ($permissions as $permission) {
            DB::table('role_menu')->insert(['id_role' => $roleId, 'permission' => $permission]);
        }
        foreach ($branchIds as $branchId) {
            DB::table('user_branch')->insert(['user_id' => $userId, 'branch_id' => $branchId]);
        }
        DB::table('users')->insert(['id_user' => $userId, 'nm_user' => 'Uji ' . $userId, 'name' => 'Uji ' . $userId, 'is_aktif' => true]);

        $user = new User();
        $user->id_user = $userId;
        $user->nm_user = 'Uji ' . $userId;
        $user->name = 'Uji ' . $userId;
        $user->is_aktif = true;
        $this->actingAs($user);

        return $user;
    }
}
