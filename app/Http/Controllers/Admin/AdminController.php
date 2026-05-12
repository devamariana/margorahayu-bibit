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
use Illuminate\Support\Facades\Auth;
use App\Notifications\SistemNotifikasi;

use App\Traits\WhatsappNotifier;

class AdminController extends Controller
{
    use WhatsappNotifier;
    /**
     * Menampilkan Dashboard Utama Admin dengan data asli
     */
    public function index()
    {
        // SYNC STATUS BIBIT: Jika sudah lewat 7 hari, pastikan ditutup di DB
        \App\Models\Bibit::where('is_buka', true)
             ->where('tanggal_buka', '<=', now()->subDays(7))
             ->update(['is_buka' => false]);

        // 1. Menghitung total semua petani di tabel petanis
        $totalPetani = Petani::count();

        // 2. Menghitung petani yang statusnya masih 'pending' (butuh verifikasi)
        $totalPending = Petani::where('status', 'pending')->count();

        // 3. Menghitung total stok bibit yang sedang AKTIF didistribusikan
        $totalStok = Bibit::where('is_buka', true)->sum('stok');

        // 4. Mengambil 5 data petani terbaru untuk ditampilkan di tabel dashboard
        $petaniTerbaru = Petani::with('user')->latest()->take(5)->get();

        // 5. Data Chart Pembayaran
        $totalLunas = \App\Models\Transaksi::where('status_pembayaran', 'sukses')->count();
        $totalPendingTx = \App\Models\Transaksi::whereIn('status_pembayaran', ['pending', 'menunggu_pembayaran'])->count();

        // 6. Statistik Periode Aktif
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        $danaPeriodeAktif = 0;
        $bibitPeriodeAktif = 0;
        if ($periodeAktif) {
            $danaPeriodeAktif = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereHas('bibit', function($q) use ($periodeAktif) {
                    $q->where('periode_id', $periodeAktif->id);
                })
                ->sum('total_harga');
            $bibitPeriodeAktif = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereHas('bibit', function($q) use ($periodeAktif) {
                    $q->where('periode_id', $periodeAktif->id);
                })
                ->sum('jumlah_beli');
        }

        // 7. Data Chart Penjualan (6 bulan terakhir)
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
            'chartData',
            'periodeAktif',
            'danaPeriodeAktif',
            'bibitPeriodeAktif'
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
        // 1. Cari Petani
        $petani = Petani::find($id);
        if (!$petani) {
            return back()->with('error', 'Data petani dengan ID ' . $id . ' tidak ditemukan.');
        }

        // 2. Update Status Langsung
        $petani->status = $request->status;
        $petani->save();

        // 3. Proses Notifikasi (Optional, bungkus agar tidak menggagalkan update utama)
        try {
            // Tandai notifikasi dibaca
            Auth::user()->unreadNotifications()
                ->where('data->id_terkait', (string)$petani->user_id)
                ->get()->markAsRead();

            // Notifikasi Web
            if ($petani->user) {
                $petani->user->notify(new SistemNotifikasi(
                    'Akun Diverifikasi', 
                    'Akun Anda telah berhasil diverifikasi oleh Admin.', 
                    'success',
                    url('/dashboard-petani'),
                    $petani->id
                ));
            }

            // Notifikasi WA
            if (!empty($petani->no_hp)) {
                $this->sendWA($petani->no_hp, "✅ *AKUN DIVERIFIKASI*\n\nHalo {$petani->nama_lengkap}, akun Anda telah berhasil diverifikasi oleh Admin.");
            }
        } catch (\Exception $e) {
            \Log::error("Gagal mengirim notifikasi verifikasi: " . $e->getMessage());
        }

        return back()->with('success', 'Petani ' . $petani->nama_lengkap . ' BERHASIL diverifikasi!');
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
     * Menampilkan Halaman Fitur Pengalihan Jatah (Revisi Dosen)
     */
    public function pindahJatah()
    {
        // Hanya ambil petani yang sudah terverifikasi (disetujui)
        $petanis = Petani::where('status', 'disetujui')->get();
        // Ambil riwayat dengan relasi bibit
        $riwayatPindah = PindahJatah::with(['pengirim', 'penerima', 'bibit'])->latest()->get();
        // Ambil bibit yang sedang aktif
        $bibitsAktif = Bibit::where('is_buka', true)->get();
        
        return view('layouts.admin.transfer_jatah', compact('petanis', 'riwayatPindah', 'bibitsAktif'));
    }

    /**
     * Memproses Pengalihan Jatah Antar Petani
     */
    public function prosesPindahJatah(Request $request)
    {
        $request->validate([
            'bibit_id' => 'required|exists:bibits,id',
            'pengirim_id' => 'required|exists:petanis,id',
            'penerima_id' => 'required|different:pengirim_id|exists:petanis,id',
            'jumlah_kg' => 'required|numeric|min:0.1',
        ]);

        $pengirim = Petani::findOrFail($request->pengirim_id);
        $penerima = Petani::findOrFail($request->penerima_id);
        $bibit = Bibit::findOrFail($request->bibit_id);

        if (!$bibit->is_buka) {
            return back()->with('error', 'Distribusi untuk bibit ini sudah ditutup.');
        }

        // Logika Hitung Sisa Jatah Pengirim (Proporsional Dinamis)
        $totalLuasRef = $bibit->total_luas_snapshot > 0 ? $bibit->total_luas_snapshot : 1;
        $userArea = Lahan::where('petani_id', $pengirim->id)->where('status', 'disetujui')->sum('luas_lahan');
        
        $hakProposional = ($userArea / $totalLuasRef) * $bibit->stok_awal;
        
        // Jatah = Hak Proporsional + Transfer Masuk - Transfer Keluar
        $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $pengirim->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg') 
                           - \App\Models\PindahJatah::where('pengirim_id', $pengirim->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');
        
        $hakTotal = $hakProposional + $tambahanTransfer;
        
        $sudahDibeli = Transaksi::where('petani_id', $pengirim->id)
                        ->where('bibit_id', $bibit->id)
                        ->where('status_pembayaran', 'sukses')
                        ->sum('jumlah_beli');
        
        $sisaJatah = max(0, $hakTotal - $sudahDibeli);

        if ($sisaJatah < $request->jumlah_kg) {
            return back()->with('error', "Jatah pengirim untuk {$bibit->nama_bibit} tidak mencukupi (Sisa: {$sisaJatah} Kg)!");
        }

        // Jalankan Transaction agar jika satu gagal, semua batal (Database Integrity)
        DB::transaction(function () use ($pengirim, $penerima, $bibit, $request) {
            // Catat Riwayat/Log (Tanpa increment/decrement manual di tabel Petani, karena sekarang dihitung dinamis)
            PindahJatah::create([
                'bibit_id' => $bibit->id,
                'pengirim_id' => $request->pengirim_id,
                'penerima_id' => $request->penerima_id,
                'jumlah_kg' => $request->jumlah_kg,
                'alasan' => 'Pengalihan oleh Admin'
            ]);

            // Beri notifikasi ke penerima
            $penerima->user->notify(new SistemNotifikasi(
                'Jatah Anda Ditambah Admin!', 
                "Admin telah mengalihkan jatah bibit '{$bibit->nama_bibit}' sebesar {$request->jumlah_kg} Kg kepada Anda.", 
                'success',
                url('/dashboard-petani'),
                $pengirim->id
            ));

            // WhatsApp Notification to Receiver
            if (!empty($penerima->no_hp)) {
                $pesanWA = "📩 *TRANSFER JATAH BIBIT*\n\nHalo {$penerima->nama_lengkap},\nAdmin telah mengalihkan jatah bibit *{$bibit->nama_bibit}* sebesar *{$request->jumlah_kg} Kg* kepada Anda.\n\nCek jatah Anda di aplikasi sekarang!";
                $this->sendWA($penerima->no_hp, $pesanWA);
            }
        });

        return back()->with('success', "Jatah {$bibit->nama_bibit} sebesar {$request->jumlah_kg} kg berhasil dialihkan!");
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
        $lahans = Lahan::with(['petani', 'transaksi.bibit'])->latest()->get();
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

        // Tandai notifikasi terkait lahan ini sebagai dibaca (untuk admin)
        Auth::user()->unreadNotifications()
            ->where('data->id_terkait', $lahan->id)
            ->get()->markAsRead();

        // Kirim Notifikasi ke User terkait lahan
        $petani = $lahan->petani;
        if ($petani && $petani->user) {
            $pesan = $request->status == 'disetujui' 
                ? "Data Lahan Anda berlokasi di {$lahan->nama_blok} telah disetujui." 
                : "Pengajuan Data Lahan Anda di {$lahan->nama_blok} ditolak Admin.";
            $petani->user->notify(new SistemNotifikasi(
                'Status Data Lahan', 
                $pesan, 
                $request->status == 'disetujui' ? 'success' : 'warning',
                url('/petani/lahan'),
                $lahan->id
            ));
        }

        // WhatsApp Notification
        if ($petani && !empty($petani->no_hp)) {
            $pesanWA = $request->status == 'disetujui' 
                ? "✅ *LAHAN DISETUJUI*\n\nHalo {$petani->nama_lengkap},\nData Lahan Anda di *{$lahan->nama_blok}* dengan luas {$lahan->luas_lahan} m2 telah disetujui Admin." 
                : "❌ *LAHAN DITOLAK*\n\nHalo {$petani->nama_lengkap},\nMohon maaf, pengajuan data lahan Anda di *{$lahan->nama_blok}* ditolak Admin.";
            $this->sendWA($petani->no_hp, $pesanWA);
        }

        return back()->with('success', 'Status Lahan berhasil diperbarui menjadi ' . ucfirst($request->status));
    }

    /**
     * Memproses Verifikasi Pesanan Bibit (Menunggu Persetujuan)
     */
    public function verifikasiTransaksi(Request $request, $id)
    {
        $request->validate([
            'status_pembayaran' => 'required|in:sukses,ditolak,menunggu_pembayaran'
        ]);

        $transaksi = Transaksi::findOrFail($id);

        // Jika disetujui (Sukses)
        if ($request->status_pembayaran == 'sukses') {
            $transaksi->status_pembayaran = 'sukses';
            $transaksi->save();

            // Beri Notifikasi ke User
            $petani = $transaksi->petani;
            if ($petani && $petani->user) {
                $bibitNama = $transaksi->bibit->nama_bibit ?? 'Bibit';
                $petani->user->notify(new SistemNotifikasi(
                    'Pembayaran Berhasil!', 
                    "Pembayaran {$bibitNama} Anda telah diverifikasi oleh Admin. Silakan ambil bibit di lokasi.", 
                    'success',
                    url('/riwayat-pembelian'),
                    $transaksi->id
                ));

                // WA Notif
                if (!empty($petani->no_hp)) {
                    $pesanWA = "✅ *PEMBAYARAN DIVERIFIKASI*\n\nHalo {$petani->nama_lengkap},\nPembayaran pesanan *{$transaksi->order_id}* telah diverifikasi Admin.\n\nDetail:\n- Bibit: {$bibitNama}\n- Jumlah: {$transaksi->jumlah_beli} Kg\n\nSilakan ambil bibit Anda di lokasi Kelompok Tani.";
                    $this->sendWA($petani->no_hp, $pesanWA);
                }
            }

            return back()->with('success', 'Transaksi berhasil diverifikasi sebagai LUNAS.');
        }

        // Jika Ditolak
        if ($request->status_pembayaran == 'ditolak') {
            $transaksi->status_pembayaran = 'ditolak';
            $transaksi->catatan_admin = $request->catatan ?? 'Bukti pembayaran tidak valid.';
            
            // Kembalikan stok bibit
            $bibit = $transaksi->bibit;
            $bibit->stok += $transaksi->jumlah_beli;
            $bibit->save();
            
            $transaksi->save();

            // Notif Petani
            $petani = $transaksi->petani;
            if ($petani && $petani->user) {
                $petani->user->notify(new SistemNotifikasi(
                    'Pembayaran Ditolak', 
                    "Maaf, bukti pembayaran untuk pesanan {$transaksi->order_id} ditolak Admin. Alasan: {$transaksi->catatan_admin}", 
                    'warning',
                    url('/riwayat-pembelian'),
                    $transaksi->id
                ));
            }

            return back()->with('warning', 'Transaksi telah ditolak dan stok dikembalikan.');
        }

        return back()->with('error', 'Aksi tidak valid.');
    }

    /**
     * Menampilkan Halaman Rekap Laporan dengan Statistik Ringkas
     */
    public function halamanLaporan(Request $request)
    {
        $periodeId = $request->input('periode_id');
        $periodeTerpilih = null;

        $query = Transaksi::where('status_pembayaran', 'sukses');

        if ($periodeId) {
            $periodeTerpilih = \App\Models\Periode::find($periodeId);
            if ($periodeTerpilih) {
                $query->whereHas('bibit', function($q) use ($periodeId) {
                    $q->where('periode_id', $periodeId);
                });
            }
        }

        // Statistik Laporan
        $totalDanaMasuk = (clone $query)->sum('total_harga');
        $totalBibitKeluar = (clone $query)->sum('jumlah_beli');
        $totalTransaksiSelesai = (clone $query)->count();
        
        // Ambil data bibit paling laku
        $terlaris = DB::table('transaksis')
                    ->join('bibits', 'transaksis.bibit_id', '=', 'bibits.id')
                    ->select('bibits.nama_bibit', DB::raw('SUM(transaksis.jumlah_beli) as total_kg'))
                    ->where('transaksis.status_pembayaran', 'sukses')
                    ->when($periodeId, function($q, $periodeId) {
                        return $q->where('bibits.periode_id', $periodeId);
                    })
                    ->groupBy('bibits.nama_bibit')
                    ->orderByDesc('total_kg')
                    ->limit(5)
                    ->get();

        $periodes = \App\Models\Periode::orderBy('tahun', 'desc')->get();

        return view('layouts.admin.laporan', compact(
            'totalDanaMasuk', 
            'totalBibitKeluar', 
            'totalTransaksiSelesai', 
            'terlaris', 
            'periodes', 
            'periodeTerpilih'
        ));
    }

    /**
     * Memproses Export Data ke Excel (FastExcel)
     */
    public function exportExcel(Request $request)
    {
        $periodeId = $request->input('periode_id');
        $query = Transaksi::with(['petani', 'bibit', 'lahan'])
                        ->where('status_pembayaran', 'sukses');

        if ($periodeId) {
            $periode = \App\Models\Periode::find($periodeId);
            if ($periode) {
                $query->whereHas('bibit', function($q) use ($periodeId) {
                    $q->where('periode_id', $periodeId);
                });
            }
        }

        $transaksis = $query->latest()->get();

        $data = $transaksis->map(function ($t) {
            return [
                'ID Transaksi' => $t->order_id ?? $t->id,
                'Tanggal' => $t->created_at->format('d-m-Y'),
                'Nama Petani' => $t->petani->nama_lengkap ?? '-',
                'Jenis Bibit' => $t->bibit->nama_bibit ?? '-',
                'Jumlah (Kg)' => $t->jumlah_beli,
                'Total Bayar' => $t->total_harga,
                'Lahan' => $t->lahan->nama_blok ?? '-',
                'Status' => 'LUNAS'
            ];
        });

        $filename = 'Laporan-Margo-Rahayu-';
        $filename .= $periodeId ? 'Periode-'.$periodeId.'-' : '';
        $filename .= date('Y-m-d').'.xlsx';

        return (new \Rap2hpoutre\FastExcel\FastExcel($data))->download($filename);
    }

    /**
     * Memproses Export Laporan Data Penjualan ke PDF (DomPDF)
     */
    public function exportPdf(Request $request)
    {
        $periodeId = $request->input('periode_id');
        $query = Transaksi::with(['petani', 'bibit', 'lahan'])
                        ->where('status_pembayaran', 'sukses');

        $periodeTerpilih = null;
        if ($periodeId) {
            $periodeTerpilih = \App\Models\Periode::find($periodeId);
            if ($periodeTerpilih) {
                $query->whereHas('bibit', function($q) use ($periodeId) {
                    $q->where('periode_id', $periodeId);
                });
            }
        }

        $transaksis = $query->latest()->get();
        $danaMasuk = $transaksis->sum('total_harga');
        $totalBibit = $transaksis->sum('jumlah_beli');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('layouts.admin.laporan_pdf', compact('transaksis', 'danaMasuk', 'totalBibit', 'periodeTerpilih'));
        
        $pdf->setPaper('a4', 'landscape');
        
        $filename = 'Laporan-Margo-Rahayu-';
        $filename .= $periodeId ? 'Periode-'.$periodeId.'-' : '';
        $filename .= date('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Memproses Export Laporan Data Petani ke PDF (DomPDF)
     */
    public function cetakPetaniPdf()
    {
        $petanis = Petani::with('user')
                        ->orderByRaw("FIELD(status, 'pending', 'disetujui', 'ditolak')")
                        ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('layouts.admin.laporan_petani_pdf', compact('petanis'));
        
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->stream('Laporan-Data-Petani-'.date('Y-m-d').'.pdf');
    }

    /**
     * Tandai Semua Notifikasi Sudah Dibaca (Admin)
     */
    public function bacaSemuaNotifikasi()
    {
        \Illuminate\Support\Facades\Auth::user()->unreadNotifications->markAsRead();
        return back();
    }
}