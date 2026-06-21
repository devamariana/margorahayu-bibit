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

        // 1. Menghitung total semua petani
        $totalPetani = Petani::count();

        // 2. Menghitung Antrean Keseluruhan (Petani, Lahan, Pengajuan, Transaksi)
        $countPendingPetani = Petani::where('status', 'pending')->count();
        $countPendingLahan = \App\Models\Lahan::where('status', 'pending')->count();
        $countPendingPengajuan = \App\Models\Pengajuan::where('status', 'menunggu')->count();
        $countPendingTransaksi = \App\Models\Transaksi::whereIn('status_pembayaran', ['pending', 'menunggu_pembayaran', 'menunggu_persetujuan'])->count();
        
        $totalAntrean = $countPendingPetani + $countPendingLahan + $countPendingPengajuan + $countPendingTransaksi;

        // 3. Menghitung total stok bibit yang sedang AKTIF didistribusikan
        $totalStok = Bibit::where('is_buka', true)->sum('stok');

        // 4. Mengambil 5 data petani terbaru
        $petaniTerbaru = Petani::with('user')->latest()->take(5)->get();

        // 5. Data Chart Pembayaran
        $totalLunas = \App\Models\Transaksi::where('status_pembayaran', 'sukses')->count();
        $totalPendingTx = $countPendingTransaksi;

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
            $chartLabels[] = $month->translatedFormat('M');
            $chartData[] = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('jumlah_beli');
        }

        $hasPengambilan = (collect($chartData)->sum() > 0);
        
        return view('layouts.admin.dashboard', compact(
            'totalPetani', 
            'totalAntrean',
            'countPendingPetani',
            'countPendingLahan',
            'countPendingPengajuan',
            'countPendingTransaksi',
            'totalStok', 
            'petaniTerbaru',
            'totalLunas',
            'totalPendingTx',
            'chartLabels',
            'chartData',
            'periodeAktif',
            'danaPeriodeAktif',
            'bibitPeriodeAktif',
            'hasPengambilan'
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
        // Ambil info musim aktif saat ini
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        $currentMusimAktif = $periodeAktif->musim ?? null;

        // Hanya ambil petani yang sudah terverifikasi (disetujui)
        $petanis = Petani::where('status', 'disetujui')->get();
        // Ambil riwayat dengan relasi bibit
        $riwayatPindah = PindahJatah::with(['pengirim', 'penerima', 'bibit'])->latest()->get();
        
        // Ambil bibit yang sedang aktif DISTRIBUSI DAN sesuaI musim saat ini
        $bibitsAktif = Bibit::where('is_buka', true)
            ->where('kategori_musim', $currentMusimAktif)
            ->get();
        
        return view('layouts.admin.transfer_jatah', compact('petanis', 'riwayatPindah', 'bibitsAktif', 'currentMusimAktif'));
    }

    /**
     * Memproses Pengalihan Jatah Antar Petani / Pengembalian ke Admin
     */
    public function prosesPindahJatah(Request $request)
    {
        $request->validate([
            'bibit_id' => 'required|exists:bibits,id',
            'pengirim_id' => 'required|exists:petanis,id',
            'penerima_id' => 'required',
            'jumlah_kg' => 'required|numeric|min:0.1',
            'lahan_id' => 'required|exists:lahans,id',
        ]);

        $pengirim = Petani::findOrFail($request->pengirim_id);
        $bibit = Bibit::findOrFail($request->bibit_id);
        
        $penerimaId = $request->penerima_id;
        $penerima = null;
        if ($penerimaId !== 'admin') {
            if ($penerimaId == $request->pengirim_id) {
                return back()->with('error', 'Pengirim dan penerima tidak boleh sama.');
            }
            $penerima = Petani::findOrFail($penerimaId);
        }

        if (!$bibit->is_buka) {
            return back()->with('error', 'Distribusi untuk bibit ini sudah ditutup.');
        }

        // 1. Hitung total luas global berdasarkan Pengajuan yang disetujui
        $totalLuasGlobal = \App\Models\Pengajuan::where('pengajuans.bibit_id', $bibit->id)
            ->where('pengajuans.status', 'disetujui')
            ->join('lahans', 'pengajuans.lahan_id', '=', 'lahans.id')
            ->sum('lahans.luas_lahan');

        // 2. Hitung Hak Proposional Khusus Lahan Ini
        // Perubahan: hitung proporsional berdasarkan totalLuasGlobal apabila tersedia,
        // walaupun lahan itu sendiri tidak memiliki pengajuan yang eksplisit disetujui.
        $hakLahanIni = 0;
        if ($totalLuasGlobal > 0) {
            $lahan = Lahan::find($request->lahan_id);
            if ($lahan) {
                $hakLahanIni = ($lahan->luas_lahan / $totalLuasGlobal) * $bibit->stok_awal_real;
            }
        }
        // 3. Hitung Yang Sudah Dibeli di Lahan Ini
        $sudahDibeli = \App\Models\Transaksi::where('petani_id', $pengirim->id)
            ->where('bibit_id', $bibit->id)
            ->where('lahan_id', $request->lahan_id)
            ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak', 'cancel', 'expire'])
            ->sum('jumlah_beli');

        // 4. Hitung Tambahan dari Transfer Jatah
        $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $pengirim->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg')
                           - \App\Models\PindahJatah::where('pengirim_id', $pengirim->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');

        // 5. Sisa Jatah Pengirim
        $sisaJatahPengirim = max(0, ($hakLahanIni - $sudahDibeli) + $tambahanTransfer);

        if ($sisaJatahPengirim < $request->jumlah_kg) {
            return back()->with('error', "Jatah pengirim untuk {$bibit->nama_bibit} tidak mencukupi (Sisa: {$sisaJatahPengirim} Kg)!");
        }

        // Jalankan Transaction agar jika satu gagal, semua batal (Database Integrity)
        DB::transaction(function () use ($pengirim, $penerima, $penerimaId, $bibit, $request) {
            // Catat Riwayat/Log
            PindahJatah::create([
                'bibit_id' => $bibit->id,
                'pengirim_id' => $request->pengirim_id,
                'penerima_id' => $penerimaId === 'admin' ? null : $penerimaId,
                'jumlah_kg' => $request->jumlah_kg,
                'alasan' => $penerimaId === 'admin' ? 'Kembalikan ke Admin / Gudang' : 'Pengalihan oleh Admin'
            ]);

            // Jika dikembalikan ke admin, update stok bibit agar bertambah
            if ($penerimaId === 'admin') {
                $bibit->stok += $request->jumlah_kg;
                $bibit->save();
            } else if ($penerima) {
                // Beri notifikasi ke penerima jika penerima adalah petani
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
            }
        });

        if ($penerimaId === 'admin') {
            return back()->with('success', "Jatah {$bibit->nama_bibit} sebesar {$request->jumlah_kg} kg berhasil dikembalikan ke Admin!");
        }
        return back()->with('success', "Jatah {$bibit->nama_bibit} sebesar {$request->jumlah_kg} kg berhasil dialihkan!");
    }

    /**
     * AJAX: Cek Sisa Jatah Petani untuk Bibit Tertentu
     */
    public function cekSisaJatah(Request $request)
    {
        $petani = Petani::find($request->petani_id);
        $bibit = Bibit::find($request->bibit_id);
        $lahan = Lahan::find($request->lahan_id);

        if (!$petani || !$bibit || !$lahan) return response()->json(['sisa' => 0]);

        // 1. Hitung total luas global berdasarkan Pengajuan yang disetujui
        $totalLuasGlobal = \App\Models\Pengajuan::where('pengajuans.bibit_id', $bibit->id)
            ->where('pengajuans.status', 'disetujui')
            ->join('lahans', 'pengajuans.lahan_id', '=', 'lahans.id')
            ->sum('lahans.luas_lahan');
        
        // 2. Hitung Hak Proposional Khusus Lahan Ini
        $hakLahanIni = 0;
        if ($totalLuasGlobal > 0) {
            // Cek apakah ada pengajuan disetujui untuk lahan ini
            $pengajuan = \App\Models\Pengajuan::where('bibit_id', $bibit->id)
                ->where('lahan_id', $lahan->id)
                ->where('pengajuans.status', 'disetujui')
                ->first();
            
            if ($pengajuan) {
                $hakLahanIni = ($lahan->luas_lahan / $totalLuasGlobal) * $bibit->stok_awal_real;
            }
        }

        // 3. Hitung Yang Sudah Dibeli di Lahan Ini
        $sudahDibeli = \App\Models\Transaksi::where('petani_id', $petani->id)
            ->where('bibit_id', $bibit->id)
            ->where('lahan_id', $lahan->id)
            ->whereNotIn('status_pembayaran', ['batal', 'kadaluarsa', 'ditolak', 'cancel', 'expire'])
            ->sum('jumlah_beli');

        // 4. Hitung Tambahan dari Transfer Jatah
        $tambahanTransfer = \App\Models\PindahJatah::where('penerima_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg')
                           - \App\Models\PindahJatah::where('pengirim_id', $petani->id)->where('bibit_id', $bibit->id)->sum('jumlah_kg');

        // 5. Sisa Jatah
        $sisaJatah = max(0, ($hakLahanIni - $sudahDibeli) + $tambahanTransfer);

        return response()->json(['sisa' => $sisaJatah]);
    }

    public function getPetaniLahans($id)
    {
        $lahans = Lahan::where('petani_id', $id)->where('status', 'disetujui')->get();
        return response()->json($lahans);
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
    public function dataLahan(Request $request)
    {
        $search = $request->input('search');
        $lahans = Lahan::with(['petani', 'transaksi.bibit', 'pengajuans.bibit'])
            ->when($search, function($query, $search) {
                return $query->where('nama_blok', 'like', "%{$search}%")
                             ->orWhereHas('petani', function($q) use ($search) {
                                 $q->where('nama_lengkap', 'like', "%{$search}%");
                             });
            })
            ->latest()
            ->paginate(20);

        // Cari tahun-tahun transaksi yang tersedia secara global (untuk filter)
        $tahunTersedia = Transaksi::whereIn('status_pembayaran', ['sukses', 'lunas'])
            ->selectRaw('YEAR(created_at) as tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        $selectedTahun = $request->tahun ?? date('Y');
            
        return view('layouts.admin.data_lahan', compact('lahans', 'tahunTersedia', 'selectedTahun'));
    }

    /**
     * Verifikasi Status Lahan & Pengajuan Bibit Terintegrasi (Satu Aksi)
     */
    public function verifikasiLahan(Request $request, $id)
    {
        $lahan = Lahan::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,disetujui,ditolak',
            'catatan' => 'nullable|string'
        ]);

        // 1. Update Status Lahan
        $lahan->status = $request->status;
        $lahan->catatan_admin = $request->catatan;
        $lahan->save();

        // 2. OTOMATIS: Verifikasi Pengajuan Bibit yang menempel pada lahan ini
        $pengajuanPending = \App\Models\Pengajuan::where('lahan_id', $lahan->id)
            ->where('status', 'menunggu')
            ->first();

        if ($pengajuanPending) {
            $pengajuanPending->status = $request->status == 'disetujui' ? 'disetujui' : 'ditolak';
            $pengajuanPending->catatan = $request->catatan;
            $pengajuanPending->save();
        }

        // Tandai notifikasi terkait lahan ini sebagai dibaca (untuk admin)
        Auth::user()->unreadNotifications()
            ->where('data->id_terkait', $lahan->id)
            ->get()->markAsRead();

        // 3. Kirim Notifikasi ke User (Lahan & Bibit sekaligus)
        $petani = $lahan->petani;
        if ($petani && $petani->user) {
            $statusText = $request->status == 'disetujui' ? 'disetujui' : 'ditolak';
            $bibitInfo = $pengajuanPending ? " serta pengajuan bibit {$pengajuanPending->bibit->nama_bibit}" : "";
            
            $pesan = $request->status == 'disetujui' 
                ? "Data Lahan Anda di {$lahan->nama_blok}{$bibitInfo} telah disetujui Admin." 
                : "Pengajuan Lahan di {$lahan->nama_blok}{$bibitInfo} ditolak Admin. Alasan: " . ($request->catatan ?? 'Tidak ada catatan.');

            $petani->user->notify(new SistemNotifikasi(
                'Status Verifikasi Lahan & Bibit', 
                $pesan, 
                $request->status == 'disetujui' ? 'success' : 'warning',
                url('/petani/lahan'),
                $lahan->id
            ));
        }

        // 4. WhatsApp Notification
        if ($petani && !empty($petani->no_hp)) {
            $bibitInfoWA = $pengajuanPending ? " & bibit {$pengajuanPending->bibit->nama_bibit}" : "";
            $pesanWA = $request->status == 'disetujui' 
                ? "✅ *VERIFIKASI BERHASIL*\n\nHalo {$petani->nama_lengkap},\nData Lahan di *{$lahan->nama_blok}*{$bibitInfoWA} telah disetujui Admin. Silakan cek jatah bibit Anda!" 
                : "❌ *VERIFIKASI DITOLAK*\n\nHalo {$petani->nama_lengkap},\nMohon maaf, pengajuan lahan *{$lahan->nama_blok}*{$bibitInfoWA} ditolak Admin.";
            $this->sendWA($petani->no_hp, $pesanWA);
        }

        return back()->with('success', 'Status Lahan & Pengajuan Bibit berhasil diperbarui menjadi ' . ucfirst($request->status));
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

        $filename = 'Laporan-Margo-Rahayu-II-';
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
        
        $filename = 'Laporan-Margo-Rahayu-II-';
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
    /**
     * Menampilkan daftar semua pengajuan bibit
     */
    public function dataPengajuan()
    {
        $pengajuans = \App\Models\Pengajuan::with(['petani', 'bibit', 'lahan'])->latest()->get();
        return view('layouts.admin.data_pengajuan', compact('pengajuans'));
    }

    /**
     * Verifikasi Pengajuan Bibit
     */
    public function verifikasiPengajuan(Request $request, $id)
    {
        $pengajuan = \App\Models\Pengajuan::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan' => 'nullable|string'
        ]);

        $pengajuan->status = $request->status;
        $pengajuan->catatan = $request->catatan;
        $pengajuan->save();

        // Notifikasi ke Petani
        $petani = $pengajuan->petani;
        if ($petani && $petani->user) {
            $pesan = $request->status == 'disetujui' 
                ? "Pengajuan bibit '{$pengajuan->bibit->nama_bibit}' untuk lahan {$pengajuan->lahan->nama_blok} telah disetujui. Jatah Anda sudah muncul!" 
                : "Pengajuan bibit '{$pengajuan->bibit->nama_bibit}' Anda ditolak. Alasan: " . ($request->catatan ?? 'Tidak ada.');
            
            $petani->user->notify(new \App\Notifications\SistemNotifikasi(
                'Status Pengajuan Bibit', 
                $pesan, 
                $request->status == 'disetujui' ? 'success' : 'warning',
                url('/beli-bibit'),
                $pengajuan->id
            ));
        }

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }
}