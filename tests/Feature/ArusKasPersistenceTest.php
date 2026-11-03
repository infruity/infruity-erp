<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArusKasPersistenceTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDatabase = tempnam(getenv('TMPDIR') ?: storage_path('framework/testing'), 'arus-kas-');
        config(['database.default' => 'arus_kas_test', 'database.connections.arus_kas_test' => [
            'driver' => 'sqlite', 'database' => $this->testDatabase, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);

        Schema::create('role', fn (Blueprint $table) => $table->integer('id_role')->primary());
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
            $table->string('permission', 160);
            $table->boolean('is_allowed');
            $table->timestamps();
            $table->unique(['id_user', 'permission']);
        });
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_aktif')->default(true);
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('branch_id');
        });
        Schema::create('expenditure', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('type');
            $table->integer('branch_id')->nullable();
            $table->date('date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->decimal('paid', 15, 2)->nullable();
            $table->integer('payment_method')->nullable();
            $table->string('status');
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('expenditure_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('expenditure_id');
            $table->string('product_name')->nullable();
            $table->string('product_unit')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->string('type')->default('product');
            $table->integer('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('arus_kas_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('invoice_number');
            $table->integer('branch_id');
            $table->date('date');
            $table->string('status');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('arus_kas_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('arus_kas_transaction_id');
            $table->string('direction');
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        foreach (['index', 'create', 'edit', 'update', 'destroy'] as $action) {
            DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'expenditure.' . $action]);
        }
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Dimas Pratama', 'name' => 'Dimas Pratama', 'is_aktif' => true]);
        DB::table('branch')->insert([
            ['id' => 1, 'name' => 'Gresik / Driyorejo - 001'],
            ['id' => 2, 'name' => 'Surabaya / Rungkut - 001'],
        ]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('expenditure')->insert([
            'uuid' => 'legacy-uuid', 'type' => 'pengeluaran', 'branch_id' => 1, 'date' => '2026-10-09',
            'invoice_number' => 'EXP202610001', 'total' => 125000, 'paid' => 125000,
            'status' => 'paid', 'created_by' => 7, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('expenditure_detail')->insert([
            'expenditure_id' => 1, 'product_name' => 'Biaya Operasional Lama', 'product_unit' => 'Paket',
            'quantity' => 1, 'price' => 125000, 'subtotal' => 125000, 'discount' => 0,
            'created_by' => 7, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = new User();
        $user->id_user = 7;
        $user->nm_user = 'Dimas Pratama';
        $user->name = 'Dimas Pratama';
        $user->is_aktif = true;
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        DB::disconnect('arus_kas_test');
        if (isset($this->testDatabase) && file_exists($this->testDatabase)) {
            unlink($this->testDatabase);
        }
        parent::tearDown();
    }

    public function test_drafts_and_completed_flows_are_saved_separately_from_legacy_finance_rows(): void
    {
        $draft = $this->post('/arus-kas', [
            'branch_id' => 1,
            'status' => 'draft',
            'items_json' => json_encode([
                ['type' => 'out', 'name' => 'Es Kristal', 'unit' => 'Pcs', 'quantity' => 2, 'price' => 20000],
                ['type' => 'in', 'name' => 'Jual Kardus Bekas', 'unit' => 'Kg', 'quantity' => 10, 'price' => 5000],
            ], JSON_THROW_ON_ERROR),
        ]);

        $draft->assertRedirect('/arus-kas');
        $this->assertDatabaseCount('arus_kas_transactions', 1);
        $this->assertDatabaseHas('arus_kas_transactions', ['status' => 'draft', 'branch_id' => 1]);
        $this->assertDatabaseCount('arus_kas_items', 2);
        $this->assertDatabaseCount('expenditure', 1);
        $this->assertDatabaseHas('expenditure', ['invoice_number' => 'EXP202610001', 'total' => 125000]);

        $completed = $this->post('/arus-kas', [
            'branch_id' => 1,
            'status' => 'completed',
            'items_json' => json_encode([
                ['type' => 'in', 'name' => 'Setoran Modal Kasir', 'unit' => null, 'quantity' => 1, 'price' => 500000],
                ['type' => 'out', 'name' => 'Bensin', 'unit' => 'Liter', 'quantity' => 3, 'price' => 10000],
            ], JSON_THROW_ON_ERROR),
        ]);

        $completed->assertRedirect('/arus-kas');
        $this->assertDatabaseCount('arus_kas_transactions', 2);
        $this->assertDatabaseCount('arus_kas_items', 4);
        $this->assertDatabaseCount('expenditure', 1);
    }

    public function test_index_renders_legacy_finance_rows_as_read_only_data(): void
    {
        $response = $this->get('/arus-kas');

        $response->assertOk();
        $response->assertSee('Arus Kas');
        $response->assertSee('Biaya Operasional Lama');
        $response->assertSee('EXP202610001');
        $response->assertSee('x-show="detailModalOpen"', false);
    }

    public function test_edit_route_renders_an_in_scope_draft_without_touching_legacy_data(): void
    {
        $now = now();
        $transactionId = DB::table('arus_kas_transactions')->insertGetId([
            'uuid' => '00000000-0000-4000-8000-000000000007',
            'invoice_number' => 'AK-QA-DRAFT-007',
            'branch_id' => 1,
            'date' => '2026-10-09',
            'status' => 'draft',
            'created_by' => 7,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('arus_kas_items')->insert([
            'arus_kas_transaction_id' => $transactionId,
            'direction' => 'out',
            'description' => 'Draft biaya operasional',
            'unit' => 'Paket',
            'quantity' => 1,
            'unit_price' => 125000,
            'subtotal' => 125000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->get('/arus-kas/' . $transactionId . '/edit')
            ->assertOk()
            ->assertSee('Edit Arus Kas')
            ->assertSee('Draft biaya operasional')
            ->assertSee('Simpan Transaksi');
        $this->assertDatabaseCount('expenditure', 1);
        $this->assertDatabaseHas('expenditure', ['invoice_number' => 'EXP202610001']);
    }

    public function test_editing_a_draft_keeps_its_reference_and_groups_completed_items(): void
    {
        $now = now();
        $transactionId = DB::table('arus_kas_transactions')->insertGetId([
            'uuid' => '00000000-0000-4000-8000-000000000008',
            'invoice_number' => 'AK-QA-DRAFT-008',
            'branch_id' => 1,
            'date' => '2026-10-09',
            'status' => 'draft',
            'created_by' => 7,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('arus_kas_items')->insert([
            'arus_kas_transaction_id' => $transactionId,
            'direction' => 'out',
            'description' => 'Draft awal',
            'unit' => 'Paket',
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->put('/arus-kas/' . $transactionId, [
            'branch_id' => 1,
            'status' => 'completed',
            'items_json' => json_encode([
                ['type' => 'out', 'name' => 'Es batu', 'unit' => 'Pcs', 'quantity' => 2, 'price' => 20000],
                ['type' => 'in', 'name' => 'Jual kardus', 'unit' => 'Kg', 'quantity' => 3, 'price' => 10000],
            ], JSON_THROW_ON_ERROR),
        ])->assertRedirect('/arus-kas');

        $this->assertDatabaseCount('arus_kas_transactions', 1);
        $this->assertDatabaseHas('arus_kas_transactions', [
            'id' => $transactionId, 'invoice_number' => 'AK-QA-DRAFT-008', 'status' => 'completed',
        ]);
        $this->assertDatabaseCount('arus_kas_items', 2);
        $this->assertDatabaseHas('arus_kas_items', ['arus_kas_transaction_id' => $transactionId, 'description' => 'Jual kardus']);
        $this->assertDatabaseCount('expenditure', 1);
    }

    public function test_status_flow_completes_a_new_draft_without_changing_legacy_rows(): void
    {
        $now = now();
        $transactionId = DB::table('arus_kas_transactions')->insertGetId([
            'uuid' => '00000000-0000-4000-8000-000000000009',
            'invoice_number' => 'AK-QA-STATUS-009',
            'branch_id' => 1,
            'date' => '2026-10-09',
            'status' => 'draft',
            'created_by' => 7,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('arus_kas_items')->insert([
            'arus_kas_transaction_id' => $transactionId,
            'direction' => 'out',
            'description' => 'Biaya status QA',
            'unit' => 'Paket',
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->patchJson('/arus-kas/' . $transactionId . '/status', ['status' => 'completed'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'completed']);

        $this->assertDatabaseHas('arus_kas_transactions', ['id' => $transactionId, 'status' => 'completed']);
        $this->assertDatabaseCount('arus_kas_items', 1);
        $this->assertDatabaseCount('expenditure', 1);
    }

    public function test_transactions_cannot_be_created_for_a_branch_outside_the_users_scope(): void
    {
        $this->post('/arus-kas', [
            'branch_id' => 2,
            'status' => 'draft',
            'items_json' => '[]',
        ])->assertForbidden();

        $this->assertDatabaseCount('arus_kas_transactions', 0);
        $this->assertDatabaseCount('expenditure', 1);
    }
}
