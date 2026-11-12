<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Pre-deploy QA, phase 1 — login and access control with a real database.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class LoginAccessBehaviorTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
    }

    public function test_valid_username_login_reaches_dashboard_with_role_in_session(): void
    {
        $roleId = $this->makeRole('Kasir');
        $user = $this->makeUser($roleId);

        $this->post('/auth/login', ['email' => $user->username, 'password' => 'qa-password'])
            ->assertRedirect('dashboard')
            ->assertSessionHas('role.id_role', $roleId);

        $this->assertSame($user->id_user, Auth::id());
    }

    public function test_valid_email_login_works(): void
    {
        $user = $this->makeUser($this->makeRole('Kasir'));

        $this->post('/auth/login', ['email' => $user->email, 'password' => 'qa-password'])
            ->assertRedirect('dashboard');
        $this->assertSame($user->id_user, Auth::id());
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->makeUser($this->makeRole('Kasir'));

        $this->post('/auth/login', ['email' => $user->username, 'password' => 'salah'])
            ->assertSessionHas('error', 'Username atau Password tidak terdaftar');
        $this->assertGuest();
    }

    public function test_empty_credentials_fail_validation(): void
    {
        $this->post('/auth/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = $this->makeUser($this->makeRole('Kasir'), ['is_aktif' => 0]);

        $this->post('/auth/login', ['email' => $user->username, 'password' => 'qa-password'])
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_user_without_any_role_is_logged_out_again(): void
    {
        $user = $this->makeUser(null);

        $this->post('/auth/login', ['email' => $user->username, 'password' => 'qa-password'])
            ->assertRedirect('auth/login')
            ->assertSessionHas('error', 'User tidak terdaftar di role apa pun');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        foreach (['/dashboard', '/pos', '/product-stock', '/settings', '/user/data', '/roles/data'] as $path) {
            $response = $this->get($path);
            $this->assertTrue($response->isRedirect(), "{$path} must redirect a guest, got {$response->getStatusCode()}");
            $this->assertStringContainsString('login', (string) $response->headers->get('Location'), $path);
        }
    }

    public function test_register_is_closed(): void
    {
        $this->post('/register', ['name' => 'x', 'email' => 'x@example.test', 'password' => 'secret-pass'])
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.test']);
    }

    public function test_register_page_redirects_back_to_login(): void
    {
        $this->get('/register')->assertRedirect('auth/login');
    }

    public function test_only_user_id_1_can_impersonate(): void
    {
        $victim = $this->makeUser($this->makeRole('Kasir'));
        $attacker = $this->makeUser($this->superAdminRoleId());

        $this->actAsRole($attacker, 1);
        $this->get("/user/impersonate/{$victim->id_user}")->assertForbidden();
        $this->assertAuthenticatedAs($attacker);
    }

    public function test_role_without_permission_cannot_change_user_permissions(): void
    {
        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId);
        $target = $this->makeUser($roleId);

        $this->actAsRole($kasir, $roleId);
        $this->put("/user/{$target->id_user}/permissions", ['overrides' => ['user.update' => true]])
            ->assertForbidden();
        $this->assertDatabaseMissing('user_permissions', ['id_user' => $target->id_user]);
    }

    public function test_role_without_permission_cannot_read_role_list(): void
    {
        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId);
        $this->actAsRole($kasir, $roleId);

        $this->getJson('/roles/data')->assertForbidden();
    }

    /**
     * SECURITY regression: GET /user/data used to have no access check, so any logged-in account
     * (even a role with zero permissions) could list every user's email and phone number.
     */
    public function test_role_without_permission_cannot_read_user_list(): void
    {
        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId);
        $other = $this->makeUser($roleId);
        $this->actAsRole($kasir, $roleId);

        $response = $this->getJson('/user/data');

        $this->assertContains($response->getStatusCode(), [302, 403], 'GET /user/data leaked the user list to a role without permission');
        $this->assertStringNotContainsString($other->email, $response->getContent());
    }

    public function test_role_with_user_index_permission_can_read_user_list(): void
    {
        $roleId = $this->makeRole('Admin Staff');
        $user = $this->makeUser($roleId);
        DB::table('user_permissions')->insert(['id_user' => $user->id_user, 'permission' => 'user.index', 'is_allowed' => 1]);

        $this->actAsRole($user, $roleId);
        $this->getJson('/user/data')->assertOk();
    }

    public function test_permission_override_grants_access_to_a_role_without_menu_rights(): void
    {
        $roleId = $this->makeRole('Kasir');
        $user = $this->makeUser($roleId);
        DB::table('user_permissions')->insert(['id_user' => $user->id_user, 'permission' => 'role.index', 'is_allowed' => 1]);

        $this->actAsRole($user, $roleId);
        $this->getJson('/roles/data')->assertOk();
    }
}
