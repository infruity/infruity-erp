<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChangePasswordReferenceLayoutTest extends TestCase
{
    public function test_password_form_preserves_backend_fields_and_has_reference_controls(): void
    {
        $view = file_get_contents(resource_path('views/change-password.blade.php'));

        $this->assertStringContainsString('class="erp-password-page"', $view);
        $this->assertStringContainsString('<h1>Perbarui Kata Sandi</h1>', $view);
        $this->assertStringContainsString('action="{{ url(\'change-password\') }}"', $view);
        $this->assertStringContainsString('name="old_password"', $view);
        $this->assertStringContainsString('name="password"', $view);
        $this->assertStringContainsString('name="password_confirmation"', $view);
        $this->assertSame(3, substr_count($view, 'data-password-toggle='));
        $this->assertStringContainsString('data-password-submit disabled', $view);
        $this->assertStringContainsString('minlength="12"', $view);
        $this->assertStringContainsString('minimal 12 karakter', $view);
    }

    public function test_password_screen_uses_full_bleed_surface_and_pinned_action(): void
    {
        $styles = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('body:has(#password-change-form) #kt_content { padding:0; scroll-padding-bottom:0; }', $styles);
        $this->assertStringContainsString('.erp-ui .erp-password-page { width:100vw; margin-inline:calc(50% - 50vw);', $styles);
        $this->assertStringContainsString('.erp-ui .erp-password-actions { position:fixed;', $styles);
    }

    public function test_password_page_surface_and_action_fade_match_the_white_reference(): void
    {
        $styles = file_get_contents(public_path('assets/css/erp-theme.css'));

        $this->assertStringContainsString('background:#fff; padding:24px 16px calc(92px + env(safe-area-inset-bottom));', $styles);
        $this->assertStringContainsString('linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,.92) 30%, #fff 100%)', $styles);
    }
}
