<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentMethodListSubtitleTest extends TestCase
{
    public function test_payment_method_rows_have_no_subtitle(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/payment-method/index.blade.php'));

        $this->assertStringContainsString("\$subtitleFields = [];", $view);
    }
}
