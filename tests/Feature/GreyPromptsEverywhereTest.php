<?php

namespace Tests\Feature;

use Tests\TestCase;

class GreyPromptsEverywhereTest extends TestCase
{
    public function test_the_theme_has_one_grey_for_placeholders_empty_selects_and_empty_dates(): void
    {
        $css = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('input::placeholder, textarea::placeholder { color: #9ca3af; opacity: 1; }', $css);
        $this->assertStringContainsString('select.erp-empty, input.erp-empty { color: #9ca3af !important; }', $css);
        $this->assertStringContainsString('select.erp-empty option { color: #1f2937 !important; }', $css);
        $this->assertStringNotContainsString('color: var(--erp-muted); font-weight: 400; }', explode('.form-control::placeholder', $css)[1] ?? '');
    }

    public function test_both_layouts_load_the_script_that_marks_empty_fields(): void
    {
        foreach (['resources/views/template/root.blade.php', 'resources/views/layouts/erp-tailwind.blade.php'] as $layout) {
            $this->assertStringContainsString("assets/js/erp-empty-fields.js", file_get_contents(base_path($layout)), $layout);
        }
        $js = file_get_contents(public_path('assets/js/erp-empty-fields.js'));
        $this->assertStringContainsString("/^[\\s\\-\\u2013]*pilih/i.test(option.textContent.trim())", $js);
        $this->assertStringContainsString("el.classList.toggle('erp-empty', !el.value)", $js);
        $this->assertStringContainsString('setInterval(', $js, 'values set by scripts fire no event');
    }
}
