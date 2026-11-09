<?php

namespace App\Support;

final class Like
{
    /**
     * Build a LIKE pattern that matches $term literally, so a typed % or _ is not a wildcard.
     * MySQL's default escape character is the backslash. A missing keyword (an empty query-string
     * value arrives as null) matches everything, as the old "%{$term}%" interpolation did.
     */
    public static function contains(?string $term): string
    {
        return '%' . self::escape($term) . '%';
    }

    /** Escape LIKE wildcards in $term without adding any of its own. */
    public static function escape(?string $term): string
    {
        return addcslashes((string) $term, '\\%_');
    }
}
