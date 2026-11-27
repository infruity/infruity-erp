<?php
// route for administrator
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleContextController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\P_rolemenu;
use Modules\Crm\Http\Controllers\DashboardController;
use App\Http\Controllers\P_role;
use App\Http\Controllers\FCMController;
use Illuminate\Support\Facades\Http;


Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
	Route::get('/', [DashboardController::class, 'index'])->name('crm.home');
	// Route::get('/dashboard', 'DashboardController@index')->name('admin.module');
	Route::get('/change-password', 'DashboardController@change_password')->name('change-password');
	Route::post('/change-password', 'DashboardController@save_change_password')->name('save_change_password');
	Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
	// Route::resource('layanan', LayananController::class);
	Route::post('user/impersonate/{id}', [UserController::class, 'impersonate'])->name('user.impersonate');
	Route::post('role/switch', [RoleContextController::class, 'switch'])->name('role.switch');
	Route::put('user/{id}/permissions', [UserController::class, 'updatePermissions'])->name('user.permissions.update');
	Route::delete('user/{id}/permissions', [UserController::class, 'resetPermissions'])->name('user.permissions.reset');
	Route::post('user/{id}/reset-password', [UserController::class, 'resetPassword'])->name('user.reset-password');
	Route::resource('user', UserController::class)->names('user')->except('show');
	// The role editor is hidden (FEATURE_ROLE_PAGES): permissions are managed per account in Akun Pengguna.
	Route::middleware(\App\Http\Middleware\EnsureFeatureEnabled::class . ':role_pages')->group(function () {
		Route::resource('role-menu', P_rolemenu::class);
		Route::resource('roles', P_role::class)->names('roles')->except('show');
		Route::get('roles/detail/{id}', [P_role::class, 'show'])->name('roles.show');
		Route::post('roles/duplicate/{id}', [P_role::class, 'duplicate'])->name('roles.duplicate');
	});
});
Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
	Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.crm-dashboard');
	Route::get('roles/data', [P_role::class, 'get_data'])->name('roles.data')->middleware(\App\Http\Middleware\EnsureFeatureEnabled::class . ':role_pages');
	Route::get('user/data', [UserController::class, 'get_data'])->name('user.data');
	Route::post('/api/save-fcm-token', [FCMController::class, 'store']);

});
