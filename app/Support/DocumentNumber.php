<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Numbers like PO202611007: a prefix followed by a counter that restarts with the prefix.
 *
 * Reading the highest number and saving the next one must not run twice at once. Take lock() before the transaction
 * that saves the document begins (so its first read is made while holding the lock), and unlock() after it ends.
 */
class DocumentNumber
{
    /** The next free number of a prefix. The whole counter is read, so it keeps going past 999. */
    public static function next(string $model, string $column, string $prefix, int $digits = 3): string
    {
        $last = $model::query()
            ->where($column, 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING({$column}, ?) AS UNSIGNED)) AS last_number", [strlen($prefix) + 1])
            ->value('last_number');

        return $prefix . str_pad((string) (((int) $last) + 1), $digits, '0', STR_PAD_LEFT);
    }

    public static function lock(string $type): void
    {
        if (self::locksAreSupported()
            && (int) DB::selectOne('SELECT GET_LOCK(?, 10) AS locked', [self::lockName($type)])->locked !== 1) {
            abort(503, 'Nomor dokumen sedang dibuat pengguna lain. Coba lagi.');
        }
    }

    /** Safe to call when the lock is no longer held (a disconnect releases it). */
    public static function unlock(string $type): void
    {
        if (self::locksAreSupported()) {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [self::lockName($type)]);
        }
    }

    private static function locksAreSupported(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** Lock names are shared by the whole database server, and other sites run on it. */
    private static function lockName(string $type): string
    {
        return 'docnum:' . substr(md5(DB::getDatabaseName()), 0, 12) . ':' . $type;
    }
}
