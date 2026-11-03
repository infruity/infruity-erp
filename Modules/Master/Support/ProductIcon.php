<?php

namespace Modules\Master\Support;

class ProductIcon
{
    /**
     * Resolve the product avatar from its name and category.
     *
     * @return array{0: string, 1: string}
     */
    public static function resolve($product): array
    {
        $needle = mb_strtolower(($product->name ?? '') . ' ' . data_get($product, 'category.name', ''));

        if (str_contains($needle, 'jambu air') && (str_contains($needle, 'hijau') || str_contains($needle, 'madu'))) {
            return ['🍏', 'bg-emerald-50'];
        }
        if (str_contains($needle, 'jambu air') || str_contains($needle, 'jambu biji')) {
            return ['🍎', 'bg-rose-50'];
        }
        if (str_contains($needle, 'mangga') && (str_contains($needle, 'manalagi') || str_contains($needle, 'gincu'))) {
            return ['🥭', 'bg-emerald-50'];
        }
        if (str_contains($needle, 'jeruk pamelo') || str_contains($needle, 'jeruk bali')) {
            return ['🍈', 'bg-amber-50'];
        }

        $map = [
            'stroberi' => ['🍓', 'bg-pink-100'],
            'strawberry' => ['🍓', 'bg-pink-100'],
            'mangga' => ['🥭', 'bg-amber-50'],
            'jambu' => ['🍎', 'bg-rose-50'],
            'pisang' => ['🍌', 'bg-yellow-50'],
            'jeruk' => ['🍊', 'bg-orange-50'],
            'apel hijau' => ['🍏', 'bg-emerald-50'],
            'apel' => ['🍎', 'bg-red-50'],
            'anggur' => ['🍇', 'bg-purple-50'],
            'nanas' => ['🍍', 'bg-yellow-50'],
            'semangka' => ['🍉', 'bg-red-50'],
            'melon' => ['🍈', 'bg-lime-50'],
            'alpukat' => ['🥑', 'bg-green-50'],
            'nangka' => ['🟡', 'bg-yellow-50'],
            'durian' => ['🟢', 'bg-green-50'],
            'sirsak' => ['🟢', 'bg-green-50'],
            'kelapa' => ['🥥', 'bg-stone-50'],
        ];

        foreach ($map as $keyword => $icon) {
            if (str_contains($needle, $keyword)) {
                return $icon;
            }
        }

        return ['📦', 'bg-gray-100'];
    }
}
