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
}
