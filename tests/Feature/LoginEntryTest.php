<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginEntryTest extends TestCase
{
    public function test_first_visit_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_has_no_guest_button_by_default(): void
    {
        config(['app.guest_login' => false]);
        $this->get('/login')->assertOk()->assertDontSee('guest-login-form', false)->assertDontSee('Masuk sebagai Guest');
    }
}
