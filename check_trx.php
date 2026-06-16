<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transaksis = \App\Models\Transaksi::where('petani_id', 12)->get();
echo $transaksis->toJson();
