<?php

namespace Tests\Feature;

use Tests\TestCase;

class SettingsReferenceLayoutTest extends TestCase
{
    public function test_settings_mobile_cards_match_the_reference_widths(): void
    {
        $styles = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('@media (max-width: 767.98px) {', $styles);
        $this->assertStringContainsString('.erp-ui .erp-settings-page {', $styles);
        $this->assertStringContainsString('padding:24px 0 20px;', $styles);
        $this->assertStringContainsString('background:#f8fafc;', $styles);
        $this->assertStringContainsString('width:min(100%, 384px);', $styles);
        $this->assertStringContainsString('width:min(100%, 448px);', $styles);
    }

    public function test_settings_page_uses_full_width_without_mobile_dock_clearance(): void
    {
        $styles = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('body:has(.erp-settings-page) #kt_content { padding:0; scroll-padding-bottom:0; }', $styles);
        $this->assertStringContainsString('width:100vw; margin-inline:calc(50% - 50vw);', $styles);
    }

    public function test_settings_option_rows_have_a_divider_between_each_row(): void
    {
        $styles = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('.erp-ui .erp-settings-page .erp-settings-options > .erp-settings-option + .erp-settings-option {', $styles);
        $this->assertStringContainsString('border-top:1px solid var(--settings-line);', $styles);
    }
}
