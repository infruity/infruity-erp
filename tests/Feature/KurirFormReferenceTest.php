<?php

namespace Tests\Feature;

use Tests\TestCase;

class KurirFormReferenceTest extends TestCase
{
    public function test_kurir_form_defaults_to_internal_has_no_description_and_no_stray_gap(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/kurir/index.blade.php'));
        $shared = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));

        $this->assertStringContainsString("'default' => 'internal',", $view);
        $this->assertStringNotContainsString("'Deskripsi'", $view);
        $this->assertStringContainsString("\$formBodyClass = 'px-5 pb-6 pt-5 flex flex-col gap-5';", $view);
        $this->assertStringContainsString('[field.key, field.default ?? \'\']', $shared, 'a field can declare a default for a new record');
    }
}
