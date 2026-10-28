<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataEndpointAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'data_endpoint_authorization_test', 'database.connections.data_endpoint_authorization_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('role', function (Blueprint $table) {
            $table->integer('id_role')->primary();
            $table->string('nm_role');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
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
        DB::table('role')->insert([
            ['id_role' => 1, 'nm_role' => 'Administrator'], ['id_role' => 2, 'nm_role' => 'Operator'],
        ]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        // Authentication is real, but listing tables deliberately do not exist yet.
        $user = new User();
        $user->id_user = 7;
        $user->nm_user = 'Data Operator';
        $this->actingAs($user);
        session(['role' => ['id_role' => 2]]);
    }

    public static function endpoints(): array
    {
        return [
            'users' => ['/user/data', 'user.index', 'users'],
            'tiers' => ['/tier/data', 'tier.index', 'crm_tier'],
            'customer report' => ['/customer-report/data', 'customer-report.index', 'vw_customer_report'],
        ];
    }

    private function assertBlockedBeforeDataQuery(string $url, string $table): void
    {
        $this->assertFalse(Schema::hasTable($table));
        $queries = [];
        DB::connection()->beforeExecuting(function ($query) use (&$queries) {
            $queries[] = $query;
        });
        $this->getJson($url)->assertForbidden();
        $dataQueries = array_filter($queries, fn ($query) => preg_match('/\b(?:from|join)\s+["`]?'.preg_quote($table, '/').'["`]?\b/i', $query));
        $this->assertSame([], array_values($dataQueries), 'Authorization must precede every listing-table query.');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_missing_permission_is_forbidden_without_listing_table(string $url, string $permission, string $table): void
    {
        $this->assertBlockedBeforeDataQuery($url, $table);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_unrelated_permission_does_not_grant_listing_access(string $url, string $permission, string $table): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'product.index']);
        $this->assertBlockedBeforeDataQuery($url, $table);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_explicit_deny_overrides_role_permission_before_data_query(string $url, string $permission, string $table): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => $permission]);
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => $permission, 'is_allowed' => false]);
        $this->assertBlockedBeforeDataQuery($url, $table);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_explicit_deny_overrides_administrator_before_data_query(string $url, string $permission, string $table): void
    {
        DB::table('role_user')->update(['id_role' => 1]);
        session(['role' => ['id_role' => 1]]);
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => $permission, 'is_allowed' => false]);
        $this->assertBlockedBeforeDataQuery($url, $table);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_matching_role_permission_returns_real_records(string $url, string $permission, string $table): void
    {
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => $permission]);
        $this->assertPermittedRecords($url, $table);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_explicit_allow_grants_access_without_role_permission(string $url, string $permission, string $table): void
    {
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => $permission, 'is_allowed' => true]);
        $this->assertPermittedRecords($url, $table);
    }

    private function assertPermittedRecords(string $url, string $table): void
    {
        $this->createListingRecords($table);
        $count = $table === 'users' ? 2 : 1;
        $response = $this->getJson($url)->assertOk()->assertJsonCount($count, 'data')
            ->assertJsonPath('recordsTotal', $count)->assertJsonPath('recordsFiltered', $count);
        if ($table === 'crm_tier') {
            $response->assertJsonPath('data.0.id', 31)->assertJsonPath('data.0.name', 'Fixture Tier')
                ->assertJsonPath('data.0.exp', '12.000,00');
            return;
        }
        if ($table === 'vw_customer_report') {
            $response->assertJsonPath('data.0.id_customer', 41)->assertJsonPath('data.0.name', 'Fixture Customer')
                ->assertJsonPath('data.0.total_transaksi', '25.000,00');
            $this->assertStringContainsString('Member', $response->json('data.0.type_customer'));
            return;
        }
        $response->assertJsonFragment(['id_user' => 8, 'username' => 'fixture@example.test']);
        $this->assertStringContainsString('Fixture Account', $response->json('data.1.nm_user'));
        $this->assertStringContainsString('Fixture Branch', $response->json('data.1.branch'));
    }

    private function createListingRecords(string $table): void
    {
        if ($table === 'vw_customer_report') {
            Schema::create('vw_customer_report', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->integer('id_customer');
                $table->string('name');
                $table->string('type_customer');
                $table->integer('total_transaksi');
            });
            DB::table('vw_customer_report')->insert([
                'id' => 41, 'id_customer' => 41, 'name' => 'Fixture Customer',
                'type_customer' => 'member', 'total_transaksi' => 25000,
            ]);
            return;
        }
        if ($table === 'crm_tier') {
            Schema::create('crm_tier', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->string('name');
                $table->integer('exp');
                $table->json('free_product_id')->nullable();
            });
            DB::table('crm_tier')->insert(['id' => 31, 'name' => 'Fixture Tier', 'exp' => 12000]);
            return;
        }
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->string('username');
            $table->boolean('is_aktif')->default(true);
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });
        DB::table('users')->insert([
            ['id_user' => 7, 'nm_user' => 'Data Operator', 'username' => 'operator@example.test'],
            ['id_user' => 8, 'nm_user' => 'Fixture Account', 'username' => 'fixture@example.test'],
        ]);
        DB::table('branch')->insert(['id' => 10, 'name' => 'Fixture Branch']);
        DB::table('user_branch')->insert([['user_id' => 7, 'branch_id' => 10], ['user_id' => 8, 'branch_id' => 10]]);
    }
}
