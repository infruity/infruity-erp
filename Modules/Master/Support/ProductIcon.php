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
            'mango' => ['🥭', 'bg-amber-50'],
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
            'pepaya' => ['🍈', 'bg-orange-50'],
            'buah naga' => ['🐉', 'bg-pink-50'],
            'dragon' => ['🐉', 'bg-pink-50'],
            'kiwi' => ['🥝', 'bg-green-50'],
            'berry' => ['🫐', 'bg-violet-50'],
            'blueberry' => ['🫐', 'bg-violet-50'],
            'lemon' => ['🍋', 'bg-yellow-50'],
            'wortel' => ['🥕', 'bg-orange-50'],
            'salad' => ['🥗', 'bg-lime-50'],
            'rujak' => ['🥗', 'bg-lime-50'],
            'rainbow' => ['🌈', 'bg-purple-50'],
            'parfait' => ['🍨', 'bg-pink-50'],
            'dessert' => ['🍨', 'bg-pink-50'],
            'sticky rice' => ['🍚', 'bg-amber-50'],
            'fruit box premium' => ['🎁', 'bg-purple-50'],
            'fruit box' => ['📦', 'bg-sky-50'],
            'keranjang' => ['🧺', 'bg-amber-50'],
            'jar' => ['🫙', 'bg-lime-50'],
            'bowl' => ['🥣', 'bg-orange-50'],
            'sop buah' => ['🥣', 'bg-sky-50'],
            'cold press' => ['🧃', 'bg-amber-50'],
            'detox' => ['🥬', 'bg-green-50'],
            'infused' => ['💧', 'bg-sky-50'],
            'smoothie' => ['🥤', 'bg-amber-50'],
            'milkshake' => ['🥤', 'bg-pink-50'],
            'jus' => ['🧃', 'bg-orange-50'],
            'mix fruit' => ['🍱', 'bg-green-50'],
            'buah potong' => ['🍱', 'bg-green-50'],
            'buah' => ['🍱', 'bg-green-50'],
            // Added after the entries above so a name that already had an icon keeps it. Unicode has no emoji for
            // lychee, longan, salak, dates or plum; those use the nearest fruit or a round brown/red fruit.
            'cherry' => ['🍒', 'bg-red-50'],
            'leci' => ['🍒', 'bg-rose-50'],
            'lychee' => ['🍒', 'bg-rose-50'],
            'rambutan' => ['🍒', 'bg-red-50'],
            'plum' => ['🍑', 'bg-rose-50'],
            'nectarine' => ['🍑', 'bg-orange-50'],
            'kesemek' => ['🍑', 'bg-orange-50'],
            'pear' => ['🍐', 'bg-lime-50'],
            'naga' => ['🐉', 'bg-pink-50'],
            'kelengkeng' => ['🟤', 'bg-amber-50'],
            'longan' => ['🟤', 'bg-amber-50'],
            'duku' => ['🟤', 'bg-amber-50'],
            'salak' => ['🟤', 'bg-amber-50'],
            'kurma' => ['🟤', 'bg-amber-50'],
            'mathoa' => ['🟤', 'bg-amber-50'],
            'matoa' => ['🟤', 'bg-amber-50'],
            'manggis' => ['🍇', 'bg-purple-50'],
            'juwet' => ['🍇', 'bg-purple-50'],
            'delima' => ['🍎', 'bg-red-50'],
            'degan' => ['🥥', 'bg-stone-50'],
            'jamur' => ['🍄', 'bg-stone-50'],
            'edamame' => ['🫛', 'bg-green-50'],
            'ubi ' => ['🍠', 'bg-orange-50'],
            'timun' => ['🥒', 'bg-green-50'],
            'tomat' => ['🍅', 'bg-red-50'],
            'sambal' => ['🌶️', 'bg-red-50'],
            'nampan' => ['🧺', 'bg-amber-50'],
            'rotan' => ['🧺', 'bg-amber-50'],
            'mangkok' => ['🥣', 'bg-orange-50'],
        ];

        if (preg_match('/\bes\b/u', $needle)) {
            return ['🧊', 'bg-sky-50'];
        }

        foreach ($map as $keyword => $icon) {
            if (str_contains($needle, $keyword)) {
                return $icon;
            }
        }

        return ['📦', 'bg-gray-100'];
    }
}
