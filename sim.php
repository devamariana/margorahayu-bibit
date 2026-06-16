<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$petani = \App\Models\Petani::find(12); // Farmer 12 is the one
$currentMusimAktif = 'kemarau'; // or whatever it is
$bibitsTerbuka = \App\Models\Bibit::where('is_buka', true)
    ->where('stok', '>', 0)
    // ->where('kategori_musim', $currentMusimAktif)
    ->get();

$estimasiJatah = 0;
foreach ($bibitsTerbuka as $bibit) {
    $totalLuasGlobal = \App\Models\Pengajuan::where('pengajuans.bibit_id', $bibit->id)
            ->where('pengajuans.status', 'disetujui')
            ->join('lahans', 'pengajuans.lahan_id', '=', 'lahans.id')
            ->sum('lahans.luas_lahan');
    
    $hakTotal = 0;
    if ($totalLuasGlobal > 0) {
        $luasLahanPengajuan = \App\Models\Pengajuan::where('pengajuans.bibit_id', $bibit->id)
                ->where('pengajuans.petani_id', $petani->id)
                ->where('pengajuans.status', 'disetujui')
                ->join('lahans', 'pengajuans.lahan_id', '=', 'lahans.id')
                ->sum('lahans.luas_lahan');
        
        if ($luasLahanPengajuan > 0) {
            $hakTotal = ($luasLahanPengajuan / $totalLuasGlobal) * $bibit->stok_awal_real;
        }
    }

    $sudahDibeli = \App\Models\Transaksi::where('petani_id', $petani->id)
        ->where('bibit_id', $bibit->id)
        ->whereIn('status_pembayaran', ['sukses', 'lunas'])
        ->whereNotNull('lahan_id')
        ->sum('jumlah_beli');

    $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg')
                       - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');

    $addition = min($bibit->stok, max(0, round(($hakTotal - $sudahDibeli) + $tambahanTransfer, 1)));
    echo "Bibit: {$bibit->nama_bibit}, Stok: {$bibit->stok}, HakTotal: $hakTotal, SudahDibeli: $sudahDibeli, Tambah: $tambahanTransfer, ADDITION: $addition\n";
    $estimasiJatah += $addition;
}

echo "TOTAL ESTIMASI: $estimasiJatah\n";
