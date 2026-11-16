<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class MobileNavbarScrollContractTest extends TestCase
{
    public function test_the_dock_only_shrinks_for_a_list_that_really_scrolls(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/erp/mobile-navbar.blade.php');

        // The scroll listener first checks that the scrolled element overflows by more than a few pixels.
        $this->assertStringContainsString('const reallyScrolls = target =>', $view);
        $this->assertStringContainsString('scroller.scrollHeight - scroller.clientHeight > 24', $view);
        $this->assertStringContainsString('if (!reallyScrolls(event.target)) return;', $view);
        $this->assertLessThan(
            strpos($view, 'handleScrollActivity();', strpos($view, "window.addEventListener('scroll'")),
            strpos($view, 'if (!reallyScrolls(event.target)) return;'),
        );
    }

    public function test_dock_ignores_scrolls_the_page_makes_by_itself(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/erp/mobile-navbar.blade.php');

        $this->assertStringContainsString("['touchstart', 'touchmove', 'wheel', 'keydown', 'pointerdown', 'mousedown']", $view);
        $this->assertStringContainsString('Date.now() - lastGesture > 1200', $view);
    }
}
