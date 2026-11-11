<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Master\Entities\Branch;
use Modules\Transaction\Entities\Transfer;
use Modules\Transaction\Entities\TransferDetail;
use Tests\TestCase;

class TransferConcurrencyTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branchA;
    protected $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests exercise the real transfer tables; the default phpunit.xml uses an empty in-memory SQLite
        // database. Run them with: php vendor/bin/phpunit -c phpunit.mysql.xml
        if (! Schema::hasTable('users') || ! Schema::hasTable('transfer')) {
            $this->markTestSkipped('Needs a migrated database (use phpunit.mysql.xml).');
        }

        // The tests write rows, so never let them run against anything but a dedicated *_test database.
        $database = DB::connection()->getDatabaseName();
        if (! str_ends_with((string) $database, '_test')) {
            $this->markTestSkipped("Refusing to run against non-test database '{$database}'.");
        }

        $this->user = User::first() ?? User::create([
            'name'     => 'Test Admin',
            'username' => 'testadmin',
            'email'    => 'testadmin@example.com',
            'password' => bcrypt('password'),
        ]);

        $branches = Branch::limit(2)->get();
        if ($branches->count() < 2) {
            $this->branchA = Branch::create(['name' => 'Cabang 1']);
            $this->branchB = Branch::create(['name' => 'Cabang 2']);
        } else {
            $this->branchA = $branches[0];
            $this->branchB = $branches[1];
        }

        $this->seedOriginStock([1, 2, 3]);
        $this->actAsSuperAdmin($this->user);
    }

    /**
     * Saving a transfer checks the origin branch's stock, which is a ledger. Give the origin
     * branch ample stock through a finished opname row (rolled back with the transaction).
     */
    private function seedOriginStock(array $productIds): void
    {
        foreach ($productIds as $productId) {
            DB::table('stock_opname')->insert([
                'code'        => 'SO-TEST-' . $productId,
                'date'        => date('Y-m-d'),
                'branch_id'   => $this->branchA->id,
                'status'      => 'selesai',
                'product_id'  => $productId,
                'stock'       => 0,
                'real_stock'  => 1000,
                'difference'  => 1000,
                'avg_price'   => 10000,
                'created_by'  => $this->user->id_user,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    /**
     * Account roles are read from role_user (the session only selects among assigned roles),
     * so a super admin needs a real assignment, not just a session value.
     */
    private function actAsSuperAdmin(User $user): void
    {
        if (! DB::table('role')->where('id_role', 1)->exists()) {
            DB::table('role')->insert(['id_role' => 1, 'nm_role' => 'Super Admin', 'id_creator' => $user->id_user]);
        }

        DB::table('role_user')->insertOrIgnore(['id_user' => $user->id_user, 'id_role' => 1]);

        $this->actingAs($user);
        session(['role' => ['id_role' => 1, 'nm_role' => 'Super Admin']]);
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
        $userB = User::create([
            'nm_user'  => 'User B',
            'username' => 'userb_test1',
            'email'    => 'userb_test1@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actAsSuperAdmin($userB);

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

        // User B accesses create -> gets new temp draft with next order number
        $userB = User::create([
            'nm_user'  => 'User B',
            'username' => 'userb_test2',
            'email'    => 'userb_test2@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actAsSuperAdmin($userB);

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
    }
}
