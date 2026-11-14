<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class UserResetPasswordTest extends TestCase
{
    public function test_reset_puts_the_default_password_on_the_account_without_asking_for_one(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/UserController.php');
        $view = file_get_contents($root . '/resources/views/admin/user/index.blade.php');
        $routes = file_get_contents($root . '/routes/admin.php');

        self::assertStringContainsString("public const DEFAULT_RESET_PASSWORD = 'infruity123';", $controller);
        self::assertStringContainsString('Hash::make(self::DEFAULT_RESET_PASSWORD)', $controller);
        self::assertStringNotContainsString("'password' => ['required', 'string', 'min:8']", $controller);
        self::assertStringContainsString("defaultPassword: 'infruity123'", $view);
        self::assertStringContainsString("->name('user.reset-password')", $routes);
    }

    public function test_account_sheet_closes_with_an_icon_back_button_and_the_other_actions_are_text_only(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/user/index.blade.php');
        $footer = substr($view, strpos($view, 'Reset Password</button>') - 900, 1700);

        self::assertStringContainsString('border-rose-200 bg-rose-50', $footer);
        self::assertStringNotContainsString('ph-key', $footer);
        self::assertStringContainsString('ph-bold ph-arrow-left', $footer);
        self::assertStringContainsString('x-show="!editing" @click="closeDrawer()"', $footer);
        self::assertStringContainsString('>Batal</button>', $footer);
    }
}
