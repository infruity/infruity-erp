<?php

namespace App\Support;

use Modules\Transaction\Entities\ProductHppRunning;
use Modules\Transaction\Entities\ProductStock;

/**
 * Batch lookups for the product_stock and product_hpp views.
 *
 * Both views rebuild the whole stock ledger on every query, so asking for
 * ten products costs the same as asking for one. Fetch all products of a
 * transaction in one call instead of querying inside item loops.
 */
class InventorySnapshot
{
    /**
     * stock_available per product id for one branch. Every requested id is in
     * the result; products without a stock row get 0 (same as the old
     * "$productStock ? ... : 0" fallback).
     *
     * @return array<int, float>
     */
    public static function stockAvailable(iterable $productIds, $branchId): array
    {
        $ids = self::ids($productIds);
        if ($ids === []) {
            return [];
        }

        $stock = array_fill_keys($ids, 0.0);
        $seen = [];

        $rows = ProductStock::query()
            ->whereIn('id', $ids)
            ->where('branch_id', $branchId)
            ->get(['id', 'stock_available']);

        // Keep the first row per product, like the former ->first() lookups.
        foreach ($rows as $row) {
            if (! isset($seen[$row->id])) {
                $seen[$row->id] = true;
                $stock[(int) $row->id] = (float) $row->stock_available;
            }
        }

        return $stock;
    }

    /**
     * Latest product_hpp row per product id (highest ledger seq). Ids without
     * HPP history map to null.
     *
     * @return array<int, ProductHppRunning|null>
     */
    public static function latestHpp(iterable $productIds): array
    {
        $ids = self::ids($productIds);
        if ($ids === []) {
            return [];
        }

        $latest = array_fill_keys($ids, null);

        $rows = ProductHppRunning::query()
            ->whereIn('product_id', $ids)
            ->orderBy('seq', 'desc')
            ->get();

        foreach ($rows as $row) {
            $latest[(int) $row->product_id] ??= $row;
        }

        return $latest;
    }

    /** @return list<int> */
    private static function ids(iterable $productIds): array
    {
        $ids = [];
        foreach ($productIds as $id) {
            if (is_numeric($id)) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }
}
