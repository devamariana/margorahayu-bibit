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

    /**
     * Menampilkan halaman khusus pengelolaan banyak lahan (Revisi)
     */
    public function lahan()
    {
        $petani = Petani::where('user_id', Auth::id())->first();
        
        $lahans = Lahan::where('petani_id', $petani->id)->get();
        $totalLuas = $lahans->sum('luas_lahan');
        $jumlahLahan = $lahans->count();

        return view('petani.lahan', compact('petani', 'lahans', 'totalLuas', 'jumlahLahan'));
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

        Lahan::create([
            'petani_id' => $petani->id,
            'nama_blok' => $request->nama_blok,
            'luas_lahan' => $request->luas_lahan,
            'rencana_bibit' => $request->rencana_bibit,
            'jenis_tanah' => $request->jenis_tanah ?? '-',
        ]);

        return back()->with('success', 'Lahan baru berhasil ditambahkan!');
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
            'metode_pembayaran' => 'required|string',
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

        // Buat Transaksi (Status Pending)
        $transaksi = Transaksi::create([
            'petani_id' => $petani->id,
            'lahan_id' => $lahan->id,
            'bibit_id' => $bibit->id,
            'order_id' => $order_id,
            'jumlah_beli' => $request->jumlah_beli,
            'total_harga' => $request->total_harga,
            'metode_pembayaran' => $request->metode_pembayaran . ' (Midtrans)',
            'status_pembayaran' => 'pending' // Menunggu pembayaran
        ]);

        // =====================================
        // KONFIGURASI MIDTRANS
        // =====================================
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false); 
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $request->total_harga,
            ],
            'customer_details' => [
                'first_name' => collect(explode(' ', $petani->nama_lengkap))->first(),
                'last_name' => collect(explode(' ', $petani->nama_lengkap))->slice(1)->implode(' '),
                'phone' => str_replace('+', '', $petani->no_hp),
            ],
            'item_details' => [
                [
                    'id' => $bibit->id,
                    'price' => $bibit->harga_subsidi,
                    'quantity' => $request->jumlah_beli,
                    'name' => $bibit->nama_bibit
                ]
            ]
        ];

        // Filter Metode Pembayaran
        if ($request->metode_pembayaran == 'Virtual Account') {
            $params['enabled_payments'] = [
                'bca_va', 'bni_va', 'bri_va', 'echannel', 'permata_va', 'other_va'
            ];
        } elseif ($request->metode_pembayaran == 'QRIS') {
            $params['enabled_payments'] = [
                'qris', 'gopay', 'shopeepay'
            ];
        }

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            $transaksi->snap_token = $snapToken;
            $transaksi->save();

            return redirect()->route('petani.bayar_bibit', $transaksi->id);

        } catch (\Exception $e) {
            // Jika gagal buat tiket midtrans, kembalikan stok
            $bibit->stok += $request->jumlah_beli;
            $bibit->save();
            $transaksi->delete();

            return back()->with('error', 'Gagal memanggil Gateway Pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Pembayaran Midtrans Eksekusi
     */
    public function bayarBibit($id)
    {
        $petani = Petani::where('user_id', Auth::id())->first();
        $transaksi = Transaksi::where('id', $id)->where('petani_id', $petani->id)->firstOrFail();

        // Jika sudah sukses, arahkan ke riwayat
        if ($transaksi->status_pembayaran == 'sukses') {
            return redirect()->route('petani.riwayat')->with('success', 'Transaksi ini sudah selesai.');
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

        // Hanya bisa dibatalkan jika statusnya masih pending
        if ($transaksi->status_pembayaran == 'pending') {
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
        if ($transaksi->status_pembayaran == 'pending') {
            $transaksi->status_pembayaran = 'sukses';
            $transaksi->save();
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

        return redirect()->route('petani.profil')->with('success', 'Profil diperbarui!');
    }
}