<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total_efectivo_expenses = App\Models\Expense::where('payment_method_id', 1)->sum('amount');
echo 'TOTAL EXPENSES IN EFECTIVO: ' . $total_efectivo_expenses . PHP_EOL;

$total_cuotas_efectivo = App\Models\Expense::where('payment_method_id', 1)->where('description', 'like', 'Pago de Cuota%')->sum('amount');
echo 'TOTAL CUOTAS IN EFECTIVO: ' . $total_cuotas_efectivo . PHP_EOL;
