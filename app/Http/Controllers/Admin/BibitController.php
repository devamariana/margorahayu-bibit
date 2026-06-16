<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bibit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\Petani;
use App\Notifications\SistemNotifikasi;

use App\Traits\WhatsappNotifier;

class BibitController extends Controller
{
    use WhatsappNotifier;

    public function index() {
        // FITUR OTOMATIS:
        // - Setelah 15 hari, jika stok habis -> tutup distribusi.
        // - Jika stok masih ada -> lanjut ke periode/musim berikutnya (agar petani bisa ambil dari stok sisa).

        $this->autoSwitchPeriodIfNeeded();

        $bibits = Bibit::latest()->get();
        return view('layouts.admin.data_bibit', compact('bibits'));
    }

    private function autoSwitchPeriodIfNeeded(): void
    {
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->first();
        if (!$periodeAktif) {
            // Tidak ada periode aktif, tidak perlu auto-switch
            return;
        }

        // Tutup distribusi yang sudah lewat 15 hari dan stoknya habis
        Bibit::where('is_buka', true)
            ->where('tanggal_buka', '<=', now()->subDays(15))
            ->where('stok', '<=', 0)
            ->update(['is_buka' => false]);

        // Cek apakah masih ada bibit yang lewat 15 hari tapi stok masih > 0.
        // Jika ya, kita lanjutkan musim/periode berikutnya.
        $masihAdaStok = Bibit::where('is_buka', true)
            ->where('tanggal_buka', '<=', now()->subDays(15))
            ->where('stok', '>', 0)
            ->exists();

        if (!$masihAdaStok) {
            return;
        }

        // Tentukan musim berikutnya
        $musimSaatIni = $periodeAktif->musim;
        $musimBerikutnya = $musimSaatIni === 'kemarau' ? 'penghujan' : 'kemarau';

        // Tutup periode aktif saat ini
        $periodeAktif->update(['status' => 'berakhir']);

        // Cari periode berstatus baru untuk musim berikutnya.
        // Prinsip: jangan buat data “setahun” sekaligus. Kita hanya aktifkan 1 periode berikutnya (periode record) jika belum ada.
        // Urutan prioritas:
        // 1) Jika ada periode berakhir dengan musim berikutnya, re-aktifkan yang paling terbaru.
        // 2) Jika tidak ada, buat periode baru hanya untuk siklus 15 hari mulai hari ini.

        $periodeBaru = \App\Models\Periode::where('status', 'berakhir')
            ->where('musim', $musimBerikutnya)
            ->latest()
            ->first();

        if ($periodeBaru) {
            $periodeBaru->update([
                'status' => 'aktif',
                'tanggal_mulai' => now()->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(15)->format('Y-m-d'),
            ]);
        } else {
            $periodeBaru = \App\Models\Periode::create([
                // Jangan kontekskan tahun ke periode aktif.
                // Karena auto-switch ini hanya membuat siklus 15 hari, gunakan tahun berdasarkan tanggal saat ini.
                'tahun' => date('Y'),
                'musim' => $musimBerikutnya,
                'tanggal_mulai' => now()->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(15)->format('Y-m-d'),
                'status' => 'aktif',
            ]);
        }

        // Sinkronisasi bibit: buka musim berikutnya, tutup musim lainnya
        \App\Models\Bibit::where('kategori_musim', '!=', $musimBerikutnya)->update(['is_buka' => false]);

        // Agar stok sisa tetap bisa dipakai, kita pindahkan periode_id bibit musim berikutnya ke periode baru
        // dan nyalakan is_buka untuk bibit yang stoknya ada.
        $bibitsMusimBerikut = \App\Models\Bibit::where('kategori_musim', $musimBerikutnya)
            ->where('stok', '>', 0)
            ->get();

        foreach ($bibitsMusimBerikut as $bibit) {
            // Jika bibit ini sudah ada di periode baru, skip.
            if ($bibit->periode_id !== $periodeBaru->id) {
                $bibit->update([
                    'periode_id' => $periodeBaru->id,
                    'is_buka' => true,
                    'tanggal_buka' => now(),
                    'stok_awal' => $bibit->stok,
                ]);
            } else {
                $bibit->update([
                    'is_buka' => true,
                    'tanggal_buka' => now(),
                ]);
            }
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_bibit' => 'required',
            'jenis' => 'required',
            'kategori_musim' => 'required|in:kemarau,penghujan',
            'stok' => 'required|numeric',
            'harga_subsidi' => 'required|numeric',
            'sumber_pasokan' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['nama_bibit', 'jenis', 'kategori_musim', 'stok', 'harga_subsidi', 'deskripsi', 'sumber_pasokan']);

        
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $namaFile = 'bibit_' . time() . '.' . $file->getClientOriginalExtension();
            $tujuanPath = public_path('uploads/bibit');

            if (!File::isDirectory($tujuanPath)) {
                File::makeDirectory($tujuanPath, 0777, true, true);
            }
            
            $file->move($tujuanPath, $namaFile);
            
            // PASTIKAN: Jika di database kolomnya bernama 'foto', gunakan 'foto'. 
            // Jika bernama 'gambar', gunakan 'gambar'. 
            // Di sini saya asumsikan 'gambar' sesuai kodingan awalmu.
            $data['gambar'] = $namaFile; 
        }

        $data['status'] = $request->stok > 0 ? 'tersedia' : 'habis';

        // OTOMATIS: Hubungkan ke Periode yang sedang AKTIF
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->latest()->first();
        if ($periodeAktif) {
            $data['periode_id'] = $periodeAktif->id;
        }

        $bibit = Bibit::create($data);

        // Notifikasi WA dan Sistem sengaja dihilangkan di sini.
        // Notifikasi hanya akan dikirim saat Admin menekan tombol "Buka Distribusi"

        return redirect()->route('admin.data_bibit')
            ->with('success', 'Master Data Masuk Berhasil Dicatat!')
            ->with('notif_petani', 'Kabar gembira! Bibit ' . $request->nama_bibit . ' terbaru sudah tersedia. Cek sekarang!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_bibit' => 'required',
            'jenis' => 'required',
            'kategori_musim' => 'required|in:kemarau,penghujan',
            'stok' => 'required|numeric',
            'harga_subsidi' => 'required|numeric',
            'sumber_pasokan' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);


        $bibit = Bibit::findOrFail($id);

        // CEK KEAMANAN: Jika sudah ada transaksi, batasi perubahan krusial
        $hasTransactions = DB::table('transaksis')->where('bibit_id', $id)->exists();
        
        if ($hasTransactions) {
            // Jika sudah ada transaksi, kita hanya izinkan update info non-nominal (deskripsi, gambar, nama)
            // Tapi untuk Stok dan Harga sebaiknya dikunci agar tidak merusak laporan keuangan/kuota
            if ($request->stok != $bibit->stok || $request->harga_subsidi != $bibit->harga_subsidi) {
                return back()->with('error', 'Gagal! Stok dan Harga tidak bisa diubah karena sudah ada transaksi yang berjalan pada batch ini.');
            }
        }

        $data = $request->only(['nama_bibit', 'jenis', 'kategori_musim', 'stok', 'harga_subsidi', 'deskripsi', 'sumber_pasokan']);


        if ($request->hasFile('gambar')) {
            if ($bibit->gambar) {
                $oldPath = public_path('uploads/bibit/' . $bibit->gambar);
                if (File::exists($oldPath)) { File::delete($oldPath); }
            }

            $file = $request->file('gambar');
            $namaFile = 'bibit_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/bibit'), $namaFile);
            $data['gambar'] = $namaFile;
        }

        $oldStok = $bibit->stok;
        $bibit->update($data);

        // Notifikasi WA dihapus dari sini agar tidak mengirim pesan saat stok ditambah secara manual.
        // Pemberitahuan hanya saat Buka Distribusi.

        return redirect()->route('admin.data_bibit')->with('success', 'Data Master Bibit berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $bibit = Bibit::find($id);
        
        if ($bibit) {
            // CEK KEAMANAN: Jangan hapus bibit jika sudah ada transaksi
            $hasTransactions = DB::table('transaksis')->where('bibit_id', $id)->exists();
            if ($hasTransactions) {
                return back()->with('error', 'Gagal! Data bibit tidak bisa dihapus karena sudah memiliki riwayat transaksi.');
            }

            if ($bibit->gambar) {
                $path = public_path('uploads/bibit/' . $bibit->gambar);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
            
            $bibit->delete();
            return back()->with('success', 'Data bibit berhasil dihapus!');
        }

        return back()->with('error', 'Data tidak ditemukan.');
    }
    public function bukaDistribusi($id)
    {
        $bibit = Bibit::findOrFail($id);

        if ($bibit->stok <= 0) {
            return back()->with('error', 'Stok bibit kosong, tidak bisa membuka distribusi.');
        }

        // Ambil total luas lahan seluruh petani TERVERIFIKASI
        $totalLuasLahanRaw = DB::table('lahans')
            ->where('status', 'disetujui')
            ->sum('luas_lahan');

        // LOGIKA PERIODE 15 HARI (Masa Tanam)
        $periodeAktif = \App\Models\Periode::where('status', 'aktif')->latest()->first();
        $hariIni = now()->format('Y-m-d');
        $limaBelasHari = now()->addDays(15)->format('Y-m-d');

        if (!$periodeAktif) {
            // Jika belum ada periode aktif, otomatis buat
            $periodeAktif = \App\Models\Periode::create([
                'tahun' => date('Y'),
                'tanggal_mulai' => $hariIni,
                'tanggal_selesai' => $limaBelasHari,
                'status' => 'aktif'
            ]);
        } else {
            // Perbarui periode aktif untuk diperpanjang 15 hari sejak distribusi dibuka
            $periodeAktif->update([
                'tanggal_selesai' => $limaBelasHari
            ]);
        }

        $bibit->update([
            'is_buka' => true,
            'tanggal_buka' => now(),
            'stok_awal' => $bibit->stok,
            'total_luas_snapshot' => $totalLuasLahanRaw,
            'periode_id' => $periodeAktif->id // Pastikan terhubung ke periode ini
        ]);

        // Kirim Notifikasi ke semua petani
        $petanis = Petani::with('user')->where('status', 'disetujui')->get();
        $targetWA = [];

        foreach ($petanis as $petani) {
            $userLuas = DB::table('lahans')
                ->where('petani_id', $petani->id)
                ->where('status', 'disetujui')
                ->sum('luas_lahan');
            
            $jatah = $totalLuasLahanRaw > 0 ? ($userLuas / $totalLuasLahanRaw) * $bibit->stok : 0;

            if ($petani->user) {
                $petani->user->notify(new SistemNotifikasi(
                    'Distribusi Bibit Dibuka! 📢', 
                    "Distribusi bibit {$bibit->nama_bibit} telah dibuka. Berdasarkan proporsi lahan, jatah maksimal Anda adalah " . number_format($jatah, 1) . " Kg. Segera ambil dalam 1 minggu!", 
                    'success',
                    url('/dashboard-petani'),
                    $bibit->id
                ));
            }

            if (!empty($petani->no_hp)) {
                $targetWA[] = $petani->no_hp;
            }
        }

        // Kirim WA Notifikasi Distribusi Dibuka
        if (!empty($targetWA)) {
            $pesanWA = "📢 *DISTRIBUSI BIBIT DIBUKA* 📢\n\n"
                     . "Halo Bapak/Ibu Petani,\n"
                     . "Distribusi bibit *" . $bibit->nama_bibit . "* resmi dibuka hari ini!\n\n"
                     . "📍 Segera cek kuota proporsional Anda di aplikasi.\n"
                     . "⏰ Batas waktu pengambilan khusus adalah *7 hari*.\n\n"
                     . "Login di sini: " . url('/login') . "\n\n"
                     . "Terima kasih,\n*Admin Kelompok Tani Margo Rahayu*";

            $this->sendWA(implode(',', $targetWA), $pesanWA);
        }

        return back()->with('success', 'Distribusi bibit ' . $bibit->nama_bibit . ' resmi dibuka!');
    }

    public function tutupDistribusi($id)
    {
        $bibit = Bibit::findOrFail($id);
        $bibit->update(['is_buka' => false]);
        return back()->with('success', 'Distribusi bibit ' . $bibit->nama_bibit . ' telah ditutup.');
    }

    /**
     * Menampilkan Detail Transparansi Distribusi (Siapa ambil berapa)
     */
    public function detailDistribusi($id)
    {
        $bibit = Bibit::findOrFail($id);
        
        // Ambil SEMUA petani yang disetujui
        $petanis = Petani::where('status', 'disetujui')->with('lahans')->get();
        
        $dataDistribusi = $petanis->map(function($p) use ($bibit) {
            $userLuas = $p->lahans->where('status', 'disetujui')->sum('luas_lahan');
            
            // Hitung Jatah Hak (setiap petani mendapat stok penuh untuk lahan mereka)
            $hakProposional = $bibit->stok_awal;
            
            // Hitung Tambahan dari Transfer Jatah (PindahJatah)
            $tambahanTransfer = \DB::table('pindah_jatahs')
                                ->where('bibit_id', $bibit->id)
                                ->where('penerima_id', $p->id)
                                ->sum('jumlah_kg')
                                - \DB::table('pindah_jatahs')
                                ->where('bibit_id', $bibit->id)
                                ->where('pengirim_id', $p->id)
                                ->sum('jumlah_kg');
            
            $hak = round($hakProposional + $tambahanTransfer, 1);
            
            // Cari Realisasi (Berapa yang sudah diambil/dibayar)
            $diambil = DB::table('transaksis')
                        ->where('petani_id', $p->id)
                        ->where('bibit_id', $bibit->id)
                        ->where('status_pembayaran', 'sukses')
                        ->sum('jumlah_beli');
                        
            return [
                'nama' => $p->nama_lengkap,
                'luas' => $userLuas,
                'hak' => $hak,
                'diambil' => $diambil,
                'sisa' => max(0, $hak - $diambil),
                'status' => $diambil >= $hak ? 'Lengkap' : ($diambil > 0 ? 'Sebagian' : 'Belum Ambil')
            ];
        });

        // Helper function inside map won't work easily if Math_round is not PHP
        // Correction: use standard PHP round
        
        return view('layouts.admin.detail_distribusi', compact('bibit', 'dataDistribusi'));
    }
}