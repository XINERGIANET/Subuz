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
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CashboxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\InventoryController;


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
Route::get('clear-cache', function () {
	$cached = base_path('bootstrap/cache/config.php');
	if (is_file($cached)) {
		@unlink($cached);
	}
	Artisan::call('config:clear');
	Artisan::call('cache:clear');
	Artisan::call('view:clear');
	return "Caché y configuración limpias con éxito.";
});

Route::get('photo-view/{path}', function ($path) {
	$fullPath = storage_path('app/public/' . $path);

	if (!file_exists($fullPath)) {
		// Debugging: return the path as text if not found instead of 404/redirect
		return "ERROR: File not found at " . $fullPath;
	}

	if (ob_get_level())
		ob_end_clean();
	return response()->file($fullPath);
})->where('path', '.*');

Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

Route::get('comprobante/{invoice}', [InvoiceController::class, 'publicDetail'])
	->name('invoices.public_detail')
	->middleware('signed');

Route::middleware('auth')->group(function () {

	Route::get('api/reniec', [\App\Http\Controllers\ApiDocumentController::class, 'apiReniec'])->name('api.reniec');
	Route::get('api/ruc', [\App\Http\Controllers\ApiDocumentController::class, 'apiRuc'])->name('api.ruc');

	Route::get('/', [WebController::class, 'index']);

	Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
	Route::get('sales/product-payments/{id}', [SaleController::class, 'productPayments'])->name('sales.product_payments');
	Route::get('sales/{sale}/details', [SaleController::class, 'details'])->name('sales.details');
	Route::post('sales/{sale}/dispatch', [SaleController::class, 'markDispatch'])->name('sales.dispatch');
	Route::post('sales/{sale}/delivery-status', [SaleController::class, 'updateDeliveryStatus'])->name('sales.updateDeliveryStatus');
	Route::post('sales/{sale}/add-detail', [SaleController::class, 'addDetail'])->name('sales.addDetail');
	Route::patch('sales/{sale}/details/{detail}', [SaleController::class, 'updateDetail'])->name('sales.updateDetail');
	Route::post('sales/{sale}/details/{detail}/split', [SaleController::class, 'splitDetail'])->name('sales.splitDetail');
	Route::delete('sales/{sale}/details/{detail}', [SaleController::class, 'destroyDetail'])->name('sales.destroyDetail');


	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

	Route::middleware('role:admin|asistente')->group(function () {
		Route::get('dashboard/api', [WebController::class, 'dashboard'])->name('dashboard.api');
		Route::get('dashboard/detail/api', [WebController::class, 'dashboardDetail'])->name('dashboard.detail.api');
		Route::get('dashboard/daily/api', [WebController::class, 'dashboardDaily'])->name('dashboard.daily.api');
		Route::get('dashboard/product/api', [WebController::class, 'dashboardProduct'])->name('dashboard.product.api');
		Route::get('dashboard/distribution/api', [WebController::class, 'dashboardDistribution'])->name('dashboard.distribution.api');

		Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
		Route::get('invoices/pending', [InvoiceController::class, 'pending'])->name('invoices.pending');
		Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
		Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
		Route::post('invoices/manual', [InvoiceController::class, 'storeManual'])->name('invoices.store_manual');
		Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'showPdf'])->name('invoices.pdf');
		Route::get('invoices/{invoice}/local-pdf', [InvoiceController::class, 'localPdf'])->name('invoices.local_pdf');
		Route::get('invoices/{invoice}/xml', [InvoiceController::class, 'downloadXml'])->name('invoices.xml');
		Route::get('invoices/{invoice}/cdr', [InvoiceController::class, 'downloadCdr'])->name('invoices.cdr');
		Route::post('invoices/{invoice}/resend', [InvoiceController::class, 'resend'])->name('invoices.resend');
		Route::post('invoices/{invoice}/release-error', [InvoiceController::class, 'releaseErrorSunat'])->name('invoices.release_error');
		Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
		Route::get('invoices/deleted', [InvoiceController::class, 'indexDeleted'])->name('reports.deleted_invoices');
	});

	Route::middleware('role:admin')->group(function () {
		Route::get('dispatchers', [UserController::class, 'indexDispatchers'])->name('users.dispatchers.index');
		Route::get('dispatchers/create', [UserController::class, 'createDispatcher'])->name('users.dispatchers.create');
		Route::post('dispatchers', [UserController::class, 'storeDispatcher'])->name('users.dispatchers.store');
		Route::get('dispatchers/{dispatcher}/edit', [UserController::class, 'editDispatcher'])->name('users.dispatchers.edit');
		Route::put('dispatchers/{dispatcher}', [UserController::class, 'updateDispatcher'])->name('users.dispatchers.update');
		Route::delete('dispatchers/{dispatcher}', [UserController::class, 'destroyDispatcher'])->name('users.dispatchers.destroy');
		Route::get('dispatchers/{dispatcher}/report', [UserController::class, 'dispatcherReport'])->name('users.dispatchers.report');
		Route::get('dispatchers/{dispatcher}/report-data', [UserController::class, 'dispatcherReportData'])->name('users.dispatchers.report_data');

		Route::get('assistants', [UserController::class, 'indexAssistants'])->name('users.assistants.index');
		Route::get('assistants/create', [UserController::class, 'createAssistant'])->name('users.assistants.create');
		Route::post('assistants', [UserController::class, 'storeAssistant'])->name('users.assistants.store');
		Route::get('assistants/{assistant}/edit', [UserController::class, 'editAssistant'])->name('users.assistants.edit');
		Route::put('assistants/{assistant}', [UserController::class, 'updateAssistant'])->name('users.assistants.update');
		Route::delete('assistants/{assistant}', [UserController::class, 'destroyAssistant'])->name('users.assistants.destroy');
	});

	Route::middleware('role:admin|seller|asistente')->group(function () {
		Route::get('products/api', [ProductController::class, 'api'])->name('products.api');
		Route::get('products/{product}/purchase-history', [ProductController::class, 'purchaseHistory'])->name('products.purchase-history');
		Route::get('supplies/{supply}/purchase-history', [SupplyController::class, 'purchaseHistory'])->name('supplies.purchase-history');
		Route::resource('products', ProductController::class);
		Route::resource('stocks', StockController::class);

		Route::post('clients/store', [ClientController::class, 'storeInSale'])->name('clients.storeInSale');
		Route::resource('clients', ClientController::class)->where(['client' => '[0-9]+']);

		Route::resource('prices', PriceController::class);
		Route::get('prices/special/{client_id}', [PriceController::class, 'getSpecialPrices'])->name('prices.special');

		Route::resource('payment_methods', PaymentMethodController::class);
		Route::resource('supplies', SupplyController::class)->except(['index', 'create', 'show']);

		Route::get('sales/excel', [SaleController::class, 'excel'])->name('sales.excel');

		Route::get('cart', [CartController::class, 'index'])->name('cart.index');
		Route::post('cart', [CartController::class, 'store'])->name('cart.store');
		Route::patch('cart', [CartController::class, 'update'])->name('cart.update');
		Route::post('cart/split', [CartController::class, 'split'])->name('cart.split');
		Route::delete('destroy', [CartController::class, 'destroy'])->name('cart.destroy');
		Route::post('cart/update-prices', [CartController::class, 'updatePricesByClient'])->name('cart.updatePrices');
		Route::resource('quotes', QuoteController::class)->except(['show']);
		Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
		Route::post('quotes/{quote}/approve', [QuoteController::class, 'approve'])->name('quotes.approve');
	});

	Route::middleware('role:admin|seller|despachador|asistente')->group(function () {
		Route::resource('sales', SaleController::class)->except(['index', 'show']);
		Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
	});

	Route::middleware('role:admin|viewer|asistente')->group(function () {
		Route::get('charges/credit', [ChargeController::class, 'credit'])->name('charges.credit');
		Route::get('charges/pending', [ChargeController::class, 'pending'])->name('charges.pending');
		Route::get('charges/history', [ChargeController::class, 'history'])->name('charges.history');

		Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
		Route::delete('payments/{id}', [PaymentController::class, 'destroy'])->name('payments.destroy');
		Route::get('payments/excel', [PaymentController::class, 'excel'])->name('payments.excel');
		Route::get('payments/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');

		Route::get('expenses/indicators', [ExpenseController::class, 'indicators'])->name('expenses.indicators');
		Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
		Route::get('expenses/pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
	});

	Route::middleware('role:admin|viewer|asistente|despachador')->group(function () {
		Route::post('expenses/stock', [ExpenseController::class, 'storeStockPurchase'])->name('expenses.storeStock');
		Route::resource('expenses', ExpenseController::class);
	});

	Route::middleware('role:admin|viewer|asistente')->group(function () {
		Route::resource('expense-categories', ExpenseCategoryController::class);
		Route::post('expense-categories/{category}/subcategories', [ExpenseCategoryController::class, 'storeSubcategory'])->name('expense-categories.subcategories.store');
		Route::put('expense-subcategories/{subcategory}', [ExpenseCategoryController::class, 'updateSubcategory'])->name('expense-subcategories.update');
		Route::delete('expense-subcategories/{subcategory}', [ExpenseCategoryController::class, 'destroySubcategory'])->name('expense-subcategories.destroy');
	});

	Route::middleware('role:admin|viewer')->group(function () {
		Route::resource('finances', FinanceController::class)->except(['create']);
		Route::post('finances/payment', [FinanceController::class, 'storePayment'])->name('finances.payment');
		Route::get('finances/payment/{payment}/edit', [FinanceController::class, 'editPayment'])->name('finances.payment.edit');
		Route::patch('finances/payment/{payment}', [FinanceController::class, 'updatePayment'])->name('finances.payment.update');
		Route::delete('finances/payment/{payment}', [FinanceController::class, 'destroyPayment'])->name('finances.payment.destroy');
	});

	Route::middleware('role:admin|seller|viewer|asistente')->group(function () {
		Route::get('sales/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
		Route::get('sales/pdf-summary', [SaleController::class, 'pdfSummary'])->name('sales.pdf_summary');

		Route::get('sales/report-pdf', [SaleController::class, 'reportPdf'])->name('sales.report_pdf');
		Route::get('sales/report-data', [SaleController::class, 'reportData'])->name('sales.report_data');
		Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
		Route::get('reports/products', [ReportController::class, 'products'])->name('reports.products');
		Route::get('reports/liquidation', [ReportController::class, 'liquidation'])->name('reports.liquidation');
		Route::get('reports/liquidation/sales', [ReportController::class, 'getSalesForLiquidation'])->name('reports.liquidation.sales');
		Route::get('reports/liquidations-history', [ReportController::class, 'liquidationsHistory'])->name('reports.liquidations_history');
		Route::get('reports/cashbox', [ReportController::class, 'cashbox'])->name('reports.cashbox');
		Route::get('reports/cashbox/{cashbox_id}/pdf', [ReportController::class, 'cashboxPdf'])->name('reports.cashbox_pdf');
		
		Route::get('jerry-can-report', [SaleController::class, 'jerryCanReportView'])->name('reports.jerryCan');
		Route::post('jerry-can-report/return', [SaleController::class, 'returnJerryCans'])->name('reports.returnJerryCans');
		Route::post('jerry-can-report/buy', [SaleController::class, 'buyJerryCans'])->name('reports.buyJerryCans');
		Route::get('jerry-can-report/pdf', [SaleController::class, 'jerryCanReportPdf'])->name('reports.jerryCanPdf');
	});

	Route::middleware('role:admin|despachador|asistente')->group(function () {
		Route::get('cashbox', [CashboxController::class, 'index'])->name('cashbox.index');
		Route::post('cashbox/open', [CashboxController::class, 'open'])->name('cashbox.open');
		Route::post('cashbox/close', [CashboxController::class, 'close'])->name('cashbox.close');
		Route::post('cashbox/income', [CashboxController::class, 'storeIncome'])->name('cashbox.income');
		Route::post('cashbox/transfer', [CashboxController::class, 'storeTransfer'])->name('cashbox.transfer');
		Route::get('cashbox/method-breakdown/{payment_method_id}', [CashboxController::class, 'getMethodBreakdown'])->name('cashbox.method_breakdown');
		Route::post('cashbox/update-opening-balance/{payment_method_id}', [CashboxController::class, 'updateOpeningBalance'])->name('cashbox.update_opening_balance');
		Route::delete('cashbox/movements/{movement}', [CashboxController::class, 'destroyMovement'])->name('cashbox.movements.destroy');
	});
});

Route::get('fixed-assets', [FixedAssetController::class, 'index'])->name('fixed-assets.index')->middleware('auth');
Route::post('fixed-assets', [FixedAssetController::class, 'store'])->name('fixed-assets.store')->middleware('auth');
Route::post('fixed-assets/{fixedAsset}/assign', [FixedAssetController::class, 'assign'])->name('fixed-assets.assign')->middleware('auth');
Route::post('/fixed-assets/{fixedAsset}/return', [FixedAssetController::class, 'returnAsset'])->name('fixed-assets.return')->middleware('auth');
Route::post('/fixed-assets/{fixedAsset}/status', [FixedAssetController::class, 'updateStatus'])->name('fixed-assets.updateStatus')->middleware('auth');
Route::post('/fixed-assets/{installment}/income', [FixedAssetController::class, 'registerIncome'])->name('fixed-assets.registerIncome')->middleware('auth');
Route::post('/fixed-assets/{id}/expense', [FixedAssetController::class, 'registerExpense'])->name('fixed-assets.registerExpense')->middleware('auth');
Route::get('/fixed-assets/category/{id}', [FixedAssetController::class, 'category'])->name('fixed-assets.category')->middleware('auth');

Route::middleware('auth')->group(function () {
	Route::get('inventories', [InventoryController::class, 'index'])->name('inventories.index');
	Route::post('inventories/initial-balance', [InventoryController::class, 'storeInitialBalance'])->name('inventories.initial_balance');
	Route::post('inventories/movement', [InventoryController::class, 'storeMovement'])->name('inventories.movement');
	Route::get('inventories/history/{itemType}/{itemId?}', [InventoryController::class, 'history'])->name('inventories.history');
	Route::post('inventories/supplies', [InventoryController::class, 'storeSupply'])->name('inventories.supplies.store');
	Route::post('inventories/toggle-dispatcher-permission', [InventoryController::class, 'toggleDispatcherPermission'])->name('inventories.toggle_dispatcher_permission');
});

