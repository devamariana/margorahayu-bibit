<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Periode;

class PeriodeController extends Controller
{
    public function index()
    {
        $periodes = Periode::latest()->get();
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

        Periode::create($request->all());

        return back()->with('success', 'Periode Tanam berhasil ditambahkan!');
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
