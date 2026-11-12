<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupplierListSubtitleTest extends TestCase
{
    public function test_supplier_rows_show_only_the_phone_number(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/supllier/index.blade.php'));

        $this->assertStringContainsString("\$subtitleFields = ['pic_whatsapp'];", $view);
        $this->assertStringNotContainsString("\$subtitleFields = ['pic_whatsapp','pic_name']", $view);
    }
}
