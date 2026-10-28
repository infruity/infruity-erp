<?php

namespace Tests\Feature;

use App\Models\RoleMenu;
use App\Models\User as LegacyUser;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPermissionOverridesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'user_permission_test', 'database.connections.user_permission_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);

        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('role', function (Blueprint $table) {
            $table->integer('id_role')->primary();
            $table->string('nm_role')->nullable();
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
        (require database_path('migrations/2026_10_27_000001_create_user_permissions_table.php'))->up();

        DB::table('users')->insert([
            ['id_user' => 100, 'nm_user' => 'Permission Manager', 'email' => 'manager@example.test', 'password' => 'secret', 'is_aktif' => true],
            ['id_user' => 200, 'nm_user' => 'First Account', 'email' => 'first@example.test', 'password' => 'secret', 'is_aktif' => true],
            ['id_user' => 201, 'nm_user' => 'Second Account', 'email' => 'second@example.test', 'password' => 'secret', 'is_aktif' => true],
            ['id_user' => 202, 'nm_user' => 'Administrator Account', 'email' => 'admin@example.test', 'password' => 'secret', 'is_aktif' => true],
        ]);
        DB::table('role')->insert([
            ['id_role' => 1, 'nm_role' => 'Administrator'],
            ['id_role' => 2, 'nm_role' => 'Operator'],
            ['id_role' => 3, 'nm_role' => 'Permission Manager'],
        ]);
        DB::table('role_user')->insert([
            ['id_user' => 100, 'id_role' => 3],
            ['id_user' => 200, 'id_role' => 2],
            ['id_user' => 201, 'id_role' => 2],
            ['id_user' => 202, 'id_role' => 1],
        ]);
        DB::table('role_menu')->insert([
            ['id_role' => 2, 'permission' => 'products.index'],
            ['id_role' => 2, 'permission' => 'stock-out.index'],
            ['id_role' => 3, 'permission' => 'user.update'],
        ]);

        $this->actingAs(User::findOrFail(100));
        session(['role' => ['id_role' => 3]]);
    }

    public function test_permission_overrides_apply_to_one_user_without_changing_the_role_defaults(): void
    {
        $response = $this->putJson('/user/200/permissions', [
            'overrides' => [
                'products.index' => false,
                'products.create' => true,
                'kurir.index' => true,
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $savedOverrides = $response->json('overrides');
        $this->assertSame(false, $savedOverrides['products.index']);
        $this->assertSame(true, $savedOverrides['products.create']);
        $this->assertSame(true, $savedOverrides['kurir.index']);
        $this->assertDatabaseHas('user_permissions', [
            'id_user' => 200,
            'permission' => 'products.index',
            'is_allowed' => 0,
        ]);

        $this->actingAs(User::findOrFail(200));
        session(['role' => ['id_role' => 2]]);
        $this->assertFalse(RoleMenu::checkAccess('products.index'));
        $this->assertTrue(RoleMenu::checkAccess('products.create'));
        $this->assertTrue(RoleMenu::checkAccess('stock-out.index'));

        $this->actingAs(User::findOrFail(201));
        session(['role' => ['id_role' => 2]]);
        $this->assertTrue(RoleMenu::checkAccess('products.index'));
        $this->assertFalse(RoleMenu::checkAccess('products.create'));
    }

    public function test_unknown_permissions_are_rejected_without_writing_an_override(): void
    {
        $this->putJson('/user/200/permissions', [
            'overrides' => ['products.manage-everything' => true],
        ])->assertStatus(422);

        $this->assertDatabaseCount('user_permissions', 0);
    }

    public function test_a_user_cannot_change_their_own_permission_overrides(): void
    {
        $this->putJson('/user/100/permissions', [
            'overrides' => ['user.update' => false],
        ])->assertForbidden();

        $this->assertDatabaseCount('user_permissions', 0);
    }

    public function test_account_permission_changes_require_user_update_access(): void
    {
        $this->actingAs(User::findOrFail(200));
        session(['role' => ['id_role' => 2]]);
        $this->putJson('/user/201/permissions', [
            'overrides' => ['products.create' => true],
        ])->assertForbidden();

        $this->assertDatabaseCount('user_permissions', 0);
    }

    public function test_explicit_overrides_are_applied_even_when_the_account_role_is_administrator(): void
    {
        $this->putJson('/user/202/permissions', [
            'overrides' => ['products.index' => false],
        ])->assertOk();

        $this->actingAs(User::findOrFail(202));
        session(['role' => ['id_role' => 1]]);
        $this->assertFalse(RoleMenu::checkAccess('products.index'));
        $this->assertTrue(RoleMenu::checkAccess('products.create'));
    }

    public function test_permission_overrides_are_loaded_once_for_repeated_checks(): void
    {
        $this->actingAs(User::findOrFail(201));
        session(['role' => ['id_role' => 2]]);
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        RoleMenu::checkAccess('products.index');
        RoleMenu::checkAccess('products.create');
        $permissionQueries = collect(DB::connection()->getQueryLog())
            ->filter(fn ($query) => str_contains($query['query'], 'user_permissions'));
        DB::connection()->disableQueryLog();

        $this->assertCount(1, $permissionQueries);
    }

    public function test_role_detail_route_renders_the_roles_list_with_a_permission_overlay(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
        ]);

        $this->get('/roles/detail/2')
            ->assertOk()
            ->assertSee('data-role-permission-overlay', false)
            ->assertSee('first@example.test')
            ->assertSee('action="http://localhost/role-menu/2"', false)
            ->assertSee('Simpan Hak Akses');
    }

    public function test_permission_checker_supports_the_legacy_user_model(): void
    {
        $this->actingAs(LegacyUser::findOrFail(201));
        session(['role' => ['id_role' => 2]]);

        $this->assertTrue(RoleMenu::checkAccess('products.index'));
        $this->assertFalse(RoleMenu::checkAccess('products.create'));
    }
}
