<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Expense;
use App\Models\LoanPayment;

$payments = LoanPayment::whereNotNull('payment_method_id')->get();
$fixed_count = 0;
$fixed_total = 0;

foreach ($payments as $payment) {
    $loan = \App\Models\BankLoan::find($payment->bank_loan_id);
    if (!$loan) {
        continue; // skip orphaned payments
    }
    
    $description = 'Pago de Cuota ' . $payment->installment_number . ' - Crédito Banco ' . $loan->bank_name;
    
    $expense = Expense::where('description', $description)
        ->where('amount', $payment->amount)
        ->where('payment_method_id', $payment->payment_method_id)
        ->first();
        
    if ($expense) {
        $expense->delete();
        $fixed_count++;
        $fixed_total += $payment->amount;
    }
    
    // Set the payment as external
    $payment->payment_method_id = null;
    $payment->save();
}

echo "Se convirtieron $fixed_count pagos de cuotas a externos." . PHP_EOL;
echo "Total removido de las cajas: S/ " . number_format($fixed_total, 2) . PHP_EOL;

