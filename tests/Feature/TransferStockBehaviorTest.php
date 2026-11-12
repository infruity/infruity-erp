<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Pre-deploy QA, phase 1 — stock transfer between branches: what leaves one branch must arrive
 * (or be accounted for as shrinkage) at the other. Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class TransferStockBehaviorTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $origin;
    private int $destination;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->origin = $this->makeBranch('ASAL');
        $this->destination = $this->makeBranch('TUJUAN');
        $this->productId = $this->makeProduct('Jeruk', 15000);
        $this->giveStock($this->productId, $this->origin, 10);

        $admin = $this->makeUser($this->superAdminRoleId());
        $this->actAsRole($admin, 1);
    }

    private function stock(int $branchId): float
    {
        return InventorySnapshot::stockAvailable([$this->productId], $branchId)[$this->productId];
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'transfer_id'           => null,
            'branch_id'             => $this->origin,
            'branch_destination_id' => $this->destination,
            'date'                  => date('Y-m-d'),
            'invoice_number'        => 'TRFQA' . strtoupper(Str::random(8)),
            'items'                 => [['id' => $this->productId, 'price' => 15000, 'qty' => 4, 'discount' => 0, 'total_input' => 60000]],
            'subtotal'              => 60000,
            'total'                 => 60000,
            'status'                => 'pending',
        ], $override);
    }

    private function send(array $override = []): int
    {
        $response = $this->postJson('/transfer/save-transaction', $this->payload($override));
        $response->assertOk()->assertJson(['success' => true]);

        return (int) $response->json('transaksi_id');
    }

    private function receive(int $transferId, float $qty, ?float $expected = null)
    {
        $detail = DB::table('transfer_detail')->where('transfer_id', $transferId)->first();

        return $this->postJson("/transfer-penerima/set-selesai/{$transferId}", ['items' => [[
            'detail_id'         => $detail->id,
            'quantity'          => $qty,
            'expected_quantity' => $expected ?? (float) $detail->quantity,
        ]]]);
    }

    public function test_sending_removes_stock_from_origin_but_destination_waits_for_receipt(): void
    {
        $this->send();

        $this->assertSame(6.0, $this->stock($this->origin));
        $this->assertSame(0.0, $this->stock($this->destination));
    }

    public function test_full_receipt_moves_exactly_the_sent_quantity(): void
    {
        $transferId = $this->send();

        $this->receive($transferId, 4)->assertOk()->assertJson(['success' => true]);

        $this->assertSame(6.0, $this->stock($this->origin));
        $this->assertSame(4.0, $this->stock($this->destination));
        $this->assertDatabaseHas('transfer', ['id' => $transferId, 'status' => 'selesai']);
    }

    public function test_short_receipt_counts_the_difference_as_shrinkage_not_as_stock_at_origin(): void
    {
        $transferId = $this->send();

        $this->receive($transferId, 3)->assertOk();

        $this->assertSame(6.0, $this->stock($this->origin), 'origin keeps its reduction, shortfall is not returned');
        $this->assertSame(3.0, $this->stock($this->destination));
    }

    public function test_receiving_more_than_was_sent_is_rejected(): void
    {
        $transferId = $this->send();

        $this->receive($transferId, 5)->assertStatus(422);

        $this->assertSame(0.0, $this->stock($this->destination));
        $this->assertDatabaseHas('transfer', ['id' => $transferId, 'status' => 'pending']);
    }

    public function test_a_transfer_cannot_be_received_twice(): void
    {
        $transferId = $this->send();
        $this->receive($transferId, 4)->assertOk();

        $this->receive($transferId, 4)->assertStatus(422);

        $this->assertSame(4.0, $this->stock($this->destination), 'second receipt must not add stock again');
    }

    public function test_sending_more_than_origin_stock_is_refused_and_nothing_is_created(): void
    {
        $payload = $this->payload([
            'items'    => [['id' => $this->productId, 'price' => 15000, 'qty' => 11, 'discount' => 0, 'total_input' => 165000]],
            'subtotal' => 165000,
            'total'    => 165000,
        ]);

        $response = $this->postJson('/transfer/save-transaction', $payload);

        $this->assertNotSame(200, $response->getStatusCode(), 'overselling stock to another branch must not succeed');
        $this->assertDatabaseMissing('transfer', ['invoice_number' => $payload['invoice_number']]);
        $this->assertSame(10.0, $this->stock($this->origin));
    }

    public function test_origin_and_destination_must_differ(): void
    {
        $this->postJson('/transfer/save-transaction', $this->payload(['branch_destination_id' => $this->origin]))
            ->assertStatus(422)->assertJsonValidationErrors('branch_destination_id');
    }

    public function test_tampered_total_is_rejected(): void
    {
        $this->postJson('/transfer/save-transaction', $this->payload(['total' => 1]))
            ->assertStatus(422)->assertJsonValidationErrors('total');
        $this->assertSame(10.0, $this->stock($this->origin));
    }

    public function test_duplicate_product_lines_are_rejected(): void
    {
        $line = ['id' => $this->productId, 'price' => 15000, 'qty' => 2, 'discount' => 0, 'total_input' => 30000];

        $this->postJson('/transfer/save-transaction', $this->payload(['items' => [$line, $line], 'subtotal' => 60000]))
            ->assertStatus(422);
        $this->assertSame(10.0, $this->stock($this->origin));
    }

    public function test_user_cannot_send_from_a_branch_they_are_not_assigned_to(): void
    {
        $roleId = $this->makeRole('Gudang');
        $gudang = $this->makeUser($roleId, [], [$this->destination]);
        DB::table('user_permissions')->insert(['id_user' => $gudang->id_user, 'permission' => 'transfer.save-transaction', 'is_allowed' => 1]);
        $this->actAsRole($gudang, $roleId);

        $this->postJson('/transfer/save-transaction', $this->payload())->assertForbidden();
        $this->assertSame(10.0, $this->stock($this->origin));
    }

    public function test_only_staff_of_the_destination_branch_can_receive(): void
    {
        $transferId = $this->send();

        $roleId = $this->makeRole('Gudang');
        $outsider = $this->makeUser($roleId, [], [$this->makeBranch('LAIN')]);
        DB::table('user_permissions')->insert(['id_user' => $outsider->id_user, 'permission' => 'transfer-penerima.set_selesai', 'is_allowed' => 1]);
        $this->actAsRole($outsider, $roleId);

        $this->receive($transferId, 4)->assertNotFound();
        $this->assertSame(0.0, $this->stock($this->destination));
    }

    public function test_receiver_without_permission_is_forbidden(): void
    {
        $transferId = $this->send();

        $roleId = $this->makeRole('Gudang');
        $gudang = $this->makeUser($roleId, [], [$this->destination]);
        $this->actAsRole($gudang, $roleId);

        $this->receive($transferId, 4)->assertForbidden();
        $this->assertSame(0.0, $this->stock($this->destination));
    }
}
