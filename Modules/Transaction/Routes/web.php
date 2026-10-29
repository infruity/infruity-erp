<?php

use Illuminate\Support\Facades\Route;
use Modules\Transaction\Http\Controllers\WholesaleController;
use Modules\Transaction\Http\Controllers\SortirController;
use Modules\Transaction\Http\Controllers\TransferController;
use Modules\Transaction\Http\Controllers\ProductReceiptController;
use Modules\Master\Http\Controllers\ProductController;
use Modules\Transaction\Http\Controllers\StockOutController;
use Modules\Transaction\Http\Controllers\StockOpnameController;
use Modules\Transaction\Http\Controllers\StockOutTypeController;
use Modules\Transaction\Http\Controllers\ProductionController;
use Modules\Transaction\Http\Controllers\ProductionParcelController;
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
    Route::get('product-stock', [ProductController::class, 'get_stock'])->name('product-stock');
    Route::get('product-stock/{id}/show', [ProductController::class, 'show_stock'])->name('product-stock.show');
    Route::get('product-transaction/{id}/show', [ProductController::class, 'show_transaction'])->name('product-transaction.show');
    Route::get('product-stock/available-stock', [ProductController::class, 'get_data_available'])->name('ajax.stock-available');

    Route::resource('wholesale', WholesaleController::class)->names('wholesale')->except('show');
    Route::get('wholesale/{id}/show', [WholesaleController::class, 'show'])->name('wholesale-show');
    Route::get('wholesale/receive-product/{id}', [WholesaleController::class, 'receive_product'])->name('wholesale.receive_product');
    Route::post('wholesale/save-receive', [WholesaleController::class, 'save_receive'])->name('wholesale-save-receive');
    Route::get('wholesale/show/{id}', [WholesaleController::class, 'show'])->name('wholesale.show');
    Route::post('wholesale/receive/{id}', [WholesaleController::class, 'receive_product'])->name('wholesale.receive_product');
    Route::get('wholesale/process/{id}', [WholesaleController::class, 'receive_process'])->name('wholesale.receive_process');
    Route::get('wholesale/get-product/{id}', [WholesaleController::class, 'get_product'])->name('wholesale.get-product');
    Route::get('wholesale/edit-product/{id}', [WholesaleController::class, 'edit_product'])->name('wholesale.edit-product');
    Route::put('wholesale/update-product/{id}', [WholesaleController::class, 'update_product'])->name('wholesale.update-product');
    Route::post('wholesale/save-product', [WholesaleController::class, 'save_product'])->name('wholesale.save-product');
    Route::post('wholesale/save-transaction', [WholesaleController::class, 'saveTransaction'])->name('wholesale.save-transaction');
    Route::delete('wholesale/delete-product/{id}', [WholesaleController::class, 'delete_product'])->name('wholesale.delete_product');
    Route::post('wholesale/update-receive-product/{id}', [WholesaleController::class, 'update_receive_product'])->name('wholesale.update_receive_product');
    Route::post('wholesale/set-selesai/{id}', [WholesaleController::class, 'set_selesai'])->name('wholesale.set_selesai');
    Route::post('wholesale/reset', [WholesaleController::class, 'reset_transactions'])->name('wholesale.reset');
    Route::get('wholesale/table-product-data', [WholesaleController::class, 'getProductTableData'])->name('wholsale.product-table-data');

    Route::resource('sortir', SortirController::class)->names('sortir')->except('show');
    Route::get('sortir/show/{id}', [SortirController::class, 'show'])->name('sortir.show');
    Route::post('sortir/save-stock', [SortirController::class, 'save_stock'])->name('sortir.save-stock');
    Route::post('sortir/save-transaction', [SortirController::class, 'saveTransaction'])->name('sortir.save-transaction');

    // Transfer - Pengirim
    Route::prefix('transfer-pengirim')->name('transfer-pengirim.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('create', [TransferController::class, 'create'])->name('create');
        Route::post('store', [TransferController::class, 'store'])->name('store');
        Route::get('edit/{id}', [TransferController::class, 'edit'])->name('edit');
        Route::put('update/{id}', [TransferController::class, 'update'])->name('update');
        Route::delete('delete/{id}', [TransferController::class, 'destroy'])->name('destroy');
        Route::get('show/{id}', [TransferController::class, 'show'])->name('show');
        Route::post('set-selesai/{id}', [TransferController::class, 'set_selesai'])->name('set_selesai');
    });

    // Transfer - Penerima
    Route::prefix('transfer-penerima')->name('transfer-penerima.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('create', [TransferController::class, 'create'])->name('create');
        Route::post('store', [TransferController::class, 'store'])->name('store');
        Route::get('edit/{id}', [TransferController::class, 'edit'])->name('edit');
        Route::put('update/{id}', [TransferController::class, 'update'])->name('update');
        Route::delete('delete/{id}', [TransferController::class, 'destroy'])->name('destroy');
        Route::get('show/{id}', [TransferController::class, 'show'])->name('show');
        Route::post('set-selesai/{id}', [TransferController::class, 'set_selesai'])->name('set_selesai');
    });

    // Transfer - Shared (AJAX)
    Route::post('transfer/save-stock', [TransferController::class, 'save_stock'])->name('transfer.save-stock');
    Route::post('transfer/save-correction', [TransferController::class, 'saveCorrection'])->name('transfer.save-correction');
    Route::post('transfer/save-transaction', [TransferController::class, 'saveTransaction'])->name('transfer.save-transaction');

    Route::resource('product-receipt', ProductReceiptController::class)->names('product-receipt')->except('show');

    Route::resource('stock-out', StockOutController::class)->names('stock-out')->except('show');

    Route::resource('stock-opname', StockOpnameController::class)->names('stock-opname')->except('show');
    Route::get('stock-opname/{id}/history', [StockOpnameController::class, 'get_history'])->name('stock-opname.history');
    Route::get('stock-opname/{id}/discussion', [StockOpnameController::class, 'get_discussion'])->name('stock-opname.discussion');
    Route::post('stock-opname/{id}/discussion', [StockOpnameController::class, 'post_discussion'])->name('stock-opname.discussion.post');
    Route::post('stock-opname/{id}/finalize', [StockOpnameController::class, 'finalize'])->name('stock-opname.finalize');
    Route::post('stock-opname/{id}/adjust', [StockOpnameController::class, 'adjust'])->name('stock-opname.adjust');
    Route::get('stock-opname-export', [StockOpnameController::class, 'export'])->name('stock-opname.export');
    Route::get('stock-opname-preview', [StockOpnameController::class, 'preview'])->name('stock-opname.preview');

    Route::resource('stock-out-type', StockOutTypeController::class)->names('stock-out-type')->except('show');

    Route::resource('production', ProductionController::class)->names('production')->except('show');
    Route::get('production/{id}/payment', [ProductionController::class, 'payment'])->name('production.payment');
    Route::post('production/save-completion', [ProductionController::class, 'saveCompletion'])->name('production.save-completion');
    Route::get('production/{id}/completion-notification', [ProductionController::class, 'completionNotification'])->name('production.completion-notification');
    Route::get('production/{id}/detail', [ProductionController::class, 'show'])->name('production.detail');
    Route::get('production/{id}/print', [ProductionController::class, 'printProduction'])->name('production.print');
    Route::get('products/get-receipt', [ProductReceiptController::class, 'getReceipt'])->name('products.get-receipt');
    Route::get('production/get-receipt/{id}', [ProductReceiptController::class, 'get_product'])->name('production.get-receipt');
    Route::get('production/get-recipe-data/{id}', [ProductReceiptController::class, 'getRecipeData'])->name('production.get-recipe-data');
    Route::get('production/get-detail/{id}', [ProductionController::class, 'get_detail_product'])->name('production.get-receipt');
    Route::delete('production/delete-detail/{id}', [ProductionController::class, 'delete_detail'])->name('production.delete_detail');
    Route::post('production/update-product-id/{id}', [ProductionController::class, 'update_product_id'])->name('production.update_product_id');
    Route::post('production/save-ajax', [ProductionController::class, 'save_additional_ingredient'])->name('production.save-ajax');
    Route::delete('production/delete-product/{id}', [ProductionController::class, 'delete_additional_ingredient'])->name('production.delete-ajax');
    Route::get('production/edit-product/{id}', [ProductionController::class, 'edit_additional_ingredient'])->name('production.edit-ajax');
    Route::put('production/update-product/{id}', [ProductionController::class, 'update_additional_ingredient'])->name('production.edit-ajax');
    Route::delete('production/delete-product/{id}', [ProductionController::class, 'delete_additional_ingredient'])->name('receipt.delete-ajax');

    Route::resource('receipt', ProductReceiptController::class)->names('receipt')->except('show');
    Route::post('receipt/save-ajax', [ProductReceiptController::class, 'save_additional_ingredient'])->name('receipt.save-ajax');
    Route::delete('receipt/delete-product/{id}', [ProductReceiptController::class, 'delete_additional_ingredient'])->name('receipt.delete-ajax');
    Route::get('receipt/edit-product/{id}', [ProductReceiptController::class, 'edit_additional_ingredient'])->name('receipt.edit-ajax');
    Route::put('receipt/update-product/{id}', [ProductReceiptController::class, 'update_additional_ingredient'])->name('receipt.edit-ajax');

    Route::resource('parcel', ProductionParcelController::class)->names('parcel')->except('show');
    Route::get('parcel/show/{id}', [ProductionParcelController::class, 'show'])->name('parcel.show');
    Route::get('parcel/process/{id}', [ProductionParcelController::class, 'process'])->name('parcel.process');
    Route::get('parcel/get-product/{id}', [ProductionParcelController::class, 'get_product'])->name('parcel.get-product');
    Route::get('parcel/edit-product/{id}', [ProductionParcelController::class, 'edit_product'])->name('parcel.edit-product');
    Route::put('parcel/update-product/{id}', [ProductionParcelController::class, 'update_product'])->name('parcel.update-product');
    Route::post('parcel/save-product', [ProductionParcelController::class, 'save_product'])->name('parcel.save-product');
    Route::delete('parcel/delete-product/{id}', [ProductionParcelController::class, 'delete_product'])->name('parcel.delete_product');
    Route::post('parcel/set-selesai/{id}', [ProductionParcelController::class, 'set_selesai'])->name('parcel.set_selesai');
});

Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
    Route::get('product-stock/data', [ProductController::class, 'get_data_stock'])->name('product-stock-data');
    Route::get('product-stock-data-show', [ProductController::class, 'get_data_stock_show'])->name('product-stock-data-show');
    Route::get('product-transaction-data', [ProductController::class, 'get_data_transaction'])->name('product-transaction-data');
    Route::get('wholesale/data', [WholesaleController::class, 'get_data'])->name('wholesale-data');
    Route::get('sortir/data', [SortirController::class, 'get_data'])->name('sortir-data');
    Route::get('transfer/data', [TransferController::class, 'get_data'])->name('transfer-data');
    Route::get('stock-out/data', [StockOutController::class, 'get_data'])->name('stock-out.data');
    Route::get('stock-opname/data', [StockOpnameController::class, 'get_data'])->name('stock-opname.data');
    Route::get('stock-out-type/data', [StockOutTypeController::class, 'get_data'])->name('stock-out-type.data');
    Route::get('production/data', [ProductionController::class, 'get_data'])->name('production-data');
    Route::get('receipt/data', [ProductReceiptController::class, 'get_data'])->name('receipt-data');
    Route::get('parcel/data', [ProductionParcelController::class, 'get_data'])->name('parcel-data');
});
