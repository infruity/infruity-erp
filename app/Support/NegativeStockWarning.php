<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Products whose stock went below zero in the given branches: the warning on the POS, purchase and stock-opname pages.
 *
 * It reads the product_stock view, which rebuilds the whole stock ledger (about 70,000 rows on the real data) on every
 * query: 340 ms to 1.1 s, and the three pages ran it on every visit. The result only drives a warning banner, so it is
 * kept for a short time per set of branches. After a sale or a stock change the banner can lag by up to TTL seconds.
 */
final class NegativeStockWarning
{
    public const TTL = 60;

    /** @param iterable<int|string> $branchIds */
    public static function forBranches(iterable $branchIds): Collection
    {
        $ids = collect($branchIds)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return Cache::remember('negative_stock_warning:' . md5(implode(',', $ids)), self::TTL, fn () => self::query($ids));
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
