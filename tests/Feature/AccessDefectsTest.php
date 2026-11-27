<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Access and delete rules found by the 2026-11-27 defect hunt (H-25, H-35, B9, #6, other-book, order book).
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class AccessDefectsTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $mine;
    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->superAdminRoleId();
        $this->mine = $this->makeBranch('AKSESKU');
        $this->other = $this->makeBranch('AKSESLAIN');
    }

    private function actAsCrew(array $permissions = []): void
    {
        $role = $this->makeRole('Crew');
        $user = $this->makeUser($role, [], [$this->mine]);
        foreach ($permissions as $permission) {
            DB::table('user_permissions')->insert(['id_user' => $user->id_user, 'permission' => $permission, 'is_allowed' => 1]);
        }
        $this->actAsRole($user, $role);
    }

    private function actAsMaster(): void
    {
        $user = \App\Models\User::withoutGlobalScope('is_aktif')->where('email', 'master@infruity.com')->first()
            ?: $this->makeUser(1, ['email' => 'master@infruity.com']);
        $this->actAsRole($user, 1);
    }

    public static function lists(): array
    {
        return array_map(fn ($url) => [$url], [
            'purchases' => '/wholesale/data', 'suppliers' => '/supplier/data', 'productions' => '/production/data',
            'recipes' => '/receipt/data', 'parcels' => '/parcel/data', 'campaigns' => '/campaign/data', 'tiers' => '/tier/data',
            'accounts' => '/account/data', 'couriers' => '/kurir/data', 'payment methods' => '/payment-method/data',
            'branches' => '/branch/data', 'orders' => '/order-book/data',
        ]);
    }

    /** @dataProvider lists */
    #[\PHPUnit\Framework\Attributes\DataProvider('lists')]
    public function test_a_list_is_refused_without_its_permission(string $url): void
    {
        config(['features.tier_pages' => true]);
        $this->actAsCrew();

        $response = $this->getJson($url);

        $this->assertContains($response->getStatusCode(), [403, 404], $url . ' answered ' . $response->getStatusCode());
    }

    public function test_the_lookups_the_cashier_needs_stay_open_and_the_address_save_does_not(): void
    {
        $customer = DB::table('customer')->insertGetId(['name' => 'QA Alamat', 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsCrew();

        $this->getJson('/customer/get-customer?search=QA')->assertOk();
        $this->postJson('/customer/store-address', ['customer_id' => $customer, 'address' => 'Jl. QA'])->assertForbidden();

        $this->actAsCrew(['pos.save-transaction']);
        $this->postJson('/customer/store-address', ['customer_id' => $customer, 'address' => 'Jl. QA'])->assertOk();
    }

    public function test_records_of_another_branch_cannot_be_read_by_id(): void
    {
        $production = DB::table('production')->insertGetId(['branch_id' => $this->other, 'product_id' => $this->makeProduct(),
            'quantity' => 1, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        $order = DB::table('order_book')->insertGetId(['branch_id' => $this->other, 'invoice_number' => 'ORDQA' . Str::random(5),
            'date' => date('Y-m-d'), 'note' => 'rahasia', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsCrew(['production.detail', 'production.edit', 'production.payment', 'production.print', 'order-book.order', 'order-book.edit']);

        foreach (["/production/{$production}/detail", "/production/{$production}/edit", "/production/{$production}/payment", "/production/{$production}/print"] as $url) {
            $this->assertNotSame(200, $this->get($url)->getStatusCode(), $url);
        }
        foreach (["/order-book/{$order}/order", "/order-book/{$order}/edit"] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_the_unused_model_listing_and_get_impersonation_are_gone(): void
    {
        $this->actAsMaster();

        $this->get('/get-ai-models')->assertNotFound();
        $this->get('/user/impersonate/1')->assertStatus(405);
    }

    // ---- H-35 --------------------------------------------------------------------------------

    public function test_master_data_used_by_history_cannot_be_deleted(): void
    {
        $product = $this->makeProduct('Terjual');
        $customer = DB::table('customer')->insertGetId(['name' => 'QA Langganan', 'created_at' => now(), 'updated_at' => now()]);
        $method = DB::table('payment_method')->insertGetId(['name' => 'QA ' . Str::random(6), 'created_at' => now(), 'updated_at' => now()]);
        $sale = DB::table('pos_transaction')->insertGetId(['uuid' => (string) Str::uuid(), 'branch_id' => $this->mine, 'customer_id' => $customer,
            'date' => date('Y-m-d'), 'total' => 40000, 'status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pos_transaction_detail')->insert(['pos_id' => $sale, 'product_id' => $product, 'quantity' => 1, 'price' => 40000,
            'subtotal' => 40000, 'type' => 'product', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pos_payment')->insert(['uuid' => (string) Str::uuid(), 'pos_id' => $sale, 'branch_id' => $this->mine, 'date' => date('Y-m-d'),
            'payment_method_id' => json_encode([$method]), 'total' => 40000, 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsMaster();

        foreach (["/products/{$product}" => ['products', $product], "/customers/{$customer}" => ['customer', $customer],
            "/branch/{$this->mine}" => ['branch', $this->mine], "/payment-method/{$method}" => ['payment_method', $method]] as $url => [$table, $id]) {
            $this->deleteJson($url)->assertStatus(422)->assertJson(['success' => false]);
            $this->assertDatabaseHas($table, ['id' => $id]);
        }
    }

    public function test_unused_master_data_can_still_be_deleted(): void
    {
        $product = $this->makeProduct('Salah Input');
        $customer = DB::table('customer')->insertGetId(['name' => 'QA Salah', 'created_at' => now(), 'updated_at' => now()]);
        $empty = $this->makeBranch('KOSONG');
        $method = DB::table('payment_method')->insertGetId(['name' => 'QA ' . Str::random(6), 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsMaster();

        foreach (["/products/{$product}" => ['products', $product], "/customers/{$customer}" => ['customer', $customer],
            "/branch/{$empty}" => ['branch', $empty], "/payment-method/{$method}" => ['payment_method', $method]] as $url => [$table, $id]) {
            $this->deleteJson($url)->assertOk()->assertJson(['success' => true]);
            $this->assertDatabaseMissing($table, ['id' => $id]);
        }
    }
}
