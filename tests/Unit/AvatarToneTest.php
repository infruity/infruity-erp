<?php

namespace Tests\Unit;

use App\Support\AvatarTone;
use PHPUnit\Framework\TestCase;

class AvatarToneTest extends TestCase
{
    public function test_it_matches_the_javascript_side_number_for_number(): void
    {
        // The same table is pinned in tests/js/erp-tone.test.cjs.
        $table = ['Toko GKB' => 0, 'Toko GBA' => 8, 'Gresik / Driyorejo - 001' => 4, 'Surabaya / Rungkut - 001' => 6, 'Pelanggan Ünï' => 7, '' => 1, 'x' => 5];

        foreach ($table as $name => $index) {
            $this->assertSame($index, AvatarTone::index((string) $name), $name);
        }
        $this->assertSame(AvatarTone::index('Toko GKB'), AvatarTone::index('  toko gkb '));
        $this->assertSame('erp-tone-0', AvatarTone::cssClass('Toko GKB'));
    }

    public function test_every_tone_has_its_colours_in_the_theme(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/assets/css/erp-theme.css');

        for ($i = 0; $i < AvatarTone::COUNT; $i++) {
            $this->assertStringContainsString(".erp-ui .erp-tone-$i {", $css);
        }
    }
}
