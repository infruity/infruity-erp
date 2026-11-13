<?php

namespace Tests\Feature;

use Tests\TestCase;

class PositionFormReferenceTest extends TestCase
{
    public function test_the_position_form_has_only_the_name_with_the_reference_placeholder(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/position/index.blade.php'));

        $this->assertStringContainsString("\$formFields = [['key'=>'name','label'=>'Nama Posisi','required'=>true,'placeholder'=>'Misal: Kru Toko']];", $view);
        $this->assertStringNotContainsString("'Departemen'", $view);
        $this->assertStringNotContainsString("'Deskripsi'", $view);
    }
}
