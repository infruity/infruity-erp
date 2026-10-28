<?php

namespace Tests\Feature;

use Tests\TestCase;

class WholesaleEditBranchArrowTest extends TestCase
{
    public function test_mobile_edit_branch_select_anchors_its_dropdown_arrow_to_the_field(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/create2.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode\s+#branch_id\s*\+\s*\.select2-container\s+\.select2-selection--single\s*\{[^}]*position\s*:\s*relative[^}]*\}/s',
            $source,
            'The edit-mode Select2 selection must anchor its absolute dropdown arrow.'
        );
    }
}
