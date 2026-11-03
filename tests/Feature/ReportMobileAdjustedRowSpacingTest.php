<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportMobileAdjustedRowSpacingTest extends TestCase
{
    public function test_adjusted_profit_rows_use_compact_mobile_spacing(): void
    {
        $styles = file_get_contents(public_path('assets/css/report-mobile.css'));

        $this->assertStringContainsString(
            '.report-mobile-page--adjusted .report-mobile-row { min-height:60px; padding-top:10px; padding-bottom:10px; }',
            $styles
        );
    }
}
