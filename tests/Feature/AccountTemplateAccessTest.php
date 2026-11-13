<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountTemplateAccessTest extends TestCase
{
    public function test_the_staff_form_calls_the_role_choice_a_template_and_points_to_the_account_page(): void
    {
        $form = file_get_contents(base_path('Modules/Master/Resources/views/staff/form-fields.blade.php'));

        $this->assertStringContainsString('Template Akses <span class="text-red-400">*</span>', $form);
        $this->assertStringContainsString('Sesuaikan per akun di Akun Pengguna.', $form);
        $this->assertStringNotContainsString('>Hak Akses <', $form);
        $this->assertStringContainsString('name="role_ids[]"', $form, 'the posted field is unchanged');
    }

    public function test_the_access_matrix_no_longer_lists_the_hidden_role_pages(): void
    {
        $view = file_get_contents(base_path('resources/views/admin/user/index.blade.php'));

        $this->assertStringNotContainsString("['key' => 'role-menu', 'label' => 'Hak Akses']", $view);
        $this->assertStringNotContainsString("['key' => 'role', 'label' => 'Peran']", $view);
    }
}
