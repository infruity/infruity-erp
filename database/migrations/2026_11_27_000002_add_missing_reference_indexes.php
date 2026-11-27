<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference columns that no index starts with (finding D2). Only indexes are added: no row changes, and no foreign
 * keys, which older rows pointing at deleted records would make fail.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'crm_point_decrement' => ['customer_id'],
        'deposito' => ['customer_id', 'tier_id'],
        'expenditure' => ['branch_id'],
        'expenditure_detail' => ['parcel_id', 'product_id', 'production_id'],
        'expenditure_payment' => ['branch_id'],
        'kurir' => ['staff_id'],
        'order_book' => ['customer_id'],
        'order_book_detail' => ['product_id'],
        'pos_payment' => ['branch_id'],
        'pos_transaction' => ['branch_process_id', 'courier_id', 'deposito_id'],
        'pos_transaction_detail' => ['production_id'],
        'position' => ['department_id'],
        'product_branch' => ['branch_id'],
        'product_receipt' => ['product_id', 'product_receipt_id', 'receipt_id'],
        'production' => ['staff_id'],
        'production_parcel' => ['product_id', 'staff_id'],
        'production_parcel_detail' => ['kemasan_id'],
        'products' => ['category_id', 'parent_id'],
        'receipt' => ['product_id'],
        'staff' => ['department_id', 'position_id'],
        'staff_position' => ['position_id'],
        'stock_in' => ['product_id', 'reference_id'],
        'stock_out' => ['product_id', 'reference_id'],
        'stock_out_transaction' => ['product_id', 'type_id'],
        'transfer_detail_correction' => ['transfer_detail_id'],
        'user_branch' => ['branch_id'],
        'wholesale' => ['supplier_id'],
        'wholesale_product' => ['category_id', 'supplier_id'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column) || $this->leadsAnIndex($table, $column)) {
                    continue;
                }
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($column, $this->name($table, $column)));
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if (Schema::hasTable($table) && collect(Schema::getIndexes($table))->contains('name', $this->name($table, $column))) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($this->name($table, $column)));
                }
            }
        }
    }

    private function leadsAnIndex(string $table, string $column): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($index) => ($index['columns'][0] ?? null) === $column);
    }

    private function name(string $table, string $column): string
    {
        return substr('ref_' . $table . '_' . $column, 0, 60) . '_idx';
    }
};
