<?php

namespace App\Support;

/**
 * Which tint an initials avatar gets (branch, customer, ...): the same name always the same one, different names spread
 * over the ten tones. Same FNV-1a hash as public/assets/js/erp-tone.js, so server-rendered and script-rendered avatars agree.
 * The colours are the .erp-tone-N classes in erp-theme.css.
 */
final class AvatarTone
{
    public const COUNT = 10;

    public static function index(?string $name): int
    {
        $value = mb_strtolower(trim((string) $name));
        $units = $value === '' ? [] : array_values(unpack('n*', mb_convert_encoding($value, 'UTF-16BE', 'UTF-8')));
        $hash = 2166136261; // FNV-1a over the UTF-16 code units, like erp-tone.js
        foreach ($units as $unit) {
            $hash = (($hash ^ $unit) * 16777619) & 0xFFFFFFFF;
        }

        return $hash % self::COUNT;
    }

    public static function cssClass(?string $name): string
    {
        return 'erp-tone-'.self::index($name);
    }
}
