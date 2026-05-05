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
            Periode::where('status', 'aktif')->update(['status' => 'berakhir']);
        }

        Periode::create($request->all());

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
