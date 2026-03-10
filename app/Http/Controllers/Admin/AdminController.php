<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; 
use App\Models\Petani; 
use App\Models\Bibit; 
use App\Models\PindahJatah; // Tambahkan ini agar pemanggilan model lebih simpel
use App\Models\Lahan;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

class AdminController extends Controller
{
    /**
     * Menampilkan Dashboard Utama Admin dengan data asli
     */
    public function index()
    {
        // 1. Menghitung total semua petani di tabel petanis
        $totalPetani = Petani::count();

        // 2. Menghitung petani yang statusnya masih 'pending' (butuh verifikasi)
        $totalPending = Petani::where('status', 'pending')->count();

        // 3. Menghitung total stok bibit dari semua jenis bibit yang ada
        $totalStok = Bibit::sum('stok');

        // 4. Mengambil 5 data petani terbaru untuk ditampilkan di tabel dashboard
        $petaniTerbaru = Petani::with('user')->latest()->take(5)->get();

        // 5. Data Chart Pembayaran
        $totalLunas = \App\Models\Transaksi::where('status_pembayaran', 'sukses')->count();
        $totalPendingTx = \App\Models\Transaksi::where('status_pembayaran', 'pending')->count();

        // 6. Data Chart Penjualan (6 bulan terakhir)
        $chartLabels = [];
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = \Carbon\Carbon::today()->subMonths($i);
            $chartLabels[] = $month->translatedFormat('M'); // atau format('M')
            $chartData[] = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('jumlah_beli');
        }

        return view('layouts.admin.dashboard', compact(
            'totalPetani', 
            'totalPending', 
            'totalStok', 
            'petaniTerbaru',
            'totalLunas',
            'totalPendingTx',
            'chartLabels',
            'chartData'
        ));
    }

    /**
     * Menampilkan daftar semua petani untuk Admin
     */
    public function dataPetani()
    {
        $petanis = Petani::with('user')
                        ->orderByRaw("FIELD(status, 'pending', 'disetujui', 'ditolak')")
                        ->get();

        return view('layouts.admin.data_petani', compact('petanis'));
    }

    /**
     * Memproses verifikasi status petani (Disetujui/Ditolak)
     */
    public function verifikasiPetani(Request $request, $id)
    {
        $petani = Petani::find($id);

        if (!$petani) {
            return back()->with('error', 'Data petani tidak ditemukan.');
        }

        $petani->status = $request->status;
        $petani->save();

        return back()->with('success', 'Status petani ' . $petani->nama_lengkap . ' berhasil diperbarui menjadi ' . $petani->status);
    }

    /**
     * Menghapus Petani (Menghapus di 2 tabel sekaligus secara aman)
     */
    public function hapusPetani($id)
    {
        DB::beginTransaction();
        try {
            $petani = Petani::find($id);
            
            if ($petani) {
                User::where('id', $petani->user_id)->delete();
                $petani->delete();

                DB::commit();
                return back()->with('success', 'Data petani dan akun berhasil dihapus!');
            }

            return back()->with('error', 'Data tidak ditemukan.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan Halaman Fitur Pindah Jatah (Revisi Dosen)
     */
    public function pindahJatah()
    {
        // Hanya ambil petani yang sudah terverifikasi (disetujui)
        $petanis = Petani::where('status', 'disetujui')->get();
        $riwayatPindah = PindahJatah::with(['pengirim', 'penerima'])->latest()->get();
        
        return view('layouts.admin.pindah_jatah', compact('petanis', 'riwayatPindah'));
    }

    /**
     * Memproses Perpindahan Jatah Antar Petani
     */
    public function prosesPindahJatah(Request $request)
    {
        $request->validate([
            'pengirim_id' => 'required',
            'penerima_id' => 'required|different:pengirim_id',
            'jumlah_kg' => 'required|numeric|min:1',
        ]);

        $pengirim = Petani::findOrFail($request->pengirim_id);
        $penerima = Petani::findOrFail($request->penerima_id);

        // Logika Hitung Sisa Jatah Pengirim (Lahan Disetujui + Tambahan)
        $lahansDisetujui = \App\Models\Lahan::where('petani_id', $pengirim->id)
                                          ->where('status', 'disetujui')
                                          ->get();
        $totalLuasLahan = $lahansDisetujui->sum('luas_lahan');
                                          
        $jatahLahan = ($totalLuasLahan / 100) * 10;
        $totalSisaJatah = $jatahLahan + ($pengirim->jatah_tambahan ?? 0);

        if ($totalSisaJatah < $request->jumlah_kg) {
            return back()->with('error', 'Jatah pengirim tidak mencukupi untuk dipindahkan!');
        }

        // Jalankan Transaction agar jika satu gagal, semua batal (Database Integrity)
        DB::transaction(function () use ($pengirim, $penerima, $request) {
            // 1. Kurangi jatah pengirim
            $pengirim->decrement('jatah_tambahan', $request->jumlah_kg);
            
            // 2. Tambah jatah penerima
            $penerima->increment('jatah_tambahan', $request->jumlah_kg);

            // 3. Catat Riwayat/Log
            PindahJatah::create([
                'pengirim_id' => $request->pengirim_id,
                'penerima_id' => $request->penerima_id,
                'jumlah_kg' => $request->jumlah_kg,
            ]);
        });

        return back()->with('success', 'Jatah sebesar ' . $request->jumlah_kg . ' kg berhasil dipindahkan!');
    }

    /**
     * Menampilkan semua data riwayat dari tabel transaksis
     */
    public function riwayatTransaksi()
    {
        $transaksis = Transaksi::with(['petani', 'bibit', 'lahan'])->latest()->get();
        return view('layouts.admin.riwayat_transaksi', compact('transaksis'));
    }

    /**
     * Menampilkan semua data lahan petani
     */
    public function dataLahan()
    {
        $lahans = Lahan::with('petani')->latest()->get();
        return view('layouts.admin.data_lahan', compact('lahans'));
    }

    /**
     * Verifikasi Status Lahan
     */
    public function verifikasiLahan(Request $request, $id)
    {
        $lahan = Lahan::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,disetujui,ditolak'
        ]);

        $lahan->status = $request->status;
        $lahan->save();

        return back()->with('success', 'Status Lahan berhasil diperbarui menjadi ' . ucfirst($request->status));
    }
}