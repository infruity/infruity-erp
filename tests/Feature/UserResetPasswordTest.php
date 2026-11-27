<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class UserResetPasswordTest extends TestCase
{
    public function test_reset_makes_a_new_password_on_the_server_and_shows_it_once(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Http/Controllers/UserController.php');
        $view = file_get_contents($root . '/resources/views/admin/user/index.blade.php');
        $routes = file_get_contents($root . '/routes/admin.php');

        // No shared password: PrivilegeEscalationTest checks that every reset differs.
        self::assertStringContainsString('$password = $this->temporaryPassword();', $controller);
        self::assertStringNotContainsString('infruity123', $controller);
        self::assertStringContainsString('result.password', $view);
        self::assertStringNotContainsString("'password' => ['required', 'string', 'min:8']", $controller);
        self::assertStringNotContainsString('infruity123', $view, 'the default password is never shown on the page');
        self::assertStringContainsString("const label = user.email;", $view);
        self::assertStringContainsString("->name('user.reset-password')", $routes);
    }

    public function test_account_sheet_closes_with_an_icon_back_button_and_the_other_actions_are_text_only(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/user/index.blade.php');
        $footer = substr($view, strpos($view, 'Reset Password</button>') - 900, 1700);

        self::assertStringContainsString('border-rose-200 bg-rose-50', $footer);
        self::assertStringNotContainsString('ph-key', $footer);
        self::assertStringContainsString('ph-bold ph-arrow-left', $footer);
        self::assertStringContainsString('@click="backToDetail()"', $footer, 'the back arrow returns to the access sheet');
        self::assertStringContainsString('x-show="!editing" @click="closeDrawer()"', $footer);
        self::assertStringContainsString('>Batal</button>', $footer);
    }
}
