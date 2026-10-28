<?php

namespace Tests\Feature;

use Tests\TestCase;

class WholesaleEditBranchArrowTest extends TestCase
{
    public function test_mobile_edit_branch_selector_matches_reference_open_and_selected_states(): void
    {
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/create2.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode\s+#branch_id\s*\+\s*\.select2-container--open\s+\.select2-selection__arrow\s+b\s*\{[^}]*transform\s*:\s*rotate\(225deg\)[^}]*\}/s',
            $source,
            'The open edit-mode branch selector must point its caret upward.'
        );
        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode\s+#branch_id\s*\+\s*\.select2-container--open\s+\.select2-selection--single\s*\{[^}]*background\s*:\s*#fff[^}]*\}/s',
            $source,
            'The open branch selector must use the white focused surface.'
        );
        $this->assertMatchesRegularExpression(
            '/\.wholesale-procurement\.is-edit-mode\s+#branch_id\s*\+\s*\.select2-container\s+\.select2-selection__rendered:not\(\.select2-selection__placeholder\)\s*\{[^}]*font-weight\s*:\s*600[^}]*\}/s',
            $source,
            'A selected branch must use the semibold reference treatment.'
        );
    }

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
