<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerListSubtitleTest extends TestCase
{
    public function test_customer_rows_show_only_the_phone_and_never_fall_back_to_the_code(): void
    {
        $customer = file_get_contents(base_path('Modules/Master/Resources/views/customer/index.blade.php'));
        $shared = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));

        $this->assertStringContainsString("\$subtitleFields = ['whatsapp'];", $customer);
        $this->assertStringContainsString("|| (this.subtitleFields.length ? '' : (record.code || ''))", $shared);
        $this->assertStringNotContainsString("|| record.code || ''", $shared);
    }
}
