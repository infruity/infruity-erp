<?php
namespace Tests\Feature;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
class PasswordConfirmationTest extends TestCase
{
    public function test_mismatching_confirmation_is_rejected_before_authentication_or_database_write(): void
    {
        Route::middleware('web')->post('/_password-fixture', [DashboardController::class, 'save_change_password']);
        $this->from('/change-password')->post('/_password-fixture', [
            'old_password' => 'fixture', 'password' => 'FixturePassword123!', 'password_confirmation' => 'DifferentPassword123!',
        ])->assertRedirect('/change-password')->assertSessionHasErrors('password');
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertFalse(session()->hasOldInput('old_password'));
    }
}
