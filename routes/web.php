<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentMethodController;


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

Route::middleware('auth')->group(function(){

	Route::get('/',[WebController::class, 'index']);

	Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
	Route::get('sales/{sale}/details', [SaleController::class, 'details'])->name('sales.details');
	Route::post('sales/{sale}/dispatch', [SaleController::class, 'markDispatch'])->name('sales.dispatch');
	Route::post('sales/{sale}/delivery-status', [SaleController::class, 'updateDeliveryStatus'])->name('sales.updateDeliveryStatus');

	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

	Route::middleware('role:admin')->group(function(){
		Route::get('dashboard/api', [WebController::class, 'dashboard'])->name('dashboard.api');
		Route::get('dashboard/daily/api', [WebController::class, 'dashboardDaily'])->name('dashboard.daily.api');
		Route::get('dashboard/product/api', [WebController::class, 'dashboardProduct'])->name('dashboard.product.api');
		Route::get('dashboard/distribution/api', [WebController::class, 'dashboardDistribution'])->name('dashboard.distribution.api');
		Route::get('dispatchers', [UserController::class, 'indexDispatchers'])->name('users.dispatchers.index');
		Route::get('dispatchers/create', [UserController::class, 'createDispatcher'])->name('users.dispatchers.create');
		Route::post('dispatchers', [UserController::class, 'storeDispatcher'])->name('users.dispatchers.store');
		Route::get('dispatchers/{dispatcher}/edit', [UserController::class, 'editDispatcher'])->name('users.dispatchers.edit');
		Route::put('dispatchers/{dispatcher}', [UserController::class, 'updateDispatcher'])->name('users.dispatchers.update');
		Route::delete('dispatchers/{dispatcher}', [UserController::class, 'destroyDispatcher'])->name('users.dispatchers.destroy');
	});

	Route::middleware('role:admin|seller')->group(function(){
		Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
		Route::get('products/api', [ProductController::class, 'api'])->name('products.api');
		Route::resource('products', ProductController::class);

		Route::post('clients/store', [ClientController::class, 'storeInSale'])->name('clients.storeInSale');
		Route::resource('clients', ClientController::class)->where(['client' => '[0-9]+']);

		Route::resource('prices', PriceController::class);

		Route::resource('payment_methods', PaymentMethodController::class);

		Route::get('sales/excel', [SaleController::class, 'excel'])->name('sales.excel');
		Route::resource('sales', SaleController::class)->except(['index', 'show']);

		Route::get('cart', [CartController::class, 'index'])->name('cart.index');
		Route::post('cart', [CartController::class, 'store'])->name('cart.store');
		Route::patch('cart', [CartController::class, 'update'])->name('cart.update');
		Route::delete('destroy', [CartController::class, 'destroy'])->name('cart.destroy');
	});

	Route::middleware('role:admin|viewer')->group(function(){
		Route::get('charges/credit', [ChargeController::class, 'credit'])->name('charges.credit');
		Route::get('charges/pending', [ChargeController::class, 'pending'])->name('charges.pending');
		Route::get('charges/history', [ChargeController::class, 'history'])->name('charges.history');

		Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
		Route::get('payments/excel', [PaymentController::class, 'excel'])->name('payments.excel');

		Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
		Route::resource('expenses', ExpenseController::class);
	});

	Route::middleware('role:admin|seller|viewer')->group(function(){
		Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
		Route::get('reports/liquidation', [ReportController::class, 'liquidation'])->name('reports.liquidation');
	});

	Route::middleware('role:admin|despachador')->group(function(){
		Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
		Route::post('cashbox/open', [CashboxController::class, 'open'])->name('cashbox.open');
		Route::post('cashbox/close', [CashboxController::class, 'close'])->name('cashbox.close');
	});

});
