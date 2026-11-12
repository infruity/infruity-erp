<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Pre-deploy QA, phase 1 — reports: numbers must equal a hand calculation, dates and branches must
 * be respected, and nobody sees a report they have no permission for.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class ReportBehaviorTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branchA;
    private int $branchB;
    private int $apel;
    private int $jeruk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->branchA = $this->makeBranch('RPT-A');
        $this->branchB = $this->makeBranch('RPT-B');
        $this->apel = $this->makeProduct('Apel', 20000);
        $this->jeruk = $this->makeProduct('Jeruk', 15000);
    }

    private function asAdmin(): void
    {
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    /** @param array<int, array{0:int,1:float,2:int}> $lines [productId, qty, price] */
    private function sale(int $branchId, string $status, string $date, array $lines): int
    {
        $total = array_sum(array_map(fn ($l) => $l[1] * $l[2], $lines));

        $posId = DB::table('pos_transaction')->insertGetId([
            'uuid'           => (string) Str::uuid(),
            'invoice_number' => 'INVRPT' . strtoupper(Str::random(8)),
            'date'           => $date,
            'total'          => $total,
            'discount'       => 0,
            'paid'           => in_array($status, ['paid', 'debt'], true) ? $total : 0,
            'status'         => $status,
            'branch_id'      => $branchId,
            'created_by'     => 1,
            'created_at'     => $date . ' 10:00:00',
            'updated_at'     => $date . ' 10:00:00',
        ]);

        foreach ($lines as [$productId, $qty, $price]) {
            DB::table('pos_transaction_detail')->insert([
                'pos_id'     => $posId,
                'product_id' => $productId,
                'quantity'   => $qty,
                'price'      => $price,
                'discount'   => 0,
                'subtotal'   => $qty * $price,
                'exp'        => 0,
                'exp_value'  => 0,
                'type'       => 'product',
                'created_at' => $date . ' 10:00:00',
                'updated_at' => $date . ' 10:00:00',
            ]);
        }

        return $posId;
    }

    private function productSales(array $query): \Illuminate\Testing\TestResponse
    {
        return $this->getJson('/report-product-sales/data?' . http_build_query(array_merge([
            'draw' => 1, 'start' => 0, 'length' => 50,
        ], $query)));
    }

    private function rowsByProduct($response): array
    {
        $rows = [];
        foreach ($response->json('data') as $row) {
            $rows[(int) $row['product_id']][] = $row;
        }

        return $rows;
    }

    // ---- numbers -----------------------------------------------------------------------------

    public function test_product_sales_sums_paid_and_debt_sales_and_ignores_drafts_and_other_branches(): void
    {
        $today = date('Y-m-d');
        $this->sale($this->branchA, 'paid', $today, [[$this->apel, 3, 20000], [$this->jeruk, 2, 15000]]);   // 60.000 + 30.000
        $this->sale($this->branchA, 'debt', $today, [[$this->apel, 2, 20000]]);                              // 40.000
        $this->sale($this->branchA, 'draft', $today, [[$this->apel, 9, 20000]]);                             // ignored
        $this->sale($this->branchA, 'canceled', $today, [[$this->apel, 9, 20000]]);                          // ignored
        $this->sale($this->branchB, 'paid', $today, [[$this->apel, 7, 20000]]);                              // other branch

        $this->asAdmin();
        $response = $this->productSales(['branch_id' => $this->branchA, 'start_date' => $today, 'end_date' => $today])->assertOk();
        $rows = $this->rowsByProduct($response);

        $this->assertCount(1, $rows[$this->apel], 'same product and price are grouped into one row');
        $this->assertEquals(5, $rows[$this->apel][0]['quantity']);
        $this->assertEquals(100000, $rows[$this->apel][0]['total_formatted'] === null ? 0 : preg_replace('/\D/', '', $rows[$this->apel][0]['total_formatted']));
        $this->assertEquals(2, $rows[$this->jeruk][0]['quantity']);
        $this->assertSame('Rp. 130.000', $response->json('grand_total'), '100.000 apel + 30.000 jeruk');
    }

    public function test_product_sales_respects_the_date_range(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this->sale($this->branchA, 'paid', $yesterday, [[$this->apel, 4, 20000]]);   // 80.000
        $this->sale($this->branchA, 'paid', $today, [[$this->apel, 1, 20000]]);       // 20.000

        $this->asAdmin();

        $onlyToday = $this->productSales(['branch_id' => $this->branchA, 'start_date' => $today, 'end_date' => $today]);
        $this->assertSame('Rp. 20.000', $onlyToday->json('grand_total'));

        $both = $this->productSales(['branch_id' => $this->branchA, 'start_date' => $yesterday, 'end_date' => $today]);
        $this->assertSame('Rp. 100.000', $both->json('grand_total'), 'range limits are inclusive');
    }

    public function test_deleted_sales_do_not_count(): void
    {
        $today = date('Y-m-d');
        $posId = $this->sale($this->branchA, 'paid', $today, [[$this->apel, 3, 20000]]);
        DB::table('pos_transaction')->where('id', $posId)->update(['deleted_at' => now()]);

        $this->asAdmin();
        $response = $this->productSales(['branch_id' => $this->branchA, 'start_date' => $today, 'end_date' => $today])->assertOk();

        $this->assertSame([], $response->json('data'));
    }

    public function test_transaction_report_lists_only_the_requested_branch(): void
    {
        $today = date('Y-m-d');
        $a = $this->sale($this->branchA, 'paid', $today, [[$this->apel, 1, 20000]]);
        $b = $this->sale($this->branchB, 'paid', $today, [[$this->apel, 1, 20000]]);

        $this->asAdmin();
        $response = $this->getJson('/report-transaction/data?' . http_build_query([
            'draw' => 1, 'start' => 0, 'length' => 500, 'branch_id' => $this->branchA,
        ]))->assertOk();

        $ids = array_map('intval', array_column($response->json('data'), 'pos_id'));
        $this->assertContains($a, $ids);
        $this->assertNotContains($b, $ids);
    }

    // ---- access ------------------------------------------------------------------------------

    public function test_every_report_data_endpoint_refuses_a_role_without_permission(): void
    {
        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId, [], [$this->branchA]);
        $this->actAsRole($kasir, $roleId);

        $endpoints = [
            '/report-transaction/data', '/report-product-sales/data', '/report-product-sales/history',
            '/report-profit-revenue/data', '/report-profit-revenue/history',
            '/report-profit-adjusted/data', '/report-profit-adjusted/history',
            '/report-shipping-cost/data', '/report-shipping-cost/history',
            '/report-total-aset/data', '/report-product-buang/data', '/report-product-buang/history',
            '/report-customer-transaction/data', '/report-branch-transaction/data',
            '/report-branch-product/data', '/report-customer-product/data',
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertForbidden("{$url} must be forbidden without permission");
        }
    }

    public function test_cross_branch_aggregate_reports_are_super_admin_only_even_with_permission(): void
    {
        $roleId = $this->makeRole('Manager');
        $manager = $this->makeUser($roleId, [], [$this->branchA]);
        foreach (['report.customer.transaction', 'report.branch.transaction', 'report.branch.product', 'report.customer.product'] as $permission) {
            DB::table('user_permissions')->insert(['id_user' => $manager->id_user, 'permission' => $permission, 'is_allowed' => 1]);
        }
        $this->actAsRole($manager, $roleId);

        foreach (['/report-customer-transaction/data', '/report-branch-transaction/data', '/report-branch-product/data', '/report-customer-product/data'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
    }

    public function test_branch_user_cannot_request_another_branchs_report(): void
    {
        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId, [], [$this->branchA]);
        DB::table('user_permissions')->insert(['id_user' => $kasir->id_user, 'permission' => 'report.product.sales', 'is_allowed' => 1]);
        $this->actAsRole($kasir, $roleId);
        $today = date('Y-m-d');

        $this->productSales(['branch_id' => $this->branchB, 'start_date' => $today, 'end_date' => $today])->assertForbidden();
        $this->productSales(['branch_id' => 'null', 'start_date' => $today, 'end_date' => $today])->assertForbidden();
    }

    public function test_branch_user_without_filter_only_sees_own_branch(): void
    {
        $today = date('Y-m-d');
        $this->sale($this->branchA, 'paid', $today, [[$this->apel, 1, 20000]]);    // 20.000 own
        $this->sale($this->branchB, 'paid', $today, [[$this->apel, 5, 20000]]);    // 100.000 not theirs

        $roleId = $this->makeRole('Kasir');
        $kasir = $this->makeUser($roleId, [], [$this->branchA]);
        DB::table('user_permissions')->insert(['id_user' => $kasir->id_user, 'permission' => 'report.product.sales', 'is_allowed' => 1]);
        $this->actAsRole($kasir, $roleId);

        $response = $this->productSales(['start_date' => $today, 'end_date' => $today])->assertOk();

        $this->assertSame('Rp. 20.000', $response->json('grand_total'));
    }

    public function test_report_pages_render_for_super_admin(): void
    {
        $this->asAdmin();

        foreach (['/reports', '/report-transaction', '/report-product-sales', '/report-profit-revenue', '/report-shipping-cost', '/report-total-aset'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
