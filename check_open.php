<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bibits = \App\Models\Bibit::where('is_buka', true)->where('stok', '>', 0)->get();
foreach($bibits as $b) {
    echo $b->id . ' ' . $b->nama_bibit . ' ' . $b->stok . "\n";
}
