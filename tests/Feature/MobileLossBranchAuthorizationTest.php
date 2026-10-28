<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileLossBranchAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mobile_loss_authorization_test', 'database.connections.mobile_loss_authorization_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->boolean('is_aktif')->default(true);
        });
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
        Schema::create('branch', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name');
        });
        Schema::create('sortir_transaction', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('branch_id')->nullable();
            $table->date('date');
            $table->dateTime('created_at');
            $table->string('invoice_number');
        });
        Schema::create('sortir_transaction_detail', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('sortir_id');
            $table->integer('product_id');
            $table->decimal('subtotal');
            $table->decimal('quantity');
            $table->decimal('price');
            $table->decimal('discount')->default(0);
        });
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Loss Operator']);
        DB::table('role')->insert([
            ['id_role' => 1, 'nm_role' => 'Administrator'],
            ['id_role' => 2, 'nm_role' => 'Operator'],
        ]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'report.product.buang']);
        DB::table('branch')->insert([['id' => 10, 'name' => 'Own Branch'], ['id' => 20, 'name' => 'Foreign Branch']]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 10]);
        DB::table('products')->insert(['id' => 1, 'name' => 'Fixture Fruit']);
        foreach ([[1, 10, 15000, 3], [2, 20, 90000, 9], [3, null, 70000, 7]] as [$id, $branch, $amount, $quantity]) {
            DB::table('sortir_transaction')->insert([
                'id' => $id, 'branch_id' => $branch, 'date' => '2026-10-28',
                'created_at' => '2026-10-28 10:00:00', 'invoice_number' => 'LOSS-'.$id,
            ]);
            DB::table('sortir_transaction_detail')->insert([
                'id' => $id, 'sortir_id' => $id, 'product_id' => 1, 'subtotal' => $amount,
                'quantity' => $quantity, 'price' => $amount / $quantity,
            ]);
        }
        $this->actingAs(User::findOrFail(7));
        session(['role' => ['id_role' => 2]]);
    }

    public static function endpoints(): array
    {
        return [['dates'], ['history']];
    }

    private function endpoint(string $endpoint, string $branch): string
    {
        return '/reports/mobile/loss/'.$endpoint.'?'.http_build_query([
            'start_date' => '2026-10-28', 'end_date' => '2026-10-28', 'branch_id' => $branch,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_all_only_returns_assigned_branch_losses(string $endpoint): void
    {
        $this->assertOwnLoss($endpoint, 'all');
    }

    private function assertOwnLoss(string $endpoint, string $branch): void
    {
        $response = $this->getJson($this->endpoint($endpoint, $branch))->assertOk()->assertJsonCount(1, 'data');
        if ($endpoint === 'dates') {
            $response->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 1)
                ->assertJsonPath('data.0.branch_id', 10)->assertJsonPath('data.0.branch_name', 'Own Branch')
                ->assertJsonPath('grand_total', 'Rp 15.000');
            $this->assertEquals(15000, $response->json('data.0.amount'));
        } else {
            $response->assertJsonPath('status', 'success')->assertJsonPath('data.0.invoice', 'LOSS-1')
                ->assertJsonPath('data.0.branch_name', 'Own Branch')->assertJsonPath('data.0.product_name', 'Fixture Fruit')
                ->assertJsonPath('data.0.type', 'Barang Buang')->assertJsonPath('data.0.total_label', 'Kerugian')
                ->assertJsonPath('data.0.total', 'Rp 15.000');
            $this->assertEquals(15000, $response->json('data.0.amount_raw'));
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_explicit_assigned_branch_preserves_loss_payload(string $endpoint): void
    {
        $this->assertOwnLoss($endpoint, '10');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_empty_branch_filter_still_scopes_losses(string $endpoint): void
    {
        $this->assertOwnLoss($endpoint, '');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_non_admin_cannot_select_central_losses(string $endpoint): void
    {
        $this->getJson($this->endpoint($endpoint, 'null'))->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_user_without_branch_assignments_gets_no_losses(string $endpoint): void
    {
        DB::table('user_branch')->delete();
        $this->getJson($this->endpoint($endpoint, 'all'))->assertOk()->assertJsonCount(0, 'data');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_admin_all_preserves_access_to_all_losses(string $endpoint): void
    {
        DB::table('role_user')->update(['id_role' => 1]);
        session(['role' => ['id_role' => 1]]);
        $response = $this->getJson($this->endpoint($endpoint, 'all'))->assertOk()->assertJsonCount(3, 'data');
        if ($endpoint === 'dates') {
            $response->assertJsonPath('recordsTotal', 3)->assertJsonPath('grand_total', 'Rp 175.000');
        } else {
            $this->assertEqualsCanonicalizing(['LOSS-1', 'LOSS-2', 'LOSS-3'], array_column($response->json('data'), 'invoice'));
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_admin_can_select_central_losses(string $endpoint): void
    {
        DB::table('role_user')->update(['id_role' => 1]);
        session(['role' => ['id_role' => 1]]);
        $response = $this->getJson($this->endpoint($endpoint, 'null'))->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.branch_name', 'Pusat');
        if ($endpoint === 'dates') {
            $response->assertJsonPath('data.0.branch_id', null)->assertJsonPath('grand_total', 'Rp 70.000');
        } else {
            $response->assertJsonPath('data.0.invoice', 'LOSS-3')->assertJsonPath('data.0.total', 'Rp 70.000');
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpoints')]
    public function test_explicit_foreign_branch_is_forbidden(string $endpoint): void
    {
        $this->getJson($this->endpoint($endpoint, '20'))->assertForbidden();
    }
}
