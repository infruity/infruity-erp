<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * One writer at a time may take stock out of a branch: sale payment, delivery, sorting, transfer, production, parcel,
 * stock count and purchase deletion all check what is left and then take it.
 *
 * Row locks inside the transaction are not enough. Under REPEATABLE READ the stock is read from a snapshot made at the
 * transaction's first plain read, so a request that waited for a lock still sees the old stock and takes it again.
 * Therefore take this BEFORE the transaction begins, and release it after the transaction ends. Take it before any
 * DocumentNumber lock, never after.
 */
class StockLock
{
    /** @var list<string> */
    private static array $held = [];

    public static function lock(int|string|null ...$branchIds): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $branchIds))));
        sort($ids); // the same order for everybody, so two requests cannot wait for each other
        foreach ($ids as $id) {
            $name = 'stock:' . substr(md5(DB::getDatabaseName()), 0, 12) . ':' . $id;
            if ((int) DB::selectOne('SELECT GET_LOCK(?, 15) AS locked', [$name])->locked !== 1) {
                self::release();
                abort(503, 'Stok cabang ini sedang diproses pengguna lain. Coba lagi sebentar.');
            }
            self::$held[] = $name;
        }
    }

    /** Runs the work (which opens and closes its own transaction) while holding the branches' stock. */
    public static function during(array $branchIds, \Closure $work): mixed
    {
        self::lock(...$branchIds);
        try {
            return $work();
        } finally {
            self::release();
        }
    }

    /** Safe to call more than once, and after a disconnect (which releases the locks by itself). */
    public static function release(): void
    {
        while ($name = array_pop(self::$held)) {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
        }
    }
}
