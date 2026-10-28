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
            $table->integer('id_role', true);
            $table->string('nm_role', 60);
            $table->text('description')->nullable();
            $table->integer('id_creator');
            $table->timestamps();
            $table->softDeletes();
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
            ['id_role' => 1, 'nm_role' => 'Administrator', 'id_creator' => 1],
            ['id_role' => 2, 'nm_role' => 'Operator', 'id_creator' => 1],
            ['id_role' => 3, 'nm_role' => 'Permission Manager', 'id_creator' => 1],
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

    public function test_hak_akses_lists_roles_instead_of_user_accounts(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
        ]);

        $this->get('/roles')
            ->assertOk()
            ->assertSee('Administrator')
            ->assertSee('Operator')
            ->assertSee('Permission Manager')
            ->assertSee('Menampilkan 3 dari 3 role')
            ->assertDontSee('first@example.test')
            ->assertDontSee('second@example.test');
    }

    public function test_hak_akses_search_filters_by_role_name(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
        ]);

        $this->get('/roles?q=Operator')
            ->assertOk()
            ->assertSee('Operator')
            ->assertSee('ROLE-0002')
            ->assertDontSee('ROLE-0001')
            ->assertDontSee('ROLE-0003');
    }

    public function test_role_creation_form_accepts_digits_and_hyphens(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.store'],
        ]);

        $this->get('/roles')
            ->assertOk()
            ->assertSee('pattern="[A-Za-z0-9 -]+"', false);
    }

    public function test_role_creation_request_accepts_digits_and_hyphens(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.store'],
        ]);

        $this->from('/roles')->post('/roles', [
            'nm_role' => 'Test1',
            'description' => 'Role untuk pengujian',
        ])->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Data role Disimpan');

        $this->assertDatabaseHas('role', [
            'nm_role' => 'Test1',
            'description' => 'Role untuk pengujian',
        ]);
    }

    public function test_role_detail_shows_edit_and_delete_actions_before_the_permission_overlay(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
            ['id_role' => 3, 'permission' => 'role-menu.update'],
            ['id_role' => 3, 'permission' => 'role.destroy'],
        ]);

        $this->get('/roles/detail/2')
            ->assertOk()
            ->assertSee('Operator')
            ->assertDontSee('first@example.test')
            ->assertSee('data-role-detail-overlay', false)
            ->assertSee('data-role-permission-edit', false)
            ->assertSee('data-role-delete-action', false)
            ->assertSee('permissionOpen: false', false)
            ->assertSee('action="http://localhost/role-menu/2"', false);
    }

    public function test_role_index_prepares_same_page_detail_navigation(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
        ]);

        $this->get('/roles')
            ->assertOk()
            ->assertSee('data-role-detail-mount', false)
            ->assertSee('data-role-detail-link', false)
            ->assertSee('href="http://localhost/roles/detail/2"', false)
            ->assertSee('@click="navigateRoleDetail($event, $el.href)"', false);
    }

    public function test_role_detail_request_can_return_a_modal_fragment_for_async_navigation(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
            ['id_role' => 3, 'permission' => 'role-menu.update'],
            ['id_role' => 3, 'permission' => 'role.destroy'],
        ]);

        $this->get('/roles/detail/2', ['X-Role-Modal' => 'true'])
            ->assertOk()
            ->assertSee('data-role-modal-fragment', false)
            ->assertSee('data-role-detail-overlay', false)
            ->assertSee('data-role-permission-overlay', false)
            ->assertDontSee('<html', false);
    }

    public function test_role_detail_keeps_parent_popup_and_mobile_nav_create_action(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
            ['id_role' => 3, 'permission' => 'role.store'],
        ]);

        $this->get('/roles/detail/2')
            ->assertOk()
            ->assertSee('data-role-detail-overlay', false)
            ->assertSee('data-role-detail-panel class="relative z-10 flex max-h-[calc(100dvh-2rem)] w-full max-w-sm flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"', false)
            ->assertSee('class="absolute inset-0 bg-black/60"', false)
            ->assertSee('href="http://localhost/roles?create=1" aria-label="Tambah" title="Tambah" class="erp-mobile-nav__add"', false);
    }

    public function test_role_detail_popup_uses_white_surfaces(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
            ['id_role' => 3, 'permission' => 'role-menu.update'],
            ['id_role' => 3, 'permission' => 'role.destroy'],
        ]);

        $this->get('/roles/detail/1')
            ->assertOk()
            ->assertSee('data-role-detail-panel class="relative z-10 flex max-h-[calc(100dvh-2rem)] w-full max-w-sm flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"', false)
            ->assertSee('data-role-detail-summary class="space-y-4 overflow-y-auto bg-white px-5 py-5"', false)
            ->assertSee('data-role-permission-edit', false)
            ->assertSee('data-role-delete-action', false);
    }

    public function test_mobile_topbar_component_uses_opaque_white_background(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
        ]);

        $this->get('/roles')
            ->assertOk()
            ->assertSee('.erp-topbar-mobile {', false)
            ->assertSee('border-bottom:1px solid rgba(255,255,255,.6); background:#fff; box-shadow:', false);
    }

    public function test_administrator_role_delete_action_is_visible_but_disabled(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'role.index'],
            ['id_role' => 3, 'permission' => 'role.show'],
            ['id_role' => 3, 'permission' => 'role-menu.update'],
            ['id_role' => 3, 'permission' => 'role.destroy'],
        ]);

        $this->get('/roles/detail/1')
            ->assertOk()
            ->assertSee('data-role-delete-action disabled', false)
            ->assertSee('data-role-permission-edit', false);
    }

    public function test_permission_checker_supports_the_legacy_user_model(): void
    {
        $this->actingAs(LegacyUser::findOrFail(201));
        session(['role' => ['id_role' => 2]]);

        $this->assertTrue(RoleMenu::checkAccess('products.index'));
        $this->assertFalse(RoleMenu::checkAccess('products.create'));
    }
}
