<?php

namespace App\Support;

/**
 * Diskon Nota and Diskon Ongkir of a POS sale: a number plus the unit the cashier picked ('percent' or 'amount').
 * A row without a unit was saved before the choice existed and keeps the old reading: 100 or less is a percentage,
 * above that it is rupiah.
 */
class PosDiscount
{
    public const PERCENT = 'percent';

    public const AMOUNT = 'amount';

    public static function isPercent(float|int|string|null $value, ?string $type): bool
    {
        $value = (float) $value;

        return $value > 0 && ($type === self::PERCENT || (! $type && $value <= 100));
    }

    /** The discount in rupiah, taken from $base. */
    public static function amount(float|int|string|null $value, ?string $type, float|int|string|null $base): float
    {
        return self::isPercent($value, $type) ? (float) $base * (float) $value / 100 : (float) $value;
    }

    /** The unit a stored discount is read with, to preselect the Rp / % buttons. */
    public static function typeOf(float|int|string|null $value, ?string $type): string
    {
        return $type ?: (self::isPercent($value, null) ? self::PERCENT : self::AMOUNT);
    }

    /** The same rule as amount(), for report queries. The arguments are column names written in code. */
    public static function sql(string $value, string $type, string $base): string
    {
        return "CASE WHEN COALESCE({$value}, 0) > 0 AND ({$type} = 'percent' OR ({$type} IS NULL AND {$value} <= 100))"
            . " THEN COALESCE({$base}, 0) * {$value} / 100 ELSE COALESCE({$value}, 0) END";
    }
}
