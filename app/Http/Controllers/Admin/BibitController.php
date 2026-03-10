<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bibit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

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

        Bibit::create($data);

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
}