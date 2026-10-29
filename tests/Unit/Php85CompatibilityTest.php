<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class Php85CompatibilityTest extends TestCase
{
    public function test_authenticator_qr_url_preserves_encoded_identity_and_custom_dimensions(): void
    {
        $authenticator = new \App\Libraries\GoogleAuthenticator();

        $this->assertSame(
            'https://api.qrserver.com/v1/create-qr-code/?data=otpauth%3A%2F%2Ftotp%2Falice%40example.com%3Fsecret%3DJBSWY3DPEHPK3PXP%26issuer%3DInfruity%2BTeam&size=320x180&ecc=H',
            $authenticator->getQRCodeGoogleUrl('alice@example.com', 'JBSWY3DPEHPK3PXP', 'Infruity Team', ['width' => 320, 'height' => 180, 'level' => 'H'])
        );
        $this->assertSame(
            'https://api.qrserver.com/v1/create-qr-code/?data=otpauth%3A%2F%2Ftotp%2Falice%3Fsecret%3DJBSWY3DPEHPK3PXP&size=200x200&ecc=M',
            $authenticator->getQRCodeGoogleUrl('alice', 'JBSWY3DPEHPK3PXP', null, ['width' => -1, 'height' => 0, 'level' => 'invalid'])
        );
    }

    public function test_authenticator_generates_and_verifies_a_known_totp_vector_and_drift_window(): void
    {
        $authenticator = new \App\Libraries\GoogleAuthenticator();
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $authenticator->getCode($secret, 1));
        $this->assertTrue($authenticator->verifyCode($secret, '287082', 0, 1));
        $this->assertFalse($authenticator->verifyCode($secret, '287082', 0, 2));
        $this->assertTrue($authenticator->verifyCode($secret, '287082', 1, 2));
        $this->assertFalse($authenticator->verifyCode($secret, '12345', 1, 1));
        $this->assertFalse($authenticator->verifyCode($secret, '000000', 0, 1));
    }

    public static function compatibilityFiles(): array
    {
        return [
            'CRM dashboard casts' => ['Modules/Crm/Http/Controllers/DashboardController.php'],
            'stock opname casts' => ['Modules/Transaction/Http/Controllers/StockOpnameController.php'],
            'authenticator interpolation' => ['app/Libraries/GoogleAuthenticator.php'],
        ];
    }

    #[DataProvider('compatibilityFiles')]
    public function test_owned_php_files_lint_without_deprecations_using_the_running_php_binary(string $file): void
    {
        $process = new Process([
            PHP_BINARY,
            '-d', 'error_reporting=-1',
            '-d', 'display_errors=1',
            '-d', 'log_errors=0',
            '-l', dirname(__DIR__, 2).'/'.$file,
        ]);
        $process->mustRun();
        $output = $process->getOutput().$process->getErrorOutput();

        $this->assertStringContainsString('No syntax errors detected', $output, PHP_BINARY);
        $this->assertDoesNotMatchRegularExpression('/deprecated|warning|fatal error|parse error/i', $output, PHP_BINARY."\n".$output);
    }
}
