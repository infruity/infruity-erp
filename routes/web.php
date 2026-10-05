<?php
// route for admin
use Illuminate\Support\Facades\Route;
use Modules\Master\Http\Controllers\ProductController;
use App\Http\Controllers\InfruityUiController;
include 'admin.php';

// Preserve the original HTML directory structure so iframe navigation and relative assets work.
Route::get('/infruity-ui/{page?}', [InfruityUiController::class, 'show'])
    ->where('page', '.*')
    ->name('infruity-ui');

// Route::get('/', [ProductController::class, 'index'])->name('products-data');
Route::get('/', 'DashboardController@landing')->name('landing');
Route::get('/login', 'Auth\LoginController@showLogin')->name('login');
Route::get('/forgot-password', 'DashboardController@forgot_password')->name('forgot-password');
Route::post('/forgot-password', 'DashboardController@forgot_password_check')->name('forgot-password');
Route::put('/forgot-password', 'DashboardController@forgot_password_save')->name('forgot-password');
Route::get('/register', 'Auth\RegisterController@showRegister')->name('register');
Route::post('/register', 'Auth\RegisterController@create');
Route::get('/qrcode', 'DashboardController@generateQrCode');
// Route::get('/produk', 'DashboardController@produk')->name('produk');
Route::group(['prefix' => '/auth'], function () {
    // Route::get('/', 'Auth\LoginController@showLogin')->name('login');
    Route::get('/login', 'Auth\LoginController@showLogin');
    Route::post('/login', 'Auth\LoginController@login');
    Route::get('/logout/{id?}', 'UserController@logout');
});

Route::post('/logout', 'UserController@logout')->middleware('auth')->name('logout');
