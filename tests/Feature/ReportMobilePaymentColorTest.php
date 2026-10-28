<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportMobilePaymentColorTest extends TestCase
{
    public function test_sales_history_payment_value_has_a_blue_only_payment_style(): void
    {
        $template = file_get_contents(base_path('Modules/Report/Resources/views/partials/mobile-detail.blade.php'));
        $styles = file_get_contents(public_path('assets/css/report-mobile.css'));

        $this->assertStringContainsString(
            '<strong :class="config.type === \'sales\' && line.label === \'Pembayaran\' ? \'is-payment\' : \'\'" x-text="line.value"></strong>',
            $template
        );
        $this->assertStringContainsString(
            '.report-mobile-receipt__line strong.is-payment { color:#2563eb; }',
            $styles
        );
    }
}
