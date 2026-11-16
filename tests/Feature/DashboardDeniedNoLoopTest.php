<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * An account whose dashboard permission is switched off (e.g. OPR003, whose whole CRM bundle is denied) used to be
 * bounced between /dashboard, the page it came from and the login page for ever. It now lands on a page it may open.
 */
class DashboardDeniedNoLoopTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'dashboard_loop_test', 'database.connections.dashboard_loop_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('users', function (Blueprint $t) {
            $t->integer('id_user')->primary();
            $t->string('nm_user');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->boolean('is_aktif')->default(true);
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('role', function (Blueprint $t) {
            $t->integer('id_role', true);
            $t->string('nm_role', 60);
            $t->text('description')->nullable();
            $t->integer('id_creator');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->increments('id_ru');
            $t->integer('id_user');
            $t->integer('id_role');
            $t->timestamps();
        });
        Schema::create('role_menu', function (Blueprint $t) {
            $t->increments('id_rm');
            $t->integer('id_role');
            $t->string('permission');
        });
        (require database_path('migrations/2026_10_27_000001_create_user_permissions_table.php'))->up();

        DB::table('users')->insert(['id_user' => 300, 'nm_user' => 'Operator', 'email' => 'op@example.test', 'password' => 'x', 'is_aktif' => true]);
        DB::table('role')->insert(['id_role' => 2, 'nm_role' => 'Operator', 'id_creator' => 1]);
        DB::table('role_user')->insert(['id_user' => 300, 'id_role' => 2]);
        DB::table('role_menu')->insert([['id_role' => 2, 'permission' => 'production.index'], ['id_role' => 2, 'permission' => 'crm-dashboard.index']]);
        DB::table('user_permissions')->insert(['id_user' => 300, 'permission' => 'crm-dashboard.index', 'is_allowed' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_a_denied_dashboard_sends_the_account_to_a_page_it_may_open_not_back_to_a_loop(): void
    {
        foreach ([null, url('/login'), url('/'), url('/dashboard')] as $previous) {
            $response = $this->actingAs(User::find(300))->withHeaders($previous ? ['referer' => $previous] : [])->get('/dashboard');

            $response->assertRedirect(route('production.index'));
        }
    }

    public function test_a_real_previous_page_is_still_used(): void
    {
        $this->actingAs(User::find(300))->withHeaders(['referer' => url('/production')])->get('/dashboard')->assertRedirect(url('/production'));
    }

    public function test_an_account_with_no_open_page_gets_403_instead_of_a_loop(): void
    {
        DB::table('role_menu')->where('id_role', 2)->delete();

        $this->actingAs(User::find(300))->get('/dashboard')->assertStatus(403);
    }
}
