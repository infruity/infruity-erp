<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            $table->string('nickname')->nullable();
            $table->date('date_in')->nullable();
            $table->string('gender')->nullable();
            $table->string('nik')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
        Schema::create('staff_role', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('staff_id');
            $table->integer('id_role');
            $table->timestamps();
            $table->unique(['staff_id', 'id_role']);
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
        Schema::create('position', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id')->nullable();
        });
        Schema::create('department', function (Blueprint $table) {
            $table->id();
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
            ['id' => 10, 'name' => 'Dwi Febbrianti Robbiatul Adawiyah', 'branch_id' => 1, 'status' => 'aktif'],
        ]);
        DB::table('role')->insert([
            ['id_role' => 2, 'nm_role' => 'Operator'],
            ['id_role' => 3, 'nm_role' => 'User Manager'],
        ]);
        DB::table('staff_role')->insert(['staff_id' => 10, 'id_role' => 2]);
        DB::table('role_user')->insert([
            ['id_user' => 1, 'id_role' => 3],
            ['id_user' => 2, 'id_role' => 2],
            ['id_user' => 3, 'id_role' => 2],
        ]);
        DB::table('role_menu')->insert([
            ['id_role' => 3, 'permission' => 'user.index'],
            ['id_role' => 3, 'permission' => 'user.create'],
            ['id_role' => 3, 'permission' => 'user.update'],
            ['id_role' => 3, 'permission' => 'user.edit'],
            ['id_role' => 3, 'permission' => 'user.store'],
            ['id_role' => 3, 'permission' => 'staff.store'],
            ['id_role' => 3, 'permission' => 'staff.create'],
            ['id_role' => 3, 'permission' => 'staff.index'],
            ['id_role' => 3, 'permission' => 'staff.edit'],
            ['id_role' => 3, 'permission' => 'staff.update'],
            ['id_role' => 3, 'permission' => 'staff.assign-roles'],
            ['id_role' => 3, 'permission' => 'role.index'],
        ]);
        DB::table('branch')->insert(['id' => 1, 'name' => 'Cabang Test']);
        DB::table('position')->insert(['id' => 1, 'name' => 'Operator', 'department_id' => 1]);
        DB::table('department')->insert(['id' => 1, 'name' => 'Operasional']);
        DB::table('user_branch')->insert(['user_id' => 1, 'branch_id' => 1]);
        DB::table('user_branch')->insert(['user_id' => 2, 'branch_id' => 1]);
        DB::table('user_branch')->insert(['user_id' => 3, 'branch_id' => 1]);

        $this->actingAs(User::findOrFail(1));
        session(['role' => ['id_role' => 3]]);
    }

    public function test_staff_create_opens_as_an_overlay_with_only_reference_employee_fields(): void
    {
        $this->get('/staff/create')->assertRedirect('/staff?create=1');

        $this->get('/staff?create=1')
            ->assertOk()
            ->assertSee('data-staff-create-overlay', false)
            ->assertSee('name="staff_name"', false)
            ->assertSee('name="date_in"', false)
            ->assertSee('name="position"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="role_ids[]"', false)
            ->assertDontSee('name="create_account"', false)
            ->assertDontSee('name="account_email"', false)
            ->assertDontSee('name="nickname"', false)
            ->assertDontSee('name="nik"', false)
            ->assertDontSee('name="contact"', false)
            ->assertDontSee('name="gender"', false)
            ->assertDontSee('name="department"', false)
            ->assertDontSee('name="description"', false)
            ->assertDontSee('name="id_branch[]"', false);
    }

    public function test_staff_create_overlay_is_teleported_above_the_shell_header(): void
    {
        $source = file_get_contents(base_path('Modules/Master/Resources/views/staff/create-overlay.blade.php'));
        $teleportStart = strpos($source, '<template x-teleport="body">');
        $overlayStart = strpos($source, 'data-staff-create-overlay');
        $teleportEnd = $teleportStart === false ? false : strpos($source, '</template>', $teleportStart);

        $this->assertNotFalse($teleportStart);
        $this->assertNotFalse($overlayStart);
        $this->assertNotFalse($teleportEnd);
        $this->assertTrue($teleportStart < $overlayStart && $overlayStart < $teleportEnd);
    }

    public function test_staff_edit_route_renders_the_list_with_a_prefilled_edit_overlay(): void
    {
        $response = $this->get('/staff/10/edit');
        $response
            ->assertOk()
            ->assertSee('data-staff-edit-overlay', false)
            ->assertSee('id="staff_edit_name"', false)
            ->assertSee('id="staff_edit_date_in"', false)
            ->assertSee('id="staff_edit_date_in" name="date_in" value=""', false)
            ->assertSee('data-staff-form', false)
            ->assertSee('Edit Karyawan')
            ->assertSee('value="Dwi Febbrianti Robbiatul Adawiyah"', false)
            ->assertSee('name="staff_name"', false)
            ->assertSee('name="date_in"', false)
            ->assertSee('name="position"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="role_ids[]"', false)
            ->assertDontSee('Kembali ke karyawan')
            ->assertDontSee('name="nickname"', false)
            ->assertDontSee('name="nik"', false)
            ->assertDontSee('name="contact"', false)
            ->assertDontSee('name="gender"', false)
            ->assertDontSee('name="department"', false)
            ->assertDontSee('name="description"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="avatar"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'id="staff_name"'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="staff_edit_name"'));
    }

    public function test_staff_edit_overlay_hides_the_linked_account_summary(): void
    {
        DB::table('users')->where('id_user', 2)->update(['staff_id' => 10]);

        $this->get('/staff/10/edit')
            ->assertOk()
            ->assertDontSee('id="staff-account-heading"', false)
            ->assertDontSee('Akun tertaut:')
            ->assertDontSee('first@example.test')
            ->assertDontSee('Kelola tautan akun');
    }

    public function test_staff_edit_overlay_fragment_renders_the_real_update_form(): void
    {
        $this->get('/staff/10/edit?overlay=1')
            ->assertOk()
            ->assertSee('id="staff-edit-form"', false)
            ->assertSee('id="staff_edit_name"', false)
            ->assertSee('id="staff_edit_date_in"', false)
            ->assertSee('action="http://localhost/staff/10"', false)
            ->assertSee('name="staff_name"', false)
            ->assertSee('name="date_in"', false)
            ->assertSee('name="position"', false)
            ->assertSee('name="branch_id"', false)
            ->assertSee('name="role_ids[]"', false)
            ->assertDontSee('data-staff-edit-overlay', false)
            ->assertDontSee('Kembali ke karyawan');
    }

    public function test_staff_creation_persists_the_selected_position_date_and_branch(): void
    {
        $this->post('/staff', [
            'staff_name' => 'New Employee',
            'date_in' => '2026-10-28',
            'position' => 1,
            'branch_id' => 1,
            'role_ids' => [2, 3],
        ])->assertRedirect('/staff');

        $staff = DB::table('staff')->where('name', 'New Employee')->first();
        $this->assertNotNull($staff);
        $this->assertSame('2026-10-28', $staff->date_in);
        $this->assertSame(1, (int) $staff->position_id);
        $this->assertSame(1, (int) $staff->branch_id);
        $this->assertSame('Cabang Test', Staff::findOrFail($staff->id)->branch?->name);
        $this->assertEqualsCanonicalizing([2, 3], DB::table('staff_role')->where('staff_id', $staff->id)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
    }

    public function test_staff_creation_requires_at_least_one_access_role(): void
    {
        $this->from('/staff?create=1')->post('/staff', [
            'staff_name' => 'Employee Without Role',
            'date_in' => '2026-10-28',
            'position' => 1,
            'branch_id' => 1,
        ])->assertRedirect('/staff?create=1')
            ->assertSessionHasErrors(['role_ids']);

        $this->assertDatabaseMissing('staff', ['name' => 'Employee Without Role']);
    }

    public function test_staff_update_preserves_legacy_fields_not_shown_in_the_new_form(): void
    {
        DB::table('staff')->where('id', 10)->update([
            'nickname' => 'Legacy Nickname',
            'nik' => '1234567890123456',
            'contact' => '081234567890',
            'email' => 'legacy@example.test',
            'gender' => 'female',
            'description' => 'Legacy notes',
        ]);

        $this->put('/staff/10', [
            'staff_name' => 'Updated Employee',
            'date_in' => '2025-02-03',
            'position' => 1,
            'branch_id' => 1,
            'role_ids' => [2],
        ])->assertRedirect('/staff');

        $this->assertDatabaseHas('staff', [
            'id' => 10,
            'name' => 'Updated Employee',
            'nickname' => 'Legacy Nickname',
            'nik' => '1234567890123456',
            'contact' => '081234567890',
            'email' => 'legacy@example.test',
            'gender' => 'female',
            'description' => 'Legacy notes',
        ]);
    }

    public function test_staff_creation_does_not_provision_an_account_from_account_fields(): void
    {
        $this->post('/staff', [
            'staff_name' => 'Employee Account Name',
            'date_in' => '2026-01-10',
            'position' => 1,
            'branch_id' => 1,
            'create_account' => '1',
            'account_email_type' => 'custom',
            'account_email' => 'employee-login@example.test',
            'account_password' => 'test-password',
            'role_ids' => [2, 3],
        ])->assertRedirect('/staff');

        $staff = DB::table('staff')->where('name', 'Employee Account Name')->first();
        $this->assertNotNull($staff);
        $this->assertDatabaseMissing('users', ['email' => 'employee-login@example.test']);
    }

    public function test_staff_name_edits_keep_the_linked_account_name_in_sync(): void
    {
        DB::table('users')->where('id_user', 2)->update(['staff_id' => 10]);

        $this->put('/staff/10', [
            'staff_name' => 'Updated Employee Name',
            'date_in' => '2026-01-10',
            'position' => 1,
            'branch_id' => 1,
            'role_ids' => [2],
        ])->assertRedirect('/staff');

        $this->assertDatabaseHas('users', ['id_user' => 2, 'nm_user' => 'Updated Employee Name']);
    }

    public function test_staff_role_updates_sync_to_a_linked_account_and_branch(): void
    {
        DB::table('users')->where('id_user', 2)->update(['staff_id' => 10]);
        DB::table('staff')->where('id', 10)->update(['user_id' => 2]);

        $this->put('/staff/10', [
            'staff_name' => 'Updated Employee Name',
            'date_in' => '2026-01-10',
            'position' => 1,
            'branch_id' => 1,
            'role_ids' => [2, 3],
        ])->assertRedirect('/staff');

        $this->assertEqualsCanonicalizing([2, 3], DB::table('staff_role')->where('staff_id', 10)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertEqualsCanonicalizing([2, 3], DB::table('role_user')->where('id_user', 2)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertDatabaseHas('user_branch', ['user_id' => 2, 'branch_id' => 1]);
        $this->assertDatabaseHas('users', ['id_user' => 2, 'nm_user' => 'Updated Employee Name']);
    }

    public function test_staff_edit_does_not_offer_account_provisioning_controls(): void
    {
        $this->get('/staff/10/edit')
            ->assertOk()
            ->assertDontSee('name="create_account"', false)
            ->assertDontSee('name="account_email"', false)
            ->assertDontSee('name="account_password"', false)
            ->assertSee('name="role_ids[]"', false);
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
            'nm_user' => 'Dwi Febbrianti Robbiatul Adawiyah',
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

    public function test_user_create_route_opens_the_reference_add_account_drawer(): void
    {
        $this->get('/user/create')->assertRedirect('/user?create=1');

        $this->get('/user?create=1')
            ->assertOk()
            ->assertSee('user-account-drawer-panel', false)
            ->assertSee('Tambah Akun', false)
            ->assertSee('name="email"', false)
            ->assertSee('Tautkan ke Karyawan')
            ->assertDontSee('Nama, cabang, dan Hak Akses akun mengikuti data karyawan.')
            ->assertSee('name="staff_id"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('name="full_name"', false)
            ->assertDontSee('name="role_ids[]"', false)
            ->assertDontSee('name="id_branch[]"', false);
    }

    public function test_account_creation_on_user_page_creates_and_links_the_account(): void
    {
        $this->post('/user', [
            'email' => 'new-user@example.test',
            'password' => 'test-password',
            'staff_id' => 10,
        ])->assertRedirect('/user');

        $account = DB::table('users')->where('email', 'new-user@example.test')->first();
        $this->assertNotNull($account);
        $this->assertSame('Dwi Febbrianti Robbiatul Adawiyah', $account->nm_user);
        $this->assertSame(10, (int) $account->staff_id);
        $this->assertTrue(Hash::check('test-password', $account->password));
        $this->assertSame((int) $account->id_user, (int) DB::table('staff')->where('id', 10)->value('user_id'));
        $this->assertEqualsCanonicalizing([2], DB::table('role_user')->where('id_user', $account->id_user)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertDatabaseHas('user_branch', ['user_id' => $account->id_user, 'branch_id' => 1]);
    }

    public function test_account_creation_cannot_adopt_an_administrator_role_for_a_nonadmin_actor(): void
    {
        DB::table('role')->insert(['id_role' => 1, 'nm_role' => 'Administrator']);
        DB::table('staff_role')->where('staff_id', 10)->update(['id_role' => 1]);

        $this->post('/user', [
            'email' => 'unauthorized-admin@example.test',
            'password' => 'fixture-password',
            'staff_id' => 10,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized-admin@example.test']);
        $this->assertDatabaseHas('staff', ['id' => 10, 'user_id' => null]);
    }

    public function test_account_creation_requires_assignment_permission_when_adopting_pending_roles(): void
    {
        DB::table('role_menu')->where('id_role', 3)->where('permission', 'staff.assign-roles')->delete();

        $this->post('/user', [
            'email' => 'unauthorized-role@example.test',
            'password' => 'fixture-password',
            'staff_id' => 10,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized-role@example.test']);
        $this->assertDatabaseHas('staff', ['id' => 10, 'user_id' => null]);
    }

    public function test_account_creation_requires_a_karyawan_link(): void
    {
        $this->from('/user?create=1')->post('/user', [
            'email' => 'unlinked@example.test',
            'password' => 'test-password',
        ])->assertRedirect('/user?create=1')
            ->assertSessionHasErrors(['staff_id']);

        $this->assertDatabaseMissing('users', ['email' => 'unlinked@example.test']);
    }

    public function test_account_creation_rejects_karyawan_without_branch_or_pending_role(): void
    {
        DB::table('staff')->insert(['id' => 11, 'name' => 'Incomplete Employee', 'status' => 'aktif']);

        $this->from('/user?create=1')->post('/user', [
            'email' => 'incomplete@example.test',
            'password' => 'test-password',
            'staff_id' => 11,
        ])->assertRedirect('/user?create=1')
            ->assertSessionHasErrors(['staff_id']);

        $this->assertDatabaseMissing('users', ['email' => 'incomplete@example.test']);
    }

    public function test_link_only_edit_changes_only_the_employee_link(): void
    {
        $this->put('/user/2', [
            'link_only' => '1',
            'staff_id' => 10,
            'role_ids' => [2, 3],
        ])->assertRedirect('/user');

        $this->assertEqualsCanonicalizing([2], DB::table('role_user')->where('id_user', 2)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertDatabaseHas('users', [
            'id_user' => 2,
            'staff_id' => 10,
            'nm_user' => 'Dwi Febbrianti Robbiatul Adawiyah',
            'email' => 'first@example.test',
            'password' => 'secret',
        ]);
        $this->assertDatabaseHas('user_branch', ['user_id' => 2, 'branch_id' => 1]);
    }

    public function test_user_update_rejects_account_changes_outside_the_link_form(): void
    {
        $this->from('/user')->put('/user/2', [
            'full_name' => 'Changed Outside Staff Form',
            'email' => 'changed-outside-staff@example.test',
            'password' => 'changed-password',
            'role_ids' => [3],
            'id_branch' => [1],
            'staff_id' => null,
        ])->assertRedirect('/user')
            ->assertSessionHasErrors(['link_only']);

        $this->assertDatabaseHas('users', [
            'id_user' => 2,
            'nm_user' => 'First Account',
            'email' => 'first@example.test',
            'password' => 'secret',
            'staff_id' => null,
        ]);
        $this->assertSame([2], DB::table('role_user')->where('id_user', 2)->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        $this->assertDatabaseHas('user_branch', ['user_id' => 2, 'branch_id' => 1]);
    }

    public function test_link_update_requires_an_explicit_staff_value_before_unlinking(): void
    {
        DB::table('users')->where('id_user', 2)->update(['staff_id' => 10]);
        DB::table('staff')->where('id', 10)->update(['user_id' => 2]);

        $this->from('/user')->put('/user/2', [
            'link_only' => '1',
        ])->assertRedirect('/user')
            ->assertSessionHasErrors(['staff_id']);

        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => 10]);
        $this->assertDatabaseHas('staff', ['id' => 10, 'user_id' => 2]);
    }

    public function test_user_index_lists_all_roles_assigned_to_an_account(): void
    {
        DB::table('role_user')->insert(['id_user' => 2, 'id_role' => 3]);

        $response = $this->get('/user')
            ->assertOk()
            ->assertViewIs('admin.user.index')
            ->assertViewHas('users', function ($users) {
                $account = $users->getCollection()->firstWhere('id_user', 2);

                return $account !== null
                    && $account->email === 'first@example.test'
                    && $account->role_ids === [2, 3]
                    && $account->role_names === ['Operator', 'User Manager'];
            });

        $document = new \DOMDocument();
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $rows = $xpath->query('//button[@aria-label="Detail first@example.test"]');

        $this->assertCount(1, $rows);
        $row = $rows->item(0);
        $this->assertStringContainsString('first@example.test', $row->textContent);
        $this->assertStringContainsString('First Account', $row->textContent);
        $this->assertCount(1, $xpath->query('.//span[normalize-space(.)="Operator · User Manager"]', $row));
    }

    public function test_role_index_lists_the_role_catalog_not_account_assignments(): void
    {
        $this->get('/roles')
            ->assertOk()
            ->assertViewIs('admin.role.index')
            ->assertViewHas('roles', function ($roles) {
                return $roles->getCollection()->map(fn ($role) => [
                    (int) $role->id_role,
                    $role->nm_role,
                    (int) $role->role_menu_count,
                ])->all() === [[2, 'Operator', 0], [3, 'User Manager', 12]];
            })
            ->assertSee('Menampilkan 2 dari 2 role')
            ->assertSeeInOrder(['Operator', 'ROLE-0002', '0 izin', 'User Manager', 'ROLE-0003', '12 izin'])
            ->assertDontSee('first@example.test')
            ->assertDontSee('second@example.test');
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
