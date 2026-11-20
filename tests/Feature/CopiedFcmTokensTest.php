<?php

namespace Tests\Feature;

use App\Services\FcmService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * user_devices is copied from the legacy database at cutover. Tokens that belong to another Firebase project must
 * clean themselves up on the first send instead of failing (and being logged as errors) forever.
 */
final class CopiedFcmTokensTest extends TestCase
{
    #[DataProvider('deadTokenCodes')]
    public function test_token_specific_errors_delete_the_token(string $code): void
    {
        $this->assertTrue(FcmService::isDeadTokenError($code));
    }

    public static function deadTokenCodes(): array
    {
        return ['app removed or token expired' => ['UNREGISTERED'], 'token of another firebase project' => ['SENDER_ID_MISMATCH']];
    }

    #[DataProvider('keepTokenCodes')]
    public function test_other_failures_never_delete_tokens(?string $code): void
    {
        $this->assertFalse(FcmService::isDeadTokenError($code));
    }

    public static function keepTokenCodes(): array
    {
        return [
            'payload problem' => ['INVALID_ARGUMENT'],
            'quota' => ['QUOTA_EXCEEDED'],
            'outage' => ['UNAVAILABLE'],
            'internal' => ['INTERNAL'],
            'no code at all' => [null],
            'empty' => [''],
        ];
    }

    public function test_the_send_loop_uses_that_decision(): void
    {
        $source = file_get_contents(base_path('app/Services/FcmService.php'));

        $this->assertStringContainsString('if (self::isDeadTokenError($code)) {', $source);
        $this->assertStringNotContainsString("\$code === 'UNREGISTERED'", $source);
    }

    public function test_the_nota_settings_form_keeps_the_stored_logo_when_it_is_saved_without_a_new_file(): void
    {
        $controller = file_get_contents(base_path('Modules/Pos/Http/Controllers/SettingNotaController.php'));

        $this->assertStringNotContainsString("is_using_logo = \$request->hasFile('avatar') ? true : false", $controller);
        $this->assertStringContainsString("if (\$request->hasFile('avatar')) {\n                \$settingNota->is_using_logo = true;", $controller);
    }
}
