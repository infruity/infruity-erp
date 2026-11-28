<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * POS balancing changes revenue only, and every event can be counted per cashier.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class PosBalancingTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branch;

    private int $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->branch = $this->makeBranch('BAL');
        $this->customer = DB::table('customer')->insertGetId(['name' => 'QA Balancing', 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsRole($this->makeUser($this->superAdminRoleId(), [], [$this->branch]), 1);
    }

    private function sale(?string $date = null, string $status = 'paid', ?int $branch = null): int
    {
        $id = DB::table('pos_transaction')->insertGetId(['uuid' => (string) Str::uuid(), 'branch_id' => $branch ?? $this->branch,
            'customer_id' => $this->customer, 'date' => $date ?? date('Y-m-d'), 'total' => 87000, 'ongkir' => 10000,
            'status' => $status, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pos_transaction_detail')->insert(['pos_id' => $id, 'product_id' => $this->makeProduct(), 'quantity' => 1, 'price' => 77000,
            'subtotal' => 77000, 'hpp' => 1000, 'subtotal_hpp' => 1000, 'type' => 'product', 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function balance(int $sale, float $amount, ?string $key = null)
    {
        return $this->postJson('/pos-balancing', ['pos_id' => $sale, 'amount' => $amount, 'note' => 'lapak pagi',
            'idempotency_key' => $key ?? (string) Str::uuid()]);
    }

    private function cancel(int $id)
    {
        return $this->postJson("/pos-balancing/{$id}/cancel", ['note' => 'salah input', 'idempotency_key' => (string) Str::uuid()]);
    }

    private function actAsCashier(): void
    {
        $role = $this->makeRole('Kasir');
        DB::table('role_menu')->insert(['id_role' => $role, 'permission' => 'pos.receipt']);
        $this->actAsRole($this->makeUser($role, [], [$this->branch]), $role);
    }

    public function test_balancing_changes_revenue_and_nothing_else(): void
    {
        $sale = $this->sale();
        $before = [(array) DB::table('pos_transaction')->find($sale), DB::table('pos_transaction_detail')->where('pos_id', $sale)->get()->toJson()];

        $this->balance($sale, 5000)->assertCreated()->assertJsonPath('revenue', 82000);
        $this->balance($sale, -2000)->assertCreated()->assertJsonPath('revenue', 80000);

        $this->assertEquals($before, [(array) DB::table('pos_transaction')->find($sale), DB::table('pos_transaction_detail')->where('pos_id', $sale)->get()->toJson()]);
        $this->assertSame(0, DB::table('pos_payment')->where('pos_id', $sale)->count());
    }

    public function test_dashboard_and_report_procedures_count_it_once_per_row(): void
    {
        foreach ([date('Y-m-d'), date('Y-m-d', strtotime('-7 days'))] as $date) {
            $sale = $this->sale($date);
            $this->sale($date);
            $this->balance($sale, 5000)->assertCreated();
            $this->balance($sale, -2000)->assertCreated();
        }

        $response = $this->get("/crm-dashboard?period=week&branch_id={$this->branch}")->assertOk();
        foreach (['metrics', 'previousMetrics'] as $period) {
            $this->assertEquals(157000, $response->viewData($period)['revenue'], $period);
            $this->assertEquals(155000, $response->viewData($period)['profit'], $period);
        }
        $this->assertEquals(157000, collect($response->viewData('chart'))->sum('revenue'));

        $day = date('Y-m-d');
        foreach (['get_branch_report' => ['branch_id', $this->branch], 'get_customer_report' => ['customer_id', $this->customer]] as $procedure => [$key, $id]) {
            $row = (array) collect(DB::select("CALL {$procedure}(?, ?)", [$day, $day]))->firstWhere($key, $id);
            $this->assertEquals([157000, 2, 2000, 155000],
                [(float) $row['total_omset'], (int) $row['total_transaction'], (float) $row['hpp'], (float) $row['profit']], $procedure);
        }
    }

    public function test_a_repeated_key_is_saved_once(): void
    {
        $sale = $this->sale();
        $this->balance($sale, 5000, 'sama')->assertCreated();
        $this->balance($sale, 5000, 'sama')->assertOk();
        $this->balance($sale, 6000, 'sama')->assertUnprocessable();

        $this->assertSame(1, DB::table('pos_balancing')->where('pos_id', $sale)->count());
    }

    public function test_invalid_balancing_is_refused(): void
    {
        $this->balance($this->sale(status: 'draft'), 5000)->assertUnprocessable();
        $sale = $this->sale();
        $this->balance($sale, 0)->assertUnprocessable();
        $this->balance($sale, -77001)->assertUnprocessable();   // revenue leaves shipping out
        $this->balance($sale, -77000)->assertCreated();
        $this->assertSame(1, DB::table('pos_balancing')->where('pos_id', $sale)->count());

        $elsewhere = $this->sale(branch: $this->makeBranch('LAIN'));
        $this->actAsCashier();   // an administrator may work in every branch
        $this->balance($elsewhere, 5000)->assertNotFound();
    }

    public function test_an_older_sale_needs_the_backdate_permission(): void
    {
        $old = $this->sale(date('Y-m-d', strtotime('-1 day')));
        $this->balance($old, 5000)->assertCreated();            // administrator

        $this->actAsCashier();
        $this->balance($old, 5000)->assertUnprocessable();
        $this->balance($this->sale(), 5000)->assertCreated();
    }

    public function test_cancelling_once_by_the_maker(): void
    {
        $sale = $this->sale();
        $byAdmin = $this->balance($sale, 5000)->json('data.id');

        $this->actAsCashier();
        $this->cancel($byAdmin)->assertForbidden();
        $own = $this->balance($sale, 3000)->json('data.id');
        $reversal = $this->cancel($own)->assertCreated()->assertJsonPath('revenue', 82000)->json('data.id');
        $this->cancel($own)->assertUnprocessable();
        $this->cancel($reversal)->assertUnprocessable();
    }

    public function test_summary_counts_events_per_cashier(): void
    {
        $this->actAsCashier();
        $cashier = auth()->id();
        $sale = $this->sale();
        $this->sale();
        $this->balance($sale, 5000)->assertCreated();
        $this->balance($sale, -2000)->assertCreated();
        $this->cancel($this->balance($sale, 9000)->json('data.id'))->assertCreated();

        $this->getJson('/pos-balancing/summary')->assertForbidden();
        $this->getJson('/pos-balancing')->assertForbidden();
        $this->getJson("/pos-balancing?pos_id={$sale}")->assertOk()->assertJsonPath('total', 4);

        $this->actAsRole($this->makeUser(1, [], [$this->branch]), 1);
        $today = date('Y-m-d');
        $response = $this->getJson("/pos-balancing/summary?from={$today}&to={$today}&branch_id={$this->branch}")->assertOk();

        $this->assertEquals([['user_id' => $cashier, 'balancing_count' => 3, 'cancelled_count' => 1, 'surplus_total' => 5000,
            'minus_total' => -2000, 'net_total' => 3000, 'balanced_sale_count' => 1, 'sale_count' => 2]],
            collect($response->json('cashiers'))->map(fn ($row) => collect($row)->except('name')->all())->all());
        $this->assertSame(3, array_sum(array_column($response->json('per_hour'), 'total')));
        $this->assertSame(3, array_sum(array_column($response->json('per_date'), 'total')));
    }
}
