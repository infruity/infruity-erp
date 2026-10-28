<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WholesaleDetailAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertFalse($this->app->configurationIsCached());
        config([
            'database.default' => 'wholesale_detail_test',
            'database.connections.wholesale_detail_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
        });
        Schema::create('role', fn (Blueprint $table) => $table->integer('id_role')->primary());
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
            $table->string('permission', 160);
            $table->boolean('is_allowed');
            $table->timestamps();
            $table->unique(['id_user', 'permission']);
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });
        Schema::create('supplier', fn (Blueprint $table) => $table->increments('id'));
        Schema::create('wholesale', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('branch_id')->nullable();
            $table->string('order_number');
            $table->date('order_date');
            $table->string('status');
            $table->integer('created_by');
            $table->timestamps();
        });
        Schema::create('wholesale_product', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('wholesale_id');
            $table->integer('product_id');
            $table->integer('supplier_id')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->integer('price')->nullable();
            $table->integer('total_price')->nullable();
            $table->string('status')->default('processing');
            $table->timestamps();
        });

        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Wholesale Fixture']);
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        foreach (['wholesale.update-product', 'wholesale.delete_product'] as $permission) {
            DB::table('role_menu')->insert(['id_role' => 2, 'permission' => $permission]);
        }
        DB::table('branch')->insert([
            ['id' => 1, 'name' => 'Allowed fixture'],
            ['id' => 2, 'name' => 'Foreign fixture'],
        ]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('supplier')->insert([['id' => 1], ['id' => 2]]);
        $this->actingAs(User::findOrFail(7));
    }

    protected function tearDown(): void
    {
        DB::purge('wholesale_detail_test');
        parent::tearDown();
    }

    private function detail(?int $branchId = 1, string $status = 'draft', int $creator = 7): int
    {
        $headerId = DB::table('wholesale')->insertGetId([
            'branch_id' => $branchId, 'order_number' => 'FIXTURE-' . $branchId . '-' . $status,
            'order_date' => '2026-10-29', 'status' => $status, 'created_by' => $creator,
            'created_at' => '2026-10-29 00:00:00', 'updated_at' => '2026-10-29 00:00:00',
        ]);

        return DB::table('wholesale_product')->insertGetId([
            'wholesale_id' => $headerId, 'product_id' => 11, 'supplier_id' => 1,
            'quantity' => 2, 'price' => 5000, 'total_price' => 10000, 'status' => 'processing',
            'created_at' => '2026-10-29 00:00:00', 'updated_at' => '2026-10-29 00:00:00',
        ]);
    }

    private function payload(): array
    {
        return ['supplier_id' => 2, 'price' => 7500, 'qty' => 3];
    }

    private function state(): array
    {
        return [
            'headers' => DB::table('wholesale')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'details' => DB::table('wholesale_product')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }

    public static function mutationEndpoints(): array
    {
        return ['update' => ['update'], 'delete' => ['delete']];
    }

    private function mutate(string $operation, int $id): TestResponse
    {
        return $operation === 'update'
            ? $this->putJson(route('wholesale.update-product', $id), $this->payload())
            : $this->deleteJson(route('wholesale.delete_product', $id));
    }

    #[DataProvider('mutationEndpoints')]
    public function test_missing_permission_returns_403_without_mutating_state(string $operation): void
    {
        $id = $this->detail();
        DB::table('role_menu')->delete();
        $before = $this->state();

        $this->mutate($operation, $id)->assertForbidden();

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_complete_header_cannot_be_mutated(string $operation): void
    {
        $id = $this->detail(1, 'complete');
        $before = $this->state();

        $this->mutate($operation, $id)->assertStatus(409)->assertJsonMissingPath('data');

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function mutableHeaders(): array
    {
        return [
            'update temp' => ['update', 'temp'],
            'update draft' => ['update', 'draft'],
            'update posting' => ['update', 'posting'],
            'delete temp' => ['delete', 'temp'],
            'delete draft' => ['delete', 'draft'],
            'delete posting' => ['delete', 'posting'],
        ];
    }

    #[DataProvider('mutableHeaders')]
    public function test_allowed_mutable_header_preserves_response_contract_and_other_records(string $operation, string $status): void
    {
        // Branch access, not ownership, grants access to an existing assigned header.
        $id = $this->detail(1, $status, 8);
        $this->detail(2);
        $before = $this->state();

        $response = $this->mutate($operation, $id);

        if ($operation === 'update') {
            $response->assertOk()->assertExactJson(['message' => 'Product updated successfully']);
            $this->assertDatabaseHas('wholesale_product', [
                'id' => $id, 'supplier_id' => 2, 'price' => 7500, 'quantity' => 3,
                'wholesale_id' => $before['details'][0]['wholesale_id'],
                'product_id' => 11, 'total_price' => 10000, 'status' => 'processing',
            ]);
        } else {
            $response->assertStatus(201)->assertExactJson(['message' => 'Product berhasil dihapus.']);
            $this->assertDatabaseMissing('wholesale_product', ['id' => $id]);
        }

        $after = $this->state();
        $this->assertSame($before['headers'], $after['headers']);
        $this->assertSame($before['details'][1], end($after['details']));
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_missing_detail_returns_404_and_preserves_existing_records(string $operation): void
    {
        $this->detail();
        $before = $this->state();

        $this->mutate($operation, 999999)->assertNotFound()->assertJsonMissingPath('data');

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_orphan_detail_cannot_be_mutated(string $operation): void
    {
        $id = $this->detail();
        DB::table('wholesale')->delete();
        $before = $this->state();

        $this->mutate($operation, $id)->assertNotFound()->assertJsonMissingPath('data');

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_unassigned_temp_header_is_not_an_assigned_branch_even_for_its_creator(string $operation): void
    {
        $id = $this->detail(null, 'temp');
        $before = $this->state();

        $this->mutate($operation, $id)->assertNotFound()->assertJsonMissingPath('data');

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_branch_denial_does_not_disclose_final_state_or_header_data(string $operation): void
    {
        $id = $this->detail(2, 'complete');
        $before = $this->state();

        $response = $this->mutate($operation, $id);
        $response->assertNotFound()->assertJsonMissingPath('data');
        $this->assertStringNotContainsString($before['headers'][0]['order_number'], $response->getContent());
        $this->assertStringNotContainsString('Pengadaan yang sudah selesai', $response->getContent());
        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_explicit_user_deny_overrides_role_permission(string $operation): void
    {
        $id = $this->detail();
        DB::table('user_permissions')->insert([
            'id_user' => 7, 'permission' => $operation === 'update' ? 'wholesale.update-product' : 'wholesale.delete_product',
            'is_allowed' => false,
        ]);
        $before = $this->state();

        $this->mutate($operation, $id)->assertForbidden();

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_explicit_user_allow_without_role_grant_can_mutate_an_assigned_header(string $operation): void
    {
        $id = $this->detail();
        DB::table('role_menu')->delete();
        DB::table('user_permissions')->insert([
            'id_user' => 7, 'permission' => $operation === 'update' ? 'wholesale.update-product' : 'wholesale.delete_product',
            'is_allowed' => true,
        ]);

        $response = $this->mutate($operation, $id);
        $response->assertStatus($operation === 'update' ? 200 : 201);
        if ($operation === 'update') {
            $this->assertDatabaseHas('wholesale_product', ['id' => $id, 'price' => 7500, 'quantity' => 3]);
        } else {
            $this->assertDatabaseMissing('wholesale_product', ['id' => $id]);
        }
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_permission_override_does_not_grant_branch_access(string $operation): void
    {
        $id = $this->detail();
        DB::table('user_branch')->delete();
        DB::table('user_permissions')->insert([
            'id_user' => 7, 'permission' => $operation === 'update' ? 'wholesale.update-product' : 'wholesale.delete_product',
            'is_allowed' => true,
        ]);
        $before = $this->state();

        $this->mutate($operation, $id)->assertNotFound();

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_actual_admin_branch_helper_allows_other_branches(string $operation): void
    {
        $id = $this->detail(2);
        DB::table('role')->insert(['id_role' => 1]);
        DB::table('role_user')->update(['id_role' => 1]);
        DB::table('user_branch')->delete();
        DB::table('role_menu')->delete();

        $this->mutate($operation, $id)->assertStatus($operation === 'update' ? 200 : 201);

        if ($operation === 'update') {
            $this->assertDatabaseHas('wholesale_product', ['id' => $id, 'price' => 7500, 'quantity' => 3]);
        } else {
            $this->assertDatabaseMissing('wholesale_product', ['id' => $id]);
        }
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_admin_cannot_bypass_the_complete_header_guard(string $operation): void
    {
        $id = $this->detail(2, 'complete');
        DB::table('role')->insert(['id_role' => 1]);
        DB::table('role_user')->update(['id_role' => 1]);
        DB::table('user_branch')->delete();
        $before = $this->state();

        $this->mutate($operation, $id)->assertStatus(409);

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    #[DataProvider('mutationEndpoints')]
    public function test_database_failure_rolls_back_detail_and_trigger_changes(string $operation): void
    {
        $id = $this->detail();
        $this->detail(2);
        $before = $this->state();
        $event = $operation === 'update' ? 'UPDATE' : 'DELETE';
        $row = $operation === 'update' ? 'NEW' : 'OLD';
        // RAISE(FAIL) preserves prior trigger/statement changes unless the controller rolls back.
        DB::unprepared("CREATE TRIGGER fixture_write_failure AFTER {$event} ON wholesale_product
            BEGIN
                UPDATE wholesale SET status = 'complete' WHERE id = {$row}.wholesale_id;
                SELECT RAISE(FAIL, 'isolated fixture write failure');
            END");

        $response = $this->mutate($operation, $id);
        if ($operation === 'update') {
            $response->assertOk()->assertExactJson(['message' => 'Product updated failed']);
        } else {
            $response->assertNotFound()->assertExactJson(['message' => 'Product gagal dihapus.']);
        }

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_invalid_update_payload_is_422_and_preserves_state(): void
    {
        $id = $this->detail();
        $before = $this->state();

        $this->putJson(route('wholesale.update-product', $id), ['supplier_id' => 999, 'price' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['supplier_id', 'price', 'qty']);

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_delete_rejects_another_branch_even_when_the_actor_created_the_header(): void
    {
        $id = $this->detail(2);
        $before = $this->state();

        $this->deleteJson(route('wholesale.delete_product', $id))
            ->assertNotFound()->assertJsonMissingPath('data');

        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_update_rejects_another_branch_even_when_the_actor_created_the_header(): void
    {
        $id = $this->detail(2);
        $before = $this->state();

        $response = $this->putJson(route('wholesale.update-product', $id), $this->payload());

        $response->assertNotFound()->assertJsonMissingPath('data');
        $this->assertSame($before, $this->state());
        $this->assertSame(0, DB::transactionLevel());
    }
}
