<?php

namespace App\Support;

/**
 * Emoji and soft tile colour shown as a product's avatar in mobile lists, picked from the product name.
 * Purely presentational: nothing is stored.
 */
final class ProductGlyph
{
    private const DEFAULT = ['emoji' => '🧃', 'color' => '#fff3e0'];

    /** First keyword found in the name wins, so fruit words must come before generic ones. */
    private const KEYWORDS = [
        'stroberi' => ['🍓', '#fce4ec'],
        'strawberry' => ['🍓', '#fce4ec'],
        'alpukat' => ['🥑', '#e8f5e9'],
        'semangka' => ['🍉', '#fce4ec'],
        'melon' => ['🍈', '#f1f8e9'],
        'mangga' => ['🥭', '#fff8e1'],
        'jeruk' => ['🍊', '#fff3e0'],
        'pisang' => ['🍌', '#fffde7'],
        'anggur' => ['🍇', '#ede7f6'],
        'apel' => ['🍎', '#ffebee'],
        'nanas' => ['🍍', '#fffde7'],
        'kiwi' => ['🥝', '#f1f8e9'],
        'pir' => ['🍐', '#f1f8e9'],
        'lemon' => ['🍋', '#fffde7'],
        'kelapa' => ['🥥', '#efebe9'],
        'salad' => ['🥗', '#f1f8e9'],
    ];

    /** @return array{emoji: string, color: string} */
    public static function for(?string $name): array
    {
        $haystack = mb_strtolower((string) $name);

        foreach (self::KEYWORDS as $keyword => [$emoji, $color]) {
            if ($haystack !== '' && str_contains($haystack, $keyword)) {
                return ['emoji' => $emoji, 'color' => $color];
            }
        }

        return self::DEFAULT;
    }
}
