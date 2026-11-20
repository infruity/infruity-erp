<?php

namespace Tests\Unit;

use Modules\Master\Support\ProductIcon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductIconTest extends TestCase
{
    private function icon(string $name, string $category = ''): string
    {
        return ProductIcon::resolve((object) ['name' => $name, 'category' => (object) ['name' => $category]])[0];
    }

    #[DataProvider('fruits')]
    public function test_fruit_without_its_own_emoji_is_not_a_box(string $name, string $emoji): void
    {
        $this->assertSame($emoji, $this->icon($name));
    }

    public static function fruits(): array
    {
        return [
            'cherry' => ['Cherry', '🍒'],
            'cherry pack' => ['Cherry ( pack )', '🍒'],
            'leci' => ['Leci Merah Madu (Pack)', '🍒'],
            'plum' => ['Sugar Plum', '🍑'],
            'pear' => ['Pear Forella', '🍐'],
            'naga' => ['Naga Grade A', '🐉'],
            'kelengkeng' => ['Kelengkeng', '🟤'],
            'kurma' => ['Kurma Ajwa', '🟤'],
            'jamur' => ['Jamur Enoki', '🍄'],
            'ubi' => ['Ubi Ungu', '🍠'],
            'tomat' => ['Tomat', '🍅'],
        ];
    }

    public function test_packaging_and_unknown_names_keep_the_box(): void
    {
        foreach (['Plastik Oval', 'Kereta Dorong', 'Foam Tebal', 'Kardus Besar'] as $name) {
            $this->assertSame('📦', $this->icon($name), $name);
        }
    }

    public function test_names_that_already_had_an_icon_keep_it(): void
    {
        $this->assertSame('🍊', $this->icon('Jeruk Sunkist'));
        $this->assertSame('🍎', $this->icon('Apel Fuji'));
        $this->assertSame('🍓', $this->icon('Strawberry Mencir (Grade A)'));
        $this->assertSame('🐉', $this->icon('Buah Naga Merah'));
    }

    public function test_ubi_does_not_match_inside_other_words(): void
    {
        $this->assertSame('📦', $this->icon('Kubis'));
    }
}
