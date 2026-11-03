<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportHubTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDatabase = tempnam(sys_get_temp_dir(), 'report-hub-');
        config(['database.default' => 'report_hub_test', 'database.connections.report_hub_test' => [
            'driver' => 'sqlite', 'database' => $this->testDatabase, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        Schema::create('role', fn (Blueprint $table) => $table->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $table) { $table->increments('id_ru'); $table->integer('id_user'); $table->integer('id_role'); $table->timestamps(); });
        Schema::create('role_menu', function (Blueprint $table) { $table->increments('id_rm'); $table->integer('id_role'); $table->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $table) { $table->increments('id'); $table->integer('id_user'); $table->string('permission', 160); $table->boolean('is_allowed'); $table->timestamps(); $table->unique(['id_user', 'permission']); });
        Schema::create('users', function (Blueprint $table) { $table->integer('id_user')->primary(); $table->string('nm_user'); });
        DB::table('role')->insert([['id_role' => 1], ['id_role' => 2]]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Petugas']);
        $user = new User(); $user->id_user = 7; $user->nm_user = 'Petugas';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        DB::disconnect('report_hub_test');
        if (isset($this->testDatabase)) unlink($this->testDatabase);
        parent::tearDown();
    }

    public function test_stock_only_user_can_open_hub_and_only_sees_accessible_report_link(): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'product-stock.index']);
        $this->get('/reports')->assertOk()->assertViewHas('reports', fn ($reports) => $reports->pluck('route')->all() === ['product-stock']);
    }

    public function test_reports_are_filtered_by_the_destination_permissions(): void
    {
        foreach (['report.product.sales', 'report.total.aset'] as $permission) DB::table('role_menu')->insert(['id_role' => 2, 'permission' => $permission]);
        $this->get('/reports')->assertOk()->assertViewHas('reports', fn ($reports) => $reports->pluck('route')->all() === ['report-product-sales.index', 'report-total-aset']);
    }

    public function test_admin_sees_all_seven_reports_with_stock_first_and_valid_routes(): void
    {
        DB::table('role_user')->update(['id_role' => 1]);
        $this->get('/reports')->assertOk()->assertViewHas('reports', function ($reports) {
            $this->assertCount(7, $reports);
            $this->assertSame('product-stock', $reports->first()['route']);
            foreach ($reports as $report) $this->assertTrue(app('router')->has($report['route']));
            return true;
        })->assertSee('Pusat Laporan')->assertSee('Daftar laporan');
    }

    public function test_hub_requires_a_report_permission_and_authenticated_session(): void
    {
        $this->get('/reports')->assertForbidden();
        auth()->logout();
        $this->get('/reports')->assertRedirect(route('login'));
    }
}
