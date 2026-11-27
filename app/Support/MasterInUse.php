<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master data that history points at cannot be deleted: reports join to it, so its sales would drop out of them.
 */
class MasterInUse
{
    private const USES = [
        'product' => [
            'pos_transaction_detail' => ['product_id', 'parcel_id'], 'wholesale_product' => ['product_id'],
            'production' => ['product_id'], 'production_detail' => ['product_id'],
            'production_parcel_detail' => ['product_id', 'kemasan_id'], 'stock_opname' => ['product_id'],
            'transfer_detail' => ['product_id'], 'sortir_transaction_detail' => ['product_id'], 'stock_in' => ['product_id'],
            'stock_out' => ['product_id'], 'stock_out_transaction' => ['product_id'],
            'expenditure_detail' => ['product_id', 'parcel_id'], 'order_book_detail' => ['product_id'],
        ],
        'branch' => [
            'pos_transaction' => ['branch_id', 'branch_process_id'], 'pos_payment' => ['branch_id'], 'wholesale' => ['branch_id'],
            'production' => ['branch_id'], 'stock_opname' => ['branch_id'], 'transfer' => ['branch_id', 'branch_destination_id'],
            'sortir_transaction' => ['branch_id'], 'order_book' => ['branch_id'], 'arus_kas_transactions' => ['branch_id'],
            'expenditure' => ['branch_id'], 'staff' => ['branch_id'],
        ],
        'customer' => [
            'pos_transaction' => ['customer_id'], 'order_book' => ['customer_id'], 'deposito' => ['customer_id'],
            'crm_point_decrement' => ['customer_id'],
        ],
        'payment_method' => [
            'pos_transaction' => ['payment_method'], 'expenditure' => ['payment_method'],
            'expenditure_payment' => ['payment_method_id'],
        ],
    ];

    public static function check(string $kind, array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return false;
        }

        foreach (self::USES[$kind] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                if (self::live($table)->whereIn($column, $ids)->exists()) {
                    return true;
                }
            }
        }

        return $kind === 'payment_method' && self::usedInPayments($ids);
    }

    /** A payment keeps its methods as a JSON list, such as [1] or ["1","3"]. */
    private static function usedInPayments(array $ids): bool
    {
        if (! Schema::hasColumn('pos_payment', 'payment_method_id')) {
            return false;
        }

        return DB::table('pos_payment')->where(function ($query) use ($ids) {
            foreach ($ids as $id) {
                foreach (["[{$id}]", "[{$id},%", "%,{$id}]", "%,{$id},%", "[\"{$id}\"]", "%\"{$id}\"%"] as $pattern) {
                    $query->orWhere('payment_method_id', 'like', $pattern);
                }
            }
        })->exists();
    }

    private static function live(string $table): Builder
    {
        $query = DB::table($table);

        return Schema::hasColumn($table, 'deleted_at') ? $query->whereNull('deleted_at') : $query;
    }
}
