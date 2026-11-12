<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Products whose stock went below zero in the given branches: the warning on the POS, purchase and stock-opname pages.
 *
 * It reads the product_stock view, which rebuilds the whole stock ledger (about 70,000 rows on the real data) on every
 * query: 340 ms to 1.1 s, and the three pages ran it on every visit. The answer only changes when the ledger changes,
 * so it is kept until then (LedgerCache): always current, and instant while nothing moves.
 */
final class NegativeStockWarning
{
    /** @param iterable<int|string> $branchIds */
    public static function forBranches(iterable $branchIds): Collection
    {
        $ids = collect($branchIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return LedgerCache::remember('negative_stock_warning:' . md5(implode(',', $ids)), fn () => self::query($ids));
    }

    private static function query(array $ids): Collection
    {
        return DB::table('product_stock')
            ->join('branch', 'product_stock.branch_id', '=', 'branch.id')
            ->select('product_stock.*', 'branch.name as branch_name')
            ->whereIn('product_stock.branch_id', $ids)
            ->where('stock_available', '<', 0)
            ->whereNull('is_variant')
            ->get();
    }
}
