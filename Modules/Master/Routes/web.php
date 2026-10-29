<?php
use Illuminate\Support\Facades\Route;
use Modules\Master\Http\Controllers\ProductController;
use Modules\Master\Http\Controllers\ProductCategoryController;
use Modules\Master\Http\Controllers\ProductUnitController;
use Modules\Master\Http\Controllers\LocationController;
use Modules\Master\Http\Controllers\HandlingController;
use Modules\Master\Http\Controllers\DepartmentController;
use Modules\Master\Http\Controllers\PositionController;
use Modules\Master\Http\Controllers\SupplierController;
use Modules\Master\Http\Controllers\CustomerController;
use Modules\Master\Http\Controllers\RegionController;
use Modules\Master\Http\Controllers\StaffController;
use Modules\Master\Http\Controllers\PaymentMethodController;
use Modules\Master\Http\Controllers\BranchController;
use Modules\Master\Http\Controllers\AccountController;
use Modules\Master\Http\Controllers\KurirController;
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
    // Route::get('/products', function () {
    //     return view('Master::products.index'); // di view ini kamu panggil @livewire('product-table')
    // });
    Route::resource('products', ProductController::class)->names('products')->except('show');
    Route::put('products/{id}/update-price', [ProductController::class, 'updatePrice']);
    Route::get('products/{id}/show', [ProductController::class, 'show'])->name('products.show');
    Route::resource('category', ProductCategoryController::class)->names('category')->except('show');
    Route::get('category/export', [ProductCategoryController::class, 'excel'])->name('export-data');
    Route::resource('unit', ProductUnitController::class)->names('unit')->except('show');
    Route::resource('location', LocationController::class)->names('location')->except('show');
    Route::resource('handling', HandlingController::class)->names('handling')->except('show');
    Route::resource('department', DepartmentController::class)->names('department')->except('show');
    Route::get('department/export', [DepartmentController::class, 'excel'])->name('export-department-data');
    Route::resource('position', PositionController::class)->names('position')->except('show');
    Route::get('position/export', [PositionController::class, 'excel'])->name('export-position-data');
    Route::resource('supplier', SupplierController::class)->names('supplier')->except('show');
    // Route::get('supplier/export', [SupplierController::class, 'excel'])->name('supplier-data');
    Route::resource('customers', CustomerController::class)->names('customers')->except('show');
    Route::get('customers/{id}/show', [CustomerController::class, 'show'])->name('customers.show');
    Route::resource('staff', StaffController::class)->names('staff')->except('show');
    Route::get('staff/show/{id}', [StaffController::class,'show'])->name('staff.show');
    Route::resource('branch', BranchController::class)->names('branch')->except('show');
    Route::resource('payment-method', PaymentMethodController::class)->names('payment-method')->except('show');
    Route::resource('account', AccountController::class)->names('account')->except('show');
    Route::resource('kurir', KurirController::class)->names('kurir')->except('show');
});

Route::group(['prefix' => '/', 'middleware' => ['auth']], function () {
    Route::get('/ajax/province', [RegionController::class, 'getProvince'])->name('ajax.province');
    Route::get('/ajax/city', [RegionController::class, 'getCity'])->name('ajax.city');
    Route::get('/ajax/district', [RegionController::class, 'getDistrict'])->name('ajax.district');
    Route::get('/ajax/village', [RegionController::class, 'getVillage'])->name('ajax.village');
    Route::get('/ajax/department', [DepartmentController::class, 'getDepartment'])->name('ajax.department');
    Route::get('/ajax/position', [PositionController::class, 'getPosition'])->name('ajax.position');
    Route::get('/ajax/category', [ProductCategoryController::class, 'getCategory'])->name('ajax.category');
    Route::get('/ajax/getVariant', [ProductController::class, 'getVariant'])->name('ajax.getVariant');
    Route::get('/ajax/getProduct', [ProductController::class, 'getProduct'])->name('ajax.getProduct');
    Route::get('/ajax/listProduct', [ProductController::class, 'listProduct'])->name('ajax.listProduct');
    Route::get('/ajax/getPaymentMethod', [PaymentMethodController::class, 'getPaymentMethod'])->name('ajax.getPaymentMethod');
    Route::get('/ajax/getBranch', [BranchController::class, 'getBranch'])->name('ajax.getBranch');
    Route::get('/ajax/getStaff', [StaffController::class, 'getStaff'])->name('ajax.getStaff');
    Route::get('/ajax/getKurir', [KurirController::class, 'getKurir'])->name('ajax.getKurir');
    Route::get('kurir/get-kurir', [KurirController::class, 'getKurir'])->name('kurir.get-kurir');
    Route::get('/ajax/getKurirStaff', [KurirController::class, 'getStaff'])->name('ajax.getKurirStaff');
    Route::post('products/variant/store', [ProductController::class, 'storeVariant'])->name('products.store-variant');
    Route::get('products/variants/get', [ProductController::class, 'getVariant'])->name('variants.get');
    Route::put('products/variants/{id}', [ProductController::class, 'updateVariant'])->name('products.update-variant');
    Route::delete('products/variants/{id}', [ProductController::class, 'destroyVariant'])->name('products.destroy-variant');
    Route::get('products/get-product-receipt', [ProductController::class, 'getProductReceipt'])->name('products.get-product-receipt');
    Route::post('products/generate-branch-price', [ProductController::class, 'generateBranchPrice'])->name('products.generate-branch-price');
    Route::get('staff/get-staff', [StaffController::class, 'getStaff'])->name('staff.get-staff');
    Route::get('customer/get-customer', [CustomerController::class, 'getCustomer'])->name('customer.get-customer');
    Route::get('customer/get-address', [CustomerController::class, 'getAddress'])->name('customer.get-address');
    Route::post('customer/store-address', [CustomerController::class, 'storeAddress'])->name('customer.store-address');

    // Datatable
    Route::get('products/data', [ProductController::class, 'get_data'])->name('products-data');
    Route::get('products/child-data', [ProductController::class, 'get_child_data'])->name('products-child-data');
    Route::get('products/stock-data', [ProductController::class, 'get_data_stock'])->name('product-stock-data');
    Route::get('category/data', [ProductCategoryController::class, 'get_data'])->name('category-data');
    Route::get('unit/data', [ProductUnitController::class, 'get_data'])->name('unit-data');
    Route::get('location/data', [LocationController::class, 'get_data'])->name('location-data');
    Route::get('handling/data', [HandlingController::class, 'get_data'])->name('handling-data');
    Route::get('department/data', [DepartmentController::class, 'get_data'])->name('department-data');
    Route::get('position/data', [PositionController::class, 'get_data'])->name('position-data');
    Route::get('supplier/data', [SupplierController::class, 'get_data'])->name('supplier-data');
    Route::get('customers/data', [CustomerController::class, 'get_data'])->name('customers-data');
    Route::get('staff/data', [StaffController::class, 'get_data'])->name('staff-data');
    Route::get('branch/data', [BranchController::class, 'get_data'])->name('branch.data');
    Route::get('payment-method/data', [PaymentMethodController::class, 'get_data'])->name('payment-method.data');
    Route::get('account/data', [AccountController::class, 'get_data'])->name('account.data');
    Route::get('kurir/data', [KurirController::class, 'get_data'])->name('kurir.data');
});
