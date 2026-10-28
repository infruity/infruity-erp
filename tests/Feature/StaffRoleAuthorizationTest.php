<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffRoleAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'staff_role_authorization_test', 'database.connections.staff_role_authorization_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->integer('staff_id')->nullable();
            $table->boolean('is_aktif')->default(true);
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
        });
        Schema::create('staff', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->date('date_in')->nullable();
            $table->integer('position_id')->nullable();
            $table->integer('branch_id');
            $table->string('status')->default('aktif');
            $table->integer('user_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('staff_role', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('staff_id');
            $table->integer('id_role');
            $table->timestamps();
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });
        Schema::create('position', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });
        DB::table('role')->insert([
            ['id_role' => 1, 'nm_role' => 'Administrator'],
            ['id_role' => 2, 'nm_role' => 'Operator'],
            ['id_role' => 3, 'nm_role' => 'Supervisor'],
        ]);
        DB::table('users')->insert([
            ['id_user' => 1, 'nm_user' => 'Actor', 'staff_id' => 10],
            ['id_user' => 2, 'nm_user' => 'Other Account', 'staff_id' => 20],
            ['id_user' => 3, 'nm_user' => 'Admin', 'staff_id' => null],
        ]);
        DB::table('staff')->insert([
            ['id' => 10, 'name' => 'Actor', 'branch_id' => 1, 'user_id' => 1],
            ['id' => 20, 'name' => 'Other Account', 'branch_id' => 1, 'user_id' => 2],
        ]);
        DB::table('staff_role')->insert([
            ['staff_id' => 10, 'id_role' => 2], ['staff_id' => 20, 'id_role' => 2],
        ]);
        DB::table('role_user')->insert([
            ['id_user' => 1, 'id_role' => 2], ['id_user' => 2, 'id_role' => 2], ['id_user' => 3, 'id_role' => 1],
        ]);
        DB::table('role_menu')->insert([
            ['id_role' => 2, 'permission' => 'staff.update'],
            ['id_role' => 2, 'permission' => 'staff.store'],
        ]);
        DB::table('branch')->insert(['id' => 1, 'name' => 'Fixture Branch']);
        DB::table('position')->insert(['id' => 1, 'name' => 'Fixture Position']);
        DB::table('user_branch')->insert([['user_id' => 1, 'branch_id' => 1], ['user_id' => 2, 'branch_id' => 1]]);
        $this->actingAs(User::findOrFail(1));
        session(['role' => ['id_role' => 2]]);
    }

    private function payload(array $roles = [2]): array
    {
        return ['staff_name' => 'Edited Employee', 'date_in' => '2026-10-29', 'position' => 1, 'branch_id' => 1, 'role_ids' => $roles];
    }

    public function test_ordinary_employee_edits_preserve_roles_without_assignment_permission(): void
    {
        $assignmentId = DB::table('role_user')->where('id_user', 2)->value('id_ru');
        $this->put('/staff/20', $this->payload())->assertRedirect('/staff')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff', ['id' => 20, 'name' => 'Edited Employee']);
        $this->assertDatabaseHas('users', ['id_user' => 2, 'nm_user' => 'Edited Employee']);
        $this->assertDatabaseHas('role_user', ['id_ru' => $assignmentId, 'id_user' => 2, 'id_role' => 2]);
    }

    public function test_delegated_assignment_can_change_normal_roles_for_another_account(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        $this->put('/staff/20', $this->payload([3]))->assertRedirect('/staff')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff_role', ['staff_id' => 20, 'id_role' => 3]);
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 3]);
        $this->assertDatabaseMissing('role_user', ['id_user' => 2, 'id_role' => 2]);
    }

    public function test_assignment_permission_does_not_allow_changing_ones_own_roles(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        $this->put('/staff/10', $this->payload([3]))->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 1, 'id_role' => 2]);
        $this->assertDatabaseMissing('staff_role', ['staff_id' => 10, 'id_role' => 3]);
    }

    public function test_canonical_staff_link_is_protected_even_without_the_compatibility_mirror(): void
    {
        DB::table('users')->where('id_user', 1)->update(['staff_id' => null]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        $this->put('/staff/10', $this->payload([3]))->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 1, 'id_role' => 2]);
    }

    public function test_non_administrator_cannot_remove_an_existing_admin_role(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        DB::table('role_user')->where('id_user', 2)->update(['id_role' => 1]);
        DB::table('staff_role')->where('staff_id', 20)->update(['id_role' => 1]);
        $this->put('/staff/20', $this->payload())->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 1]);
        $this->assertDatabaseHas('staff_role', ['staff_id' => 20, 'id_role' => 1]);
    }

    public function test_administrator_can_assign_administrator_to_another_account(): void
    {
        $this->actingAs(User::findOrFail(3));
        session(['role' => ['id_role' => 1]]);
        $this->put('/staff/20', $this->payload([1]))->assertRedirect('/staff')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 1]);
        $this->assertDatabaseHas('staff_role', ['staff_id' => 20, 'id_role' => 1]);
    }

    public function test_explicit_permission_denial_overrides_an_administrators_assignment_access(): void
    {
        DB::table('user_permissions')->insert(['id_user' => 3, 'permission' => 'staff.assign-roles', 'is_allowed' => false]);
        $this->actingAs(User::findOrFail(3));
        session(['role' => ['id_role' => 1]]);
        $this->put('/staff/20', $this->payload([1]))->assertForbidden();
        $this->assertDatabaseMissing('role_user', ['id_user' => 2, 'id_role' => 1]);
    }

    public function test_unauthorized_edit_cannot_silently_resynchronize_a_linked_accounts_roles(): void
    {
        DB::table('role_user')->where('id_user', 2)->update(['id_role' => 3]);
        $this->put('/staff/20', $this->payload())->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 3]);
        $this->assertDatabaseHas('staff', ['id' => 20, 'name' => 'Other Account']);
    }

    public function test_role_order_only_does_not_require_assignment_permission(): void
    {
        DB::table('role_user')->insert(['id_user' => 2, 'id_role' => 3]);
        DB::table('staff_role')->insert(['staff_id' => 20, 'id_role' => 3]);
        $this->put('/staff/20', $this->payload([3, 2]))->assertRedirect('/staff')->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('role_user')->where('id_user', 2)->count());
    }

    public function test_delegated_creator_cannot_preassign_administrator_to_unlinked_staff(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        $this->post('/staff', $this->payload([1]))->assertForbidden();
        $this->assertSame(2, DB::table('staff')->count());
    }

    public function test_delegated_creator_can_create_staff_with_normal_pending_roles(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        $this->post('/staff', $this->payload([3]))->assertRedirect('/staff')->assertSessionHasNoErrors();
        $staffId = DB::table('staff')->where('name', 'Edited Employee')->value('id');
        $this->assertNotNull($staffId);
        $this->assertDatabaseHas('staff_role', ['staff_id' => $staffId, 'id_role' => 3]);
        $this->assertSame(3, DB::table('users')->count());
    }

    public function test_relinking_another_account_to_admin_staff_requires_an_administrator(): void
    {
        DB::table('role_menu')->insert([
            ['id_role' => 2, 'permission' => 'user.update'],
            ['id_role' => 2, 'permission' => 'staff.assign-roles'],
        ]);
        DB::table('staff')->insert(['id' => 30, 'name' => 'Unlinked Admin', 'branch_id' => 1]);
        DB::table('staff_role')->insert(['staff_id' => 30, 'id_role' => 1]);
        $this->put('/user/2', ['link_only' => '1', 'staff_id' => 30])->assertForbidden();
        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => 20]);
        $this->assertDatabaseHas('staff', ['id' => 20, 'user_id' => 2]);
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 2]);
    }

    public function test_relinking_without_a_role_change_does_not_require_assignment_permission(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'user.update']);
        DB::table('staff')->insert(['id' => 30, 'name' => 'Unlinked Operator', 'branch_id' => 1]);
        DB::table('staff_role')->insert(['staff_id' => 30, 'id_role' => 2]);
        $this->put('/user/2', ['link_only' => '1', 'staff_id' => 30])->assertRedirect('/user');
        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => 30]);
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 2]);
    }

    public function test_relinking_own_account_cannot_adopt_another_staff_members_roles(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'user.update']);
        DB::table('staff')->insert(['id' => 30, 'name' => 'Unlinked Supervisor', 'branch_id' => 1]);
        DB::table('staff_role')->insert(['staff_id' => 30, 'id_role' => 3]);
        $this->put('/user/1', ['link_only' => '1', 'staff_id' => 30])->assertForbidden();
        $this->assertDatabaseHas('users', ['id_user' => 1, 'staff_id' => 10]);
        $this->assertDatabaseHas('staff', ['id' => 10, 'user_id' => 1]);
        $this->assertDatabaseHas('role_user', ['id_user' => 1, 'id_role' => 2]);
        $this->assertDatabaseMissing('role_user', ['id_user' => 1, 'id_role' => 3]);
    }

    public function test_staff_creation_also_requires_role_assignment_permission(): void
    {
        $this->post('/staff', $this->payload())->assertForbidden();
        $this->assertSame(2, DB::table('staff')->count());
        $this->assertSame(2, DB::table('staff_role')->count());
    }

    public function test_delegated_role_assignment_cannot_grant_administrator(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'staff.assign-roles']);
        $this->put('/staff/20', $this->payload([1]))->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 2]);
        $this->assertDatabaseMissing('role_user', ['id_user' => 2, 'id_role' => 1]);
        $this->assertDatabaseHas('staff_role', ['staff_id' => 20, 'id_role' => 2]);
    }

    public function test_changing_another_employees_roles_requires_a_separate_permission(): void
    {
        $this->put('/staff/20', $this->payload([3]))->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 2, 'id_role' => 2]);
        $this->assertDatabaseHas('staff', ['id' => 20, 'name' => 'Other Account']);
        $this->assertDatabaseHas('staff_role', ['staff_id' => 20, 'id_role' => 2]);
    }

    public function test_staff_update_permission_cannot_promote_own_linked_account_to_admin(): void
    {
        $this->put('/staff/10', $this->payload([1]))->assertForbidden();
        $this->assertDatabaseHas('role_user', ['id_user' => 1, 'id_role' => 2]);
        $this->assertDatabaseMissing('role_user', ['id_user' => 1, 'id_role' => 1]);
        $this->assertDatabaseHas('staff', ['id' => 10, 'name' => 'Actor']);
        $this->assertDatabaseHas('staff_role', ['staff_id' => 10, 'id_role' => 2]);
    }
}
