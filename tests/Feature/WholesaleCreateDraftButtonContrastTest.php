<?php

namespace Tests\Feature;

use Tests\TestCase;

final class WholesaleCreateDraftButtonContrastTest extends TestCase
{
    public function test_create_draft_button_text_is_white_while_the_theme_turns_it_green(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/create2.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-create-mode\s+\.wholesale-summary\s+\.btn-primary:is\(:hover,:focus,:active,\.active,\.show\)\s*\{[^}]*color\s*:\s*#fff\s*!important[^}]*\}/s',
            $source,
        );
    }
}
