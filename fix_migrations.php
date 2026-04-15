<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\DB::table('migrations')->insertOrIgnore([
    ['migration' => '2026_03_20_161905_add_photo_to_sales_table', 'batch' => 2],
    ['migration' => '2026_03_20_184523_add_stock_to_products_table', 'batch' => 2],
    ['migration' => '2026_03_23_173857_make_guide_nullable_in_sales_table', 'batch' => 2],
    ['migration' => '2026_03_23_181127_add_payment_method_id_to_loan_payments_table', 'batch' => 2]
]);
echo "Migrated!\n";
