<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Branch;
use Modules\Transaction\Entities\Transfer;
use Tests\TestCase;

/**
 * Sequential HTTP regressions for overlapping previews and actor-owned drafts.
 * SQLite does not prove parallel MySQL locking or invoice allocation is safe.
 */
class TransferConcurrencyTest extends TestCase
{
    private const CONNECTION = 'transfer_concurrency_test';

    protected $user;
    protected $branchA;
    protected $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        // Never inspect or migrate the developer database, even with a different local .env.
        config([
            'database.default' => self::CONNECTION,
            'database.connections.' . self::CONNECTION => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        $this->createFixtureSchema();

        DB::table('role')->insert(['id_role' => 1, 'nm_role' => 'Super Admin']);
        DB::table('branch')->insert([
            ['id' => 1, 'name' => 'Cabang 1'],
            ['id' => 2, 'name' => 'Cabang 2'],
        ]);
        $this->branchA = Branch::findOrFail(1);
        $this->branchB = Branch::findOrFail(2);
        DB::table('product_units')->insert(['id' => 1, 'abbreviation' => 'Kg']);
        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Apel', 'product_unit' => 1, 'hpp' => 10000, 'tipe' => 'product'],
            ['id' => 2, 'name' => 'Jeruk', 'product_unit' => 1, 'hpp' => 20000, 'tipe' => 'product'],
        ]);
        // product_stock is a reporting view in MySQL; only its read columns are needed here.
        DB::table('product_stock')->insert([
            ['id' => 1, 'branch_id' => 1, 'stock_available' => 100],
            ['id' => 2, 'branch_id' => 1, 'stock_available' => 100],
        ]);

        $this->user = $this->createActor('testadmin');
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        try {
            DB::purge(self::CONNECTION);
        } finally {
            parent::tearDown();
        }
    }

    private function createFixtureSchema(): void
    {
        $schema = DB::connection(self::CONNECTION)->getSchemaBuilder();
        $schema->create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('nm_user');
            $table->string('username');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        $schema->create('role', function (Blueprint $table) {
            $table->increments('id_role');
            $table->string('nm_role');
        });
        $schema->create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
            $table->timestamps();
        });
        $schema->create('user_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_user');
            $table->string('permission');
            $table->boolean('is_allowed');
            $table->timestamps();
            $table->unique(['id_user', 'permission']);
        });
        $schema->create('branch', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        $schema->create('user_branch', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('branch_id');
            $table->timestamps();
        });
        $schema->create('product_units', function (Blueprint $table) {
            $table->increments('id');
            $table->string('abbreviation');
        });
        $schema->create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('product_unit');
            $table->integer('hpp');
            $table->string('tipe');
            $table->timestamps();
        });
        $schema->create('product_stock', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('branch_id');
            $table->decimal('stock_available', 15, 2);
            $table->primary(['id', 'branch_id']);
        });
        $schema->create('transfer', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('invoice_number')->nullable();
            // Matches the later make_branch_nullable_in_transfer_table migration for temp drafts.
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('branch_destination_id')->nullable();
            $table->date('date')->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('pic_penerima')->nullable();
            $table->dateTime('tanggal_diterima')->nullable();
            $table->timestamps();
        });
        $schema->create('transfer_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 15, 2);
            $table->decimal('discount', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        $schema->create('transfer_detail_correction', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('transfer_detail_id');
            $table->decimal('old_quantity', 15, 2);
            $table->decimal('new_quantity', 15, 2);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createActor(string $username): User
    {
        // The legacy schema uses nm_user/id_user; nm_user is not mass assignable on User.
        $user = new User();
        $user->forceFill([
            'nm_user' => $username,
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
        ])->save();
        // SyncRoleSession resolves actual assignments; a fabricated session role is insufficient.
        DB::table('role_user')->insert(['id_user' => $user->id_user, 'id_role' => 1]);
        DB::table('user_branch')->insert(['user_id' => $user->id_user, 'branch_id' => $this->branchA->id]);

        return $user;
    }

    public function test_two_users_creating_transfer_with_same_preview_invoice_do_not_overwrite()
    {
        $payloadA = [
            'transfer_id'           => null,
            'branch_id'             => $this->branchA->id,
            'branch_destination_id' => $this->branchB->id,
            'date'                  => date('Y-m-d'),
            'invoice_number'        => 'TRF202609001',
            'subtotal'              => 50000,
            'total'                 => 50000,
            'status'                => 'draft',
            'items'                 => [
                [
                    'id'          => 1,
                    'price'       => 10000,
                    'qty'         => 5,
                    'discount'    => 0,
                    'total_input' => 50000,
                ],
            ],
        ];

        // User A saves first
        $responseA = $this->postJson('/transfer/save-transaction', $payloadA);
        $responseA->assertStatus(200);
        $responseA->assertJson(['success' => true]);
        $transaksiIdA = $responseA->json('transaksi_id');

        $transferA = Transfer::findOrFail($transaksiIdA);
        $this->assertNotEmpty($transferA->invoice_number);

        // User B had opened the form at the same time and has the SAME invoice_number preview 'TRF202609001'
        $userB = $this->createActor('userb_test1');
        $this->actingAs($userB);

        $payloadB = [
            'transfer_id'           => null,
            'branch_id'             => $this->branchA->id,
            'branch_destination_id' => $this->branchB->id,
            'date'                  => date('Y-m-d'),
            'invoice_number'        => 'TRF202609001',
            'subtotal'              => 100000,
            'total'                 => 100000,
            'status'                => 'pending',
            'items'                 => [
                [
                    'id'          => 2,
                    'price'       => 20000,
                    'qty'         => 5,
                    'discount'    => 0,
                    'total_input' => 100000,
                ],
            ],
        ];

        // User B saves
        $responseB = $this->postJson('/transfer/save-transaction', $payloadB);
        $responseB->assertStatus(200);
        $responseB->assertJson(['success' => true]);
        $transaksiIdB = $responseB->json('transaksi_id');

        // Verify User A's transaction was NOT deleted
        $this->assertDatabaseHas('transfer', [
            'id' => $transaksiIdA,
            'total' => 50000,
        ]);

        // Verify User B's transaction is separate
        $this->assertDatabaseHas('transfer', [
            'id' => $transaksiIdB,
            'total' => 100000,
        ]);

        $this->assertNotEquals($transaksiIdA, $transaksiIdB);

        $transferB = Transfer::findOrFail($transaksiIdB);
        $this->assertNotEquals($transferA->invoice_number, $transferB->invoice_number);
    }

    public function test_edit_transfer_preserves_invoice_number()
    {
        // First create
        $payloadCreate = [
            'transfer_id'           => null,
            'branch_id'             => $this->branchA->id,
            'branch_destination_id' => $this->branchB->id,
            'date'                  => date('Y-m-d'),
            'subtotal'              => 50000,
            'total'                 => 50000,
            'status'                => 'draft',
            'items'                 => [
                [
                    'id'          => 1,
                    'price'       => 10000,
                    'qty'         => 5,
                    'discount'    => 0,
                    'total_input' => 50000,
                ],
            ],
        ];

        $response = $this->postJson('/transfer/save-transaction', $payloadCreate);
        $response->assertStatus(200);
        $transferId = $response->json('transaksi_id');
        $original = Transfer::findOrFail($transferId);
        $originalInvoice = $original->invoice_number;

        // Now edit with transfer_id
        $payloadEdit = [
            'transfer_id'           => $transferId,
            'branch_id'             => $this->branchA->id,
            'branch_destination_id' => $this->branchB->id,
            'date'                  => date('Y-m-d'),
            'subtotal'              => 80000,
            'total'                 => 80000,
            'status'                => 'draft',
            'items'                 => [
                [
                    'id'          => 1,
                    'price'       => 10000,
                    'qty'         => 8,
                    'discount'    => 0,
                    'total_input' => 80000,
                ],
            ],
        ];

        $responseEdit = $this->postJson('/transfer/save-transaction', $payloadEdit);
        $responseEdit->assertStatus(200);

        $updated = Transfer::findOrFail($transferId);
        $this->assertEquals(80000, $updated->total);
        $this->assertEquals($originalInvoice, $updated->invoice_number);
    }

    public function test_get_order_number_handles_high_volumes()
    {
        $prefix = 'TRF' . now()->format('Ym');

        // Create a dummy record with order number 999
        Transfer::create([
            'uuid'                  => \Illuminate\Support\Str::uuid(),
            'branch_id'             => $this->branchA->id,
            'branch_destination_id' => $this->branchB->id,
            'date'                  => date('Y-m-d'),
            'invoice_number'        => $prefix . '999',
            'total'                 => 1000,
            'status'                => 'draft',
            'created_by'            => $this->user->id_user ?? $this->user->id,
        ]);

        $nextCode = Transfer::getOrderNumber();
        $this->assertEquals($prefix . '1000', $nextCode);
    }

    public function test_create_generates_and_reuses_temp_draft_per_user()
    {
        // User A accesses create
        $this->actingAs($this->user);
        $responseA = $this->get('/transfer-pengirim/create');
        $responseA->assertStatus(200);

        $tempA = Transfer::where('created_by', $this->user->id_user ?? $this->user->id)
            ->where('status', 'temp')
            ->first();
        $this->assertNotNull($tempA);
        $invoiceA = $tempA->invoice_number;

        // User A accesses create again -> reuses same temp draft
        $responseA2 = $this->get('/transfer-pengirim/create');
        $responseA2->assertStatus(200);
        $this->assertEquals($invoiceA, $responseA2->viewData('invoice_number'));
        $this->assertEquals($tempA->id, $responseA2->viewData('data')->id);
        $this->assertEquals(1, Transfer::where('created_by', $this->user->id_user)->where('status', 'temp')->count());

        // User B accesses create -> gets new temp draft with next order number
        $userB = $this->createActor('userb_test2');
        $this->actingAs($userB);

        $responseB = $this->get('/transfer-pengirim/create');
        $responseB->assertStatus(200);

        $tempB = Transfer::where('created_by', $userB->id_user ?? $userB->id)
            ->where('status', 'temp')
            ->first();
        $this->assertNotNull($tempB);
        $this->assertNotEquals($tempA->id, $tempB->id);
        $this->assertNotEquals($tempA->invoice_number, $tempB->invoice_number);

        // User A saves transaction -> updates tempA to draft
        $this->actingAs($this->user);
        $payloadSaveA = [
            'transfer_id'           => $tempA->id,
            'branch_id'             => $this->branchA->id,
            'branch_destination_id' => $this->branchB->id,
            'date'                  => date('Y-m-d'),
            'invoice_number'        => $tempA->invoice_number,
            'subtotal'              => 35000,
            'total'                 => 35000,
            'status'                => 'draft',
            'items'                 => [
                [
                    'id'          => 1,
                    'price'       => 35000,
                    'qty'         => 1,
                    'discount'    => 0,
                    'total_input' => 35000,
                ],
            ],
        ];

        $saveResponseA = $this->postJson('/transfer/save-transaction', $payloadSaveA);
        $saveResponseA->assertStatus(200);

        $tempA->refresh();
        $this->assertEquals('draft', $tempA->status);
        $this->assertEquals(35000, $tempA->total);

        // User B's draft is still intact
        $tempB->refresh();
        $this->assertEquals('temp', $tempB->status);
        $this->assertEquals($userB->id_user, $tempB->created_by);
        $this->assertEquals($responseB->viewData('invoice_number'), $tempB->invoice_number);
    }
}
