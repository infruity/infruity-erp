<?php

namespace Tests\Feature;

use Tests\TestCase;

class AutoHideScrollbarsTest extends TestCase
{
    public function test_scrollbars_are_thin_and_invisible_until_scrolling_or_hover(): void
    {
        $css = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('* { scrollbar-width: thin; scrollbar-color: transparent transparent; }', $css);
        $this->assertStringContainsString('*.erp-scrolling, *:hover { scrollbar-color: rgba(107, 114, 128, .45) transparent; }', $css);
    }

    public function test_the_script_marks_the_scrolling_element_for_a_moment_and_both_layouts_load_it(): void
    {
        $js = file_get_contents(public_path('assets/js/erp-scrollbars.js'));

        $this->assertStringContainsString("el.classList.add('erp-scrolling')", $js);
        $this->assertStringContainsString("el.classList.remove('erp-scrolling')", $js);
        $this->assertStringContainsString('capture: true', $js, 'scroll events do not bubble');
        foreach (['resources/views/template/root.blade.php', 'resources/views/layouts/erp-tailwind.blade.php'] as $layout) {
            $this->assertStringContainsString('assets/js/erp-scrollbars.js', file_get_contents(base_path($layout)), $layout);
        }
    }
}
