<?php

namespace Tests\Feature;

use App\Support\LedgerSql;
use Tests\TestCase;

class StockHistoryLedgerFilterTest extends TestCase
{
    public function test_the_view_file_still_has_the_hook_the_ledger_filter_replaces(): void
    {
        $file = file_get_contents(database_path('sql/transaction_stock_show.sql'));

        $this->assertMatchesRegularExpression('/CREATE\s+VIEW\s+transaction_stock_show\s+AS/i', $file);
        $this->assertStringContainsString('SELECT * FROM transaction_stock_show_base', $file);
        // The running stock is partitioned by ledger and branch; that is what makes filtering by ledger safe.
        $this->assertMatchesRegularExpression('/PARTITION BY\s+COALESCE\(pc\.parent_id, base\.product_id\),\s+base\.branch_id/', $file);
    }

    public function test_stock_history_endpoint_reads_one_ledger_and_pages_in_sql(): void
    {
        $source = file_get_contents(base_path('Modules/Master/Http/Controllers/ProductController.php'));

        $this->assertStringContainsString("LedgerSql::stockHistory((int) \$productId, 'transaction_stock')", $source);
        $this->assertStringNotContainsString("DB::table('transaction_stock_show AS transaction_stock')", $source);
        $this->assertStringContainsString('->skipTotalRecords()', $source);
    }

    public function test_without_mysql_the_plain_view_is_used(): void
    {
        if (\DB::connection()->getDriverName() === 'mysql') {
            $this->markTestSkipped('Runs on the test database driver only.');
        }

        $this->assertSame('transaction_stock_show as transaction_stock', LedgerSql::stockHistory(5, 'transaction_stock')->from);
    }
}
