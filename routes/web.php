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


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

Route::middleware('auth')->group(function(){

	Route::get('/',[WebController::class, 'index']);
	
	Route::get('dashboard/api', [WebController::class, 'dashboard'])->name('dashboard.api');
	Route::get('dashboard/product/api', [WebController::class, 'dashboardProduct'])->name('dashboard.product.api');
	Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
	Route::get('reports/liquidation', [ReportController::class, 'liquidation'])->name('reports.liquidation');

	Route::get('charges/credit', [ChargeController::class, 'credit'])->name('charges.credit');
	Route::get('charges/pending', [ChargeController::class, 'pending'])->name('charges.pending');
	Route::get('charges/history', [ChargeController::class, 'history'])->name('charges.history');

	Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
	Route::get('payments/excel', [PaymentController::class, 'excel'])->name('payments.excel');

	Route::get('products/api', [ProductController::class, 'api'])->name('products.api');
	Route::resource('products', ProductController::class);

	Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
	Route::post('clients/store', [ClientController::class, 'storeInSale'])->name('clients.storeInSale');
	Route::resource('clients', ClientController::class);

	Route::resource('prices', PriceController::class);

	Route::get('sales/excel', [SaleController::class, 'excel'])->name('sales.excel');
	Route::get('sales/{sale}/details', [SaleController::class, 'details'])->name('sales.details');
	Route::resource('sales', SaleController::class);

	Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
	Route::resource('expenses', ExpenseController::class);

	Route::get('cart', [CartController::class, 'index'])->name('cart.index');
	Route::post('cart', [CartController::class, 'store'])->name('cart.store');
	Route::patch('cart', [CartController::class, 'update'])->name('cart.update');
	Route::delete('destroy', [CartController::class, 'destroy'])->name('cart.destroy');

	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

});

Route::middleware('role:admin')->group(function(){

	
});
