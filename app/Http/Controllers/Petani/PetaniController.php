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

        // 3. Bangun List Distribusi (Multiple Bibit)
        $listDistribusi = [];
        $isPenjualanAktif = false;
        
        foreach ($bibitsTerbuka as $bibit) {
            $tenggat = \Carbon\Carbon::parse($bibit->tanggal_buka)->addDays(7);
            $sisaHari = (int) now()->diffInDays($tenggat, false);
            
            if ($sisaHari < 0) {
                // Lewat 7 hari, skip dari daftar (otomatis ditutup)
                continue;
            }
            
            $isPenjualanAktif = true;

            // Perbaikan: Jika sisa hari adalah 0 tapi masih di bawah 24 jam, 
            // kita bisa tampilkan 1 atau biarkan saja, tapi pastikan ini INTERGER.
            if ($sisaHari == 0 && now()->lessThan($tenggat)) {
                $sisaHari = 1;
            }
            
            // Hitung Persentase & Jatah
            $totalLuasRef = $bibit->total_luas_snapshot > 0 ? $bibit->total_luas_snapshot : 1;
            $persen = ($totalLuas / $totalLuasRef) * 100;
            
            // VALIDASI JATAH (PERBAIKAN: Jatah = Proporsional + Transfer Masuk - Transfer Keluar)
            $hakProposional = ($totalLuas / $totalLuasRef) * $bibit->stok_awal;

            $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg') 
                               - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');
            
            $hakTotal = $hakProposional + $tambahanTransfer;

            $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                            ->where('bibit_id', $bibit->id)
                            ->where('status_pembayaran', 'sukses')
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
                ->where('status_pembayaran', 'sukses')
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
            $q->where('status_pembayaran', 'sukses')->with('bibit');
        }])->where('petani_id', $petani->id)->get();
        $totalLuas = $lahans->sum('luas_lahan');
        $jumlahLahan = $lahans->count();

        return view('petani.lahan', compact('petani', 'lahans', 'totalLuas', 'jumlahLahan'));
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

        // 2. Cek apakah ada stok bibit yang DIBUKA dan belum lewat 7 hari
        // Sync status dulu
        Bibit::where('is_buka', true)
             ->where('tanggal_buka', '<=', now()->subDays(7))
             ->update(['is_buka' => false]);

        $semuaBibit = Bibit::where('is_buka', true)->where('stok', '>', 0)->get(); 
        
        if ($semuaBibit->isEmpty()) {
            return redirect()->route('petani.dashboard')->with('error', 'Mohon maaf, saat ini sedang tidak ada distribusi/penjualan bibit yang aktif.');
        }

        $isJatahTerbuka = false; // Fitur Fase Terbuka sudah dihapus, selamanya false

        // PERBAIKAN: Tambahkan pengambilan data lahan agar tidak error di Blade (HANYA YANG DISETUJUI)
        $lahans = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->get();

        // Hitung total luas lahan petani ini (snapshot saat ini)
        $totalLuasPetani = $lahans->sum('luas_lahan');

        // Tambahkan info "sudah dibeli" untuk tiap bibit
        foreach ($semuaBibit as $b) {
            $b->sudah_dibeli = Transaksi::where('petani_id', $petani->id)
                ->where('bibit_id', $b->id)
                ->where('status_pembayaran', 'sukses')
                ->sum('jumlah_beli');
                
            // Hitung total hak maksimal petani ini untuk bibit tersebut
            $pembagi = $b->total_luas_snapshot > 0 ? $b->total_luas_snapshot : 1;
            $hakTotal = ($totalLuasPetani / $pembagi) * $b->stok_awal;
            
            // Tambahan transfer
            $tambahan = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $b->id)->sum('jumlah_kg') 
                       - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $b->id)->sum('jumlah_kg');
            
            $b->sisa_jatah_global = max(0, round($hakTotal + $tambahan - $b->sudah_dibeli, 1));
        }

        return view('petani.beli_bibit', compact('semuaBibit', 'petani', 'lahans', 'isJatahTerbuka'));
    }

    /**
     * Proses Pemesanan / Beli Bibit
     */
    public function prosesBeliBibit(Request $request)
    {
        $request->validate([
            'jumlah_beli' => 'required|numeric|min:0.1',
            'total_harga' => 'required|numeric',
            'metode_pembayaran' => 'required|in:midtrans,transfer_manual,tunai',
        ]);

        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        // 0. Safety Check
        $semuaBibit = Bibit::where('is_buka', true)->where('stok', '>', 0)->get();
        if ($semuaBibit->isEmpty()) {
            return back()->with('error', 'Transaksi ditolak. Distribusi penjualan bibit sedang tidak aktif.');
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

        if ($bibit->stok < $request->jumlah_beli) {
            return back()->with('error', 'Stok bibit tidak mencukupi!');
        }

        // VALIDASI JATAH (REVISI RASIONAL): Selamanya proporsional
        // Hitung kembali jatah hak di sisi server untuk keamanan
        $totalLuasRef = $bibit->total_luas_snapshot > 0 ? $bibit->total_luas_snapshot : 1;
        $userArea = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->sum('luas_lahan');
        
        $hakProposional = ($userArea / $totalLuasRef) * $bibit->stok_awal;
        
        // Jatah = Hak Proporsional + Transfer Masuk - Transfer Keluar
        $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg') 
                           - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');
        
        $hakTotal = round($hakProposional + $tambahanTransfer, 1);
        
        // Cek realisasi sukses sebelumnya
        $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                        ->where('bibit_id', $bibit->id)
                        ->where('status_pembayaran', 'sukses')
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
        // Tentukan status awal pembayaran
        $status_awal = 'pending';
        if ($request->metode_pembayaran == 'tunai') {
            $status_awal = 'menunggu_pembayaran';
        }

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

        if ($request->metode_pembayaran == 'tunai' || $request->metode_pembayaran == 'transfer_manual') {
            return redirect()->route('petani.bayar_bibit', $transaksi->id)->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran sesuai petunjuk.');
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
            $transaksi->status_pembayaran = 'sukses'; // Langsung lunas sesuai arahan dosen
            $transaksi->save();

            return back()->with('success', 'Bukti pembayaran berhasil diunggah. Transaksi Anda kini berstatus LUNAS.');
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
        
        $query = Transaksi::with(['lahan', 'bibit'])
                    ->where('petani_id', $petani->id)
                    ->where('status_pembayaran', '!=', 'batal');

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
                    ->where('status_pembayaran', '!=', 'batal')
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
        $user = Auth::user();
        $query = Transaksi::with(['lahan', 'bibit', 'petani'])->where('id', $id);

        if ($user->role !== 'admin') {
            $petani = Petani::where('user_id', $user->id)->first();
            if (!$petani) abort(403);
            $query->where('petani_id', $petani->id);
        }

        $transaksi = $query->firstOrFail();
        $petani = $transaksi->petani;

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
        $request->validate([
            'nik' => 'required|string|max:20',
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'required|string|max:15',
            'alamat' => 'required|string',
            'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'foto_kk' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();

        if ($request->hasFile('foto_ktp')) {
            $fileKtp = $request->file('foto_ktp');
            $namaKtp = 'KTP_' . time() . '.' . $fileKtp->getClientOriginalExtension();
            $fileKtp->move(public_path('uploads/identitas'), $namaKtp);
            $petani->foto_ktp = $namaKtp;
        }

        if ($request->hasFile('foto_kk')) {
            $fileKk = $request->file('foto_kk');
            $namaKk = 'KK_' . time() . '.' . $fileKk->getClientOriginalExtension();
            $fileKk->move(public_path('uploads/identitas'), $namaKk);
            $petani->foto_kk = $namaKk;
        }

        $petani->nik = $request->nik;
        $petani->nama_lengkap = $request->nama_lengkap;
        $petani->no_hp = $request->no_hp;
        $petani->alamat = $request->alamat;
        // Jangan ubah status ke pending agar tetap terverifikasi

        $petani->save();

        // Notifikasi ke Admin bahwa ada Profil Petani yang butuh divalidasi
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SistemNotifikasi(
                'Pembaruan Profil Petani', 
                "Petani atas nama {$petani->nama_lengkap} telah melengkapi data profil dan berkas identitasnya. Mohon segera cek dan validasi kebenarannya.", 
                'info',
                url('/admin/data-petani'),
                $petani->user_id
            ));
        }

        return redirect()->route('petani.profil')->with('success', 'Profil diperbarui!');
    }

    /**
     * Menampilkan Halaman Transfer Jatah (Fitur Pengalihan)
     */
    public function transferJatah(Request $request)
    {
        $petani = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        
        // Cek Keaktifan Penjualan Bibit
        $bibitsTerbuka = Bibit::where('is_buka', true)->where('stok', '>', 0)->get();
        if ($bibitsTerbuka->isEmpty()) {
            return redirect()->route('petani.dashboard')->with('error', 'Fitur transfer jatah hanya tersedia saat distribusi bibit aktif.');
        }

        // Ambil bibit_id dari request (jika ada pemilihan bibit)
        $selectedBibitId = $request->input('bibit_id');
        $sisaJatah = 0;
        $selectedBibit = null;

        if ($selectedBibitId) {
            $selectedBibit = Bibit::find($selectedBibitId);
            if ($selectedBibit && $selectedBibit->is_buka) {
                // Kalkulasi Sisa Jatah Pengirim untuk bibit yang dipilih
                $totalLuasRef = $selectedBibit->total_luas_snapshot > 0 ? $selectedBibit->total_luas_snapshot : 1;
                $userArea = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->sum('luas_lahan');
                
                $hakProposional = ($userArea / $totalLuasRef) * $selectedBibit->stok_awal;
                
                $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $selectedBibitId)->sum('jumlah_kg') 
                                   - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $selectedBibitId)->sum('jumlah_kg');
                
                $hakTotal = $hakProposional + $tambahanTransfer;
                
                $sudahDibeli = Transaksi::where('petani_id', $petani->id)
                                ->where('bibit_id', $selectedBibitId)
                                ->where('status_pembayaran', 'sukses')
                                ->sum('jumlah_beli');
                
                $sisaJatah = max(0, $hakTotal - $sudahDibeli);
            }
        }

        // Ambil Petani Lain (Target Transfer)
        $petaniLain = Petani::where('id', '!=', $petani->id)->where('status', 'disetujui')->get();

        // Riwayat Transfer Saya
        $riwayatTransfer = \App\Models\PindahJatah::with(['penerima', 'bibit'])
                        ->where('pengirim_id', $petani->id)
                        ->latest()
                        ->get();

        return view('petani.transfer_jatah', compact('petani', 'sisaJatah', 'petaniLain', 'riwayatTransfer', 'bibitsTerbuka', 'selectedBibit'));
    }

    /**
     * Memproses Transfer Jatah ke Petani Lain
     */
    public function prosesTransferJatah(Request $request)
    {
        $request->validate([
            'bibit_id' => 'required|exists:bibits,id',
            'penerima_id' => 'required|exists:petanis,id',
            'jumlah_kg' => 'required|numeric|min:0.1',
            'alasan' => 'nullable|string|max:255',
        ]);

        $pengirim = Petani::where('user_id', Auth::guard('petani')->id() ?? Auth::id())->first();
        $penerima = Petani::findOrFail($request->penerima_id);
        $bibit = Bibit::findOrFail($request->bibit_id);

        if ($pengirim->id == $penerima->id) {
            return back()->with('error', 'Anda tidak bisa mentransfer jatah ke diri sendiri.');
        }

        // Cek Keaktifan
        if (!$bibit->is_buka) {
            return back()->with('error', 'Distribusi untuk bibit ini sudah ditutup.');
        }

        // Cek Sisa Jatah Stok (Proporsional Dinamis)
        $totalLuasRef = $bibit->total_luas_snapshot > 0 ? $bibit->total_luas_snapshot : 1;
        $userArea = Lahan::where('petani_id', $pengirim->id)->where('status', 'disetujui')->sum('luas_lahan');
        
        $hakProposional = ($userArea / $totalLuasRef) * $bibit->stok_awal;
        
        $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $pengirim->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg') 
                           - \App\Models\PindahJatah::where('pengirim_id', $pengirim->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');
        
        $hakTotal = $hakProposional + $tambahanTransfer;
        
        $sudahDibeli = Transaksi::where('petani_id', $pengirim->id)
                        ->where('bibit_id', $bibit->id)
                        ->where('status_pembayaran', 'sukses')
                        ->sum('jumlah_beli');
        
        $sisaJatah = max(0, $hakTotal - $sudahDibeli);

        if ($sisaJatah < $request->jumlah_kg) {
            return back()->with('error', 'Sisa jatah Anda tidak mencukupi untuk ditransfer.');
        }

        \App\Models\PindahJatah::create([
            'bibit_id' => $bibit->id,
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'jumlah_kg' => $request->jumlah_kg,
            'alasan' => $request->alasan ?? 'Transfer Jatah Sukarela',
        ]);

        // Beri notifikasi ke penerima
        $penerima->user->notify(new \App\Notifications\SistemNotifikasi(
            'Anda Menerima Transfer Jatah!', 
            "Petani {$pengirim->nama_lengkap} telah mentransfer jatah bibit '{$bibit->nama_bibit}' sebesar {$request->jumlah_kg} Kg kepada Anda.", 
            'success',
            url('/dashboard-petani'),
            $pengirim->id
        ));

        // WhatsApp Notification to Receiver
        if (!empty($penerima->no_hp)) {
            $pesanWA = "📩 *TRANSFER JATAH BIBIT*\n\nHalo {$penerima->nama_lengkap},\nPetani *{$pengirim->nama_lengkap}* telah mentransfer jatah bibit *{$bibit->nama_bibit}* sebesar *{$request->jumlah_kg} Kg* kepada Anda.\n\nCek jatah Anda di aplikasi sekarang!";
            $this->sendWA($penerima->no_hp, $pesanWA);
        }

        return redirect()->route('petani.transfer_jatah')->with('success', "Berhasil mentransfer {$request->jumlah_kg} Kg jatah bibit kepada {$penerima->nama_lengkap}.");
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