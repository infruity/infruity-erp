<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * QA findings: a POS nota or payment notification of another branch was readable by id, and the customer report data
 * endpoint had no permission check (only its page had one).
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class BranchScopeAndPermissionGapsTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $ownBranch;
    private int $otherBranch;
    private int $ownPayment;
    private int $otherPayment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->ownBranch = $this->makeBranch('OWN');
        $this->otherBranch = $this->makeBranch('OTH');
        $this->ownPayment = $this->makePayment($this->ownBranch);
        $this->otherPayment = $this->makePayment($this->otherBranch);
    }

    private function makePayment(int $branchId): int
    {
        $pos = DB::table('pos_transaction')->insertGetId([
            'uuid' => 'bsg-' . uniqid(), 'date' => '2026-10-28', 'invoice_number' => 'INV-BSG-' . uniqid(),
            'status' => 'paid', 'total' => 1000, 'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('pos_payment')->insertGetId([
            'uuid' => 'pay-' . uniqid(), 'pos_id' => $pos, 'branch_id' => $branchId, 'total' => 1000,
            'payment_method' => '["cash"]', 'payment_method_id' => '[1]', 'payment_amount' => '["1000"]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function loginWith(array $permissions): void
    {
        $roleId = $this->makeRole('Uji Cabang');
        foreach ($permissions as $permission) {
            DB::table('role_menu')->insert(['id_role' => $roleId, 'permission' => $permission]);
        }
        $this->actAsRole($this->makeUser($roleId, [], [$this->ownBranch]), $roleId);
    }

    public function test_print_nota_of_another_branch_looks_like_a_missing_nota(): void
    {
        $this->loginWith(['pos.printNota']);

        $this->get('/pos/printNota/' . $this->otherPayment)->assertOk()->assertSee('Nota tidak ditemukan');
    }

    public function test_print_nota_of_the_own_branch_is_still_shown(): void
    {
        $this->loginWith(['pos.printNota']);

        $this->get('/pos/printNota/' . $this->ownPayment)->assertOk()->assertDontSee('Nota tidak ditemukan');
    }

    public function test_payment_notification_of_another_branch_is_not_found(): void
    {
        $this->loginWith(['pos.paymentNotification']);

        $this->get('/pos/payment-notification/' . $this->otherPayment)->assertNotFound();
    }

    public function test_payment_notification_of_the_own_branch_is_still_shown(): void
    {
        $this->loginWith(['pos.paymentNotification']);

        $this->get('/pos/payment-notification/' . $this->ownPayment)->assertOk();
    }

    public function test_customer_report_data_requires_the_customer_report_permission(): void
    {
        $this->loginWith(['pos.index']);

        $this->getJson('/customer-report/data?draw=1&start=0&length=5')->assertForbidden();
    }

    public function test_customer_report_data_is_served_with_the_permission(): void
    {
        $this->loginWith(['customer-report.index']);

        $this->getJson('/customer-report/data?draw=1&start=0&length=5')->assertOk();
    }
}
