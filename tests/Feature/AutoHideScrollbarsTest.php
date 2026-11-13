<?php

namespace Tests\Feature;

use Tests\TestCase;

class AutoHideScrollbarsTest extends TestCase
{
    public function test_native_scrollbars_are_off_so_they_take_no_layout_space(): void
    {
        $css = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('* { scrollbar-width: none; }', $css);
        $this->assertStringContainsString('*::-webkit-scrollbar { display: none; }', $css);
        $this->assertStringContainsString('.erp-thumb { position: fixed;', $css, 'the thumb is outside the layout');
        $this->assertStringContainsString('pointer-events: none; opacity: 0;', $css);
    }

    public function test_the_script_draws_an_overlay_thumb_while_scrolling_and_leaves_custom_bars_alone(): void
    {
        $js = file_get_contents(public_path('assets/js/erp-scrollbars.js'));

        $this->assertStringContainsString("node.className = 'erp-thumb erp-thumb--' + axis;", $js);
        $this->assertStringContainsString("el.classList.contains('scrollbar-hide')", $js);
        $this->assertStringContainsString("getComputedStyle(el).scrollbarWidth !== 'none'", $js);
        $this->assertStringContainsString('capture: true', $js, 'scroll events do not bubble');
        $this->assertStringContainsString('HIDE_AFTER', $js);
        foreach (['resources/views/template/root.blade.php', 'resources/views/layouts/erp-tailwind.blade.php'] as $layout) {
            $this->assertStringContainsString('assets/js/erp-scrollbars.js', file_get_contents(base_path($layout)), $layout);
        }
    }
}
