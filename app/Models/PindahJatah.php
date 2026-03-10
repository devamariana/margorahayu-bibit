<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PindahJatah extends Model
{
    use HasFactory;

    // Nama tabelnya (pastikan sesuai dengan migration)
    protected $table = 'pindah_jatahs';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'pengirim_id',
        'penerima_id',
        'jumlah_kg',
        'alasan'
    ];

    /**
     * Relasi: Siapa yang mengirim jatah?
     */
    public function pengirim()
    {
        return $this->belongsTo(Petani::class, 'pengirim_id');
    }

    /**
     * Relasi: Siapa yang menerima jatah?
     */
    public function penerima()
    {
        return $this->belongsTo(Petani::class, 'penerima_id');
    }
}