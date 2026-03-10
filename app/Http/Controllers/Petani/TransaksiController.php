<?php

namespace App\Http\Controllers\Petani;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Petani;
use App\Models\Lahan;
use App\Models\Bibit;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'lahan_id' => 'required|exists:lahans,id',
            'bibit_id' => 'required|exists:bibits,id',
            'jumlah_beli' => 'required|numeric|min:1',
            'total_harga' => 'required|numeric',
            'metode_pembayaran' => 'required'
        ]);

        $petani = Petani::where('user_id', Auth::id())->first();

        // 2. Simpan Transaksi ke Database
        $transaksi = new Transaksi();
        $transaksi->petani_id = $petani->id;
        $transaksi->lahan_id = $request->lahan_id; // Mencatat lahan yang mana
        $transaksi->bibit_id = $request->bibit_id;
        $transaksi->jumlah_beli = $request->jumlah_beli;
        $transaksi->total_harga = $request->total_harga;
        $transaksi->metode_pembayaran = $request->metode_pembayaran;
        $transaksi->status_pembayaran = 'pending'; // Default awal
        $transaksi->save();

        // 3. Kurangi Stok Bibit di Tabel Bibit
        $bibit = Bibit::find($request->bibit_id);
        $bibit->stok -= $request->jumlah_beli;
        $bibit->save();

        return redirect()->route('petani.riwayat')->with('success', 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.');
    }
}