<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Revenue ("omzet") does not include shipping (owner's decision, 2026-11-27). A sale's stored total does:
 * items after the note discount, plus shipping after its own discount.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class OmzetTanpaOngkirTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branch;

    private int $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->branch = $this->makeBranch('OMZET');
        $this->customer = DB::table('customer')->insertGetId(['name' => 'QA Omzet', 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    /** A paid sale of $items rupiah of goods (cost 1.000), stored the way the cashier screen stores it. */
    private function sale(string $date, int $items, int $shipping = 0, float $discount = 0, ?string $type = null, float $netShipping = 0, string $status = 'paid'): void
    {
        $id = DB::table('pos_transaction')->insertGetId(['uuid' => (string) Str::uuid(), 'branch_id' => $this->branch,
            'customer_id' => $this->customer, 'date' => $date, 'total' => $items + $netShipping, 'ongkir' => $shipping,
            'ongkir_discount' => $discount, 'ongkir_discount_type' => $type, 'status' => $status,
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('pos_transaction_detail')->insert(['pos_id' => $id, 'product_id' => $this->makeProduct(), 'quantity' => 1, 'price' => $items,
            'subtotal' => $items, 'hpp' => 1000, 'subtotal_hpp' => 1000, 'type' => 'product', 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Every way a shipping discount has been stored. The goods add up to 600.000. */
    private function salesWithShipping(string $date): void
    {
        $this->sale($date, 100000);
        $this->sale($date, 100000, shipping: 15000, netShipping: 15000);
        $this->sale($date, 100000, shipping: 15000, discount: 5000, type: 'amount', netShipping: 10000);
        $this->sale($date, 100000, shipping: 20000, discount: 50, type: 'percent', netShipping: 10000);
        $this->sale($date, 100000, shipping: 20000, discount: 25, netShipping: 15000);       // saved before the unit: percent
        $this->sale($date, 100000, shipping: 20000, discount: 50, type: 'amount', netShipping: 19950); // 50 rupiah
    }

    public function test_dashboard_revenue_and_profit_leave_shipping_out(): void
    {
        $this->salesWithShipping(date('Y-m-d'));
        $this->salesWithShipping(date('Y-m-d', strtotime('-7 days')));
        $this->sale(date('Y-m-d'), 900000, shipping: 15000, netShipping: 15000, status: 'draft');

        $response = $this->get("/crm-dashboard?period=week&branch_id={$this->branch}")->assertOk();

        foreach (['metrics', 'previousMetrics'] as $period) {
            $data = $response->viewData($period);
            $this->assertEquals(600000, $data['revenue'], $period);
            $this->assertEquals(594000, $data['profit'], $period);
        }
        $this->assertEquals(600000, collect($response->viewData('chart'))->sum('revenue'), 'the bars add up to the card');
    }

    public function test_branch_and_customer_reports_leave_shipping_out(): void
    {
        $day = '2001-04-09';
        $this->salesWithShipping($day);

        foreach (['get_branch_report' => ['branch_id', $this->branch], 'get_customer_report' => ['customer_id', $this->customer]] as $procedure => [$key, $id]) {
            $row = (array) collect(DB::select("CALL {$procedure}(?, ?)", [$day, $day]))->firstWhere($key, $id);

            $this->assertEquals([600000, 6, 6000, 594000],
                [(float) $row['total_omset'], (int) $row['total_transaction'], (float) $row['hpp'], (float) $row['profit']], $procedure);
        }
    }

    public function test_shipping_never_pushes_revenue_below_zero_or_above_the_total(): void
    {
        // A discount larger than the shipping cost leaves no shipping in the total; nothing more is taken away.
        $this->sale(date('Y-m-d'), 100000, shipping: 5000, discount: 9000, type: 'amount');

        $response = $this->get("/crm-dashboard?period=week&branch_id={$this->branch}")->assertOk();

        $this->assertEquals(100000, $response->viewData('metrics')['revenue']);
    }
}
