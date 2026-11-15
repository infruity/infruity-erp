<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TransferViewsCompileTest extends TestCase
{
    /** A view with an unclosed @if/@unless only fails when it is rendered; compiling it here catches that without a database. */
    public function test_every_transfer_view_compiles_to_valid_php(): void
    {
        $files = array_merge(
            glob(base_path('Modules/Transaction/Resources/views/transfer/*.blade.php')),
            glob(base_path('Modules/Transaction/Resources/views/transfer/segment/*.blade.php')),
        );
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $compiled = Blade::compileString(file_get_contents($file));
            try {
                token_get_all($compiled, TOKEN_PARSE);
            } catch (\ParseError $error) {
                $this->fail(basename($file) . ': ' . $error->getMessage());
            }
        }
        $this->assertTrue(true);
    }
}
