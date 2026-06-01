<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expenses = App\Models\Expense::where('description', 'like', 'Pago de Cuota%')->get();
echo 'TOTAL: ' . $expenses->sum('amount') . PHP_EOL;

foreach($expenses as $e) {
    echo $e->id . ' - ' . $e->description . ' - ' . $e->amount . ' - Method: ' . $e->payment_method_id . PHP_EOL;
}
