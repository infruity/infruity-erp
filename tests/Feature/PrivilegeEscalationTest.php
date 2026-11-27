<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Managing staff and accounts must not be a way to become, or take over, an Administrator.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class PrivilegeEscalationTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $admin;
    private int $manager;
    private int $branch;
    private int $otherBranch;
    private int $position;
    private User $hr;
    private int $hrStaff;
    private User $boss;
    private int $bossStaff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->admin = $this->superAdminRoleId();
        $this->manager = $this->makeRole('HR');
        $this->branch = $this->makeBranch('HAK');
        $this->otherBranch = $this->makeBranch('LAIN');
        $this->position = DB::table('position')->insertGetId(['name' => 'QA ' . Str::random(6), 'created_at' => now(), 'updated_at' => now()]);

        [$this->hr, $this->hrStaff] = $this->account($this->manager);
        [$this->boss, $this->bossStaff] = $this->account($this->admin);

        foreach (['staff.store', 'staff.update', 'user.store', 'user.update'] as $permission) {
            DB::table('user_permissions')->insert(['id_user' => $this->hr->id_user, 'permission' => $permission, 'is_allowed' => 1]);
        }
        $this->actAsRole($this->hr, $this->manager);
    }

    private function staff(int $role): int
    {
        $id = DB::table('staff')->insertGetId([
            'name' => 'QA Staff ' . Str::random(6), 'date_in' => date('Y-m-d'), 'position_id' => $this->position,
            'branch_id' => $this->branch, 'status' => 'aktif', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('staff_role')->insert(['staff_id' => $id, 'id_role' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    /** @return array{0: User, 1: int} an account and the employee it is linked to */
    private function account(int $role, array $attributes = []): array
    {
        $staff = $this->staff($role);
        $user = $this->makeUser($role, $attributes + ['staff_id' => $staff], [$this->branch]);
        DB::table('staff')->where('id', $staff)->update(['user_id' => $user->id_user]);

        return [$user, $staff];
    }

    private function saveStaff(?int $id, array $roles, ?int $branch = null)
    {
        $body = ['staff_name' => 'QA Nama', 'date_in' => date('Y-m-d'), 'positions' => [$this->position],
            'branch_id' => $branch ?? $this->branch, 'role_ids' => $roles];

        return $id === null ? $this->post('/staff', $body) : $this->put("/staff/{$id}", $body);
    }

    private function rolesOf(int $userId): array
    {
        return DB::table('role_user')->where('id_user', $userId)->pluck('id_role')->map(fn ($r) => (int) $r)->sort()->values()->all();
    }

    private function passwordOf(int $userId): string
    {
        return (string) DB::table('users')->where('id_user', $userId)->value('password');
    }

    // ---- becoming an Administrator -------------------------------------------------------------

    public function test_a_non_administrator_cannot_make_themself_administrator(): void
    {
        $this->saveStaff($this->hrStaff, [$this->admin])->assertSessionHasErrors('role_ids');

        $this->assertSame([$this->manager], $this->rolesOf($this->hr->id_user));
    }

    public function test_a_non_administrator_cannot_change_their_own_role_or_branch(): void
    {
        $this->saveStaff($this->hrStaff, [$this->makeRole('Owner')])->assertSessionHasErrors('role_ids');
        $this->saveStaff($this->hrStaff, [$this->manager], $this->otherBranch)->assertSessionHasErrors('branch_id');

        $this->assertSame([$this->manager], $this->rolesOf($this->hr->id_user));
        $this->assertSame([$this->branch], DB::table('user_branch')->where('user_id', $this->hr->id_user)->pluck('branch_id')->map(fn ($b) => (int) $b)->all());
    }

    public function test_a_non_administrator_can_still_edit_the_rest_of_their_own_record(): void
    {
        DB::table('user_branch')->insert(['user_id' => $this->hr->id_user, 'branch_id' => $this->otherBranch]);

        $this->saveStaff($this->hrStaff, [$this->manager])->assertRedirect('staff')->assertSessionHasNoErrors();

        $this->assertSame('QA Nama', DB::table('staff')->where('id', $this->hrStaff)->value('name'));
        $this->assertSame(2, DB::table('user_branch')->where('user_id', $this->hr->id_user)->count(), 'own access is left as it was');
    }

    public function test_a_non_administrator_cannot_give_anyone_the_administrator_role(): void
    {
        [$colleague, $colleagueStaff] = $this->account($this->manager);
        $before = DB::table('staff')->count();

        $this->saveStaff($colleagueStaff, [$this->admin])->assertSessionHasErrors('role_ids');
        $this->saveStaff(null, [$this->admin])->assertSessionHasErrors('role_ids');

        $this->assertSame([$this->manager], $this->rolesOf($colleague->id_user));
        $this->assertSame($before, DB::table('staff')->count());
    }

    public function test_a_non_administrator_can_still_manage_ordinary_staff(): void
    {
        [$colleague, $colleagueStaff] = $this->account($this->manager);
        $cashier = $this->makeRole('Kasir');

        $this->saveStaff($colleagueStaff, [$cashier])->assertRedirect('staff')->assertSessionHasNoErrors();
        $this->saveStaff(null, [$cashier])->assertRedirect('staff')->assertSessionHasNoErrors();

        $this->assertSame([$cashier], $this->rolesOf($colleague->id_user));
    }

    public function test_a_non_administrator_cannot_edit_an_administrators_record(): void
    {
        $this->saveStaff($this->bossStaff, [$this->manager])->assertForbidden();
        $this->saveStaff($this->staff($this->admin), [$this->manager])->assertForbidden();

        $this->assertSame([$this->admin], $this->rolesOf($this->boss->id_user));
    }

    public function test_a_non_administrator_cannot_obtain_the_role_by_linking_an_administrator_employee(): void
    {
        $unlinked = $this->staff($this->admin);

        $this->post('/user', ['email' => 'qa_new_' . Str::random(6) . '@example.test', 'password' => 'secret-pass', 'staff_id' => $unlinked])
            ->assertSessionHasErrors('staff_id');
        $this->put("/user/{$this->hr->id_user}", ['link_only' => 1, 'staff_id' => $unlinked])->assertSessionHasErrors('staff_id');
        [$colleague] = $this->account($this->manager);
        $this->put("/user/{$colleague->id_user}", ['link_only' => 1, 'staff_id' => $unlinked])->assertSessionHasErrors('staff_id');

        $this->assertNull(DB::table('staff')->where('id', $unlinked)->value('user_id'));
        $this->assertSame([$this->manager], $this->rolesOf($this->hr->id_user));
        $this->assertSame([$this->manager], $this->rolesOf($colleague->id_user));
    }

    public function test_a_non_administrator_cannot_relink_their_own_account(): void
    {
        $this->put("/user/{$this->hr->id_user}", ['link_only' => 1, 'staff_id' => $this->staff($this->makeRole('Owner'))])
            ->assertSessionHasErrors('staff_id');

        $this->assertSame([$this->manager], $this->rolesOf($this->hr->id_user));
    }

    public function test_a_non_administrator_cannot_change_an_administrators_account(): void
    {
        $id = $this->boss->id_user;

        $this->put("/user/{$id}", ['link_only' => 1, 'staff_id' => null])->assertForbidden();
        $this->putJson("/user/{$id}/permissions", ['overrides' => ['pos.index' => false]])->assertForbidden();
        $this->deleteJson("/user/{$id}/permissions")->assertForbidden();

        $this->assertSame($this->bossStaff, (int) DB::table('users')->where('id_user', $id)->value('staff_id'));
        $this->assertSame(0, DB::table('user_permissions')->where('id_user', $id)->count());
    }

    public function test_a_refused_staff_edit_reopens_the_form_with_the_reason(): void
    {
        $this->saveStaff($this->hrStaff, [$this->admin])->assertRedirect("/staff/{$this->hrStaff}/edit");
        DB::table('user_permissions')->insert(['id_user' => $this->hr->id_user, 'permission' => 'staff.edit', 'is_allowed' => 1]);

        $this->get("/staff/{$this->hrStaff}/edit")->assertOk()->assertSee('Hanya Administrator yang dapat memberikan template akses Administrator.');
    }

    // ---- nothing beyond one's own ------------------------------------------------------------

    private function roleWith(string $name, string ...$permissions): int
    {
        $role = $this->makeRole($name);
        foreach ($permissions as $permission) {
            DB::table('role_menu')->insert(['id_role' => $role, 'permission' => $permission]);
        }

        return $role;
    }

    public function test_a_non_administrator_cannot_grant_a_permission_they_do_not_hold(): void
    {
        [$colleague] = $this->account($this->manager);

        $this->putJson("/user/{$colleague->id_user}/permissions", ['overrides' => ['pos.savePayment' => true]])->assertForbidden();
        $this->assertSame(0, DB::table('user_permissions')->where('id_user', $colleague->id_user)->count());

        // Taking away, and giving what one holds, stay possible.
        $this->putJson("/user/{$colleague->id_user}/permissions", ['overrides' => ['pos.savePayment' => false, 'user.update' => true]])->assertOk();
    }

    public function test_a_non_administrator_cannot_give_a_role_or_branch_beyond_their_own(): void
    {
        [$colleague, $colleagueStaff] = $this->account($this->manager);
        $stronger = $this->roleWith('Keuangan', 'report.profit-revenue');

        $this->saveStaff($colleagueStaff, [$stronger])->assertSessionHasErrors('role_ids');
        $this->saveStaff(null, [$stronger])->assertSessionHasErrors('role_ids');
        $this->saveStaff($colleagueStaff, [$this->manager], $this->otherBranch)->assertSessionHasErrors('branch_id');
        $this->post('/user', ['email' => 'qa_kuat_' . Str::random(6) . '@example.test', 'password' => 'secret-pass', 'staff_id' => $this->staff($stronger)])
            ->assertSessionHasErrors('staff_id');

        $this->assertSame([$this->manager], $this->rolesOf($colleague->id_user));
    }

    public function test_a_non_administrator_cannot_take_over_an_account_that_can_do_more(): void
    {
        [$stronger, $strongerStaff] = $this->account($this->roleWith('Keuangan', 'report.profit-revenue'));
        [$elsewhere] = $this->account($this->manager);
        DB::table('user_branch')->insert(['user_id' => $elsewhere->id_user, 'branch_id' => $this->otherBranch]);

        foreach ([$stronger, $elsewhere] as $target) {
            $before = $this->passwordOf($target->id_user);
            $this->postJson("/user/{$target->id_user}/reset-password")->assertForbidden();
            $this->putJson("/user/{$target->id_user}/permissions", ['overrides' => ['user.update' => true]])->assertForbidden();
            $this->put("/user/{$target->id_user}", ['link_only' => 1, 'staff_id' => null])->assertForbidden();
            $this->assertSame($before, $this->passwordOf($target->id_user));
        }
        $this->saveStaff($strongerStaff, [$this->manager])->assertForbidden();
    }

    // ---- resetting passwords -------------------------------------------------------------------

    public function test_a_non_administrator_cannot_reset_an_administrator_or_the_master(): void
    {
        $master = DB::table('users')->where('email', 'master@infruity.com')->value('id_user')
            ?: $this->makeUser($this->manager, ['email' => 'master@infruity.com'])->id_user;

        foreach ([$this->boss->id_user, $master] as $target) {
            $before = $this->passwordOf((int) $target);
            $this->postJson("/user/{$target}/reset-password")->assertForbidden();
            $this->assertSame($before, $this->passwordOf((int) $target));
        }
    }

    public function test_a_reset_gives_a_different_random_password_each_time(): void
    {
        [$colleague] = $this->account($this->manager);

        $first = $this->postJson("/user/{$colleague->id_user}/reset-password")->assertOk()->json('password');
        $this->assertGreaterThanOrEqual(10, strlen((string) $first));
        $this->assertTrue(Hash::check($first, $this->passwordOf($colleague->id_user)));

        $second = $this->postJson("/user/{$colleague->id_user}/reset-password")->assertOk()->json('password');
        $this->assertNotSame($first, $second);
    }

    // ---- an Administrator keeps full control ---------------------------------------------------

    public function test_an_administrator_can_assign_the_role_and_reset_an_administrator(): void
    {
        [$colleague, $colleagueStaff] = $this->account($this->manager);
        [$otherBoss] = $this->account($this->admin);
        $this->actAsRole($this->boss, $this->admin);

        $this->saveStaff($colleagueStaff, [$this->admin])->assertRedirect('staff')->assertSessionHasNoErrors();
        $this->assertSame([$this->admin], $this->rolesOf($colleague->id_user));

        $this->postJson("/user/{$otherBoss->id_user}/reset-password")->assertOk();
        $this->put("/user/{$this->boss->id_user}", ['link_only' => 1, 'staff_id' => $this->bossStaff])->assertSessionHasNoErrors();
    }
}
