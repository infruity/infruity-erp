<?php

use Illuminate\Support\Facades\Route;
use Modules\Crm\Http\Controllers\CampaignController;
use Modules\Report\Http\Controllers\ReportController;

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
    Route::get('report-transaction', [ReportController::class, 'index'])->name('report-transaction');
    Route::get('report-customer-transaction', [ReportController::class, 'customer_transaction'])->name('report-customer-transaction');
    Route::get('report-branch-transaction', [ReportController::class, 'branch_transaction'])->name('report-branch-transaction');
    Route::get('report-branch-product', [ReportController::class, 'branch_product'])->name('report-branch-product');
    Route::get('report-customer-product', [ReportController::class, 'customer_product'])->name('report-customer-product');
    Route::get('report-product-buang', [ReportController::class, 'product_buang'])->name('report-product-buang');
    Route::get('report-product-sales', [ReportController::class, 'product_sales'])->name('report-product-sales.index');
    Route::get('report-profit-revenue', [ReportController::class, 'profit_revenue'])->name('report-profit-revenue.index');
    Route::get('report-profit-adjusted', [ReportController::class, 'profit_adjusted'])->name('report-profit-adjusted.index');
    Route::get('report-shipping-cost', [ReportController::class, 'shipping_cost'])->name('report-shipping-cost');
    Route::get('report-total-aset', [ReportController::class, 'total_aset'])->name('report-total-aset');
});

Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
    Route::get('report-transaction/data', [ReportController::class, 'get_data_transaction'])->name('report-transaction.data');
    Route::get('report-customer-transaction/data', [ReportController::class, 'get_data_customer_transaction'])->name('report-customer-transaction.data');
    Route::get('report-branch-transaction/data', [ReportController::class, 'get_data_branch_transaction'])->name('report-branch-transaction.data');
    Route::get('report-branch-product/data', [ReportController::class, 'get_data_branch_product'])->name('report-branch-product.data');
    Route::get('report-customer-product/data', [ReportController::class, 'get_data_customer_product'])->name('report-customer-product.data');
    Route::get('report-product-buang/data', [ReportController::class, 'get_data_barang_buang'])->name('report-product-buang.data');
    Route::get('report-product-buang/history', [ReportController::class, 'get_product_buang_history'])->name('report-product-buang.history');
    Route::get('report-product-sales/data', [ReportController::class, 'get_data_product_sales'])->name('report-product-sales.data');
    Route::get('report-product-sales/history', [ReportController::class, 'get_product_sales_history'])->name('report-product-sales.history');
    Route::get('report-profit-revenue/data', [ReportController::class, 'get_data_profit_revenue'])->name('report-profit-revenue.data');
    Route::get('report-profit-revenue/history', [ReportController::class, 'get_profit_revenue_history'])->name('report-profit-revenue.history');
    Route::get('report-profit-adjusted/data', [ReportController::class, 'get_data_profit_adjusted'])->name('report-profit-adjusted.data');
    Route::get('report-profit-adjusted/history', [ReportController::class, 'get_profit_adjusted_history'])->name('report-profit-adjusted.history');
    Route::get('report-shipping-cost/data', [ReportController::class, 'get_data_shipping_cost'])->name('report-shipping-cost.data');
    Route::get('report-shipping-cost/history', [ReportController::class, 'get_shipping_cost_history'])->name('report-shipping-cost.history');
    Route::get('report-total-aset/data', [ReportController::class, 'get_data_total_aset'])->name('report-total-aset.data');
});
