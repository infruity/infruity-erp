<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\ProductChild;
use Modules\Transaction\Entities\ProductHppRunning;
use Modules\Transaction\Entities\ProductStock;

/**
 * Per-product versions of the product_hpp and product_stock views.
 *
 * Both views rebuild the ledger of every product on each query, and MySQL
 * cannot push a product filter into them (product_hpp is a recursive CTE).
 * These builders run the exact SQL of database/sql/*.sql with a product
 * filter at its /*:...*\/ markers, so only the asked products are computed
 * and the rows are the same as reading the view.
 *
 * The ids are written into the SQL as integers, not bound: MySQL 8.4 returns
 * wrong rows for this recursive CTE as a server-side prepared statement with
 * bound parameters and "select *" (seen with 20+ ids). The ids are cast to
 * int first, so this is safe.
 *
 * On other drivers (SQLite in tests) they read the table/view as before.
 */
class LedgerSql
{
    private static array $sql = [];

    /**
     * product_hpp rows of the given ledger ids. A variant has no own ledger
     * (it rolls up to its parent), same as in the view.
     */
    public static function hpp(iterable $ledgerIds): Builder
    {
        $ids = self::ids($ledgerIds);
        if (! self::filtered() || $ids === []) {
            return ProductHppRunning::query()->whereIn('product_hpp.product_id', $ids);
        }

        // Rows of the ledger itself and of its variants roll up into it.
        $members = array_values(array_unique(array_merge(
            $ids,
            ProductChild::whereIn('parent_id', $ids)->pluck('product_id')->map(fn ($id) => (int) $id)->all()
        )));

        $filter = 'b.product_id IN (' . self::list($members) . ') AND COALESCE(pc.parent_id, b.product_id) IN (' . self::list($ids) . ') AND';
        $sql = str_replace('/*:ledger_filter*/', $filter, self::body('product_hpp'));

        return ProductHppRunning::query()->fromRaw('(' . $sql . ') as product_hpp');
    }

    /** product_stock rows of the given product ids (variants included). */
    public static function stock(iterable $productIds): Builder
    {
        $ids = self::ids($productIds);
        if (! self::filtered() || $ids === []) {
            return ProductStock::query()->whereIn('product_stock.id', $ids);
        }

        // A product's stock row reads the ledger of its parent (or its own).
        $parentOf = ProductChild::whereIn('product_id', $ids)->pluck('parent_id', 'product_id')->all();
        $ledgers = array_values(array_unique(array_map(fn ($id) => (int) ($parentOf[$id] ?? $id), $ids)));

        $sql = strtr(self::body('product_stock'), [
            '/*:parent_filter*/' => 'WHERE ts.product_id IN (' . self::list($ledgers) . ')',
            '/*:child_filter*/' => 'WHERE pc.parent_id IN (' . self::list($ledgers) . ')',
            '/*:product_filter*/' => 'AND A.id IN (' . self::list($ids) . ')',
        ]);

        return ProductStock::query()->fromRaw('(' . $sql . ') as product_stock');
    }

    /** Above this many products one read of the whole view is cheaper (real data: 10 ids 17 ms, 30 ids 86 ms, 200 ids 680 ms, view 335 ms). */
    public const FILTER_LIMIT = 40;

    /**
     * product_stock rows of the given products, computed the cheapest way: only theirs when there are few, the view
     * otherwise. Same rows either way.
     */
    public static function stockFor(iterable $productIds): Builder
    {
        $ids = self::ids($productIds);

        return count($ids) <= self::FILTER_LIMIT
            ? self::stock($ids)
            : ProductStock::query()->whereIn('product_stock.id', $ids);
    }

    private static function filtered(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    /** The SELECT of a view file, without "CREATE OR REPLACE VIEW name AS" and the final ";". */
    private static function body(string $view): string
    {
        if (! isset(self::$sql[$view])) {
            $file = file_get_contents(database_path("sql/{$view}.sql"));
            if (! preg_match('/CREATE\s+OR\s+REPLACE\s+VIEW\s+`?' . $view . '`?\s+AS\s+(.*?);/is', $file, $match)) {
                throw new \RuntimeException("database/sql/{$view}.sql has no view definition.");
            }
            self::$sql[$view] = $match[1];
        }

        return self::$sql[$view];
    }

    /** Comma list of integers for an IN (...) clause. */
    private static function list(array $ids): string
    {
        return implode(',', array_map('intval', $ids));
    }

    /** @return list<int> */
    private static function ids(iterable $values): array
    {
        $ids = [];
        foreach ($values as $id) {
            if (is_numeric($id)) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }
}
