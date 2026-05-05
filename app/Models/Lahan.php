<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lahan extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara spesifik
    protected $table = 'lahans';

    /**
     * Kolom yang boleh diisi (Mass Assignment)
     * Sesuai dengan form di lahan.blade.php dan revisi bibit per lahan
     */
    protected $fillable = [
        'petani_id',
        'nama_blok',
        'luas_lahan',
        'rencana_bibit',
        'jenis_tanah',
        'lokasi',
        'status',
    ];

    /**
     * Relasi: Satu lahan dimiliki oleh satu petani
     */
    public function petani()
    {
        return $this->belongsTo(Petani::class, 'petani_id');
    }

    /**
     * Relasi: Satu lahan bisa memiliki banyak transaksi pembelian bibit
     */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'lahan_id');
    }
}