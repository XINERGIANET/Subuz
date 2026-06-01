<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Expense;
use App\Models\LoanPayment;
use App\Models\BankLoan;

$expenses = Expense::where('description', 'like', 'Pago de Cuota%')->get();
$orphaned_expenses_count = 0;
$orphaned_expenses_amount = 0;

$valid_bank_names = BankLoan::pluck('bank_name')->toArray();

foreach ($expenses as $expense) {
    $is_orphan = true;
    foreach ($valid_bank_names as $bn) {
        if (strpos($expense->description, $bn) !== false) {
            $is_orphan = false;
            break;
        }
    }
    
    if ($is_orphan) {
        $orphaned_expenses_amount += $expense->amount;
        $orphaned_expenses_count++;
        $expense->delete();
    }
}

// Clean up orphaned LoanPayments
$payments = LoanPayment::all();
$orphaned_payments_count = 0;
foreach ($payments as $p) {
    if (!$p->loan) {
        $orphaned_payments_count++;
        $p->delete();
    }
}

echo "Deleted $orphaned_expenses_count orphaned expenses for a total of S/ " . number_format($orphaned_expenses_amount, 2) . PHP_EOL;
echo "Deleted $orphaned_payments_count orphaned loan payments." . PHP_EOL;

