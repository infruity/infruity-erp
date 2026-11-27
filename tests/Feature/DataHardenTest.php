<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * data:harden adds unique document numbers and header/line foreign keys only where the data already obeys them
 * (findings D1 and D2). Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class DataHardenTest extends TestCase
{
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // Changing the schema commits, so a test transaction cannot isolate these tests.
        $this->rememberCommittedBaseline();
        Artisan::call('data:harden', ['--drop' => true]);
    }

    protected function tearDown(): void
    {
        Artisan::call('data:harden', ['--drop' => true]);
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function hasUnique(): bool
    {
        return collect(Schema::getIndexes('stock_opname'))->contains('name', 'hd_uq_stock_opname_code');
    }

    private function hasForeign(): bool
    {
        return collect(Schema::getForeignKeys('wholesale_product'))->contains('name', 'hd_fk_wholesale_product_wholesale_id');
    }

    public function test_without_apply_nothing_changes(): void
    {
        $this->assertSame(0, Artisan::call('data:harden'));

        $this->assertFalse($this->hasUnique());
        $this->assertFalse($this->hasForeign());
    }

    public function test_duplicates_and_orphans_hold_back_only_their_own_constraint(): void
    {
        $branch = $this->makeBranch('KERAS');
        $product = $this->makeProduct();
        $this->giveStock($product, $branch, 1);
        $row = (array) DB::table('stock_opname')->where('product_id', $product)->first();
        unset($row['id']);
        DB::table('stock_opname')->insert($row);
        DB::table('wholesale_product')->insert(['wholesale_id' => 999999999, 'product_id' => $product, 'quantity' => 1, 'price' => 1,
            'total_price' => 1, 'created_at' => now(), 'updated_at' => now()]);

        Artisan::call('data:harden', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('nomor kembar', $output);
        $this->assertStringContainsString('baris tanpa induk', $output);
        $this->assertFalse($this->hasUnique());
        $this->assertFalse($this->hasForeign());
        $this->assertTrue(collect(Schema::getIndexes('transfer'))->contains('name', 'hd_uq_transfer_invoice_number'));
    }

    private function insertSale(string $number, string $status, bool $deleted = false): int
    {
        return DB::table('pos_transaction')->insertGetId(['uuid' => (string) Str::uuid(), 'invoice_number' => $number,
            'status' => $status, 'date' => date('Y-m-d'), 'total' => 0, 'deleted_at' => $deleted ? now() : null,
            'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_a_sale_number_is_unique_but_an_abandoned_temp_draft_gives_it_back(): void
    {
        Artisan::call('data:harden', ['--apply' => true]);
        $number = 'INVQA' . Str::random(8);

        $this->insertSale($number, 'temp', true);
        $this->insertSale($number, 'draft');
        $order = 'ORDQA' . Str::random(8);
        $this->insertSale($order, 'draft', true);
        $this->insertSale($order, 'draft');

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $this->insertSale($number, 'temp');
    }

    public function test_a_deleted_sale_keeps_its_number(): void
    {
        $number = 'INVQA' . Str::random(8);
        $this->insertSale($number, 'paid', true);
        $this->insertSale($number, 'draft');

        Artisan::call('data:harden', ['--apply' => true]);

        $this->assertStringContainsString('nomor kembar', Artisan::output());
        $this->assertFalse(Schema::hasColumn('pos_transaction', 'hd_active_invoice'));
    }

    public function test_clean_data_gets_the_constraints_and_drop_removes_them(): void
    {
        Artisan::call('data:harden', ['--apply' => true]);
        $this->assertTrue($this->hasUnique());
        $this->assertTrue($this->hasForeign());

        $header = DB::table('wholesale')->insertGetId(['uuid' => (string) Str::uuid(), 'order_number' => 'WQA' . Str::random(8),
            'order_date' => date('Y-m-d'), 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('wholesale_product')->insert(['wholesale_id' => $header, 'quantity' => 1, 'price' => 1, 'total_price' => 1,
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('wholesale')->where('id', $header)->delete();
        $this->assertSame(0, DB::table('wholesale_product')->where('wholesale_id', $header)->count(), 'lines go with their header');

        Artisan::call('data:harden', ['--drop' => true]);
        $this->assertFalse($this->hasUnique());
        $this->assertFalse($this->hasForeign());
    }
}
