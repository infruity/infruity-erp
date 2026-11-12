<?php
// route for admin
use Illuminate\Support\Facades\Route;
use Modules\Master\Http\Controllers\ProductController;
use App\Http\Controllers\InfruityUiController;
use App\Http\Controllers\Auth\GuestLoginController;
include 'admin.php';

// Preserve the original HTML directory structure so iframe navigation and relative assets work.
Route::get('/infruity-ui/{page?}', [InfruityUiController::class, 'show'])
    ->where('page', '.*')
    ->name('infruity-ui');

// Route::get('/', [ProductController::class, 'index'])->name('products-data');
// First visit goes to the login page; a signed-in user goes on to the dashboard. The landing page stays at /landing.
Route::get('/', function () {
    return redirect(auth()->check() ? \App\Providers\RouteServiceProvider::HOME : route('login'));
});
Route::get('/landing', 'DashboardController@landing')->name('landing');
Route::get('/login', 'Auth\LoginController@showLogin')->name('login');
// Guest demo login only exists when GUEST_LOGIN_ENABLED=true (never on live).
if (config('app.guest_login')) {
    Route::post('/login/guest', [GuestLoginController::class, 'store'])->name('login.guest');
}
Route::get('/forgot-password', 'DashboardController@forgot_password')->name('forgot-password');
Route::post('/forgot-password', 'DashboardController@forgot_password_check')->name('forgot-password.request');
Route::get('/register', 'Auth\RegisterController@showRegister')->name('register');
Route::post('/register', 'Auth\RegisterController@create')->name('register.store');
Route::get('/qrcode', 'DashboardController@generateQrCode');
// Route::get('/produk', 'DashboardController@produk')->name('produk');
Route::group(['prefix' => '/auth'], function () {
    // Route::get('/', 'Auth\LoginController@showLogin')->name('login');
    Route::get('/login', 'Auth\LoginController@showLogin');
    Route::post('/login', 'Auth\LoginController@login');
    Route::get('/logout/{id?}', 'UserController@logout');
});

Route::post('/logout', 'UserController@logout')->middleware('auth')->name('logout');
