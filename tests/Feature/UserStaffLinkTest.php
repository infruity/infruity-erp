<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserStaffLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'user_staff_link_test', 'database.connections.user_staff_link_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);

        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->unsignedBigInteger('staff_id')->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('aktif');
        });
        Schema::create('role', function (Blueprint $table) {
            $table->integer('id_role')->primary();
            $table->string('nm_role');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
            $table->timestamps();
        });
        Schema::create('role_menu', function (Blueprint $table) {
            $table->increments('id_rm');
            $table->integer('id_role');
            $table->string('permission');
        });
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_user');
            $table->string('permission');
            $table->boolean('is_allowed');
            $table->timestamps();
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });

        DB::table('users')->insert([
            ['id_user' => 1, 'nm_user' => 'Manager', 'username' => 'manager', 'email' => 'manager@example.test', 'password' => 'secret', 'is_aktif' => true],
            ['id_user' => 2, 'nm_user' => 'First Account', 'username' => 'first', 'email' => 'first@example.test', 'password' => 'secret', 'is_aktif' => true],
            ['id_user' => 3, 'nm_user' => 'Second Account', 'username' => 'second', 'email' => 'second@example.test', 'password' => 'secret', 'is_aktif' => true],
        ]);
        DB::table('staff')->insert([
            ['id' => 10, 'name' => 'Dwi Febbrianti Robbiatul Adawiyah', 'status' => 'aktif'],
        ]);
        DB::table('role')->insert([
            ['id_role' => 2, 'nm_role' => 'Operator'],
            ['id_role' => 3, 'nm_role' => 'User Manager'],
        ]);
        DB::table('role_user')->insert([
            ['id_user' => 1, 'id_role' => 3],
            ['id_user' => 2, 'id_role' => 2],
            ['id_user' => 3, 'id_role' => 2],
        ]);
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'user.index'],
            ['id_role' => 3, 'permission' => 'user.update'],
        ]);
        DB::table('branch')->insert(['id' => 1, 'name' => 'Cabang Test']);
        DB::table('user_branch')->insert(['user_id' => 2, 'branch_id' => 1]);
        DB::table('user_branch')->insert(['user_id' => 3, 'branch_id' => 1]);

        $this->actingAs(User::findOrFail(1));
        session(['role' => ['id_role' => 3]]);
    }

    public function test_account_edit_persists_a_staff_link_and_rejects_a_staff_linked_to_another_account(): void
    {
        $this->put('/user/2', [
            'full_name' => 'First Account',
            'email' => 'first@example.test',
            'password' => '',
            'id_role' => 2,
            'id_branch' => [1],
            'staff_id' => 10,
        ])->assertRedirect('/user');

        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => 10]);

        $this->put('/user/3', [
            'full_name' => 'Second Account',
            'email' => 'second@example.test',
            'password' => '',
            'id_role' => 2,
            'id_branch' => [1],
            'staff_id' => 10,
        ])->assertSessionHasErrors('staff_id');

        $this->assertDatabaseHas('users', ['id_user' => 3, 'staff_id' => null]);
    }

    public function test_user_index_renders_searchable_staff_link_options(): void
    {
        $this->get('/user')
            ->assertOk()
            ->assertSee('Tautkan ke Karyawan')
            ->assertSee('Cari karyawan...')
            ->assertSee('Dwi Febbrianti Robbiatul Adawiyah')
            ->assertSee('staff_id');
    }
}
