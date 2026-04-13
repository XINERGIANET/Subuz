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
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InvoiceController;


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::get('photo-view/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        // Debugging: return the path as text if not found instead of 404/redirect
        return "ERROR: File not found at " . $fullPath;
    }

    if (ob_get_level()) ob_end_clean();
    return response()->file($fullPath);
})->where('path', '.*');

Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

Route::middleware('auth')->group(function(){

	Route::get('/',[WebController::class, 'index']);

	Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
	Route::get('sales/{sale}/details', [SaleController::class, 'details'])->name('sales.details');
	Route::post('sales/{sale}/dispatch', [SaleController::class, 'markDispatch'])->name('sales.dispatch');
	Route::post('sales/{sale}/delivery-status', [SaleController::class, 'updateDeliveryStatus'])->name('sales.updateDeliveryStatus');
	Route::post('sales/{sale}/add-detail', [SaleController::class, 'addDetail'])->name('sales.addDetail');
	Route::patch('sales/{sale}/details/{detail}', [SaleController::class, 'updateDetail'])->name('sales.updateDetail');
	Route::delete('sales/{sale}/details/{detail}', [SaleController::class, 'destroyDetail'])->name('sales.destroyDetail');


	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

	Route::middleware('role:admin|asistente')->group(function(){
		Route::get('dashboard/api', [WebController::class, 'dashboard'])->name('dashboard.api');
		Route::get('dashboard/daily/api', [WebController::class, 'dashboardDaily'])->name('dashboard.daily.api');
		Route::get('dashboard/product/api', [WebController::class, 'dashboardProduct'])->name('dashboard.product.api');
		Route::get('dashboard/distribution/api', [WebController::class, 'dashboardDistribution'])->name('dashboard.distribution.api');

		Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
		Route::get('invoices/pending', [InvoiceController::class, 'pending'])->name('invoices.pending');
		Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
	});

	Route::middleware('role:admin')->group(function(){
		Route::get('dispatchers', [UserController::class, 'indexDispatchers'])->name('users.dispatchers.index');
		Route::get('dispatchers/create', [UserController::class, 'createDispatcher'])->name('users.dispatchers.create');
		Route::post('dispatchers', [UserController::class, 'storeDispatcher'])->name('users.dispatchers.store');
		Route::get('dispatchers/{dispatcher}/edit', [UserController::class, 'editDispatcher'])->name('users.dispatchers.edit');
		Route::put('dispatchers/{dispatcher}', [UserController::class, 'updateDispatcher'])->name('users.dispatchers.update');
		Route::delete('dispatchers/{dispatcher}', [UserController::class, 'destroyDispatcher'])->name('users.dispatchers.destroy');
		Route::get('dispatchers/{dispatcher}/report', [UserController::class, 'dispatcherReport'])->name('users.dispatchers.report');

		Route::get('assistants', [UserController::class, 'indexAssistants'])->name('users.assistants.index');
		Route::get('assistants/create', [UserController::class, 'createAssistant'])->name('users.assistants.create');
		Route::post('assistants', [UserController::class, 'storeAssistant'])->name('users.assistants.store');
		Route::get('assistants/{assistant}/edit', [UserController::class, 'editAssistant'])->name('users.assistants.edit');
		Route::put('assistants/{assistant}', [UserController::class, 'updateAssistant'])->name('users.assistants.update');
		Route::delete('assistants/{assistant}', [UserController::class, 'destroyAssistant'])->name('users.assistants.destroy');
	});

	Route::middleware('role:admin|seller|asistente')->group(function(){
		Route::get('products/api', [ProductController::class, 'api'])->name('products.api');
		Route::resource('products', ProductController::class);

		Route::post('clients/store', [ClientController::class, 'storeInSale'])->name('clients.storeInSale');
		Route::resource('clients', ClientController::class)->where(['client' => '[0-9]+']);

		Route::resource('prices', PriceController::class);
		Route::get('prices/special/{client_id}', [PriceController::class, 'getSpecialPrices'])->name('prices.special');

		Route::resource('payment_methods', PaymentMethodController::class);

		Route::get('sales/excel', [SaleController::class, 'excel'])->name('sales.excel');

		Route::get('cart', [CartController::class, 'index'])->name('cart.index');
		Route::post('cart', [CartController::class, 'store'])->name('cart.store');
		Route::patch('cart', [CartController::class, 'update'])->name('cart.update');
		Route::delete('destroy', [CartController::class, 'destroy'])->name('cart.destroy');
		Route::post('cart/update-prices', [CartController::class, 'updatePricesByClient'])->name('cart.updatePrices');
	});

	Route::middleware('role:admin|seller|despachador|asistente')->group(function(){
		Route::resource('sales', SaleController::class)->except(['index', 'show']);
		Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
	});

	Route::middleware('role:admin|viewer')->group(function(){
		Route::get('charges/credit', [ChargeController::class, 'credit'])->name('charges.credit');
		Route::get('charges/pending', [ChargeController::class, 'pending'])->name('charges.pending');
		Route::get('charges/history', [ChargeController::class, 'history'])->name('charges.history');

		Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
		Route::get('payments/excel', [PaymentController::class, 'excel'])->name('payments.excel');
		Route::get('payments/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');

		Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
		Route::get('expenses/pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
		Route::resource('expenses', ExpenseController::class);

		Route::get('finances', [FinanceController::class, 'index'])->name('finances.index');
		Route::post('finances', [FinanceController::class, 'store'])->name('finances.store');
		Route::get('finances/{finance}', [FinanceController::class, 'show'])->name('finances.show');
		Route::post('finances/payment', [FinanceController::class, 'storePayment'])->name('finances.payment');
	});

	Route::middleware('role:admin|seller|viewer')->group(function(){
		Route::get('sales/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
		Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
		Route::get('reports/liquidation', [ReportController::class, 'liquidation'])->name('reports.liquidation');
		Route::get('reports/cashbox', [ReportController::class, 'cashbox'])->name('reports.cashbox');
	});

	Route::middleware('role:admin|despachador')->group(function(){
		Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
		Route::post('cashbox/open', [CashboxController::class, 'open'])->name('cashbox.open');
		Route::post('cashbox/close', [CashboxController::class, 'close'])->name('cashbox.close');
		Route::post('cashbox/income', [CashboxController::class, 'storeIncome'])->name('cashbox.income');
	});

});


