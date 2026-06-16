<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
$bibits = \App\Models\Bibit::where('periode_id', $periodeAktif->id)->get();
echo "Active Periode ID: " . $periodeAktif->id . "\n";
foreach ($bibits as $b) {
    echo "Bibit ID: {$b->id}, Nama: {$b->nama_bibit}, Stok: {$b->stok}, Stok Awal: {$b->stok_awal}\n";
}
