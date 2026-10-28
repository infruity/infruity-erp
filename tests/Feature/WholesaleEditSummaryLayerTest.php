<?php

namespace Tests\Feature;

use Tests\TestCase;

class WholesaleEditSummaryLayerTest extends TestCase
{
    public function test_mobile_edit_summary_removes_the_inner_top_border_layer(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/create2.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode \.wholesale-summary\s*\{([^}]*)\}/',
            $source,
            'The mobile edit summary should have a dedicated edit-mode rule.'
        );

        preg_match('/\.wholesale-procurement\.is-edit-mode \.wholesale-summary\s*\{([^}]*)\}/', $source, $matches);

        $this->assertMatchesRegularExpression('/(?:^|;)\s*border\s*:\s*0\s*!important(?:;|$)/', $matches[1]);
    }
}
