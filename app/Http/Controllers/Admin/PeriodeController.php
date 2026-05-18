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

        $periodes = Periode::latest()
            ->when($search, function ($query, $search) {
                return $query->where('tahun', 'like', "%{$search}%");
            })
            ->paginate(10);

        // Tambahkan statistik ringkas untuk tiap periode
        foreach ($periodes as $p) {
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

        // JIKA SET BERAKHIR: Otomatis tutup distribusi bibit yang terkait periode ini (jika ada relasi)
        if ($request->status == 'berakhir') {
            // Karena bibit lama mungkin belum punya periode_id, kita bisa tutup yang is_buka = true secara general 
            // atau yang tanggalnya masuk dalam range periode ini.
            \App\Models\Bibit::where('is_buka', true)
                ->whereBetween('tanggal_buka', [$request->tanggal_mulai, $request->tanggal_selesai])
                ->update(['is_buka' => false]);
        }

        return back()->with('success', 'Periode Tanam berhasil ditambahkan dan status periode lain telah disesuaikan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tahun' => 'required',
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

        return back()->with('success', 'Data Periode berhasil diperbarui.');
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
}
