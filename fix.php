<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
\App\Models\FixedAsset::where('status', 'maintenance')->update(['status' => 'available']);
echo "Done";
