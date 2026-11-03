<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\RedirectIfAuthenticated::class,
            \App\Http\Middleware\SyncRoleSession::class,
        ]);
    }

    public function test_login_uses_email_column_for_email_identifier(): void
    {
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'login@example.test',
                'password' => 'synthetic-test-password',
            ], false)
            ->andReturnFalse();

        $this->post('/auth/login', [
            'email' => 'login@example.test',
            'password' => 'synthetic-test-password',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Username atau Password tidak terdaftar');
    }

    public function test_login_keeps_employee_id_lookup_on_username_column(): void
    {
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'username' => 'employee-0042',
                'password' => 'synthetic-test-password',
            ], false)
            ->andReturnFalse();

        $this->post('/auth/login', [
            'email' => 'employee-0042',
            'password' => 'synthetic-test-password',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Username atau Password tidak terdaftar');
    }
}
