<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Document numbers (purchase, opname, production, ...) keep counting past 999, and a number already in use is not
 * handed to, or saved over by, another document. Numbers made at the same moment are covered by the parallel check
 * run during QA; PHPUnit runs one request at a time.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class DocumentNumberTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $fruit;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // The purchase save disconnects the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('NOMOR');
        $this->fruit = $this->makeProduct('Apel');
        $this->giveStock($this->fruit, $this->branch, 50);
        $user = $this->makeUser($this->superAdminRoleId());
        $this->userId = (int) $user->id_user;
        $this->actAsRole($user, 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    public static function generators(): array
    {
        return [
            'purchase' => [\Modules\Transaction\Entities\Wholesale::class, 'order_number', 'PO', 'Ym'],
            'stock opname' => [\Modules\Transaction\Entities\StockOpname::class, 'code', 'SO', 'ym'],
            'production' => [\Modules\Transaction\Entities\Production::class, 'production_number', 'PRO', 'Ym'],
            'parcel' => [\Modules\Transaction\Entities\ProductionParcel::class, 'production_number', 'PRC', 'Ym'],
            'recipe' => [\Modules\Transaction\Entities\Receipt::class, 'code', 'RCP', 'Ym'],
            'stock out' => [\Modules\Transaction\Entities\StockOutModel::class, 'code', 'SO', 'ym'],
            'expense' => [\Modules\Pos\Entities\Expenditure::class, 'invoice_number', 'EXP', 'Ym'],
            'order book' => [\Modules\Pos\Entities\OrderBook::class, 'invoice_number', 'ORD', 'Ym'],
        ];
    }

    /** @dataProvider generators */
    #[\PHPUnit\Framework\Attributes\DataProvider('generators')]
    public function test_numbers_keep_counting_past_999(string $model, string $column, string $letters, string $period): void
    {
        $prefix = $letters . now()->format($period);
        $table = (new $model)->getTable();
        DB::table($table)->where($column, 'like', $prefix . '%')->delete();
        $add = fn (string $number) => DB::table($table)->insert([$column => $number, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame($prefix . '001', $model::getOrderNumber());
        $add($prefix . '999');
        $this->assertSame($prefix . '1000', $model::getOrderNumber());
        $add($prefix . '1000');
        $this->assertSame($prefix . '1001', $model::getOrderNumber());
        $add($prefix . '1001');
        $this->assertSame($prefix . '1002', $model::getOrderNumber());
    }

    public function test_a_production_number_another_run_already_has_is_replaced(): void
    {
        $output = $this->makeProduct('Olahan');
        $taken = 'PRO' . now()->format('Ym') . '007';
        // What the second of two people sees: both forms were opened with the same number.
        $save = fn () => $this->postJson(route('production.store'), [
            'product_id' => $output, 'production_number' => $taken, 'submit_type' => 'draft', 'production_date' => date('Y-m-d'),
            'quantity' => 1, 'branch_id' => $this->branch, 'service_cost' => '0',
            'ingredients' => [['id' => $this->fruit, 'quantity' => 1, 'hpp' => 10000]],
        ])->assertOk();

        $save();
        $save();

        $numbers = DB::table('production')->where('product_id', $output)->orderBy('id')->pluck('production_number')->all();
        $this->assertSame([$taken, 'PRO' . now()->format('Ym') . '008'], $numbers);
    }

    /** Duplicates made before this fix are still in the data. */
    public function test_saving_a_draft_never_writes_over_another_purchase_with_the_same_number(): void
    {
        $number = 'PO' . now()->format('Ym') . 'D' . Str::upper(Str::random(4));
        $row = fn (string $status, int $owner) => DB::table('wholesale')->insertGetId([
            'uuid' => (string) Str::uuid(), 'branch_id' => $this->branch, 'order_number' => $number, 'order_date' => date('Y-m-d'),
            'status' => $status, 'created_by' => $owner, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $theirs = $row('posting', $this->userId + 1000);
        DB::table('wholesale_product')->insert(['wholesale_id' => $theirs, 'product_id' => $this->fruit, 'quantity' => 10,
            'price' => 10000, 'total_price' => 100000, 'created_at' => now(), 'updated_at' => now()]);
        $mine = $row('draft', $this->userId);
        $product = $this->makeProduct('Gula');

        $this->postJson('/wholesale/save-transaction', [
            'branch_id' => $this->branch, 'date' => date('Y-m-d'), 'invoice_number' => $number, 'status' => 'posting',
            'items' => [['id' => $product, 'price' => 1000, 'qty' => 3, 'total_input' => 3000]], 'subtotal' => 3000, 'total' => 3000,
        ])->assertOk()->assertJson(['transaksi_id' => $mine]);

        $this->assertSame(1, DB::table('wholesale_product')->where('wholesale_id', $theirs)->count());
        $this->assertEquals(10, DB::table('wholesale_product')->where('wholesale_id', $theirs)->value('quantity'));
        $this->assertSame(3.0, InventorySnapshot::stockAvailable([$product], $this->branch)[$product]);
    }
}
