<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserListRowReferenceTest extends TestCase
{
    public function test_account_rows_show_the_email_and_the_name_like_the_reference_and_keep_the_rest_as_a_tooltip(): void
    {
        $rows = explode('@forelse ($users as $user)', file_get_contents(base_path('resources/views/admin/user/index.blade.php')))[1];

        $this->assertStringContainsString("<span class=\"text-[11px] font-medium truncate\">{{ \$user->nm_user ?: 'Belum tertaut' }}</span>", $rows);
        $this->assertStringContainsString('title="{{ ($user->role_names ?', $rows, 'roles, status and modules live in the tooltip');
        $this->assertStringNotContainsString('bg-amber-50 text-amber-700', $rows, 'no module badge on the row');
        $this->assertStringContainsString('@unless ($user->is_aktif)', $rows, 'only inactive accounts get a badge');
    }
}
