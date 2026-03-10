<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    // 1. Kasih tahu Laravel nama tabelnya
    protected $table = 'transaksis';

    // 2. Daftar kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'petani_id',
        'lahan_id',
        'bibit_id',
        'order_id',
        'snap_token',
        'jumlah_beli',
        'total_harga',
        'metode_pembayaran',
        'status_pembayaran',
    ];

    /**
     * Relasi ke model Petani
     * Satu transaksi dimiliki oleh satu petani
     */
    public function petani()
    {
        return $this->belongsTo(Petani::class, 'petani_id');
    }

    /**
     * Relasi ke model Bibit
     * Satu transaksi mencatat satu jenis bibit
     */
    public function bibit()
    {
        return $this->belongsTo(Bibit::class, 'bibit_id');
    }

    /**
     * Relasi ke model Lahan
     */
    public function lahan()
    {
        return $this->belongsTo(Lahan::class, 'lahan_id');
    }
}