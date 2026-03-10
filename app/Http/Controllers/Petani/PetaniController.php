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

class PetaniController extends Controller
{
    /**
     * Menampilkan Dashboard dengan Notifikasi Bibit Terbaru dan Jatah Bibit
     */
    public function dashboard()
    {
        $bibitTerbaru = Bibit::latest()->first();
        $petani = Petani::where('user_id', Auth::id())->first();

        if (!$petani) {
            return redirect()->route('login');
        }

        // Ambil semua lahan milik petani ini yang SUDAH DISETUJUI untuk menghitung total jatah
        $lahans = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->get();
        $totalLuas = $lahans->sum('luas_lahan');

        // Hitung Jatah Bibit (Berdasarkan akumulasi semua lahan)
        $jatahBibit = 0;
        if ($petani->status == 'disetujui') {
            $jatahDasar = ($totalLuas / 100) * 10;
            $jatahBibit = $jatahDasar + ($petani->jatah_tambahan ?? 0);
        }

        // AMBIL DATA RIWAYAT ASLI
        $riwayat = Transaksi::where('petani_id', $petani->id)
                    ->latest()
                    ->take(3)
                    ->get();

        return view('petani.dashboard', compact('bibitTerbaru', 'petani', 'jatahBibit', 'riwayat', 'totalLuas'));
    }

    public function lahan()
    {
        $petani = Petani::where('user_id', Auth::id())->first();
        
        $lahans = Lahan::where('petani_id', $petani->id)->get();
        $totalLuas = $lahans->sum('luas_lahan');
        $jumlahLahan = $lahans->count();
        // Ambil dari seluruh data master bibit karena akses sudah dibebaskan (dengan jenis untuk pengelompokan)
        $rencanaBibits = \App\Models\Bibit::select('nama_bibit', 'jenis')
                            ->distinct()
                            ->orderBy('jenis')
                            ->orderBy('nama_bibit')
                            ->get();

        return view('petani.lahan', compact('petani', 'lahans', 'totalLuas', 'jumlahLahan', 'rencanaBibits'));
    }

    /**
     * Menyimpan data lahan baru
     */
    public function storeLahan(Request $request)
    {
        $request->validate([
            'nama_blok' => 'required|string|max:255',
            'luas_lahan' => 'required|numeric|min:1',
            'rencana_bibit' => 'required|string',
        ]);

        $petani = Petani::where('user_id', Auth::id())->first();

        $lahan = Lahan::create([
            'petani_id' => $petani->id,
            'nama_blok' => $request->nama_blok,
            'luas_lahan' => $request->luas_lahan,
            'rencana_bibit' => $request->rencana_bibit,
            'jenis_tanah' => $request->jenis_tanah ?? '-',
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
        $petani = Petani::where('user_id', Auth::id())->first();
        $lahan = Lahan::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();
        
        $lahan->delete();

        return back()->with('success', 'Data lahan berhasil dihapus.');
    }

    /**
     * Menampilkan halaman Informasi & Pembelian Bibit
     */
    public function beliBibit()
    {
        $petani = Petani::where('user_id', Auth::id())->first();

        // Cek Status Verifikasi
        if ($petani->status !== 'disetujui') {
            return redirect()->route('petani.dashboard')->with('error', 'Akun Anda belum diverifikasi oleh Admin. Anda belum bisa melakukan pembelian bibit.');
        }

        $semuaBibit = Bibit::all(); 
        
        // PERBAIKAN: Tambahkan pengambilan data lahan agar tidak error di Blade (HANYA YANG DISETUJUI)
        $lahans = Lahan::where('petani_id', $petani->id)->where('status', 'disetujui')->get();
        
        return view('petani.beli_bibit', compact('semuaBibit', 'petani', 'lahans'));
    }

    /**
     * Proses Pemesanan / Beli Bibit
     */
    public function prosesBeliBibit(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|exists:lahans,id',
            'bibit_id' => 'required|exists:bibits,id',
            'jumlah_beli' => 'required|numeric|min:1',
            'total_harga' => 'required|numeric',
        ]);

        $petani = Petani::where('user_id', Auth::id())->first();

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

        // Kurangi Stok Bibit
        $bibit->stok -= $request->jumlah_beli;
        $bibit->save();

        // Buat ID Transaksi Khusus
        $order_id = 'TRX-' . time() . '-' . $petani->id;

        // Buat Transaksi (Status Menunggu Persetujuan Admin)
        $transaksi = Transaksi::create([
            'petani_id' => $petani->id,
            'lahan_id' => $lahan->id,
            'bibit_id' => $bibit->id,
            'order_id' => $order_id,
            'jumlah_beli' => $request->jumlah_beli,
            'total_harga' => $request->total_harga,
            'metode_pembayaran' => '-', // Belum pilih metode
            'status_pembayaran' => 'menunggu_persetujuan'
        ]);

        // Notif Admin Ada Permintaan Beli
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SistemNotifikasi(
                'Permintaan Pembelian Bibit', 
                "Petani {$petani->nama_lengkap} meminta {$request->jumlah_beli} qty bibit '{$bibit->nama_bibit}'. Mohon dicek.", 
                'bibit',
                url('/admin/riwayat-transaksi'),
                $transaksi->id
            ));
        }

        return redirect()->route('petani.riwayat')->with('success', 'Permintaan pembelian bibit berhasil dikirim. Silakan tunggu persetujuan dari Admin sebelum melakukan pembayaran.');
    }

    /**
     * Munculkan Midtrans Snap sesudah Admin acc dan Petani pilih bayar
     */
    public function bayarBibit($id)
    {
        $petani = Petani::where('user_id', Auth::id())->first();
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
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false); 
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $transaksi->order_id,
                    'gross_amount' => $transaksi->total_harga,
                ],
                'customer_details' => [
                    'first_name' => collect(explode(' ', $petani->nama_lengkap))->first(),
                    'last_name' => collect(explode(' ', $petani->nama_lengkap))->slice(1)->implode(' '),
                    'phone' => str_replace('+', '', $petani->no_hp),
                    'email' => 'petani_' . $petani->id . '@example.com', // Added to prevent Midtrans reject anomalies
                ],
                'item_details' => [
                    [
                        'id' => $transaksi->bibit->id,
                        'price' => $transaksi->bibit->harga_subsidi,
                        'quantity' => $transaksi->jumlah_beli,
                        'name' => $transaksi->bibit->nama_bibit
                    ]
                ]
            ];

            try {
                $snapToken = \Midtrans\Snap::getSnapToken($params);
                $transaksi->snap_token = $snapToken;
                $transaksi->save();
            } catch (\Exception $e) {
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
        $petani = Petani::where('user_id', Auth::id())->first();
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
     * Callback Setelah Selesai Bayar di Midtrans (Hanya Update Status)
     */
    public function suksesBayarBibit($id)
    {
        $petani = Petani::where('user_id', Auth::id())->first();
        $transaksi = Transaksi::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();

        // Di sini skenarionya sederhana: user klik 'close' dari snap lalu kita asumsikan sukses (Bisa diganti Webhook ke depannya)
        if ($transaksi->status_pembayaran == 'menunggu_pembayaran' || $transaksi->status_pembayaran == 'pending') {
            $transaksi->status_pembayaran = 'sukses';
            $transaksi->save();

            // Beritahu Admin bahwa pembayaran telah lunas
            $admins = \App\Models\User::where('role', 'admin')->get();
            $bibitNama = $transaksi->bibit->nama_bibit ?? 'Bibit';
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SistemNotifikasi(
                    'Pembayaran Lunas! ✅', 
                    "Petani {$petani->nama_lengkap} telah melunasi pembayaran untuk bibit '{$bibitNama}' sebesar Rp " . number_format($transaksi->total_harga, 0, ',', '.') . ".", 
                    'success',
                    url('/admin/riwayat-transaksi'),
                    $transaksi->id
                ));
            }
        }

        return redirect()->route('petani.riwayat')->with('success', 'Pembelian bibit berhasil! Silahkan tunggu pengiriman.');
    }

    /**
     * Menampilkan halaman Riwayat Pembelian (Fungsi Baru)
     */
    public function riwayat()
    {
        $petani = Petani::where('user_id', Auth::id())->first();
        
        // Ambil riwayat dengan relasi lahan dan bibit (kecuali yang dibatalkan)
        $riwayat = Transaksi::with(['lahan', 'bibit'])
                    ->where('petani_id', $petani->id)
                    ->where('status_pembayaran', '!=', 'batal')
                    ->latest()
                    ->get();

        return view('petani.riwayat', compact('riwayat'));
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
        $petani = Petani::where('user_id', Auth::id())->first();
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
            'foto_ktp' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'foto_kk' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $petani = Petani::where('user_id', Auth::id())->first();

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
        $petani->alamat = $request->alamat;
        $petani->status = 'pending'; 

        $petani->save();

        // Notifikasi ke Admin bahwa ada Profil Petani yang butuh divalidasi
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\SistemNotifikasi(
                'Pembaruan Profil Petani', 
                "Petani atas nama {$petani->nama_lengkap} telah melengkapi data profil dan berkas identitasnya. Mohon segera cek dan validasi kebenarannya.", 
                'info',
                url('/admin/data-petani'),
                $user->id
            ));
        }

        return redirect()->route('petani.profil')->with('success', 'Profil diperbarui!');
    }

    /**
     * Tandai Satu Notifikasi Dibaca dan Redirect
     */
    public function bacaDanArahkan($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if ($url) {
            return redirect($url);
        }

        return back();
    }
}