<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Tombol di Blade dipanggil lewat <x-erp.button>, bukan ditulis ulang dengan class erp-action.
 * HTML yang dibangun JS (di dalam <script>) dan halaman mandiri cetak/error dikecualikan.
 */
class ErpButtonComponentTest extends TestCase
{
    private const EXCEPT = [
        'resources/views/components/erp/button.blade.php',
        'resources/views/components/erp/image-input.blade.php',
        'resources/views/errors/layout.blade.php',
        'Modules/Pos/Resources/views/pos/receipt2.blade.php',
        'Modules/Pos/Resources/views/pos/payment-success2.blade.php',
        'Modules/Pos/Resources/views/pos/print-list-payment.blade.php',
        'Modules/Transaction/Resources/views/stock-opname/exports/preview.blade.php',
    ];

    public function test_blade_views_use_the_button_component(): void
    {
        $base = dirname(__DIR__, 2);
        $found = [];
        $files = array_merge(
            glob($base.'/Modules/*/Resources/views/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) ?: [],
            glob($base.'/resources/views/{,*/,*/*/,*/*/*/}*.blade.php', GLOB_BRACE) ?: []
        );

        foreach (array_unique($files) as $file) {
            $path = str_replace($base.'/', '', $file);
            if (in_array($path, self::EXCEPT, true)) {
                continue;
            }
            $html = preg_replace('#<script\b.*?</script>#s', '', (string) file_get_contents($file));
            if (preg_match('#<(?:button|a)\b[^>]*class="[^"]*(?<![\w-])erp-action(?![\w-])#', (string) $html)) {
                $found[] = $path;
            }
        }

        $this->assertSame([], $found, "Pakai <x-erp.button> (resources/views/components/erp/button.blade.php):\n".implode("\n", $found));
    }
}
