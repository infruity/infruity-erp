<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Found in manual QA (phone viewport): on /wholesale/{id}/edit the item modal sat UNDER the Bootstrap backdrop,
 * so its fields and the Simpan button could not be tapped. The page wrapper (.animate-fade-in-up) keeps a
 * transform after its entrance animation, which makes it a stacking context that traps the modal (z-index 1055)
 * below the backdrop (z-index 1050, a direct child of body). Create mode already sets animation:none on the
 * wrapper; edit mode must too.
 */
final class WholesaleEditModalStackingTest extends TestCase
{
    private function wholesaleFormSource(): string
    {
        return file_get_contents(base_path('Modules/Transaction/Resources/views/wholesale/create2.blade.php'));
    }

    public function test_edit_mode_removes_the_wrapper_animation_like_create_mode(): void
    {
        $css = $this->wholesaleFormSource();

        $this->assertMatchesRegularExpression(
            '/is-create-mode\)\s*main\s*>\s*\.animate-fade-in-up\s*\{[^}]*animation:\s*none/',
            $css,
            'create mode rule changed unexpectedly'
        );
        $this->assertMatchesRegularExpression(
            '/is-edit-mode\)\s*main\s*>\s*\.animate-fade-in-up\s*\{[^}]*animation:\s*none/',
            $css,
            'edit mode must disable the wrapper animation, otherwise the item modal is rendered under the backdrop'
        );
    }
}
