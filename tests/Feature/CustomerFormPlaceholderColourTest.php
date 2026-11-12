<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerFormPlaceholderColourTest extends TestCase
{
    public function test_empty_gender_and_birth_date_use_the_placeholder_grey(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/customer/form-fields.blade.php'));

        $this->assertStringContainsString('#gender:has(option[value=""]:checked) { color: #9ca3af; }', $view);
    }
}
