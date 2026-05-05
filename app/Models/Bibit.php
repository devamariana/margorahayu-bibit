<?php

namespace App\Models; // PERBAIKAN: Cukup gunakan satu namespace Models saja

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bibit extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan di database
     */
    protected $table = 'bibits';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignable)
     */
    protected $fillable = [
        'nama_bibit', 
        'jenis', 
        'sumber_pasokan', // TAMBAHKAN INI agar Master Data Masuk tersimpan
        'stok', 
        'stok_awal',
        'harga_subsidi', 
        'deskripsi', 
        'gambar', 
        'status',
        'is_buka',
        'tanggal_buka',
        'total_luas_snapshot',
    ];

    /**
     * Menjamin tipe data stok dan harga selalu angka
     */
    protected $casts = [
        'stok' => 'integer',
        'harga_subsidi' => 'integer',
    ];

    /**
     * Primary Key
     */
    protected $primaryKey = 'id';

    /**
     * Aktifkan timestamps untuk mencatat Tanggal Masuk otomatis
     */
    public $timestamps = true;

    /**
     * Relasi ke Tabel Transaksi
     */
    public function transaksis()
    {
        return $this->hasMany(Transaksi::class, 'bibit_id');
    }

    public function petanis()
    {
        return $this->belongsToMany(Petani::class, 'bibit_petani')
                    ->withPivot('kuota_maksimal')
                    ->withTimestamps();
    }
}