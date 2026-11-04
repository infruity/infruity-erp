<?php

namespace Tests\Unit;

use App\Support\ProductGlyph;
use PHPUnit\Framework\TestCase;

final class ProductGlyphTest extends TestCase
{
    public function test_known_fruit_names_get_their_emoji_and_a_soft_tile_colour(): void
    {
        $this->assertSame('🍓', ProductGlyph::for('Jus Stroberi')['emoji']);
        $this->assertSame('🥑', ProductGlyph::for('Jus Alpukat')['emoji']);
        $this->assertSame('🍉', ProductGlyph::for('Semangka Merah Potong 250g')['emoji']);
        $this->assertSame('🥗', ProductGlyph::for('Salad Buah Kecil')['emoji']);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', ProductGlyph::for('Jus Stroberi')['color']);
    }

    public function test_the_first_matching_ingredient_wins_over_the_generic_drink_word(): void
    {
        $this->assertSame('🥭', ProductGlyph::for('Smoothie Mangga Pisang')['emoji']);
    }

    public function test_unknown_or_empty_names_fall_back_to_the_drink_glyph(): void
    {
        $this->assertSame('🧃', ProductGlyph::for('Produk Misterius')['emoji']);
        $this->assertSame('🧃', ProductGlyph::for('')['emoji']);
        $this->assertSame('🧃', ProductGlyph::for(null)['emoji']);
    }

    public function test_matching_ignores_case(): void
    {
        $this->assertSame('🍓', ProductGlyph::for('JUS STROBERI')['emoji']);
    }
}
