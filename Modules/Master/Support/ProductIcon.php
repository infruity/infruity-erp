<?php

namespace Modules\Master\Support;

use App\Support\AvatarTone;

class ProductIcon
{
    /**
     * Resolve the product avatar: the first two letters of its name on a tint, like the unit, branch and customer
     * avatars. The tint is the shared erp-tone-N class (same name, same colour on every page).
     *
     * No emoji any more: Unicode has none for many local fruits (leci, kelengkeng, salak, kurma ...), so those fell
     * back to a box.
     *
     * @return array{0: string, 1: string} [initials, css class]
     */
    public static function resolve($product): array
    {
        $name = trim((string) ($product->name ?? ''));
        $initials = $name === '' ? '?' : mb_strtoupper(mb_substr($name, 0, 2));

        return [$initials, AvatarTone::cssClass($name)];
    }
}
