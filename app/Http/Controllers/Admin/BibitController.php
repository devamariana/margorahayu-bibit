<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bibit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\Petani;
use App\Notifications\SistemNotifikasi;

class BibitController extends Controller
{
    public function index() {
        $bibits = Bibit::all();
        return view('layouts.admin.data_bibit', compact('bibits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_bibit' => 'required',
            'stok' => 'required|numeric',
            'harga_subsidi' => 'required|numeric',
            'sumber_pasokan' => 'required',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['nama_bibit', 'jenis', 'stok', 'harga_subsidi', 'deskripsi', 'sumber_pasokan']);
        
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

        $bibit = Bibit::create($data);

        // Notify all verified Petani about the new Bibit
        $petanis = Petani::with('user')->where('status', 'disetujui')->get();
        $targetWA = [];
        foreach ($petanis as $petani) {
            if ($petani->user) {
                $petani->user->notify(new SistemNotifikasi(
                    'Bibit Baru Tersedia! 🌱', 
                    "Kabar gembira! Bibit jenis " . ($request->jenis ?? '') . " " . $request->nama_bibit . " sekarang sudah tersedia dengan harga subsidi Rp" . number_format($request->harga_subsidi, 0, ',', '.') . ".", 
                    'bibit',
                    url('/beli-bibit')
                ));
            }
            if (!empty($petani->no_hp)) {
                $targetWA[] = $petani->no_hp;
            }
        }

        // Kirim Notifikasi via WhatsApp Fonnte ke semua petani yang disetujui
        if (!empty($targetWA)) {
            $fonnteToken = env('FONNTE_TOKEN', 'uSwGNgmjw2wNtXU8CxmN');
            if (!empty($fonnteToken) && $fonnteToken != 'your_fonnte_token_here') {
                try {
                    $pesanWA = "🌱 *INPO BIBIT BARU* 🌱\n\n"
                             . "Kabar gembira untuk para petani!\n"
                             . "Bibit *" . ($request->jenis ? $request->jenis . ' ' : '') . $request->nama_bibit . "* "
                             . "sekarang sudah tersedia di gudang *Si Margo Rahayu II*.\n\n"
                             . "💰 Harga Subsidi: *Rp " . number_format($request->harga_subsidi, 0, ',', '.') . "*\n"
                             . "📦 Stok Tersedia: *" . $request->stok . "*\n\n"
                             . "Segera cek dan pesan di Aplikasi sekarang sebelum kehabisan!\n"
                             . url('/login');

                    \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
                        'Authorization' => $fonnteToken,
                    ])->post('https://api.fonnte.com/send', [
                        'target' => implode(',', $targetWA),
                        'message' => $pesanWA,
                        'delay' => '2'
                    ]);
                } catch (\Exception $e) {
                    // Biarkan lewat agar tidak mengganggu proses admin jika Fonnte bermasalah
                }
            }
        }

        return redirect()->route('admin.data_bibit')
            ->with('success', 'Master Data Masuk Berhasil Dicatat!')
            ->with('notif_petani', 'Kabar gembira! Bibit ' . $request->nama_bibit . ' terbaru sudah tersedia. Cek sekarang!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_bibit' => 'required',
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

        $data = $request->only(['nama_bibit', 'jenis', 'stok', 'harga_subsidi', 'deskripsi', 'sumber_pasokan']);

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

        $data['status'] = $request->stok > 0 ? 'tersedia' : 'habis';
        $bibit->update($data);

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

        $bibit->update([
            'is_buka' => true,
            'tanggal_buka' => now(),
            'stok_awal' => $bibit->stok,
            'total_luas_snapshot' => $totalLuasLahanRaw
        ]);

        // Kirim Notifikasi ke semua petani
        $petanis = Petani::with('user')->where('status', 'disetujui')->get();
        foreach ($petanis as $petani) {
            if ($petani->user) {
                // Hitung jatah proporsional petani ini untuk notifikasi
                $userLuas = DB::table('lahans')
                    ->where('petani_id', $petani->id)
                    ->where('status', 'disetujui')
                    ->sum('luas_lahan');
                
                $jatah = $totalLuasLahanRaw > 0 ? ($userLuas / $totalLuasLahanRaw) * $bibit->stok : 0;

                $petani->user->notify(new SistemNotifikasi(
                    'Distribusi Bibit Dibuka! 📢', 
                    "Distribusi bibit {$bibit->nama_bibit} telah dibuka. Berdasarkan proporsi lahan, jatah maksimal Anda adalah " . number_format($jatah, 1) . " Kg. Segera ambil dalam 1 minggu!", 
                    'success',
                    url('/dashboard-petani'),
                    $bibit->id
                ));
            }
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
            $pembagi = $bibit->total_luas_snapshot > 0 ? $bibit->total_luas_snapshot : 1;
            
            // Hitung Jatah Hak
            $hak = (($userLuas / $pembagi) * $bibit->stok_awal) + ($p->jatah_tambahan ?? 0);
            $hak = round($hak, 1);
            
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