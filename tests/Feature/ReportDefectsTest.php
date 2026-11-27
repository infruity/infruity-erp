<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Report and screen defects found by the 2026-11-27 hunt (H-15, H-16, H-33, H-34, and the old discount guess).
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class ReportDefectsTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->branch = $this->makeBranch('LAPORAN');
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    private function cash(string $status, string $date, array $lines): void
    {
        $id = DB::table('arus_kas_transactions')->insertGetId(['uuid' => (string) Str::uuid(), 'invoice_number' => 'AKQA' . Str::random(8),
            'branch_id' => $this->branch, 'date' => $date, 'status' => $status, 'created_by' => 1, 'created_at' => now(), 'updated_at' => now()]);
        foreach ($lines as [$direction, $name, $amount]) {
            DB::table('arus_kas_items')->insert(['arus_kas_transaction_id' => $id, 'direction' => $direction, 'description' => $name,
                'quantity' => 1, 'unit_price' => $amount, 'subtotal' => $amount, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_the_dashboard_counts_finished_cash_outflows_as_expense(): void
    {
        $url = "/crm-dashboard?period=week&branch_id={$this->branch}";
        $this->get($url)->assertOk();
        $this->cash('completed', date('Y-m-d'), [['out', 'Gaji harian', 750000], ['in', 'Modal', 900000]]);
        $this->cash('draft', date('Y-m-d'), [['out', 'Belum jadi', 111000]]);
        $this->cash('completed', date('Y-m-d', strtotime('-7 days')), [['out', 'Minggu lalu', 50000]]);

        $response = $this->get($url)->assertOk();

        $data = $response->viewData('metrics');
        $this->assertEquals(750000, $data['expense']);
        $this->assertEquals(0, $data['revenue'], 'money paid in is not revenue');
        $this->assertEquals(50000, $response->viewData('previousMetrics')['expense']);
        $this->assertContains('Gaji harian', collect($response->viewData('contributors')['expense']['highest'])->pluck('name')->all());
    }

    public function test_the_old_branch_and_customer_reports_count_each_paid_sale_once(): void
    {
        $customer = DB::table('customer')->insertGetId(['name' => 'QA Laporan', 'created_at' => now(), 'updated_at' => now()]);
        $day = '2001-03-05';
        $sale = function (string $status, int $total, int $payments) use ($customer, $day) {
            $id = DB::table('pos_transaction')->insertGetId(['uuid' => (string) Str::uuid(), 'branch_id' => $this->branch, 'customer_id' => $customer,
                'date' => $day, 'total' => $total, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('pos_transaction_detail')->insert(['pos_id' => $id, 'product_id' => $this->makeProduct(), 'quantity' => 5, 'price' => $total / 5,
                'subtotal' => $total, 'hpp' => 10000, 'subtotal_hpp' => 50000, 'type' => 'product', 'created_at' => now(), 'updated_at' => now()]);
            for ($i = 0; $i < $payments; $i++) {
                DB::table('pos_payment')->insert(['uuid' => (string) Str::uuid(), 'pos_id' => $id, 'branch_id' => $this->branch, 'date' => $day,
                    'total' => $total / $payments, 'created_at' => now(), 'updated_at' => now()]);
            }
        };
        $sale('paid', 100000, 2);
        $sale('draft', 900000, 0);

        foreach (['get_branch_report' => 'branch_id', 'get_customer_report' => 'customer_id'] as $procedure => $key) {
            $rows = collect(DB::select("CALL {$procedure}(?, ?)", [$day, $day]));
            $row = (array) $rows->firstWhere($key, $key === 'branch_id' ? $this->branch : $customer);
            $this->assertEquals([100000, 1, 50000, 50000], [(float) $row['total_omset'], (int) $row['total_transaction'], (float) $row['hpp'], (float) $row['profit']], $procedure);
        }
        $view = DB::table('vw_customer_report')->where('id_customer', $customer)->first();
        $this->assertEquals([1, 100000], [(int) $view->jumlah_transaksi, (float) $view->total_transaksi]);
    }

    public function test_the_phone_layout_keeps_the_extra_settings_reachable_and_the_cashier_sees_no_debug_text(): void
    {
        $css = file_get_contents(public_path('assets/css/pos-form.css'));
        $this->assertDoesNotMatchRegularExpression('/\.pos-extra-settings\s*\{\s*display:\s*none/', $css);
        $this->assertStringNotContainsString('[DEBUG:', file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-buah.blade.php')));
        $this->assertStringContainsString('PosDiscount::amount($discount, $data->discount_type, $subtotal)',
            file_get_contents(base_path('Modules/Pos/Resources/views/pos/print.blade.php')));
    }

    public function test_the_cash_flow_list_loads_a_period_and_older_rows_on_request(): void
    {
        $old = date('Y-m-d', strtotime('-14 months'));
        $this->cash('completed', date('Y-m-d'), [['out', 'Baru', 1000]]);
        $this->cash('completed', $old, [['out', 'Lama', 2000]]);
        $this->cash('draft', $old, [['out', 'Draf lama', 3000]]);
        $names = fn (string $query) => collect($this->get('/arus-kas' . $query)->assertOk()->viewData('orders'))
            ->where('branchId', (string) $this->branch)->pluck('activity')->sort()->values()->all();

        $this->assertSame(['Baru', 'Draf lama'], $names(''));
        $this->assertSame(['Baru', 'Draf lama', 'Lama'], $names('?all=1'));
        $this->assertSame(['Baru', 'Draf lama', 'Lama'], $names('?from=' . $old));
        $this->assertSame(['Baru', 'Draf lama'], $names('?from=bukan-tanggal'));
    }
}
