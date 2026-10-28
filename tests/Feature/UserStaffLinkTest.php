<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Master\Entities\Staff;
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
            $table->increments('id_user');
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
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->timestamps();
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
            $table->unique(['id_user', 'id_role']);
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
            ['id_role' => 3, 'permission' => 'user.edit'],
            ['id_role' => 3, 'permission' => 'user.store'],
            ['id_role' => 3, 'permission' => 'role.index'],
        ]);
        DB::table('branch')->insert(['id' => 1, 'name' => 'Cabang Test']);
        DB::table('user_branch')->insert(['user_id' => 2, 'branch_id' => 1]);
        DB::table('user_branch')->insert(['user_id' => 3, 'branch_id' => 1]);

        $this->actingAs(User::findOrFail(1));
        session(['role' => ['id_role' => 3]]);
    }

    public function test_a_staff_record_cannot_be_linked_to_multiple_accounts(): void
    {
        DB::table('users')->where('id_user', 2)->update(['staff_id' => 10]);

        $this->put('/user/3', [
            'link_only' => '1',
            'staff_id' => 10,
        ])->assertSessionHasErrors('staff_id');

        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => 10]);
        $this->assertDatabaseHas('users', ['id_user' => 3, 'staff_id' => null]);
        $this->assertSame(2, Staff::findOrFail(10)->account?->id_user);
    }

    public function test_account_edit_links_or_unlinks_its_single_staff_without_changing_other_account_data(): void
    {
        $this->put('/user/2', [
            'link_only' => '1',
            'staff_id' => 10,
        ])->assertRedirect('/user');

        $this->assertDatabaseHas('users', [
            'id_user' => 2,
            'staff_id' => 10,
            'nm_user' => 'First Account',
            'email' => 'first@example.test',
            'password' => 'secret',
        ]);
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 2]);
        $this->assertDatabaseHas('user_branch', ['user_id' => 2, 'branch_id' => 1]);
        $this->assertSame(2, Staff::findOrFail(10)->account?->id_user);

        $this->put('/user/2', [
            'link_only' => '1',
            'staff_id' => null,
        ])->assertRedirect('/user');

        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => null]);
        $this->assertNull(Staff::findOrFail(10)->fresh()->account);
    }

    public function test_create_can_assign_multiple_roles_to_one_account(): void
    {
        $this->post('/user', [
            'full_name' => 'Multi Role Account',
            'email' => 'multi-role@example.test',
            'password' => 'test-password',
            'role_ids' => [2, 3],
            'id_branch' => [1],
            'staff_id' => null,
        ])->assertRedirect('/user');

        $accountId = (int) DB::table('users')->where('email', 'multi-role@example.test')->value('id_user');
        $this->assertGreaterThan(0, $accountId);
        $this->assertEqualsCanonicalizing([2, 3], DB::table('role_user')->where('id_user', $accountId)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
    }

    public function test_update_replaces_all_roles_for_one_account_without_duplicates(): void
    {
        $this->put('/user/2', [
            'full_name' => 'First Account Updated',
            'email' => 'first@example.test',
            'password' => '',
            'role_ids' => [2, 3],
            'id_branch' => [1],
            'staff_id' => null,
        ])->assertRedirect('/user');

        $this->assertEqualsCanonicalizing([2, 3], DB::table('role_user')->where('id_user', 2)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(2, DB::table('role_user')->where('id_user', 2)->count());
    }

    public function test_link_only_edit_updates_multiple_roles_without_changing_account_details(): void
    {
        $this->put('/user/2', [
            'link_only' => '1',
            'staff_id' => null,
            'role_ids' => [2, 3],
        ])->assertRedirect('/user');

        $this->assertEqualsCanonicalizing([2, 3], DB::table('role_user')->where('id_user', 2)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertDatabaseHas('users', [
            'id_user' => 2,
            'nm_user' => 'First Account',
            'email' => 'first@example.test',
            'password' => 'secret',
        ]);
        $this->assertDatabaseHas('user_branch', ['user_id' => 2, 'branch_id' => 1]);
    }

    public function test_role_index_lists_all_roles_assigned_to_an_account(): void
    {
        DB::table('role_user')->insert(['id_user' => 2, 'id_role' => 3]);

        $this->get('/roles')
            ->assertOk()
            ->assertSee('first@example.test')
            ->assertSee('Operator · User Manager');
    }

    public function test_create_links_staff_using_ids_not_matching_names_or_emails(): void
    {
        $this->post('/user', [
            'full_name' => 'Account Name Different From Employee',
            'email' => 'account@example.test',
            'password' => 'test-password',
            'role_ids' => [2],
            'id_branch' => [1],
            'staff_id' => 10,
        ])->assertRedirect('/user');

        $account = DB::table('users')->where('email', 'account@example.test')->first();
        $this->assertNotNull($account);
        $this->assertDatabaseHas('users', ['id_user' => $account->id_user, 'staff_id' => 10]);
    }

    public function test_account_hydration_reads_the_canonical_staff_id_relationship(): void
    {
        DB::table('users')->where('id_user', 2)->update(['staff_id' => 10]);

        $this->assertSame(10, User::findOrFail(2)->staff?->id);

        $this->get('/user/2/edit')
            ->assertOk()
            ->assertJsonPath('staff.id', 10);

        $this->get('/user')->assertOk()->assertSee('Dwi Febbrianti Robbiatul Adawiyah');
    }

    public function test_staff_account_relation_includes_inactive_account(): void
    {
        DB::table('users')->where('id_user', 3)->update(['is_aktif' => false, 'staff_id' => 10]);

        $this->assertSame(3, Staff::findOrFail(10)->account?->id_user);
    }

    public function test_assigned_roles_can_be_switched_without_allowing_an_unassigned_role(): void
    {
        DB::table('role_user')->insert(['id_user' => 1, 'id_role' => 2]);

        $this->get('/user')
            ->assertOk()
            ->assertSee('Role aktif')
            ->assertSee('Ganti role');

        $this->post('/role/switch', ['role_id' => 2])
            ->assertRedirect();

        $this->assertSame(2, (int) session('role.id_role'));
        $this->assertSame(2, \App\Models\RoleMenu::currentRoleId());

        $this->actingAs(User::findOrFail(2));
        session(['role' => ['id_role' => 2]]);
        $this->post('/role/switch', ['role_id' => 3])->assertForbidden();
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

    public function test_user_index_shows_inactive_accounts_and_their_status(): void
    {
        DB::table('users')->insert([
            'id_user' => 4,
            'nm_user' => 'Former Account',
            'username' => 'former',
            'email' => 'former@example.test',
            'password' => 'secret',
            'is_aktif' => false,
            'staff_id' => null,
        ]);

        $response = $this->get('/user')
            ->assertOk()
            ->assertSee('former@example.test')
            ->assertSee('Nonaktif');

        $response->assertViewHas('users', function ($users) {
            $userIds = $users->getCollection()->pluck('id_user')->all();

            return array_search(3, $userIds, true) < array_search(4, $userIds, true);
        });
    }
}
