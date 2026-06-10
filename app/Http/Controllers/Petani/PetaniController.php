<?php 

namespace App\Http\Controllers\Petani;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Petani; 
use App\Models\Bibit; 
use App\Models\Transaksi;
use App\Models\Lahan;
use App\Models\PindahJatah;
use App\Traits\WhatsappNotifier;

class PetaniController extends Controller
{
    use WhatsappNotifier;
    /**
     * Menampilkan Dashboard dengan Data Lahan & Jatah Bibit (Hanya jika stok ada)
     */
    public function dashboard()
    {
        // 1. Cek Periode Aktif & Bibit yang SUDAH DIBUKA oleh Admin
        // FITUR OTOMATIS: Jika distribusi sudah lewat 7 hari, otomatis tutup di DB agar sinkron
        Bibit::where('is_buka', true)
             ->where('tanggal_buka', '<=', now()->subDays(7))
             ->update(['is_buka' => false]);

        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        $bibitsTerbuka = Bibit::where('is_buka', true)->where('stok', '>', 0)->latest()->get();
        
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        if (!$petani) {
            return redirect()->route('login');
        }

        // 2. Ambil data lahan
        $lahans = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->get();
        $totalLuas = $lahans->sum('luas_lahan');
        $jumlahLahan = $lahans->count();

        // Jika belum ada lahan terverifikasi, maka jangan tampilkan estimasi jatah apa pun
        $listDistribusi = [];
        $isPenjualanAktif = false;

        if ($jumlahLahan > 0) {
            // 3. Bangun List Distribusi (Multiple Bibit)
            foreach ($bibitsTerbuka as $bibit) {
                $tenggat = \Carbon\Carbon::parse($bibit->tanggal_buka)->addDays(7);
                $sisaHari = (int) now()->diffInDays($tenggat, false);

                if ($sisaHari < 0) {
                    // Lewat 7 hari, skip dari daftar (otomatis ditutup)
                    continue;
                }



                $isPenjualanAktif = true;

                // Perbaikan: Jika sisa hari adalah 0 tapi masih di bawah 24 jam, set minimal 1
                if ($sisaHari == 0 && now()->lessThan($tenggat)) {
                    $sisaHari = 1;
                }

                // VALIDASI JATAH: Petani mendapat stok penuh untuk lahan mereka
                $hakProposional = $bibit->stok;


                $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)
                    ->where('bibit_id', $bibit->id)
                    ->sum('jumlah_kg')
                    - \App\Models\PindahJatah::where('pengirim_id', $petani->id)
                        ->where('bibit_id', $bibit->id)
                        ->sum('jumlah_kg');

                $hakTotal = $hakProposional + $tambahanTransfer;

                $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                    ->where('bibit_id', $bibit->id)
                    ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak'])
                    ->sum('jumlah_beli');

                $jatah = round(max(0, $hakTotal - $sudahDibeli), 1);

                $listDistribusi[] = [
                    'id' => $bibit->id,
                    'nama' => $bibit->nama_bibit,
                    'jenis' => $bibit->jenis,
                    'stokGudang' => $bibit->stok,
                    'jatah' => $jatah,
                    'isTerbuka' => false,
                    'sisaHari' => $sisaHari,
                    'tanggalBuka' => \Carbon\Carbon::parse($bibit->tanggal_buka)->format('d/m/Y')
                ];
            }
        }

        // 4. Ambil data riwayat
        $riwayat = Transaksi::where('petani_id', $petani->id)
                    ->latest()
                    ->take(3)
                    ->get();

        // 5. Data Chart Pembelian (6 bulan terakhir)
        $chartLabels = [];
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = \Carbon\Carbon::today()->subMonths($i);
            $chartLabels[] = $month->translatedFormat('M');
            $chartData[] = Transaksi::where('petani_id', $petani->id)
                ->whereIn('status_pembayaran', ['sukses', 'lunas'])
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('jumlah_beli');
        }

        return view('petani.dashboard', compact(
            'petani', 
            'riwayat', 
            'totalLuas', 
            'jumlahLahan', 
            'isPenjualanAktif',
            'periodeAktif',
            'listDistribusi',
            'chartLabels',
            'chartData'
        ));
    }

    public function lahan()
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        
        $lahans = Lahan::with(['transaksi' => function($q) {
            $q->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak'])->with('bibit');
        }])->where('petani_id', $petani->id)->get();
        $totalLuas = $lahans->sum('luas_lahan');
        $jumlahLahan = $lahans->count();

        $estimasiJatah = 0;
        $bibitsTerbuka = Bibit::where('is_buka', true)->where('stok', '>', 0)->get();

        // Jika belum ada lahan terverifikasi, estimasi jatah harus 0
        if ($jumlahLahan <= 0) {
            return view('petani.lahan', compact('petani', 'lahans', 'totalLuas', 'jumlahLahan', 'estimasiJatah'));
        }

        // Rumus estimasi sesuai permintaan:
        // (luas lahan petani / total luas lahan seluruh petani) × stok bibit
        $totalLuasSemuaPetani = Lahan::where('status', 'disetujui')->sum('luas_lahan');
        $luasPetani = $lahans->sum('luas_lahan');

        foreach ($bibitsTerbuka as $bibit) {
            $hakTotal = 0;
            if ($totalLuasSemuaPetani > 0) {
                $hakTotal = ($luasPetani / $totalLuasSemuaPetani) * ($bibit->stok ?? 0);
            }

            // Kurangi transaksi yang sudah sukses untuk kebutuhan estimasi jatah
            // HANYA hitung transaksi yang merupakan pengambilan petani (status_pembayaran sukses)
            // dan pastikan transaksi tersebut punya lahan (bukan transaksi lain).
            $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                ->where('bibit_id', $bibit->id)
                ->where('status_pembayaran', 'sukses')
                ->whereNotNull('lahan_id')
                ->sum('jumlah_beli');

            $estimasiJatah += max(0, round($hakTotal - $sudahDibeli, 1));
        }


        return view('petani.lahan', compact('petani', 'lahans', 'totalLuas', 'jumlahLahan', 'estimasiJatah'));
    }

    /**
     * Menyimpan data lahan baru
     */
    public function storeLahan(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        // VALIDASI AKUN TERVERIFIKASI
        if (!$petani || $petani->status !== 'disetujui') {
            return back()->with('error', 'Gagal menambahkan lahan. Akun Anda belum terverifikasi oleh Admin!');
        }

        $request->validate([
            'nama_blok' => 'required|string|max:255',
            'luas_lahan' => 'required|numeric|min:1',
        ]);

        $lahan = Lahan::create([
            'petani_id' => $petani->id,
            'nama_blok' => $request->nama_blok,
            'luas_lahan' => $request->luas_lahan,
            'jenis_tanah' => $request->jenis_tanah ?? '-',
            'rencana_bibit' => '-',
        ]);

        // Beritahu admin
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SistemNotifikasi(
                'Permintaan Data Lahan Baru', 
                "Petani {$petani->nama_lengkap} telah menambahkan lahan di {$lahan->nama_blok}. Mohon segera diverifikasi supaya mereka bisa belanja bibit.", 
                'info',
                url('/admin/data-lahan'),
                $lahan->id
            ));
        }

        return back()->with('success', 'Lahan baru berhasil ditambahkan dan menunggu verifikasi admin!');
    }

    /**
     * Menghapus Data Lahan
     */
    public function hapusLahan($id)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $lahan = Lahan::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();
        
        $lahan->delete();

        return back()->with('success', 'Data lahan berhasil dihapus.');
    }

    /**
     * Menampilkan halaman Informasi & Pembelian Bibit
     */
    public function beliBibit()
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        // 1. Cek Status Verifikasi
        if ($petani->status !== 'disetujui') {
            return redirect()->route('petani.dashboard')->with('error', 'Akun Anda belum diverifikasi oleh Admin. Anda belum bisa melakukan pembelian bibit.');
        }

        // 2. Cek apakah ada Periode yang AKTIF
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        if (!$periodeAktif) {
            return redirect()->route('petani.dashboard')->with('error', 'Fitur pembelian dikunci karena saat ini tidak ada periode distribusi bibit yang aktif.');
        }

        // PERBAIKAN: Ambil status musim langsung dari Payung Utama Data Periode yang aktif
        $currentMusimAktif = $periodeAktif->musim ?? 'kemarau';

        // Sync status masa aktif bibit (belum lewat 7 hari)
        Bibit::where('is_buka', true)
             ->where('tanggal_buka', '<=', now()->subDays(7))
             ->update(['is_buka' => false]);

        // Ambil bibit yang HANYA ada di periode AKTIF dan sesuai Musim Utama saat ini
        $semuaBibit = Bibit::where('periode_id', $periodeAktif->id)
                           ->where('stok', '>', 0)
                           ->where('kategori_musim', $currentMusimAktif)
                           ->get();

        if ($semuaBibit->isEmpty()) {
            return redirect()->route('petani.dashboard')->with('error', 'Mohon maaf, saat ini sedang tidak ada distribusi/penjualan bibit yang aktif untuk ' . ucfirst($currentMusimAktif) . '.');
        }

        $isJatahTerbuka = false; // Fitur Fase Terbuka sudah dihapus, selamanya false

        // Ambil data lahan milik petani yang disetujui
        $lahans = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->get();

        // Hitung total luas lahan petani ini
        $totalLuasPetani = $lahans->sum('luas_lahan');

        // Tambahkan info "sudah dibeli" untuk tiap bibit
        foreach ($semuaBibit as $b) {
            $b->sudah_dibeli = Transaksi::where('petani_id', $petani->id)
                ->where('bibit_id', $b->id)
                ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak', 'cancel', 'expire'])
                ->sum('jumlah_beli');

            $totalLuasSemuaPetani = \App\Models\Lahan::where('status', 'disetujui')->sum('luas_lahan');
            $luasLahanPetani = \App\Models\Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->sum('luas_lahan');

            $hakTotal = 0;
            if ($totalLuasSemuaPetani > 0) {
                $hakTotal = ($luasLahanPetani / $totalLuasSemuaPetani) * ($b->stok ?? 0);
            }

            // Kurangi stok yang sudah dibeli sukses/pending
            $b->sisa_jatah_global = max(0, round($hakTotal - $b->sudah_dibeli, 1));
        }

        // Bangun peta pembelian per lahan untuk tiap bibit
        $purchases = [];
        foreach ($semuaBibit as $b) {
            foreach ($lahans as $l) {
                $purchases[$b->id][$l->id] = Transaksi::where('petani_id', $petani->id)
                    ->where('bibit_id', $b->id)
                    ->where('lahan_id', $l->id)
                    ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak'])
                    ->sum('jumlah_beli');
            }
        }

        return view('petani.beli_bibit', compact('semuaBibit', 'petani', 'lahans', 'isJatahTerbuka', 'purchases', 'totalLuasPetani', 'currentMusimAktif'));
    }

    /**
     * Menampilkan halaman transfer jatah ke Admin
     */
    public function transferJatah(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        if (!$petani || $petani->status !== 'disetujui') {
            return redirect()->route('petani.dashboard')->with('error', 'Akun Anda belum diverifikasi oleh Admin. Anda belum bisa mengembalikan jatah.');
        }

        Bibit::where('is_buka', true)
             ->where('tanggal_buka', '<=', now()->subDays(7))
             ->update(['is_buka' => false]);

        $bibitsTerbuka = Bibit::where('is_buka', true)->where('stok', '>', 0)->get();
        $selectedBibit = null;
        $sisaJatah = 0;

        if ($request->query('bibit_id')) {
            $selectedBibit = $bibitsTerbuka->firstWhere('id', $request->query('bibit_id'));
        }

        if ($selectedBibit) {
            // Total Luas Seluruh Petani (Global)
            $totalLuasGlobal = Lahan::where('status', 'disetujui')->sum('luas_lahan');
            
            // Total Luas Lahan Petani Ini (Global aset)
            $totalLuasPetani = Lahan::where('petani_id', $petani->id)
                ->where('status', 'disetujui')
                ->sum('luas_lahan');

            // 1. Hitung Hak Proposional (Sesuai Rumus Global)
            $hakProposional = 0;
            if ($totalLuasGlobal > 0) {
                $hakProposional = ($totalLuasPetani / $totalLuasGlobal) * ($selectedBibit->stok ?? 0);
            }

            // 2. Hitung Tambahan dari Transfer Jatah (Penerima - Pengirim)
            $tambahanTransfer = PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $selectedBibit->id)->sum('jumlah_kg')
                               - PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $selectedBibit->id)->sum('jumlah_kg');

            // 3. Hak Total Pre-Purchase
            $hakTotal = round($hakProposional + $tambahanTransfer, 1);

            // 4. Hitung Yang Sudah Dibeli
            $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                ->where('bibit_id', $selectedBibit->id)
                ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak', 'cancel', 'expire'])
                ->sum('jumlah_beli');

            // 5. Sisa Jatah yang Tersedia (untuk ditransfer/dikembalikan)
            $sisaJatah = max(0, round($hakTotal - $sudahDibeli, 1));
        }

        $riwayatTransfer = PindahJatah::where('pengirim_id', $petani->id)->latest()->take(10)->get();

        return view('petani.transfer_jatah', compact('petani', 'bibitsTerbuka', 'selectedBibit', 'sisaJatah', 'riwayatTransfer'));
    }

    /**
     * Proses pengembalian jatah ke Admin
     */
    public function prosesTransfer(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        if (!$petani || $petani->status !== 'disetujui') {
            return redirect()->route('petani.dashboard')->with('error', 'Akun Anda belum diverifikasi oleh Admin. Anda belum bisa mengembalikan jatah.');
        }

        $request->validate([
            'bibit_id' => 'required|exists:bibits,id',
            'jumlah_kg' => 'required|numeric|min:0.1',
            'alasan' => 'nullable|string|max:255',
        ]);

        $bibit = Bibit::findOrFail($request->bibit_id);

        $totalLuas = Lahan::where('petani_id', $petani->id)
            ->where('status', 'disetujui')
            ->sum('luas_lahan');

        $hakProposional = ($bibit->stok ?? 0);

        $tambahanTransfer = PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg')
                           - PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');

        $hakTotal = round($hakProposional + $tambahanTransfer, 1);

        $sudahDibeli = Transaksi::where('petani_id', $petani->id)
            ->where('bibit_id', $bibit->id)
            ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak', 'cancel', 'expire'])
            ->sum('jumlah_beli');

        $sisaJatah = max(0, $hakTotal - $sudahDibeli);

        if ($request->jumlah_kg > $sisaJatah) {
            return back()->with('error', "Jumlah pengembalian ({$request->jumlah_kg} Kg) melebihi sisa jatah Anda ({$sisaJatah} Kg).");
        }

        $bibit->stok += $request->jumlah_kg;
        $bibit->save();

        PindahJatah::create([
            'bibit_id' => $bibit->id,
            'pengirim_id' => $petani->id,
            'penerima_id' => null,
            'jumlah_kg' => $request->jumlah_kg,
            'alasan' => $request->alasan,
        ]);

        return back()->with('success', "Jatah {$bibit->nama_bibit} sebesar {$request->jumlah_kg} Kg berhasil dikembalikan ke Admin.");
    }

    /**
     * Proses Pemesanan / Beli Bibit
     */
    public function prosesBeliBibit(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        // REVISI SIDANG: Pastikan periode masih aktif saat memproses pembelian
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        if (!$periodeAktif) {
            return redirect()->route('petani.dashboard')->with('error', 'Gagal! Periode distribusi sudah ditutup.');
        }

        $request->validate([
            'jumlah_beli' => 'required|numeric|min:0.1',
            'total_harga' => 'required|numeric',
            'metode_pembayaran' => 'required|in:midtrans,transfer_manual,tunai',
        ]);

        // 0. Safety Check
        $semuaBibit = Bibit::where('is_buka', true)->where('stok', '>', 0)->get();
        if ($semuaBibit->isEmpty()) {
            return back()->with('error', 'Transaksi ditolak. Distribusi penjualan bibit sedang tidak aktif.');
        }

        // MUSIM AKTIF = musim dari bibit yang sedang dibuka oleh admin
        $musimDibuka = $semuaBibit
            ->pluck('kategori_musim')
            ->filter(fn($v) => in_array($v, ['kemarau', 'penghujan'], true))
            ->unique();

        if ($musimDibuka->isEmpty()) {
            return back()->with('error', 'Transaksi ditolak. Distribusi aktif belum memiliki kategori musim yang valid.');
        }

        // Cek kembali status verifikasi untuk mencegah akses nakal
        if ($petani->status !== 'disetujui') {
            return back()->with('error', 'Akun Anda belum diverifikasi. Pembelian ditolak.');
        }
        
        // Pastikan lahan dimiliki oleh petani ini dan SUDAH DISETUJUI
        $lahan = Lahan::where('id', $request->lahan_id)
                      ->where('petani_id', $petani->id)
                      ->where('status', 'disetujui')
                      ->firstOrFail();
        
        $bibit = Bibit::findOrFail($request->bibit_id);

        // Validasi musim: bibit yang dibeli harus sesuai dengan Musim Aktif di Periode saat ini
        if ($bibit->kategori_musim !== $periodeAktif->musim) {
            return back()->with('error', 'Transaksi ditolak. Bibit ' . $bibit->nama_bibit . ' hanya dapat dibeli pada Musim ' . strtoupper($bibit->kategori_musim) . '.');
        }

        // Validasi hanya bibit yang memang dibuka
        if (!$bibit->is_buka || $bibit->tanggal_buka <= now()->subDays(7)) {
            return back()->with('error', 'Transaksi ditolak. Distribusi bibit ini sudah ditutup.');
        }

        if ($bibit->stok < $request->jumlah_beli) {
            return back()->with('error', 'Stok bibit tidak mencukupi!');
        }

        // VALIDASI JATAH: (Luas Lahan / Total Luas Petani) × Stok + Transfer
        $totalLuasPetani = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->sum('luas_lahan');
        $lahanLuas = $lahan->luas_lahan;
        
        // Hak per lahan: (luas_lahan_ini / total_luas_petani) × stok_bibit
        $hakProposional = $totalLuasPetani > 0 ? ($lahanLuas / $totalLuasPetani) * $bibit->stok : 0;
        
        // Jatah = Hak Proporsional + Transfer Masuk - Transfer Keluar
        $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg') 
                           - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');
        
        $hakTotal = round($hakProposional + $tambahanTransfer, 1);
        
        // Cek realisasi sukses sebelumnya (PER LAHAN)
        $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                ->where('bibit_id', $bibit->id)
                ->where('lahan_id', $lahan->id)
                ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak'])
                ->sum('jumlah_beli');
        
        $sisaJatah = max(0, $hakTotal - $sudahDibeli);

        if ($request->jumlah_beli > $sisaJatah) {
            return back()->with('error', "Transaksi Gagal. Jumlah yang Anda ambil ({$request->jumlah_beli} Kg) melebihi sisa hak jatah Anda ({$sisaJatah} Kg).");
        }

        // Kurangi Stok Bibit
        $bibit->stok -= $request->jumlah_beli;
        $bibit->save();

        // Buat ID Transaksi Khusus
        $order_id = 'TRX-' . time() . '-' . $petani->id;

        // Buat Transaksi
        // REVISI: Hilangkan verifikasi manual admin. 
        // 1. Tunai langsung SUKSES (otomatis lunas)
        // 2. Transfer Manual tetap PENDING agar bisa upload bukti (setelah upload otomatis SUKSES)
        // 3. Midtrans tetap PENDING (otomatis lunas via webhook)
        // REVISI SIDANG: Semua metode manual (Tunai/Transfer) butuh approve Admin
        // Status awal selalu pending
        $status_awal = 'pending';

        $transaksi = Transaksi::create([
            'order_id' => $order_id,
            'petani_id' => $petani->id,
            'bibit_id' => $request->bibit_id,
            'lahan_id' => $request->lahan_id,
            'jumlah_beli' => $request->jumlah_beli,
            'total_harga' => $request->total_harga,
            'metode_pembayaran' => $request->metode_pembayaran,
            'status_pembayaran' => $status_awal
        ]);

        // Jika Midtrans, generate Snap Token
        if ($request->metode_pembayaran == 'midtrans') {
            try {
                \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
                \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
                \Midtrans\Config::$isSanitized = true;
                \Midtrans\Config::$is3ds = true;
                \Midtrans\Config::$curlOptions = [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
                ];

                $params = [
                    'transaction_details' => [
                        'order_id' => $order_id,
                        'gross_amount' => (int)$request->total_harga,
                    ],
                    'customer_details' => [
                        'first_name' => $petani->nama_lengkap,
                        'email' => $petani->user->email ?? 'petani@mail.com',
                        'phone' => $petani->no_hp ?? '',
                    ],
                ];

                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $transaksi->snap_token = $snapToken;
                $transaksi->save();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Midtrans Error: ' . $e->getMessage());
            }

            return redirect()->route('petani.bayar_bibit', $transaksi->id)->with('success', 'Pesanan dibuat. Silakan pilih metode pembayaran Midtrans.');
        }

        // Notif Admin Ada Pembelian Baru
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SistemNotifikasi(
                'Pesanan Bibit Baru', 
                "Petani {$petani->nama_lengkap} memesan {$request->jumlah_beli} Kg bibit '{$bibit->nama_bibit}' (Metode: {$request->metode_pembayaran}).", 
                'info',
                url('/admin/riwayat-transaksi'),
                $transaksi->id
            ));
        }

        if ($request->metode_pembayaran == 'tunai') {
            $transaksi->status_pembayaran = 'menunggu_persetujuan';
            $transaksi->save();
            return redirect()->route('petani.riwayat')->with('success', 'Pesanan (Tunai) berhasil dibuat. Silakan serahkan pembayaran ke Admin/Ketua Kelompok untuk diverifikasi.');
        }

        if ($request->metode_pembayaran == 'transfer_manual') {
            return redirect()->route('petani.bayar_bibit', $transaksi->id)->with('success', 'Pesanan (Transfer) berhasil dibuat. Silakan upload bukti transfer agar dapat diverifikasi oleh Admin.');
        }

        return redirect()->route('petani.riwayat')->with('success', 'Pesanan berhasil dibuat.');
    }

    /**
     * Upload Bukti Transfer Manual
     */
    public function uploadBukti(Request $request, $id)
    {
        $request->validate([
            'bukti_pembayaran' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $transaksi = Transaksi::findOrFail($id);
        
        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $filename = 'BUKTI-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/bukti_bayar'), $filename);

            $transaksi->bukti_pembayaran = $filename;
            $transaksi->status_pembayaran = 'menunggu_persetujuan'; // Status naik ke tahap verifikasi Admin
            $transaksi->save();

            return back()->with('success', 'Bukti pembayaran berhasil diunggah. Silakan tunggu verifikasi dari Admin.');
        }

        return back()->with('error', 'Gagal mengupload bukti.');
    }

    /**
     * Munculkan Midtrans Snap sesudah Admin acc dan Petani pilih bayar
     */
    public function bayarBibit($id)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $transaksi = Transaksi::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();

        // Cek tenggat waktu 1 minggu dari tanggal persetujuan (updated_at)
        if ($transaksi->status_pembayaran == 'menunggu_pembayaran') {
            $tenggat = $transaksi->updated_at->addDays(7);
            if (now()->greaterThan($tenggat)) {
                $transaksi->status_pembayaran = 'kadaluarsa';
                $transaksi->save();

                // Kembalikan Stok
                $bibit = $transaksi->bibit;
                $bibit->stok += $transaksi->jumlah_beli;
                $bibit->save();

                return redirect()->route('petani.riwayat')->with('error', 'Pesanan ini sudah kadaluarsa (lebih dari 1 minggu belum dibayar). Stok bibit telah dikembalikan.');
            }
        }

        // Jika sudah sukses, arahkan ke riwayat
        if ($transaksi->status_pembayaran == 'sukses') {
            return redirect()->route('petani.riwayat')->with('success', 'Transaksi ini sudah selesai.');
        }

        // Generate Snap Token jika belum ada dan status menunggu pembayaran
        if (empty($transaksi->snap_token) && $transaksi->status_pembayaran == 'menunggu_pembayaran') {
            
            // Konfigurasi Midtrans
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
            \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized');
            \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds');

            // Inisialisasi CURL Options untuk mencegah error "Undefined array key 10023"
            // Dan sekaligus bypass SSL Certificate issue di localhost secara aman
            \Midtrans\Config::$curlOptions = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => array('X-Dummy: 1')
            );

            // Validasi Kunci
            if (empty(\Midtrans\Config::$serverKey)) {
                return back()->with('error', 'Konfigurasi Midtrans (Server Key) belum diisi di file .env');
            }

            $params = [
                'transaction_details' => [
                    'order_id' => $transaksi->order_id,
                    'gross_amount' => $transaksi->total_harga,
                ],
                'customer_details' => [
                    'first_name' => collect(explode(' ', $petani->nama_lengkap))->first(),
                    'last_name' => collect(explode(' ', $petani->nama_lengkap))->slice(1)->implode(' '),
                    'phone' => str_replace('+', '', $petani->no_hp),
                    'email' => 'petani_' . $petani->id . '@example.com',
                ],
                'item_details' => [
                    [
                        'id' => $transaksi->bibit->id,
                        'price' => $transaksi->bibit->harga_subsidi,
                        'quantity' => $transaksi->jumlah_beli,
                        'name' => substr($transaksi->bibit->nama_bibit, 0, 50) // Nama barang dibatasi agar tidak terlalu panjang
                    ]
                ]
            ];

            try {
                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $transaksi->snap_token = $snapToken;
                $transaksi->save();
            } catch (\Exception $e) {
                // Log error untuk debug internal jika diperlukan
                \Illuminate\Support\Facades\Log::error('Midtrans Error: ' . $e->getMessage());
                return back()->with('error', 'Gagal memanggil Gateway Pembayaran: ' . $e->getMessage());
            }
        }

        return view('petani.bayar_bibit', compact('transaksi', 'petani'));
    }

    /**
     * Membatalkan Pembayaran dan Mengembalikan Stok
     */
    public function batalBayar($id)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $transaksi = Transaksi::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();

        // Hanya bisa dibatalkan jika statusnya belum dibayar/selesai
        if (in_array($transaksi->status_pembayaran, ['pending', 'menunggu_persetujuan', 'menunggu_pembayaran'])) {
            // Kembalikan stok bibit
            $bibit = $transaksi->bibit;
            $bibit->stok += $transaksi->jumlah_beli;
            $bibit->save();

            // Hapus transaksi
            $transaksi->delete();

            return redirect()->route('petani.beli_bibit')->with('error', 'Pembayaran berhasil dibatalkan. Stok bibit telah dikembalikan.');
        }

        return redirect()->route('petani.riwayat')->with('error', 'Transaksi ini tidak dapat dibatalkan.');
    }

    /**
     * Callback Setelah Selesai Bayar di Midtrans (Front-end Redirect)
     * Verifikasi Asli Akan Dilakukan Oleh Webhook!
     */
    public function suksesBayarBibit($id)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $transaksi = Transaksi::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();

        // Jika pembayaran bukan via Midtrans, jangan langsung lunas.
        if ($transaksi->metode_pembayaran != 'midtrans') {
            if ($transaksi->status_pembayaran == 'pending') {
                $transaksi->status_pembayaran = 'menunggu_persetujuan';
                $transaksi->save();
            }

            return redirect()->route('petani.riwayat')->with('success', 'Permintaan verifikasi pembayaran telah dikirim. Silakan tunggu konfirmasi Admin.');
        }

        // Cek status satu kali lagi untuk memastikan database terupdate segera setelah redirect
        try {
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
            \Midtrans\Config::$curlOptions = [
                CURLOPT_SSL_VERIFYPEER => false, 
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => []
            ];

            $status = \Midtrans\Transaction::status($transaksi->order_id);
            if ($status->transaction_status == 'settlement' || $status->transaction_status == 'capture') {
                $transaksi->status_pembayaran = 'sukses';
                $transaksi->save();
                return redirect()->route('petani.riwayat')->with('success', 'Pembayaran Terkonfirmasi! Pesanan Anda telah lunas.');
            }
        } catch (\Exception $e) {
            \Log::error('Verification failed on redirect: ' . $e->getMessage());
        }
        
        return redirect()->route('petani.riwayat')->with('info', 'Pembayaran sedang diproses. Silakan refresh halaman jika status belum berubah.');
    }

    /**
     * Menampilkan halaman Riwayat Pembelian (Fungsi Baru)
     */
    public function riwayat(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $periode = $request->input('periode');
        
        // Ambil SEMUA riwayat (pending, sukses, batal) agar bisa dipantau petani
        $query = Transaksi::with(['lahan', 'bibit'])
                    ->where('petani_id', $petani->id);

        if ($periode) {
            $year = substr($periode, 0, 4);
            $month = substr($periode, 5, 2);
            $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        $riwayatRaw = $query->latest()->get();

        // --- FITUR AUTO-SYNC: Cek Otomatis ke Midtrans & Expired saat Halaman Dibuka ---
        // Sangat berguna jika Webhook terhambat (Misal: Localhost)
        foreach ($riwayatRaw as $trx) {
            if ($trx->status_pembayaran == 'menunggu_pembayaran' || $trx->status_pembayaran == 'pending') {
                // 1. Cek Expired (7 Hari dari updated_at - saat disetujui/dibuat)
                $tenggat = $trx->updated_at->addDays(7);
                if (now()->greaterThan($tenggat)) {
                    $trx->status_pembayaran = 'kadaluarsa';
                    $trx->save();

                    // Kembalikan Stok
                    if ($trx->bibit) {
                        $trx->bibit->stok += $trx->jumlah_beli;
                        $trx->bibit->save();
                    }
                    continue; // Lanjut ke transaksi berikutnya
                }

                // 2. Cek Status ke Midtrans
                try {
                    \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
                    \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
                    \Midtrans\Config::$curlOptions = [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_HTTPHEADER => [],
                    ];

                    $status = \Midtrans\Transaction::status($trx->order_id);
                    if ($status->transaction_status == 'settlement' || $status->transaction_status == 'capture') {
                        $trx->status_pembayaran = 'sukses';
                        $trx->save();
                    }
                } catch (\Exception $e) {
                    \Log::info("Lazy sync skipped for {$trx->order_id}: " . $e->getMessage());
                }
            }
        }

        // Ambil data terbaru untuk dikirim ke View (setelah sinkronisasi)
        $riwayat = Transaksi::with(['lahan', 'bibit'])
                    ->where('petani_id', $petani->id)
                    ->latest()
                    ->get();

        return view('petani.riwayat', compact('riwayat'));
    }

    /**
     * Sinkronisasi Manual Status Pembayaran dari Midtrans
     */
    public function syncStatus($id)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $transaksi = Transaksi::where('id', $id)
                            ->where('petani_id', $petani->id)
                            ->firstOrFail();

        try {
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
            \Midtrans\Config::$curlOptions = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => [],
            ];

            $status = \Midtrans\Transaction::status($transaksi->order_id);
            
            if ($status->transaction_status == 'settlement' || $status->transaction_status == 'capture') {
                $transaksi->status_pembayaran = 'sukses';
                $transaksi->save();
                return back()->with('success', 'Status Berhasil Disinkronkan! Pembayaran telah diterima.');
            } elseif ($status->transaction_status == 'pending') {
                return back()->with('info', 'Status: Masih Menunggu Pembayaran di Sistem Midtrans.');
            } else {
                return back()->with('info', 'Status Midtrans: ' . $status->transaction_status);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal cek status ke Midtrans: ' . $e->getMessage());
        }
    }

    /**
     * Fitur Cetak Struk/Invoice
     */
    public function cetakInvoice($id)
    {
        $user = Auth::user();
        
        $query = Transaksi::with(['lahan', 'bibit', 'petani'])->where('id', $id);

        // Jika bukan admin, pastikan hanya bisa cetak milik sendiri
        if ($user->role !== 'admin') {
            $petani = Petani::where('user_id', $user->id)->first();
            if (!$petani) abort(403);
            $query->where('petani_id', $petani->id);
        }

        $transaksi = $query->firstOrFail();

        if ($transaksi->status_pembayaran == 'ditolak' || $transaksi->status_pembayaran == 'kadaluarsa') {
            return back()->with('error', 'Transaksi ini tidak dapat dicetak karena status ' . $transaksi->status_pembayaran);
        }

        $petani = $transaksi->petani;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('petani.invoice', compact('transaksi', 'petani'));
        return $pdf->stream('Invoice-' . ($transaksi->order_id ?? $transaksi->id) . '.pdf');
    }

    /**
     * Cetak Struk Ala Kasir (Thermal)
     */
    
    public function cetakStruk($id)
    {
        // Get the transaction first
        $transaksi = Transaksi::with(['lahan', 'bibit', 'petani'])->where('id', $id)->firstOrFail();
        // Ensure we have the related petani; fallback to direct query if relationship missing
        $petani = $transaksi->petani ?? Petani::where('id', $transaksi->petani_id)->first();
        // If still null, abort with 404 for safety
        if (!$petani) abort(404, 'Petani not found for this transaction');
        return view('petani.struk', compact('transaksi', 'petani'));
    }


    /**
     * Tandai Semua Notifikasi Sudah Dibaca
     */
    public function bacaSemuaNotifikasi()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }

    /**
     * Menampilkan profil petani
     */
    public function index()
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        return view('petani.profil', compact('petani'));
    }

    /**
     * Memproses update profil
     */
    public function updateProfil(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        // Revisi: Jika profil sudah disetujui (disetujui), tidak boleh edit lagi.
        // Jika masih pending, boleh edit hanya jika NIK masih kosong (pendaftaran awal).
        if ($petani->status == 'disetujui') {
            return redirect()->route('petani.profil')->with('error', 'Profil Anda sudah disetujui oleh admin. Anda tidak dapat mengedit profil lagi.');
        }

        if ($petani->status == 'pending' && trim($petani->nik) !== '') {
            return redirect()->route('petani.profil')->with('error', 'Profil Anda sedang dalam proses verifikasi oleh pengurus. Mohon tunggu persetujuan admin.');
        }


        // Jika statusnya 'disetujui' ATAU baru pertama kali daftar (nik masih kosong ''), maka lolos dan BISA EDIT di bawah ini:
        $request->validate([
            'nik' => 'required|string|size:16',
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'required|string|max:15',
            'alamat' => 'required|string',
            'foto_ktp' => ($petani->foto_ktp ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png|max:2048',
            'foto_kk' => ($petani->foto_kk ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'nik.size' => 'NIK harus berjumlah tepat 16 digit.',
            'nik.required' => 'NIK wajib diisi.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'alamat.required' => 'Alamat rumah wajib diisi.',
            'foto_ktp.required' => 'Foto KTP wajib diunggah untuk verifikasi.',
            'foto_kk.required' => 'Foto Kartu Keluarga wajib diunggah untuk verifikasi.',
        ]);

        // Proses Upload Foto KTP
        if ($request->hasFile('foto_ktp')) {
            $fileKtp = $request->file('foto_ktp');
            $namaKtp = 'KTP_' . time() . '.' . $fileKtp->getClientOriginalExtension();
            $fileKtp->move(public_path('uploads/identitas'), $namaKtp);
            $petani->foto_ktp = $namaKtp;
        }

        // Proses Upload Foto KK
        if ($request->hasFile('foto_kk')) {
            $fileKk = $request->file('foto_kk');
            $namaKk = 'KK_' . time() . '.' . $fileKk->getClientOriginalExtension();
            $fileKk->move(public_path('uploads/identitas'), $namaKk);
            $petani->foto_kk = $namaKk;
        }

        // Simpan Data Profil Baru
        $petani->nik = $request->nik;
        $petani->nama_lengkap = $request->nama_lengkap;
        $petani->no_hp = $request->no_hp;
        $petani->alamat = $request->alamat;
        
        // 2. REVISI DOSEN: Setelah profil diedit, status otomatis turun kembali ke 'pending' 
        // agar admin memverifikasi ulang perubahan datanya.
        $petani->status = 'pending'; 

        $petani->save();

        // Notifikasi ke Admin bahwa ada data baru yang butuh divalidasi ulang
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SistemNotifikasi(
                'Pendaftaran/Pembaruan Profil Petani', 
                "Petani atas nama {$petani->nama_lengkap} telah memperbarui data profil/berkas identitasnya. Mohon segera cek dan lakukan validasi persetujuan.", 
                'info',
                url('/admin/data-petani'),
                $petani->user_id
            ));
        }

        return redirect()->route('petani.profil')->with('success', 'Profil berhasil disimpan! Harap tunggu verifikasi berkas oleh admin.');
    }

    /**
     * Membaca satu notifikasi dan mengarahkan ke URL tujuan
     */
    public function bacaDanArahkan($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            
            // Ambil URL dari data notifikasi, jika tidak ada default ke dashboard
            $url = $notification->data['url'] ?? '/dashboard-petani';
            return redirect($url);
        }

        return back();
    }
}