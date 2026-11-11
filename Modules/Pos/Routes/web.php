<?php
use App\Http\Middleware\EnsureFeatureEnabled;
use Illuminate\Support\Facades\Route;
use Modules\Pos\Http\Controllers\PosController;
use Modules\Pos\Http\Controllers\SettingNotaController;
use Modules\Pos\Http\Controllers\DeliveryOrderController;
use Modules\Master\Http\Controllers\CustomerController;
use Modules\Pos\Http\Controllers\OtherBookController;
use Modules\Pos\Http\Controllers\ExpenditureController;
use Modules\Pos\Http\Controllers\ArusKasController;
use Modules\Pos\Http\Controllers\OrderBookController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
    Route::resource('pos', PosController::class)->names('pos')->except('show');
    Route::get('pos/show/{id}', [PosController::class, 'show'])->name('pos.show');
    Route::post('pos/submitTransaction', [PosController::class, 'store'])->name('pos-submit');
    Route::post('pos/{id}/payment', [PosController::class, 'savePayment'])->name('receipt.payment');
    Route::get('pos/{id}/receipt', [PosController::class, 'showReceipt'])->name('pos.receipt');
    Route::post('pos/save-transaction', [PosController::class, 'saveTransaction'])->name('pos.save-transaction');
    Route::post('/pos/customers', [CustomerController::class, 'storeCustomer']);
    Route::get('pos/payment/{id}', [PosController::class, 'payment'])->name('pos.payment');
    Route::post('pos/savePayment', [PosController::class, 'savePayment'])->name('pos.savePayment');
    Route::get('pos/payment-notification/{id}', [PosController::class, 'paymentNotification'])->name('pos.paymentNotification');
    Route::get('pos/listPayment/{id}', [PosController::class, 'listPayment'])->name('pos.listPayment');
    Route::get('pos/printPayment/{id}', [PosController::class, 'printPayment'])->name('pos.printPayment');
    Route::get('pos/printDraftPayment/{id}', [PosController::class, 'printDraftPayment'])->name('pos.printDraftPayment');
    Route::get('pos/printNota/{id}', [PosController::class, 'printNota'])->name('pos.printNota');
    Route::post('/upload-receipt', [PosController::class, 'uploadReceipt']);
    Route::resource('setting-nota', SettingNotaController::class)->names('setting-nota')->except('show');
    Route::get('setting-nota/view-receipt', [SettingNotaController::class, 'viewReceipt'])->name('setting-nota.view-receipt');
    Route::get('delivery-order', [DeliveryOrderController::class, 'index'])->name('delivery-order');
    Route::get('delivery-order/get-courier', [DeliveryOrderController::class, 'getCourier'])->name('delivery-order.get-courier');
    Route::post('delivery-order/update-courier', [DeliveryOrderController::class, 'updateCourier'])->name('delivery-order.update-courier');
    Route::put('delivery-order/set-selesai/{id}', [DeliveryOrderController::class, 'setSelesai'])->name('delivery-order.set-selesai');
    Route::get('other-book', [OtherBookController::class, 'index'])->name('other-book')->middleware(EnsureFeatureEnabled::class . ':other_book_pages');
    Route::put('other-book/set-selesai/{id}', [OtherBookController::class, 'setSelesai'])->name('other-book.set-selesai');
    Route::resource('order-book', OrderBookController::class)->names('order-book')->except('show');
    Route::post('order-book/save-transaction', [OrderBookController::class, 'saveTransaction'])->name('order-book.save-transaction');
    Route::get('order-book/{id}/order', [OrderBookController::class, 'order'])->name('order-book.order');
    
    // Pengeluaran is not in use: hidden (404) until FEATURE_EXPENDITURE_PAGES is switched on.
    Route::middleware(EnsureFeatureEnabled::class . ':expenditure_pages')->group(function () {
        Route::resource('expenditure', ExpenditureController::class)->names('expenditure')->except('show');
        Route::post('expenditure/save-transaction', [ExpenditureController::class, 'saveTransaction'])->name('expenditure.save-transaction');
        Route::get('expenditure/show/{id}', [ExpenditureController::class, 'show'])->name('expenditure.show');
    });

    Route::get('arus-kas', [ArusKasController::class, 'index'])->name('arus-kas.index');
    Route::get('arus-kas/create', [ArusKasController::class, 'create'])->name('arus-kas.create');
    Route::post('arus-kas', [ArusKasController::class, 'store'])->name('arus-kas.store');
    Route::get('arus-kas/{id}/edit', [ArusKasController::class, 'edit'])->name('arus-kas.edit');
    Route::put('arus-kas/{id}', [ArusKasController::class, 'update'])->name('arus-kas.update');
    Route::delete('arus-kas/{id}', [ArusKasController::class, 'destroy'])->name('arus-kas.destroy');
    Route::patch('arus-kas/{id}/status', [ArusKasController::class, 'updateStatus'])->name('arus-kas.status');
});
Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
    Route::get('pos/data', [PosController::class, 'get_data'])->name('pos-data');
    Route::get('delivery-order/data', [DeliveryOrderController::class, 'get_data'])->name('delivery-order.data');
    Route::get('other-book/data', [OtherBookController::class, 'get_data'])->name('other-book.data')->middleware(EnsureFeatureEnabled::class . ':other_book_pages');
    Route::get('expenditure/data', [ExpenditureController::class, 'get_data'])->name('expenditure.data')->middleware(EnsureFeatureEnabled::class . ':expenditure_pages');
    Route::get('order-book/data', [OrderBookController::class, 'get_data'])->name('order-book.data');
});

Route::get('cek-nota/{id}', [PosController::class, 'cekNota'])->name('pos.cek-nota');
Route::get('cek-nota/draft/{id}', [PosController::class, 'cekNotaDraft'])->name('pos.cek-nota.draft');
