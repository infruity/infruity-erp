<?php

namespace Tests\Unit;

use App\Support\AvatarTone;
use Modules\Master\Support\ProductIcon;
use Tests\TestCase;

class ProductIconTest extends TestCase
{
    private function resolve(?string $name): array
    {
        return ProductIcon::resolve((object) ['name' => $name]);
    }

    public function test_avatar_is_the_first_two_letters_of_the_name_in_capitals(): void
    {
        $this->assertSame('CH', $this->resolve('Cherry')[0]);
        $this->assertSame('SU', $this->resolve('Sugar Plum (Pack)')[0]);
        $this->assertSame('LE', $this->resolve('leci hijau madu')[0]);
        $this->assertSame('KE', $this->resolve('  Kelengkeng ')[0]);
    }

    public function test_every_product_gets_initials_never_an_emoji(): void
    {
        foreach (['Cherry', 'Kurma Ajwa', 'Plastik Oval', 'Salak', 'Naga Merah', '100K - 1', 'Jus Mangga'] as $name) {
            $this->assertMatchesRegularExpression('/^[\p{L}\p{N}]{1,2}$/u', $this->resolve($name)[0], $name);
        }
    }

    public function test_tint_is_the_shared_erp_tone_of_the_name(): void
    {
        $this->assertSame(AvatarTone::cssClass('Cherry'), $this->resolve('Cherry')[1]);
        $this->assertMatchesRegularExpression('/^erp-tone-[0-9]$/', $this->resolve('Cherry')[1]);
        $this->assertSame($this->resolve('Jus Mangga')[1], $this->resolve('Jus Mangga')[1]);
    }

    public function test_one_letter_and_empty_names_do_not_break(): void
    {
        $this->assertSame('A', $this->resolve('a')[0]);
        $this->assertSame('?', $this->resolve('')[0]);
        $this->assertSame('?', $this->resolve(null)[0]);
    }
}
