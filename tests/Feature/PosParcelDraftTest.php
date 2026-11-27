<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Reopening a draft with parcels. Parcels of the same budget share one product, so each line must get back its own
 * ingredients; and a parcel without separate packaging must not stop the cart from loading.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PosParcelDraftTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $apple;
    private int $mango;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->rememberCommittedBaseline();
        $this->branch = $this->makeBranch('PARSEL');
        $this->apple = $this->makeProduct('Apel');
        $this->mango = $this->makeProduct('Mangga');
        $this->giveStock($this->apple, $this->branch, 20);
        $this->giveStock($this->mango, $this->branch, 20);
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function saveTwoParcelsOfOneBudget(): int
    {
        $cart = fn (string $id) => ['id' => $id, 'price' => 100000, 'qty' => 1, 'discount' => 0, 'total_input' => 0];
        $parcel = fn (string $id, int $fruit, int $quantity) => ['id' => $id, 'qty' => 1, 'budget' => 100000, 'fee' => 0, 'kemasan' => '',
            'data' => [['product' => $fruit, 'qty' => $quantity, 'price' => 20000]]];

        return (int) $this->postJson('/pos/save-transaction', [
            'customer_id' => null, 'date' => date('Y-m-d'), 'invoice_number' => 'INVQA' . Str::upper(Str::random(8)),
            'items' => [$cart('parcela'), $cart('parcelb')],
            'parcel' => [$parcel('parcela', $this->apple, 2), $parcel('parcelb', $this->mango, 3)],
            'subtotal' => 200000, 'total' => 200000, 'discount' => 0, 'ongkir' => 0, 'discount_ongkir' => 0,
            'status' => 'draft', 'branch_id' => $this->branch,
        ])->assertOk()->json('transaksi_id');
    }

    public function test_each_parcel_line_of_a_reopened_draft_has_only_its_own_ingredients(): void
    {
        $pos = $this->saveTwoParcelsOfOneBudget();

        $lines = $this->get("/pos/{$pos}/edit")->assertOk()->viewData('detail');

        $this->assertCount(2, $lines);
        $this->assertSame($lines[0]->product_id, $lines[1]->product_id, 'one product for one budget');
        $own = $lines->map(fn ($line) => $line->product->productionParcelDetails
            ->map(fn ($row) => [(int) $row->product_id, (float) $row->quantity])->values()->all())->all();
        $this->assertSame([[[$this->apple, 2.0]], [[$this->mango, 3.0]]], $own);
    }

    /** Found while testing: payment priced every line of one budget at their average. */
    public function test_each_parcel_line_keeps_its_own_cost_when_paid(): void
    {
        $pos = $this->saveTwoParcelsOfOneBudget(); // 2 apples, and 3 mangoes, both bought at Rp10.000

        $this->postJson('/pos/savePayment', [
            'idempotency_key' => (string) Str::uuid(), 'date' => date('Y-m-d'), 'transaction_id' => $pos,
            'payments' => [['payment_id' => $this->paymentMethodId('Cash'), 'amount' => '200.000']], 'total_payment' => 200000,
        ])->assertOk();

        $cost = DB::table('pos_transaction_detail')->where('pos_id', $pos)->whereNull('deleted_at')->orderBy('id')
            ->pluck('subtotal_hpp')->map(fn ($value) => (float) $value)->all();
        $this->assertSame([20000.0, 30000.0], $cost);
    }

    public function test_the_cart_loader_accepts_a_parcel_without_separate_packaging(): void
    {
        $script = file_get_contents(base_path('Modules/Pos/Resources/views/pos/js/js-buah.blade.php'));

        $this->assertStringContainsString("kemasan: item.parcel?.name ?? '',", $script);
        $this->assertStringNotContainsString('kemasan: item.parcel.name,', $script);
    }
}
