<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The CRM dashboard lists the biggest and smallest expenses. Found by running the app under ONLY_FULL_GROUP_BY:
 * the expense query grouped by the alias "name", which MySQL resolves to the column products.name; for expense lines
 * without a product that is NULL, so every such line collapsed into ONE group (on the real data: 154 lines, 1 group
 * instead of 87) and the lists were wrong. It also made /dashboard fail with a 500 under strict SQL mode.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class CrmDashboardExpenseTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    private function expense(array $lines): void
    {
        $id = DB::table('expenditure')->insertGetId([
            'uuid' => (string) Str::uuid(), 'type' => 'pengeluaran', 'status' => 'paid', 'branch_id' => $this->makeBranch('DASH'),
            'date' => date('Y-m-d'), 'total' => array_sum($lines), 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($lines as $name => $amount) {
            DB::table('expenditure_detail')->insert([
                'expenditure_id' => $id, 'product_id' => null, 'product_name' => $name, 'quantity' => 1, 'price' => $amount,
                'discount' => 0, 'subtotal' => $amount, 'type' => 'product', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function test_expense_lines_without_a_product_are_grouped_by_their_own_name(): void
    {
        $this->expense(['Gas' => 9000, 'Plastik' => 5000, 'Es Batu' => 1000]);

        $response = $this->get('/crm-dashboard')->assertOk();

        $highest = collect($response->viewData('contributors')['expense']['highest']);
        $this->assertSame(['Gas', 'Plastik', 'Es Batu'], $highest->pluck('name')->all(), 'one entry per expense name, biggest first');
        $this->assertEquals([9000, 5000, 1000], $highest->pluck('amount')->map(fn ($a) => (float) $a)->all());
    }

    public function test_the_same_name_on_two_lines_is_added_up(): void
    {
        $this->expense(['Gas' => 9000, 'Plastik' => 5000]);
        $this->expense(['Gas' => 3000]);

        $highest = collect($this->get('/crm-dashboard')->viewData('contributors')['expense']['highest']);

        $this->assertEquals(12000, (float) $highest->firstWhere('name', 'Gas')->amount);
    }
}
