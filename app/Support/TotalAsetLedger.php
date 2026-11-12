<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The two ledger reads behind the "total aset" report, shared by the report and by `ledger:warm` so both build the
 * very same SQL (the cache key is made from it).
 *
 * The HPP numbers are still produced only by database/sql/product_hpp.sql; this class decides nothing about them.
 */
final class TotalAsetLedger
{
    /** Last moving-average HPP row of every product (seq is the position in that product's ledger). */
    public static function latestHpp(): Collection
    {
        return LedgerCache::remember('total_aset_latest_hpp', fn () => DB::table('product_hpp')
            ->select('product_id', 'total_aset_berjalan', 'hpp_berjalan', 'seq')
            ->orderByDesc('seq')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id'));
    }

    /** Stock per (parent) product from the ledger, before any branch filter. */
    public static function stockQuery(): Builder
    {
        return DB::table('transaction_stock as A')
            ->join('products as CHILD', 'A.product_id', '=', 'CHILD.id')
            ->leftJoin('product_child as pc', 'CHILD.id', '=', 'pc.product_id')
            ->join('products as PARENT', 'PARENT.id', '=', DB::raw('COALESCE(pc.parent_id, CHILD.id)'))
            ->join('product_units as C', 'PARENT.product_unit', '=', 'C.id')
            ->where('PARENT.tipe', '!=', 'parcel')
            ->selectRaw('COALESCE(pc.parent_id, A.product_id) as product_id')
            ->selectRaw('PARENT.name as name, C.abbreviation, PARENT.hpp')
            ->selectRaw('SUM(A.quantity) as total_stock')
            ->groupByRaw('COALESCE(pc.parent_id, A.product_id), PARENT.name, C.abbreviation, PARENT.hpp');
    }

    /** Rows of a (branch-filtered) stockQuery(), kept until the ledger changes. */
    public static function stockRows(Builder $query): Collection
    {
        return LedgerCache::rememberQuery($query, 'total_aset_stock');
    }
}
