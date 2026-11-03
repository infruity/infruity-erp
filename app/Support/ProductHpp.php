<?php

namespace App\Support;

use Modules\Master\Entities\Product;
use Modules\Master\Entities\ProductChild;

/**
 * Reads the moving HPP from the product_hpp ledger. Variants share their
 * parent's ledger, the same way product_hpp rolls them up.
 */
class ProductHpp
{
    /**
     * Current moving HPP per product id. Falls back to products.hpp when the
     * product has no stock history yet.
     *
     * @return array<int, float>
     */
    public static function current(iterable $productIds): array
    {
        $ids = self::ids($productIds);
        if ($ids === []) {
            return [];
        }

        $ledgerIds = self::ledgerIds($ids);
        $latest = InventorySnapshot::latestHpp(array_values($ledgerIds));
        $fallback = Product::whereIn('id', $ids)->pluck('hpp', 'id')->all();

        $hpp = [];
        foreach ($ids as $id) {
            $row = $latest[$ledgerIds[$id]] ?? null;
            $hpp[$id] = $row !== null ? (float) $row->hpp_berjalan : (float) ($fallback[$id] ?? 0);
        }

        return $hpp;
    }

    /**
     * Copies the ledger HPP into products.hpp for the given products, their
     * parents and all variants of those parents. products.hpp is only a cached
     * copy for screens that still read it, the ledger stays the source.
     */
    public static function syncCatalog(iterable $productIds): void
    {
        $ids = self::ids($productIds);
        if ($ids === []) {
            return;
        }

        $parents = array_values(array_unique(self::ledgerIds($ids)));
        $latest = InventorySnapshot::latestHpp($parents);

        foreach ($parents as $parentId) {
            $row = $latest[$parentId] ?? null;
            if ($row === null) {
                continue;
            }

            $members = ProductChild::where('parent_id', $parentId)->pluck('product_id')->all();
            $members[] = $parentId;

            Product::whereIn('id', $members)->update(['hpp' => (float) $row->hpp_berjalan]);
        }
    }

    /** @return array<int, int> product id => ledger (parent) id */
    private static function ledgerIds(array $ids): array
    {
        $parentOf = ProductChild::whereIn('product_id', $ids)->pluck('parent_id', 'product_id')->all();

        $map = [];
        foreach ($ids as $id) {
            $map[$id] = (int) ($parentOf[$id] ?? $id);
        }

        return $map;
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
