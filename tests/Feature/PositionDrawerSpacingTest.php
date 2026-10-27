<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class PositionDrawerSpacingTest extends TestCase
{
    public function test_position_drawer_uses_tighter_spacing_without_changing_other_master_drawers(): void
    {
        $root = dirname(__DIR__, 2);
        $positionView = file_get_contents($root . '/Modules/Master/Resources/views/position/index.blade.php');
        $sharedView = file_get_contents($root . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');

        self::assertStringContainsString("\$formBodyClass = 'px-5 pt-3 pb-5 space-y-3';", $positionView);
        self::assertStringContainsString("{{ \$formBodyClass ?? 'px-5 py-5 space-y-5' }}", $sharedView);
    }
}
