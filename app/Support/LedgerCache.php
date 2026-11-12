<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Exact cache for results computed from the stock ledger (the product_stock and product_hpp views).
 *
 * Those views rebuild the whole ledger (about 70,000 rows) on every query: 0.3 s for the stock, 1.1 s for the moving
 * average HPP. The result only changes when a row of the ledger tables changes, so the cache is tied to a fingerprint
 * of those tables (CHECKSUM TABLE: every column of every row, 40 ms) and of the view definitions. Any change, including
 * edits and hard deletes, changes the fingerprint and the next read recomputes; it never serves an older ledger, and no
 * HPP rule is re-implemented: the numbers are still produced by database/sql/product_hpp.sql.
 *
 * One file per key (the entry is overwritten when the fingerprint moves), so the cache cannot grow with every sale.
 * Without a MySQL/MariaDB connection (SQLite in tests) nothing is cached.
 */
final class LedgerCache
{
    /** Every table the ledger views read (transaction_stock*.sql, product_stock.sql, product_hpp.sql). */
    public const TABLES = [
        'stock_in', 'stock_out', 'stock_out_transaction', 'stock_opname',
        'wholesale', 'wholesale_product',
        'production', 'production_detail', 'production_parcel_detail',
        'pos_transaction', 'pos_transaction_detail',
        'sortir_transaction', 'sortir_transaction_detail',
        'transfer', 'transfer_detail',
        'product_child', 'products', 'product_units', 'branch',
    ];

    private const VIEW_FILES = ['transaction_stock', 'transaction_stock_show', 'product_stock', 'product_hpp'];

    /** Safety net for abandoned keys only; correctness comes from the fingerprint. */
    private const TTL = 86400;

    /** md5 of the ledger tables and view definitions, or null when it cannot be computed (then nothing is cached). */
    public static function fingerprint(): ?string
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return null;
        }

        try {
            $quoted = implode(', ', array_map(fn ($t) => '`' . $t . '`', self::TABLES));
            $parts = [];
            foreach (DB::select('CHECKSUM TABLE ' . $quoted) as $row) {
                $row = (array) $row;
                if (($row['Checksum'] ?? null) === null) {
                    return null; // table missing or unreadable: do not trust it
                }
                $parts[] = $row['Table'] . '=' . $row['Checksum'];
            }
            foreach (self::VIEW_FILES as $view) {
                $parts[] = $view . '=' . md5_file(database_path("sql/{$view}.sql"));
            }

            return md5(implode('|', $parts));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The value of $compute, kept until the ledger changes. $key must name everything the result depends on besides
     * the ledger (branch scope, filters, the SQL itself).
     *
     * @template T
     * @param  Closure(): T  $compute
     * @return T
     */
    public static function remember(string $key, Closure $compute): mixed
    {
        $fingerprint = self::fingerprint();
        if ($fingerprint === null) {
            return $compute();
        }

        $key = 'ledger_cache:' . $key;
        $hit = Cache::get($key);
        if (is_array($hit) && ($hit['fingerprint'] ?? null) === $fingerprint) {
            return $hit['value'];
        }

        $value = $compute();
        Cache::put($key, ['fingerprint' => $fingerprint, 'value' => $value], self::TTL);

        return $value;
    }

    /** get() of a query builder, kept until the ledger changes; the key is the query's SQL and bindings. */
    public static function rememberQuery(QueryBuilder $query, string $name): mixed
    {
        return self::remember($name . ':' . md5($query->toSql() . '|' . json_encode($query->getBindings())), fn () => $query->get());
    }
}
