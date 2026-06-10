<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Periode;

class PeriodeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $periodes = Periode::with('bibits')->latest()
            ->when($search, function ($query, $search) {
                return $query->where('tahun', 'like', "%{$search}%")
                             ->orWhere('musim', 'like', "%{$search}%"); // Tambah fitur cari berdasarkan musim
            })
            ->paginate(10);

        // Tambahkan statistik ringkas untuk tiap periode
        foreach ($periodes as $p) {
            $p->list_bibit = $p->bibits;

            $p->total_transaksi = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereHas('bibit', function($q) use ($p) {
                    $q->where('periode_id', $p->id);
                })
                ->count();
            
            $p->total_dana = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereHas('bibit', function($q) use ($p) {
                    $q->where('periode_id', $p->id);
                })
                ->sum('total_harga');

            $p->total_bibit = \App\Models\Transaksi::where('status_pembayaran', 'sukses')
                ->whereHas('bibit', function($q) use ($p) {
                    $q->where('periode_id', $p->id);
                })
                ->sum('jumlah_beli');
        }
            
        return view('layouts.admin.data_periode', compact('periodes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun' => 'required',
            'musim' => 'required|in:penghujan,kemarau', // Validasi input musim baru
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:aktif,berakhir',
        ]);

        // LOGIKA OTOMATIS: Jika yang baru di-set AKTIF, maka yang lain harus BERAKHIR
        if ($request->status == 'aktif') {
            $periodeLama = Periode::where('status', 'aktif')->get();
            Periode::where('status', 'aktif')->update(['status' => 'berakhir']);
        }

        $periodeBaru = Periode::create($request->all());

        // PINDAHKAN SISA STOK KE PERIODE BARU JIKA AKTIF
        if ($request->status == 'aktif' && isset($periodeLama) && $periodeLama->count() > 0) {
            foreach ($periodeLama as $lama) {
                // Cari bibit di periode lama yang stoknya masih > 0
                $bibitSisa = \App\Models\Bibit::where('periode_id', $lama->id)
                                ->where('stok', '>', 0)
                                ->get();

                foreach ($bibitSisa as $bibit) {
                    // Duplikasi bibit untuk periode baru
                    $bibitBaru = $bibit->replicate();
                    $bibitBaru->periode_id = $periodeBaru->id;
                    $bibitBaru->is_buka = false; // Harus dibuka manual lagi atau bisa diset true jika otomatis
                    $bibitBaru->tanggal_buka = null;
                    $bibitBaru->stok_awal = $bibit->stok;
                    $bibitBaru->save();

                    // Kosongkan stok bibit di periode lama agar laporan tutup buku bersih
                    $bibit->update(['stok' => 0, 'status' => 'habis']);
                }
            }
        }

        // OTOMATISASI: Jika Periode baru AKTIF, buka bibit musim tersebut & tutup musim lainnya
        if ($request->status == 'aktif') {
            // Tutup musim lainnya agar pendaftaran transaksi hanya untuk musim terpilih
            \App\Models\Bibit::where('kategori_musim', '!=', $request->musim)->update(['is_buka' => false]);

            // Buka musim ini
            \App\Models\Bibit::where('kategori_musim', $request->musim)
                ->where('stok', '>', 0)
                ->update([
                    'is_buka' => true,
                    'tanggal_buka' => now()
                ]);
        }

        return back()->with('success', 'Periode Tanam berhasil ditambahkan. Distribusi bibit Musim ' . strtoupper($request->musim) . ' otomatis dibuka.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tahun' => 'required',
            'musim' => 'required|in:penghujan,kemarau', // Validasi input musim saat update
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:aktif,berakhir',
        ]);

        $periode = Periode::findOrFail($id);

        // LOGIKA OTOMATIS: Jika di-update menjadi AKTIF, maka periode lainnya harus BERAKHIR
        if ($request->status == 'aktif' && $periode->status != 'aktif') {
            Periode::where('id', '!=', $id)->where('status', 'aktif')->update(['status' => 'berakhir']);
        }

        $periode->update($request->all());

        // OTOMATIS: Jika periode ditutup, semua distribusi bibit di dalamnya harus ditutup
        if ($request->status == 'berakhir') {
            \App\Models\Bibit::where('periode_id', $id)->update(['is_buka' => false]);
            
            // Backup logic untuk data lama tanpa periode_id
            \App\Models\Bibit::where('is_buka', true)
                ->whereBetween('tanggal_buka', [$periode->tanggal_mulai, $periode->tanggal_selesai])
                ->update(['is_buka' => false]);
        }

        // OTOMATIS: Jika periode diubah menjadi AKTIF, pastikan bibit musiman dibuka & lainnya tutup
        if ($request->status == 'aktif') {
             // Tutup musim lainnya
            \App\Models\Bibit::where('kategori_musim', '!=', $periode->musim)->update(['is_buka' => false]);

            // Buka musim ini
            \App\Models\Bibit::where('kategori_musim', $periode->musim)
                ->where('stok', '>', 0)
                ->update([
                    'is_buka' => true,
                    'tanggal_buka' => now()
                ]);
        }

        return back()->with('success', 'Data Periode berhasil diperbarui. Sinkronisasi stok dan status distribusi bibit telah disesuaikan.');
    }

    public function destroy($id)
    {
        $periode = Periode::find($id);
        if ($periode) {
            $periode->delete();
            return back()->with('success', 'Data periode berhasil dihapus!');
        }
        return back()->with('error', 'Data tidak ditemukan.');
    }

    /**
     * Fitur Saklar Cepat Musim untuk Periode yang sedang Aktif
     */
    public function quickSwitchSeason(Request $request)
    {
        $request->validate([
            'musim' => 'required|in:kemarau,penghujan',
        ]);

        $periodeAktif = Periode::where('status', 'aktif')->first();
        
        if (!$periodeAktif) {
            return back()->with('error', 'Gagal! Tidak ada periode yang sedang Aktif saat ini.');
        }

        $periodeAktif->update(['musim' => $request->musim]);

        // SINKRONISASI OTOMATIS BIBIT (Sama seperti logika di update)
        // 1. Tutup musim lainnya
        \App\Models\Bibit::where('kategori_musim', '!=', $request->musim)->update(['is_buka' => false]);

        // 2. Buka musim terpilih
        \App\Models\Bibit::where('kategori_musim', $request->musim)
            ->where('stok', '>', 0)
            ->update([
                'is_buka' => true,
                'tanggal_buka' => now()
            ]);

        return back()->with('success', 'Saklar Berhasil! Sekarang sistem berjalan di Musim ' . strtoupper($request->musim));
    }
}