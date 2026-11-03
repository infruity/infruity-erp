<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds indexes on relation/lookup columns that the original migrations never
 * indexed (detail -> header ids, product_id, branch_id, invoice_number, ...).
 * Without them every "details of transaction X" lookup is a full table scan.
 *
 * Safe to run on an existing database: an index is skipped when the table or
 * column is missing, or when another index already starts with that column.
 * MySQL 8 builds secondary indexes online, so the tables stay writable.
 */
return new class extends Migration
{
    private array $indexes = [
        'pos_transaction'           => [['branch_id'], ['customer_id'], ['invoice_number'], ['date']],
        'pos_transaction_detail'    => [['pos_id'], ['product_id'], ['parcel_id']],
        'pos_payment'               => [['pos_id']],
        'order_book'                => [['invoice_number'], ['branch_id']],
        'order_book_detail'         => [['order_book_id']],
        'wholesale'                 => [['branch_id']],
        'wholesale_product'         => [['wholesale_id'], ['product_id']],
        'production'                => [['branch_id'], ['product_id'], ['pos_id']],
        'production_detail'         => [['production_id'], ['product_id']],
        'production_parcel_detail'  => [['pos_id'], ['production_id'], ['product_id']],
        'sortir_transaction'        => [['branch_id']],
        'sortir_transaction_detail' => [['sortir_id'], ['product_id']],
        'transfer'                  => [['branch_id'], ['branch_destination_id']],
        'transfer_detail'           => [['transfer_id'], ['product_id']],
        'stock_opname'              => [['branch_id'], ['product_id']],
        'stock_opname_history'      => [['stock_opname_id']],
        'stock_opname_discussions'  => [['stock_opname_id']],
        'product_child'             => [['product_id'], ['parent_id']],
        'product_branch'            => [['product_id', 'branch_id']],
        'user_branch'               => [['user_id', 'branch_id']],
        'user_devices'              => [['user_id']],
        'expenditure_detail'        => [['expenditure_id']],
        'expenditure_payment'       => [['expenditure_id']],
        'customer_address'          => [['customer_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                if (! Schema::hasColumns($table, $columns) || $this->hasIndexStartingWith($table, $columns[0])) {
                    continue;
                }

                Schema::table($table, fn ($blueprint) => $blueprint->index($columns, $this->name($table, $columns)));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $columns) {
                $name = $this->name($table, $columns);

                if ($this->indexExists($table, $name)) {
                    Schema::table($table, fn ($blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    private function name(string $table, array $columns): string
    {
        return 'perf_' . $table . '_' . implode('_', $columns) . '_idx';
    }

    private function hasIndexStartingWith(string $table, string $column): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->where('seq_in_index', 1)
            ->exists();
    }

    private function indexExists(string $table, string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
    }
};
